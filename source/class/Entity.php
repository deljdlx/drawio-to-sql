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

        if(count($this->fields) < 2 && count($this->getParentEntities()) == 0) {
            echo '<div style="border: solid 2px #F00">';
                echo '<div style="; background-color:#CCC">@'.__FILE__.' : '.__LINE__.'</div>';
                echo '<pre style="background-color: rgba(255,0,255, 0.8);">';
                print_r($this->getName());
                echo '</pre>';
            echo '</div>';
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




    /**
     * @return Relation[]
     */
    public function getRelations()
    {
        return $this->relations;
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
