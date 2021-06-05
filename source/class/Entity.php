<?php

namespace JDLX\DrawioMCDConverter;

use JDLX\DrawioMCDConverter\SQLExporter\MySQL\Entity as MysqlEntity;
use JDLX\DrawioMCDConverter\Traits\Timestamped;

class Entity extends AbstractEntity
{
    use Timestamped;

    public const TYPE_AUTO_COMPUTED = 'auto_computed';

    protected $value;
    protected $dataNode;


    protected $id;
    protected $name;

    protected $primaryKey;

    /**
     * @var Relation[]
     */
    protected $relations = [];

    /**
     * @var AbstractEntity[]
     */
    protected $inherits = [];


    public function __construct($graph, $xmlNode = null)
    {
        parent::__construct($graph, $xmlNode);
        $this->createPrimaryKeyField();
    }


    /**
     * @return boolean
     */
    public function isReal()
    {
        if($this->primaryKey && count($this->fields) < 2) {
            return false;
        }

        if(!$this->getName()) {
            return false;
        }
        return true;
    }

    public function createPrimaryKeyField()
    {
        $this->primaryKey = new Field($this->graph);
        $this->primaryKey->autoincrement(true);
        $this->primaryKey->setType(Field::TYPE_AUTO_ID);
        $this->primaryKey->setId('id');
        $this->primaryKey->setName('id');

        array_unshift($this->fields, $this->primaryKey);

        return $this;
    }

    public function addRelation($relation)
    {
        $this->relations[] = $relation;
        return $this;
    }


    public function inherit($abstractEntity)
    {
        $this->inherits[] = $abstractEntity;
        return $this;
    }


    /**
     * @return Relation[]
     */
    public function getRelations()
    {
        return $this->relations;
    }

    /**
     * @return AbstractEntity[]
     */
    public function getParentEntities()
    {
        return $this->inherits;
    }

    /**
     * @param string $id
     * @return this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    public function getSQL($dropIfExists = false)
    {
        $exporter = new MysqlEntity($this);
        return $exporter->getSQL($dropIfExists);
    }

    public function getIdFieldName()
    {
        return 'id';
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'data' => $this->getData(),
            'fields' => $this->fields,
        ];
    }
}
