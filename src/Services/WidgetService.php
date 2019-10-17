<?php

namespace Momocode\ShopwareBase\Services;

use Doctrine\ORM\NoResultException;
use Momocode\ShopwareBase\Widget\AbstractWidget;
use Shopware\Components\Emotion\ComponentInstaller;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Emotion\Library\Component;

/**
 * This is a service for emotion widgets
 *
 * @author Moritz Müller <moritz@momocode.de>
 */
class WidgetService
{
    /**
     * @var ComponentInstaller
     */
    protected $installer;

    /**
     * @var ModelManager
     */
    protected $modelManager;

    /**
     * AbstractWidget constructor.
     *
     * @param ComponentInstaller $installer
     * @param ModelManager $modelManager
     */
    public function __construct(ComponentInstaller $installer = null, ModelManager $modelManager = null)
    {
        $this->installer = $installer ? $installer : Shopware()->Container()->get('shopware.emotion_component_installer');
        $this->modelManager = $modelManager ? $modelManager : Shopware()->Models();
    }

    /**
     * Install the wigdet
     *
     * @param AbstractWidget $widget
     *
     * @throws \Exception
     */
    public function install(AbstractWidget $widget)
    {
        $component = $this->createComponent($widget);
        $fields = $widget->getWidgetFields();
        foreach ($fields as $fieldDefinition) {
            $component->createField($fieldDefinition);
        }
    }

    /**
     * Update the widget
     *
     * @param AbstractWidget $widget
     * @param string $oldPluginVersion
     *
     * @throws \Exception
     */
    public function update(AbstractWidget $widget, $oldPluginVersion)
    {
        try {
            $widget->getComponent();
            $widget->updateWidget($oldPluginVersion);
        } catch (NoResultException $e) {
            // In this case a new widget is installed on plugin update
            $this->install($widget);
        }
    }

    /**
     * Create component
     *
     * @param AbstractWidget $widget
     *
     * @return Component
     *
     * @throws \Exception
     */
    protected function createComponent(AbstractWidget $widget)
    {
        $defaultOpts = [
            'name' => $widget->getWidgetName(),
            'xtype' => $widget->getXType(),
        ];
        $options = array_merge($defaultOpts, $widget->getWidgetOptions());
        $component = $this->installer->createOrUpdate($widget->getPluginName(), $widget->getWidgetName(), $options);
        $this->removeDuplicateWidget($widget);

        return $component;
    }

    /**
     * Remove duplicated widget
     * (There is a shopware bug which installs widgets multiple times)
     *
     * @param AbstractWidget $widget
     */
    protected function removeDuplicateWidget(AbstractWidget $widget)
    {
        try {
            // Find duplicates
            $components = $this->modelManager->getRepository(Component::class)->findBy(
                [
                    'name' => $widget->getWidgetName(),
                    'pluginId' => $widget->getPlugin()->getId(),
                ],
                [
                    'id' => 'ASC'
                ]
            );
            // Keep the first one
            array_shift($components);
            // Remove the rest
            foreach ($components as $duplicate) {
                $this->modelManager->remove($duplicate);
            }
        } catch (\Exception $exc) {
            return;
        }
    }
}
