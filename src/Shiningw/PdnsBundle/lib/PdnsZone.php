<?php
namespace Shiningw\PdnsBundle\lib;

use Shiningw\PdnsBundle\Zone\Zone;

class PdnsZone extends PdnsApi
{

    protected $zone_id, $nameservers, $masters, $kind;
    protected $baseUrl;

    public function __construct($apikey = null, $zone_id = null, $baseUrl = null)
    {
        parent::__construct($apikey);
        $this->baseUrl = $baseUrl ? $baseUrl : 'http://127.0.0.1:8081/api/v1';
        $this->zone_id = $zone_id;
        $this->zone = new Zone($zone_id);
    }

    protected function setZoneData($params)
    {

        extract($params);
        $this->setName($name);
        $this->setKind($kind);
        $this->setMasters($masters);
        $this->setNameservers($nameservers);
    }

    public function setName($zone_id)
    {
        $this->zone->setName($zone_id);
        $this->zone_id = $zone_id;
        return $this;
    }

    public function setKind($kind = 'Native')
    {
        $this->zone->setKind($kind);
        return $this;
    }

    public function export()
    {
        return $this->zone->export();
    }

    public function setMasters($masters)
    {
        $this->zone->setMasters($masters);
        return $this;
    }

    public function setNameservers($nameservers)
    {
        $this->zone->setNameservers($nameservers);
        return $this;
    }
    public function create($params = null)
    {
        if (isset($params)) {
            $this->setZoneData($params);
        }

        if (empty($this->zone->name)) {
            throw new \Exception("Domain name is missing");
        }
        return $this->createZone($this->export());
    }

    public function remove(){
      return $this->removeZone();
    }

    public function list($zone = null) {
        return  $this->listZones($zone);
    }

}
