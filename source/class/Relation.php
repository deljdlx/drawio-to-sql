<?php
namespace JDLX\DrawioMCDConverter;

class Relation implements \JsonSerializable
{
    public const TYPE_INHERIT = 'inherit';
    public const TYPE_RELATION = 'relation';

    protected $graph;
    protected $xml;

    /**
     * @var Entity
     */
    protected $from;

    /**
     * @var Entity
     */
    protected $to;

    protected $cardinalityNodes = [];

    /**
     * @var Cardinality
     */
    protected $fromCardinality;

    /**
     * @var Cardinality
     */
    protected $toCardinality;

    /**
     * @var string
     */
    protected $type;


    public function __construct($graph, $xmlNode, $from = null, $to = null, $type = self::TYPE_RELATION)
    {
        $this->graph = $graph;
        $this->xml = $xmlNode;
        $this->from = $from;
        $this->to = $to;

        $this->type = $type;

        if($this->type == static::TYPE_RELATION) {
            $this->extractCardinality();
            $this->from->addRelation($this);
            $this->to->addRelation($this);
        }
        else if($this->type == static::TYPE_INHERIT) {
            $this->from->inherit($this->to);
        }
    }


    /**
     * @param Entity $entity
     * @return bool
     */
    public function foreignKeyOn($entity)
    {
        if($entity->getId() == $this->getFrom()->getId()) {
            $cardinality = $this->getFromCardinality();
            if($cardinality->requireForeignKey()) {
                return true;
            }
        }
        elseif($entity->getId() == $this->getTo()->getId()) {
            $cardinality = $this->getToCardinality();
            if($cardinality->requireForeignKey()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return RelationTable
     */
    public function getRelationTable()
    {

        $relationTable = new RelationTable($this->from, $this->to, $this);
        return $relationTable;
    }

    public function isNN()
    {
        if($this->getFromCardinality()->getMax() == 'n' && $this->getToCardinality()->getMax() == 'n') {
            return true;
        }
        return false;
    }

    /**
     * @return Entity
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @return Entity
     */
    public function getTo()
    {
        return $this->to;
    }


    /**
     * @return this
     */
    public function extractCardinality()
    {
        $query = '//mxCell[@parent="' . $this->getId() . '"]';
        $this->cardinalityNodes = $this->graph->xPath($query);
        return $this;
    }

    /**
     * @return Cardinality
     */
    public function getFromCardinality()
    {
        if(!$this->fromCardinality) {
            $min = null;
            $cardinality = null;
            foreach($this->cardinalityNodes as $node) {
                if($min === null || (float) $node->mxGeometry['x'] < $min) {
                    $cardinality = (string) $node['value'];
                    $min =  (float) $node->mxGeometry['x'];
                }
            }

            $this->fromCardinality = new Cardinality($cardinality);
        }
        return $this->fromCardinality;
    }

    /**
     * @return Cardinality
     */
    public function getToCardinality() {
        if (!$this->toCardinality) {
            $max = null;
            $cardinality = null;
            foreach ($this->cardinalityNodes as $node) {
                if ($max === null || (float) $node->mxGeometry['x'] > $max) {
                    $cardinality = (string) $node['value'];
                    $max =  (float) $node->mxGeometry['x'];
                }
            }
            $this->toCardinality = new Cardinality($cardinality);
        }
        return $this->toCardinality;
    }

    public function getLabel() {
        $max = null;
        $min = null;

        foreach($this->cardinalityNodes as $node) {
            if ($max === null || (float) $node->mxGeometry['x'] > $max) {
                $max =  (float) $node->mxGeometry['x'];
            }
            if($min === null || (float) $node->mxGeometry['x'] < $min) {

                $min =  (float) $node->mxGeometry['x'];
            }
        }

        foreach($this->cardinalityNodes as $node) {
            if((float) $node->mxGeometry['x'] > $min && (float) $node->mxGeometry['x'] < $max) {
                return (string) $node['value'];
            }
        }
        return '';
    }

    /**
     * @return string
     */
    public function getId()
    {
        return (string) $this->xml['id'];
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'label' => $this->getLabel(),
            'fromCardinality' => $this->getFromCardinality(),
            'toCardinality' => $this->getToCardinality(),
            'from' => $this->from,
            'to' => $this->to,
        ];
    }
}

