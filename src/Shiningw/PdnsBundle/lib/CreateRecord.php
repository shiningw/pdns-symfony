<?php
namespace Shiningw\PdnsBundle\lib;

use Shiningw\PdnsBundle\lib\Events\CreateRecordEvent;
use Shiningw\PdnsBundle\Zone\RRSet;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CreateRecord extends PdnsRecord
{

    public function __construct($apiKey = null, $zone_id = null, $baseUrl = null, EventDispatcherInterface $dispatcher)
    {
        parent::__construct($apiKey, $zone_id, $baseUrl);
        $this->dispatcher = $dispatcher;
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
        $resp = $this->push();

        if (!in_array($resp->code, array(200, 204))) {
            $resp->msg = json_decode($resp->data)->error;
        } else {
            $name = trim($this->name, ".");
            $this->setName($name);
            $event = new CreateRecordEvent($this);
            $this->dispatcher->dispatch(CreateRecordEvent::NAME, $event);
            $resp->msg = sprintf("successfully added %s", $name);
        }
        return $resp;
    }
}
