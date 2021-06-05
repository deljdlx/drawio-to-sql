<?php

namespace JDLX\DrawioMCDConverter\SQLExporter\MySQL;

use JDLX\DrawioMCDConverter\Field as DrawioMCDConverterField;

class Field extends Driver
{
    public const TYPE_DEFAULT = 'VARCHAR(255)';
    public const TYPE_ID = 'BIGINT(16) UNSIGNED';

    /**
     * @var DrawioMCDConverterField
     */
    protected $field;


    protected $type;

    public function __construct($field)
    {
        $this->field = $field;
    }


    public function getSQL($isDeclaration = true)
    {
        $field = $this->field;
        $fieldName = $this->escape($field->getName());
        if($isDeclaration) {
            return $fieldName . ' ' . $this->getDeclaration();
        }
        else {
            return $fieldName . ' ' . $this->getType();
        }

    }


    public function getDeclaration()
    {
        $sql = $this->getType();
        if($this->field->getType() == DrawioMCDConverterField::TYPE_AUTO_ID) {
            $sql .= ' AUTO_INCREMENT';
        }

        return $sql;
    }


    /**
     * @return string
     */
    public function getType()
    {
        if(!$this->type) {
            $type = $this->field->getType();

            if($type) {
                if($type == 'string') {
                    $this->type = 'VARCHAR(255)';
                }
                elseif($type == DrawioMCDConverterField::TYPE_AUTO_ID) {
                    $this->type = static::TYPE_ID;
                }
            }
            else {
                $this->type = $this->findType();
            }
        }
        return $this->type;
    }

    public function findType()
    {

        if(preg_match('`(?:[^a-z]|^)date[^a-z]`', $this->field->getName())) {
            return 'DATETIME';
        }
        elseif(preg_match('`(?:[^a-z]|^)created`', $this->field->getName())) {
            return 'DATETIME';
        }
        elseif(preg_match('`(?:[^a-z]|^)updated`', $this->field->getName())) {
            return 'DATETIME';
        }

        return static::TYPE_DEFAULT;
    }
}