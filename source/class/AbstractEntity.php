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
        $this->extractSubEntities();
    }


    /**
     * @return boolean
     */
    public function isReal()
    {
        return false;
    }


    public function extractSubEntities()
    {
        $query = '//mxCell[@parent="' . $this->getId()  . '"]';
        $nodes = $this->xml->xPath($query);

        foreach($nodes as $node) {
            $entity = new Field($this, $node);
            $this->fields[$entity->getId()] = $entity;
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


    /**
     * @return Field[]
     */
    public function getFields()
    {
        return $this->fields;
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
