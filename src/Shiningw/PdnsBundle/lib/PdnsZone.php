<?php

namespace Shiningw\PdnsBundle\lib;

class PdnsZone extends PdnsApiBase {

    protected $domain, $nameservers, $masters, $kind;
    protected $zoneData,$baseUrl;

    public function __construct($apikey = NULL, $domain = null,$baseUrl = null) {
        $this->baseUrl = $baseUrl?$baseUrl:'http://127.0.0.1:8081/api/v1/servers/localhost/zones';
        parent::__construct($apikey);
        $this->domain = $domain;
        $this->setBaseurl();
    }

    public function setZoneData() {

        $this->zoneData = array(
            'name' => $this->domain,
            'kind' => $this->kind,
            'masters' => $this->masters,
            'nameservers' => $this->nameservers,
        );
    }

    public function setDomain($domain) {
        $this->domain = $domain;
    }

    public function setKind($kind = 'Native') {
        $this->kind = $kind;
    }

    public function setMasters($masters) {
        $this->masters = $masters;
    }

    public function setNameservers($nameservers) {
        $this->nameservers = $nameservers;
    }
    public function create($params = null) {

        if (isset($params)) {
            if (isset($params['domain'])) {
                $this->setDomain($params['domain']);
            }
            if (isset($params['kind'])) {
                $this->setKind($params['kind']);
            }
            if (isset($params['masters'])) {
                $this->setMasters($params['masters']);
            }
            if (isset($params['nameservers'])) {
                $this->setNameservers($params['nameservers']);
            }
        }
        $this->setZoneData();

        if (empty($this->zoneData['name'])) {
            throw new \Exception("Domain name is missing");
        }
        $this->client->setMethod('PATCH');
        return $this->client->Request($this->baseUrl, $this->zoneData);
    }

    public function listzones($zone = NULL) {
        if (isset($zone)) {
            $url =$this->$baseUrl."/".$zone;
        } else {
            $url = $this->baseUrl;
        }
        return $this->client->Request($url);
    }

}
