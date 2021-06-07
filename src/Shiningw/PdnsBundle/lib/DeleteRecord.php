<?php
namespace Shiningw\PdnsBundle\lib;

use Shiningw\PdnsBundle\lib\Events\DeleteRecordEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DeleteRecord extends PdnsRecord
{
    public function __construct($apiKey = null, $zone_id = null, $baseUrl = null, EventDispatcherInterface $dispatcher)
    {
        parent::__construct($apiKey, $zone_id, $baseUrl);
        $this->dispatcher = $dispatcher;
    }
    public function delete($content)
    {
        // remove the trailling dot because record name is stored in the ISP table without the trailing dot.
        $name = trim($this->name, ".");
        
        //save the record id so we can delete the associated entry in the ISP table 
        $record_id = $this->dbh->getRecordId($name, $content, $this->type);
        $resp = $this->searchRRSet($this->name, $this->type)->deleteByContent($content);

        if ($resp->ok) {
            $eventData = array(
                'name' => $name,
                'content' => $content,
                'type' => $this->type,
                'zonename' => $this->zone_id,
                'rid' => $record_id,
            );
           $event = new DeleteRecordEvent($eventData);
           $this->dispatcher->dispatch(DeleteRecordEvent::NAME, $event);
            //$this->deleteISP($record_id);
        }
        return $resp;
    }

    protected function deleteISP($rid)
    {
        $this->dbh->delete($rid);
    }

}
