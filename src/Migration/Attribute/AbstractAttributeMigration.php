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
     * @return Field[]
     */
    public function getDeleteFieldStructs()
    {
        $fields = $this->getFields();
        return $this->prepareFields($fields);
    }

    /**
     * @return bool
     */
    public function hasFields()
    {
        return !empty($this->getFields());
    }

    /**
     * @return string
     */
    abstract public function getTableName();

    /**
     * @return string
     */
    abstract protected function getColumnPrefix();

    /**
     * @return array
     */
    abstract protected function getDefaultOptions();

    /**
     * @return array
     */
    abstract protected function getFields();

    /**
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
