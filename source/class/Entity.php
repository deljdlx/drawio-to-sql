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

        // NOTICE 2 fields because there is alway an id field
        if(count($this->fields) < 2 && count($this->getParentEntities()) == 0) {
            return false;
        }

        if(!$this->getName()) {
            return false;
        }
        return true;
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
