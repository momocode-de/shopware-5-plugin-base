<?php

namespace Momocode\ShopwareBase;

use Exception;
use Momocode\ShopwareBase\Migration\Attribute\AbstractAttributeMigration;
use Momocode\ShopwareBase\Services\AttributeMigrationService;
use Momocode\ShopwareBase\Widget\AbstractWidget;
use Momocode\ShopwareBase\Services\WidgetService;
use Shopware\Components\Plugin as SwPlugin;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Components\Plugin\Context\UpdateContext;

/**
 * @author Moritz Müller <moritz@momocode.de>
 */
class Plugin extends SwPlugin
{
    /**
     * @var AttributeMigrationService
     */
    protected $attributeMigrationService;

    /**
     * @var WidgetService
     */
    protected $widgetService;

    /**
     * Install plugin and run initial migrations
     *
     * @param InstallContext $context
     *
     * @throws Exception
     */
    public function install(InstallContext $context)
    {
        $this->runInitialAttributeMigrations($context->getCurrentVersion());
        $this->installWidgets();

        parent::install($context);
    }

    /**
     * Update plugin and run migrations for the new version
     *
     * @param UpdateContext $context
     *
     * @throws Exception
     */
    public function update(UpdateContext $context)
    {
        $this->runUpdateAttributeMigrations($context->getCurrentVersion());
        $this->updateWidgets($context->getCurrentVersion());

        parent::update($context);
    }

    /**
     * Delete plugin and delete added attributes if the user don't want keep it
     *
     * @param UninstallContext $context
     *
     * @throws Exception
     */
    public function uninstall(UninstallContext $context)
    {
        if (!$context->keepUserData()) {
            $this->runDeleteAttributeMigrations();
        }

        if ($context->getPlugin()->getActive()) {
            $context->scheduleClearCache(UninstallContext::CACHE_LIST_ALL);
        }
    }

    /**
     * Run initial attribute migrations
     *
     * @param string $installedPluginVersion
     *
     * @throws Exception
     */
    protected function runInitialAttributeMigrations($installedPluginVersion)
    {
        $migrationClasses = $this->getMigrationClasses();
        foreach ($migrationClasses as $migration) {
            if ($migration->hasFields()) {
                $this->getAttributeMigrationService()->addInititalFieldsForMigration($migration, $installedPluginVersion);
            }
        }
    }

    /**
     * Run attribute migrations on update
     *
     * @param string $oldPluginVersion
     *
     * @throws Exception
     */
    protected function runUpdateAttributeMigrations($oldPluginVersion)
    {
        $migrationClasses = $this->getMigrationClasses();
        foreach ($migrationClasses as $migration) {
            if ($migration->hasFields()) {
                $this->getAttributeMigrationService()->addUpdateFieldsForMigration($migration, $oldPluginVersion);
            }
        }
    }

    /**
     * Run delete attribute migrations
     *
     * @throws Exception
     */
    protected function runDeleteAttributeMigrations()
    {
        $migrationClasses = $this->getMigrationClasses();
        foreach ($migrationClasses as $migration) {
            if ($migration->hasFields()) {
                $this->getAttributeMigrationService()->removeFieldsForMigration($migration);
            }
        }
    }

    /**
     * Install widgets
     *
     * @throws Exception
     */
    protected function installWidgets()
    {
        $widgetClasses = $this->getWidgetClasses();
        foreach ($widgetClasses as $widget) {
            $this->getWidgetService()->install($widget);
        }
    }

    /**
     * Update widgets
     *
     * @param string $oldPluginVersion
     *
     * @throws Exception
     */
    protected function updateWidgets($oldPluginVersion)
    {
        $widgetClasses = $this->getWidgetClasses();
        foreach ($widgetClasses as $widget) {
            $this->getWidgetService()->update($widget, $oldPluginVersion);
        }
    }

    /**
     * Get all migration classes
     *
     * @return AbstractAttributeMigration[]
     */
    protected function getMigrationClasses()
    {
        $files = glob(sprintf('%s/Migration/Attribute/*Migration.php', $this->getPath()));

        $migrationClasses = [];
        foreach ($files as $file) {
            $class = $this->getName() . '\Migration\Attribute\\' . basename($file, '.php');
            if (class_exists($class)) {
                $migration = new $class;
                if ($migration instanceof AbstractAttributeMigration) {
                    $migrationClasses[] = $migration;
                }
            }
        }

        return $migrationClasses;
    }

    /**
     * Get all widget classes
     *
     * @return AbstractWidget[]
     */
    protected function getWidgetClasses()
    {
        $files = glob(sprintf('%s/Widgets/*Widget.php', $this->getPath()));

        $widgetClasses = [];
        foreach ($files as $file) {
            $class = $this->getName() . '\Widgets\\' . basename($file, '.php');
            if (class_exists($class)) {
                $widget = new $class(
                    $this->container->get('pluginlogger'),
                    $this->container->get('models')
                );
                if ($widget instanceof AbstractWidget) {
                    $widgetClasses[] = $widget;
                }
            }
        }

        return $widgetClasses;
    }

    /**
     * Get attribute migration service
     *
     * @return AttributeMigrationService
     */
    protected function getAttributeMigrationService()
    {
        if (!$this->attributeMigrationService) {
            $this->attributeMigrationService = new AttributeMigrationService(
                $this->container->get('models'),
                $this->container->get('shopware_attribute.crud_service')
            );
        }
        return $this->attributeMigrationService;
    }

    /**
     * Get attribute migration service
     *
     * @return WidgetService
     */
    protected function getWidgetService()
    {
        if (!$this->widgetService) {
            $this->widgetService = new WidgetService(
                $this->container->get('shopware.emotion_component_installer'),
                $this->container->get('models')
            );
        }
        return $this->widgetService;
    }
}
