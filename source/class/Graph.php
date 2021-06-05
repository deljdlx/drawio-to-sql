<?php
namespace JDLX\DrawioMCDConverter;


class Graph implements \JsonSerializable
{
    protected $file;

    /**
     * @var \SimpleXMLElement
     */
    protected $xml;

    /**
     * @var Entity[]
     */
    protected $entities = [];


    /**
     * @var AbstractEntity[]
     */
    protected $abstractEntities = [];


    /**
     * @var Relation[]
     */
    protected $relations = [];

    /**
     * @var Relation[]
     */
    protected $extends = [];

    public function __construct($file)
    {
        $this->file = $file;

        $this->xml = simplexml_load_file($this->file);


        $this->extractAbstractEntities();
        $this->extractEntities();

        $this->extractExtends();

        $this->extractRelations();
    }


    public function getSQL($dropIfExists = false)
    {
        $sql = '';
        foreach($this->entities as $entity) {
            if($entity->isReal()) {
                $sql .= $entity->getSQL($dropIfExists) . "\n";
            }
            else {

            }
        }

        foreach($this->relations as $relation) {
            if($relation->isNN()) {
                $sql .= $relation->getRelationTable()->getSQL($dropIfExists);
            }
        }

        return $sql;
    }


    public function extractExtends()
    {
        $query = '//mxCell[@source and @target]';
        $nodes = $this->xml->xPath($query);

        foreach($nodes as $node) {

            if(preg_match('`endArrow=block`', (string) $node['style'])) {

                $source = $this->getEntityById((string) $node['source']);
                $target = $this->getAbstractEntityById((string) $node['target']);

                if($source && $target) {
                    $relation = new Relation(
                        $this,
                        $node,
                        $source,
                        $target,
                        Relation::TYPE_INHERIT
                    );
                    $this->extends[$relation->getId()] = $relation;
                }
            }
        }

        return $this;
    }


    public function extractAbstractEntities()
    {
        $query = '//mxCell[@parent="1" and not(@source)]';
        $nodes = $this->xml->xPath($query);

        foreach($nodes as $node) {
            if($node['style'] == 'group') {
                $query = '//mxCell[@parent="' . $node['id'] . '"]';
                $node = $this->xml->xPath($query)[0];

            }
            if(preg_match('`dashed=1`', (string) $node['style'])) {
                $entity = new AbstractEntity($this, $node);
                $this->abstractEntities[$entity->getId()] = $entity;
            }
        }

        return $this;
    }


    public function extractEntities()
    {
        $query = '//mxCell[@parent="1" and not(@source)]';
        $nodes = $this->xml->xPath($query);

        foreach($nodes as $node) {
            if($node['style'] == 'group') {
                $query = '//mxCell[@parent="' . $node['id'] . '"]';
                $node = $this->xml->xPath($query)[0];

            }
            if(!preg_match('`dashed=1`', (string) $node['style'])) {
                $entity = new Entity($this, $node);
                $this->entities[$entity->getId()] = $entity;
            }
        }

        return $this;
    }

    public function extractRelations()
    {
        $query = '//mxCell[@source and @target]';
        $nodes = $this->xml->xPath($query);

        foreach($nodes as $node) {

            $source = $this->getEntityById((string) $node['source']);
            $target = $this->getEntityById((string) $node['target']);

            if($source && $target) {
                $relation = new Relation(
                    $this,
                    $node,
                    $source,
                    $target
                );
                $this->relations[$relation->getId()] = $relation;
            }
            else {
                // nothing yet
            }
        }
    }

    public function getEntityById($id)
    {
        if(isset($this->entities[$id])) {
            return $this->entities[$id];
        }
        else {
            return false;
        }
    }

    public function getAbstractEntityById($id)
    {
        if(isset($this->abstractEntities[$id])) {
            return $this->abstractEntities[$id];
        }
        else {
            return false;
        }
    }

    public function getEntities()
    {
        return $this->entities;
    }

    public function getId()
    {
        return $this->xml->diagram['id'];
    }

    public function getXML()
    {
        return $this->xml;
    }

    public function xPath($query)
    {
        return $this->xml->xpath($query);
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'entities' => $this->entities,
            'relations' => $this->relations,
        ];
    }
}
