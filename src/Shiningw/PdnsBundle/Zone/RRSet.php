<?php

namespace Shiningw\PdnsBundle\Zone;

use Shiningw\PdnsBundle\Zone\Comment;
use Shiningw\PdnsBundle\Zone\Record;

class RRSet
{

    public function __construct($name = '', $type = '', $content = null, $disabled
        = false, $ttl = 600, $setptr = false) {
        $this->name = $name;
        $this->type = $type;
        $this->ttl = $ttl;
        $this->changetype = 'REPLACE';
        $this->records = array();
        $this->comments = array();

        if (isset($content)) {
            $this->addRecord($content, $disabled, $setptr);
        }
    }
    public function setContent(string $oldcontent, string $content)
    {
        if (!isset($this->records)) {
            throw new \Exception("No records to modify!");
        }
        foreach ($this->records as $record) {
            if ($record->content == $oldcontent) {
                $record->content = $content;
            }
        }
    }
    public function delete()
    {
        $this->changetype = 'DELETE';
    }

    public function setTtl($ttl)
    {
        $this->ttl = $ttl;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function addRecord($content, $disabled = false, $setptr = false)
    {
        $content = trim($content);
        foreach ($this->records as $record) {
            if ($record->content == $content) {
                throw new \Exception($this->name . "/" . $this->type . " has duplicate records.");
            }
        }

        $record = new Record($content, $disabled, $setptr);
        array_push($this->records, $record);
    }

    public function deleteRecord($content)
    {
        foreach ($this->records as $idx => $record) {
            if ($record->content == $content) {
                unset($this->records[$idx]);
            }
        }
    }

    public function addComment($content, $account, $modified_at = false)
    {
        $comment = new Comment($content, $account, $modified_at);
        array_push($this->comments, $comment);
    }

    public function export()
    {
        $ret = array();
        $ret['comments'] = $this->exportComments();
        $ret['name'] = $this->name;
        $ret['records'] = $this->exportRecords();
        if ($this->changetype != 'DELETE') {
            $ret['ttl'] = $this->ttl;
        }
        $ret['type'] = $this->type;
        $ret['changetype'] = $this->changetype;
        return $ret;
    }

    public function exportRecords()
    {
        $ret = array();
        foreach ($this->records as $record) {
            if ($this->type != "A" and $this->type != "AAAA") {
                $record->setptr = false;
            }
            array_push($ret, $record->export());
        }

        return $ret;
    }

    public function exportComments()
    {
        $ret = array();
        foreach ($this->comments as $comment) {
            array_push($ret, $comment->export());
        }

        return $ret;
    }

}
