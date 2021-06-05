<?php

namespace JDLX\DrawioMCDConverter\SQLExporter\MySQL;

use JDLX\DrawioMCDConverter\Entity as McdEntity;
use JDLX\DrawioMCDConverter\Field as McdField;

class Entity extends Driver
{

    protected $defaultCollate = 'utf8mb4_unicode_ci';
    protected $defaultPrimaryKeyType = 'INT(16) UNSIGNED';


    /** @var McdEntity */
    protected $entity;


    public function __construct($entity)
    {
        $this->entity = $entity;
    }

    /**
     * @return McdEntity
     */
    public function getEntity()
    {
        return $this->entity;
    }


    public function getSQL($dropIfExists = false)
    {
        $entity = $this->getEntity();
        $instructions = [];
        $indexes = [];
        $sql ='';


        if($dropIfExists) {
            $sql .= "-- ===========================================================\n";
            $sql .= "-- DROPPING TABLE FOR ENTITY `" . $entity->getName() . "`\n";
            $sql .= "-- ===========================================================\n";
            $sql .= "DROP TABLE IF EXISTS {$this->escape($entity->getName())};\n";
        }

        $sql .= "-- ===========================================================\n";
        $sql .= "-- CREATE TABLE FOR ENTITY `" . $entity->getName() . "`\n";
        $sql .= "-- ===========================================================\n";


        $sql .= 'CREATE TABLE ' . $this->escape($entity->getName()) . ' (' . "\n";



            foreach($entity->getFields() as $field) {
                $fieldExporter = new Field($field);
                $instructions[$field->getName()] = $fieldExporter->getSQL();
            }

            // $instructions[] = '';

            $extendedFields = $this->getExtendedFields();
            $instructions = array_merge($instructions, $extendedFields['instructions']);
            $indexes = array_merge($indexes, $extendedFields['indexes']);

            $foreignKeys = $this->getForeignKeys();
            $instructions = array_merge($instructions, $foreignKeys['instructions']);
            $indexes = array_merge($indexes, $foreignKeys['indexes']);

            $sql .= implode(",\n", $instructions);
            $sql .= ",\n";

            // handling timestamp fields==============
            if($entity->isTimestamped()) {
                $sql .= $entity->getTimestampFields() . ",\n";
            }
            // =======================================


            $sql .= '    PRIMARY KEY (`' . $entity->getIdFieldName() . '`)';

            if(count($indexes)) {
                $sql .= ',' . "\n";
                foreach($indexes as $indexName => $fieldName) {
                    $sql .= '    INDEX `' . $indexName . '` (`' . $fieldName . '`)' . ",\n";
                }
                $sql = substr($sql, 0, -2);
            }

        $sql .= "\n". ')' ."\n";

        $sql .= 'COLLATE="' . $this->defaultCollate . '"' ."\n";
        $sql .= 'ENGINE="InnoDB"' .";\n";

        return $sql;
    }

    protected function getExtendedFields()
    {
        $entity = $this->getEntity();
        $instructions = [];
        $indexes = [];


        foreach($entity->getParentEntities() as $parentEntity) {
            foreach($parentEntity->getFields() as $field) {
                $exporter = new Field($field);

                $instructions[] = "-- INHERITED FIELD `{$field->getName()}` FROM `{$parentEntity->getName()}`";
                $instructions[] = $exporter->getSQL(false);
            }
        }

        return [
            'instructions' => $instructions,
            'indexes' => $indexes
        ];
    }

    protected function getForeignKeys()
    {
        $entity = $this->getEntity();
        $instructions = [];
        $indexes = [];

        $generatedRelations = [];
        foreach($entity->getRelations() as $relation) {

            $fieldName = '';

            if($relation->foreignKeyOn($entity)) {

                if(isset($generatedRelations[$relation->getId()])) {
                    continue;
                }
                $generatedRelations[$relation->getId()] = true;

                if($relation->getLabel()) {
                    $fieldName = $relation->getLabel();
                }

                if ($relation->getFrom() === $entity) {
                    $targetEntity = $relation->getTo();
                }
                else {
                    $targetEntity = $relation->getFrom();
                }

                if(!$fieldName) {
                    $fieldName = $targetEntity->getName() . '_id';
                }

                $instructions[] ="-- ======= FOREIGN KEY TO `{$targetEntity->getName()}`========";
                $field = new McdField();
                $field->setName($fieldName);
                $field->setType($targetEntity->getPrimaryKey()->getType());

                $fieldExporter = new Field($field);
                $instructions[] = $fieldExporter->getSQL(false);
            }
        }
        return [
            'instructions' => $instructions,
            'indexes' => $indexes
        ];
    }
}
