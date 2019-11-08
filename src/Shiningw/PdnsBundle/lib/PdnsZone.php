<?php

namespace Shiningw\PdnsBundle\lib;

class PdnsZone extends PdnsApiBase {

    protected $domain, $nameservers, $masters, $kind;
    protected $zoneData;

    public function __construct($apikey = NULL, $domain = null) {

        parent::__construct();
        $this->setApiKey($apikey);
        $this->domain = $domain;
        $this->setBaseurl('http://127.0.0.1:8081/api/v1/servers/localhost/zones');
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

    protected function zoneTemplates() {
        
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
        return $this->client->Request($this->baseUrl, 'POST', $this->zoneData);
    }

    public function listzones($zone = NULL) {
        if (isset($zone)) {
            $url = sprintf('http://127.0.0.1:8081/api/v1/servers/localhost/zones/%s', $zone);
        } else {
            $url = 'http://127.0.0.1:8081/api/v1/servers/localhost/zones';
        }
        return $this->client->Request($url);
    }

}
