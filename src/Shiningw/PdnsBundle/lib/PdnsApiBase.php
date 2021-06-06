<?php

namespace Shiningw\PdnsBundle\lib;

use Shiningw\PdnsBundle\lib\Client;

abstract class PdnsApiBase {

    public $apiKey;

    public function __construct($apiKey = NULL) {
        $this->client = new Client();
        $this->client->setHeaders('Content-Type', 'application/json');
        $this->setApiKey($apiKey);
    }

    protected function setApiKey($key) {

        $this->client->setHeaders('X-API-Key', $key);
    }

}
