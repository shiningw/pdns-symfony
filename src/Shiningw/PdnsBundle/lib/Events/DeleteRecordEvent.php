<?php

namespace Shiningw\PdnsBundle\lib\Events;

use Symfony\Component\EventDispatcher\Event;

class DeleteRecordEvent extends Event
{
    const NAME = 'dnsrecord.deleted';

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }
}