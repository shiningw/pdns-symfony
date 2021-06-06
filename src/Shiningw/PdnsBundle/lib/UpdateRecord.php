<?php
namespace Shiningw\PdnsBundle\lib;

use Shiningw\PdnsBundle\Zone\RRSet;
use Psr\Log\LoggerInterface;

class UpdateRecord extends PdnsRecord
{
    public function __construct($apiKey = null, $domain = null, $baseUrl = null, $logger = null)
    {
        parent::__construct($apiKey, $domain, $baseUrl);
        $this->logger = $logger;
    }

    public function update($value)
    {
        $this->searchRRSet($this->name, $this->type);
        $oldContent = $this->content;
        if ($this->updateType == 'ttl') {
            $this->RRSet->setTTL($value);
            $this->postData = array('rrsets' => array($this->RRSet->export()));
            return $this->push();
        }
        if ($this->updateType == 'content') {
            //update the content
            $this->setContent($value);
        }
        if ($this->updateType == 'name') {
            $this->setName($value);
        }
        //need to delete the existing record to update either content or name
        $this->deleteByContent($oldContent);
        //build a resource record object
        $this->buildRrset();
        //$this->logger->info(print_r($this->RRSet->export(),true));
        $this->postData = array('rrsets' => array($this->RRSet->export()));
        return $this->push();
    }
}
