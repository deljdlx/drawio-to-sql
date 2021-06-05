<?php

namespace JDLX\DrawioMCDConverter;



class Field extends Entity
{


    public const TYPE_AUTO_ID = 'auto_id';

    protected $name;
    protected $type;

    protected $autoincrement = false;

    public function __construct($graph = null, $xmlNode = null)
    {
        $this->graph = $graph;
        $this->xml = $xmlNode;

        if($this->xml) {
            $dataNode =$this->xml->xPath('parent::object');
            if(count($dataNode)) {
                $this->dataNode = $dataNode[0];
            }
        }
    }

    public function getType()
    {
        return $this->type;
    }


    /**
     * @param string $type
     * @return this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }


    public function autoincrement($value = null)
    {
        if($value === null) {
            return $this->autoincrement;
        }

        $this->autoincrement = $value;
        return $this;
    }


    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'data' => $this->getData(),
        ];
    }
}