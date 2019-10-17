<?php

namespace Momocode\ShopwareBase\Migration\Attribute;

use Momocode\ShopwareBase\Structs\Field;

/**
 * This is an abstraction for all migrations on attribute tables
 *
 * @author Moritz Müller <moritz@momocode.de>
 */
abstract class AbstractAttributeMigration
{
    const COLUMN = 0;
    const TYPE = 1;
    const OPTIONS = 2;
    const VERSION = 3;

    const POSITION_STEPS = 5;

    private $position = 0;

    /**
     * Get initial field structs
     *
     * @param string $installedPluginVersion
     *
     * @return Field[]
     */
    public function getInitialFieldStructs($installedPluginVersion)
    {
        $fields = $this->getFields();

        // Return all fields which has to be initially installed
        $newFields = [];
        foreach ($fields as $field) {
            if (version_compare($field[self::VERSION], $installedPluginVersion, '<=')) {
                $newFields[] = $field;
            }
        }

        return $this->prepareFields($fields);
    }

    /**
     * Get field structs for new version
     *
     * @param string $oldPluginVersion
     *
     * @return Field[]
     */
    public function getUpdateFieldStructs($oldPluginVersion)
    {
        $fields = $this->getFields();

        // Return only new fields
        $newFields = [];
        foreach ($fields as $field) {
            if (version_compare($field[self::VERSION], $oldPluginVersion, '>')) {
                $newFields[] = $field;
            }
        }

        return $this->prepareFields($newFields);
    }

    /**
     * Get all fields for deletion
     *
     * @return Field[]
     */
    public function getDeleteFieldStructs()
    {
        $fields = $this->getFields();
        return $this->prepareFields($fields);
    }

    /**
     * Has fields
     *
     * @return bool
     */
    public function hasFields()
    {
        return !empty($this->getFields());
    }

    /**
     * Get table name
     *
     * @return string
     */
    abstract public function getTableName();

    /**
     * Get column prefix
     *
     * @return string
     */
    abstract protected function getColumnPrefix();

    /**
     * Get default options for fields
     *
     * @return array
     */
    abstract protected function getDefaultOptions();

    /**
     * Get attribute fields for this migration
     *
     * @return array
     */
    abstract protected function getFields();

    /**
     * Prepare fields
     *
     * @param $fields
     *
     * @return array
     */
    protected function prepareFields($fields)
    {
        $structs = [];
        foreach ($fields as $field) {
            $structs[] = new Field(
                $this->getTableName(),
                $this->getColumnPrefix() . '_' . $field[self::COLUMN],
                $field[self::TYPE],
                array_merge($this->getDefaultOptions(), $field[self::OPTIONS])
            );
        }

        return $structs;
    }

    /**
     * Get position in steps
     * The fields are then automatically ordered in the order they are defined in the getFields function
     *
     * @return int
     */
    protected function getPosition()
    {
        $this->position += self::POSITION_STEPS;
        return $this->position;
    }
}
