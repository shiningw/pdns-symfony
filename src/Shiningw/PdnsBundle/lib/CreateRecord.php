<?php
namespace Shiningw\PdnsBundle\lib;

use Shiningw\PdnsBundle\lib\PdnsApi;
use Shiningw\PdnsBundle\Zone\RRSet;

class CreateRecord extends PdnsRecord
{

    public function __construct($apiKey = null, $domain = null, $baseUrl = null,$logger)
    {
        parent::__construct($apiKey, $domain, $baseUrl);
        $this->logger = $logger;
    }

    protected function preCreate()
    {
        $this->searchRRSet($this->name, $this->type)->buildRRSet();
        //$this->RRSet->addComment("test comment","user");
        $this->postData = array('rrsets' => array($this->RRSet->export()));
        return $this;
    }

    public function create()
    {
        $this->preCreate();
        return $this->push();
    }
}
