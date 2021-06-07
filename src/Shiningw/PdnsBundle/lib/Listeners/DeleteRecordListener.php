<?php
namespace Shiningw\PdnsBundle\lib\Listeners;

use Shiningw\PdnsBundle\lib\Database;
use Symfony\Component\EventDispatcher\Event;
use Shiningw\PdnsBundle\lib\Utils;

class DeleteRecordListener extends Base
{

    public function onDelete(Event $event)
    {
        $record = (object) $event->getData();
        //$record_id = $this->dbh->getRecordId($record->name, $record->content, $record->type);
        $this->dbh->delete($record->rid);
    }
}
