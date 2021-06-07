<?php

namespace Shiningw\PdnsBundle\lib\Events;

use Shiningw\PdnsBundle\lib\CreateRecord;
use Symfony\Component\EventDispatcher\Event;

class CreateRecordEvent extends Event
{
    const NAME = 'dnsrecord.created';

    protected $record;

    public function __construct(CreateRecord $record)
    {
        $this->record = $record;
    }

    public function getRecord()
    {
        return $this->record;
    }
}