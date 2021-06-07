<?php
namespace Shiningw\PdnsBundle\lib\Listeners;

use Symfony\Component\EventDispatcher\Event;

class CreateRecordListener extends Base
{

    public function onCreate(Event $event)
    {
        $record = $event->getRecord();
        $record_id = $this->dbh->getRecordId($record->name, $record->content, $record->type);
        $ispData = array("record_id" => $record_id, "name" => $record->name, "isp" => $record->isp, "isp_name" => $this->ispMaps[$record->isp]);
        $this->dbh->insertRecord($ispData);
    }
}
