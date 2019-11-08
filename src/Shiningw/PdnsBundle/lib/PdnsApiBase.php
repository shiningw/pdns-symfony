<?php

namespace Shiningw\PdnsBundle\lib;

use Shiningw\PdnsBundle\lib\Client;

abstract class PdnsApiBase extends Client {

    public $apiKey, $baseUrl, $port;

    public function __construct($apiKey = NULL, $baseUrl = NULL) {
        $this->baseUrl = $baseUrl;
        $this->client = new Client();
        $this->client->setHeaders('Content-type', 'application/json');
        $this->setApiKey($apiKey);
    }

    public function setApiKey($key) {

        $this->client->setHeaders('X-API-Key', $key);
    }

    public function setBaseurl($url) {
        $this->baseUrl = $url;
    }

}
