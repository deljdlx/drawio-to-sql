<?php

namespace JDLX\DrawioMCDConverter;

use JDLX\DrawioMCDConverter\Traits\Timestamped;
use SimpleXMLElement;

class AbstractEntity implements \JsonSerializable
{
    /** @var Graph */
    protected $graph;

    /** @var SimpleXMLElement */
    protected $xml;


    /**
     * @var Field[]
     */
    protected $fields = [];


    /**
     * @var AbstractEntity[]
     */
    protected $parentEntities = [];

    /**
     * @var Relation[]
     */
    protected $relations = [];



    protected $value;
    protected $dataNode;


    protected $id;
    protected $name;

    protected $primaryKey;

    public function __construct($graph, $xmlNode = null)
    {
        $this->graph = $graph;
        $this->xml = $xmlNode;

        if($this->xml) {
            $dataNode =$this->xml->xPath('parent::object');
            if(count($dataNode)) {
                $this->dataNode = $dataNode[0];
            }
        }
        $this->extractFields();
    }

    public function addRelation($relation)
    {
        $this->relations[] = $relation;
        return $this;
    }


    public function inherit($abstractEntity)
    {
        $this->parentEntities[] = $abstractEntity;
        return $this;
    }


    /**
     * @return AbstractEntity[]
     */
    public function getParentEntities()
    {
        $parentEntities = $this->parentEntities;

        foreach($parentEntities as $parentEntity) {
            $parentEntities = array_merge($parentEntities, $parentEntity->getParentEntities());
        }

        return $parentEntities;
    }


    /**
     * @return Field[]
     */
    public function getFields()
    {
        $fields = $this->fields;

        $fields = array_merge($fields, $this->getParentFields());
        return $fields;
        // return $this->fields;
    }


    public function getParentFields()
    {
        // $fields = $this->fields;
        $fields = [];
        $fields = $this->fields;
        $parentEntities = $this->getParentEntities();

        foreach($parentEntities as $parentEntity) {
            $fields = array_merge($fields, $parentEntity->getParentFields());
        }
        return $fields;
    }


    /**
     * @return boolean
     */
    public function isReal()
    {
        return false;
    }


    public function extractFields()
    {
        $query = '//mxCell[@parent="' . $this->getId()  . '"]';
        $nodes = $this->xml->xPath($query);

        foreach($nodes as $node) {
            $entity = new Field($this, $node);
            $this->fields[$entity->getName()] = $entity;
        }
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


    /**
     * @return string
     */
    public function getName()
    {
        if(!$this->name) {
            $this->name = $this->getValue();
        }
        return $this->name;
    }

    /**
     * @param string $name
     * @return this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }



    public function getData($key = null)
    {
        if($key === null) {
            $data = [];
            if($this->dataNode) {
                foreach($this->dataNode->attributes() as $attributeName => $value) {
                    $data[$attributeName] = (string) $value;
                }
            }
            return $data;
        }
        else {
            if ($this->dataNode) {
                if(isset($this->dataNode[$key])) {
                    return (string) $this->dataNode[$key];
                }
            }
        }
        return null;
    }

    public function getValue()
    {
        if($this->dataNode) {
            return (string) $this->dataNode['label'];
        }
        else {
            return (string) $this->xml['value'];
        }
    }

    public function getId()
    {

        if(!$this->id) {
            if($this->dataNode) {
                $this->id = (string) $this->dataNode['id'];
            }
            else {
                $this->id = (string) $this->xml['id'];;
            }
        }

        return $this->id;
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
