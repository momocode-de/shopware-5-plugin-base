<?php

namespace Momocode\ShopwareBase\Structs;

/**
 * Attribute Field Struct
 *
 * @author Moritz Müller <moritz@momocode.de>
 */
class Field
{
    /**
     * @var string
     */
    protected $table;

    /**
     * @var string
     */
    protected $column;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var string
     */
    protected $newName;

    /**
     * Field constructor.
     *
     * @param string $table
     * @param string $column
     * @param string $type
     * @param array $options
     * @param string $newName
     */
    public function __construct($table, $column, $type, array $options, $newName = '')
    {
        $this->table = $table;
        $this->column = $column;
        $this->type = $type;
        $this->options = $options;
        $this->newName = $newName;
    }

    /**
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @param string $table
     */
    public function setTable($table)
    {
        $this->table = $table;
    }

    /**
     * @return string
     */
    public function getColumn()
    {
        return $this->column;
    }

    /**
     * @param string $column
     */
    public function setColumn($column)
    {
        $this->column = $column;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * @return string
     */
    public function getNewName()
    {
        return $this->newName;
    }

    /**
     * @param string $newName
     */
    public function setNewName($newName)
    {
        $this->newName = $newName;
    }
}