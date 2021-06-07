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

        $resp = $this->searchRRSet($this->name, $this->type)->deleteByContent($content);

        if ($resp->ok) {
            $eventData = array(
                'name' => $name,
                'content' => $content,
                'type' => $this->type,
                'zonename' => $this->zone_id,
            );
           $event = new DeleteRecordEvent($eventData);
           $this->dispatcher->dispatch(DeleteRecordEvent::NAME, $event);
        }
        return $resp;
    }

}
