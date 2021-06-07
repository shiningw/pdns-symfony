<?php
namespace Shiningw\PdnsBundle\lib;

use Shiningw\PdnsBundle\Zone\RRSet;

class UpdateRecord extends PdnsRecord
{
    public function __construct($apiKey = null, $zone_id = null, $baseUrl = null, $dispatcher)
    {
        parent::__construct($apiKey, $zone_id, $baseUrl);
        $this->dispatcher = $dispatcher;
    }

    public function update($value)
    {
        $this->searchRRSet($this->name, $this->type);
        $oldContent = $this->content;
        $oldName = $this->name;
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
        $delete = new DeleteRecord($this->apiKey, $this->zone_id, null, $this->dispatcher);
        $delete
            ->setName($oldName)
            ->setType($this->type)
            ->delete($oldContent);
        //build a resource record object
       // $this->buildRrset();
        $new = new CreateRecord($this->apiKey, $this->zone_id, null, $this->dispatcher);
        return $new
            ->setType($this->type)
            ->setName($this->name)
            ->setTTL($this->ttl)
            ->setContent($this->content)
            ->create();
    }
}
