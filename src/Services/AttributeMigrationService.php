<?php

namespace Momocode\ShopwareBase\Services;

use Doctrine\Common\Cache\CacheProvider;
use Momocode\ShopwareBase\Migration\Attribute\AbstractAttributeMigration;
use Momocode\ShopwareBase\Structs\Field;
use Shopware\Bundle\AttributeBundle\Service\CrudService;
use Shopware\Components\Model\ModelManager;

/**
 * This is a service for all migrations on attribute tables
 *
 * @author Moritz Müller <moritz@momocode.de>
 */
class AttributeMigrationService
{
    /**
     * @var ModelManager
     */
    protected $modelManager;

    /**
     * @var CrudService
     */
    protected $crudService;

    /**
     * AttributeMigrationService constructor.
     *
     * @param ModelManager $modelManager
     * @param CrudService $crudService
     */
    public function __construct(ModelManager $modelManager = null, CrudService $crudService = null)
    {
        $this->modelManager = $modelManager ? $modelManager : Shopware()->Models();
        $this->crudService = $crudService ? $crudService : Shopware()->Container()->get('shopware_attribute.crud_service');
    }

    /**
     * Add initial fields for migration
     *
     * @param AbstractAttributeMigration $migration
     * @param string $installedPluginVersion
     *
     * @throws \Exception
     */
    public function addInititalFieldsForMigration($migration, $installedPluginVersion)
    {
        $fields = $migration->getInitialFieldStructs($installedPluginVersion);

        foreach ($fields as $field) {
            $this->createOrUpdateField($field);
        }

        $this->rebuildAttributeModels($migration->getTableName());
    }

    /**
     * Add update fields for migration
     *
     * @param AbstractAttributeMigration $migration
     * @param string $oldPluginVersion
     *
     * @throws \Exception
     */
    public function addUpdateFieldsForMigration($migration, $oldPluginVersion)
    {
        $fields = $migration->getUpdateFieldStructs($oldPluginVersion);

        foreach ($fields as $field) {
            $this->createOrUpdateField($field);
        }

        $this->rebuildAttributeModels($migration->getTableName());
    }

    /**
     * Remove fields for migration
     *
     * @param AbstractAttributeMigration $migration
     *
     * @throws \Exception
     */
    public function removeFieldsForMigration($migration)
    {
        $fields = $migration->getDeleteFieldStructs();

        foreach ($fields as $field) {
            $this->deleteField($field);
        }

        $this->rebuildAttributeModels($migration->getTableName());
    }

    /**
     * Create or update field
     *
     * @param Field $field
     *
     * @throws \Exception
     */
    public function createOrUpdateField(Field $field)
    {
        if ($field->getNewName()) {
            $this->crudService->update(
                $field->getTable(),
                $field->getColumn(),
                $field->getType(),
                $field->getOptions(),
                $field->getNewName()
            );
        } else {
            $this->crudService->update(
                $field->getTable(),
                $field->getColumn(),
                $field->getType(),
                $field->getOptions()
            );
        }
    }

    /**
     * Delete an existing field
     *
     * @param Field $field
     *
     * @throws \Exception
     */
    public function deleteField(Field $field)
    {
        $this->crudService->delete(
            $field->getTable(),
            $field->getColumn()
        );
    }

    /**
     * Rebuild attribute models for the provided table.
     *
     * @param string $tableName
     */
    protected function rebuildAttributeModels($tableName)
    {
        $metaDataCache = $this->modelManager->getConfiguration()->getMetadataCacheImpl();
        if ($metaDataCache instanceof CacheProvider) {
            $metaDataCache->deleteAll();
        }

        $this->modelManager->generateAttributeModels([$tableName]);
    }
}
