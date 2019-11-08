<?php

namespace Shiningw\PdnsBundle\Zone;
use Shiningw\PdnsBundle\Zone\Record;
use Shiningw\PdnsBundle\Zone\Comment;


class RRSet {

    public function __construct($name = '', $type = '', $content = null, $disabled
    = FALSE, $ttl = 3600, $setptr = FALSE) {
        $this->name = $name;
        $this->type = $type;
        $this->ttl = $ttl;
        $this->changetype = 'REPLACE';
        $this->records = Array();
        $this->comments = Array();

        if (isset($content)) {
            $this->addRecord($content, $disabled, $setptr);
        }
    }

    public function delete() {
        $this->changetype = 'DELETE';
    }

    public function setTtl($ttl) {
        $this->ttl = $ttl;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function addRecord($content, $disabled = FALSE, $setptr = FALSE) {
        $content = trim($content);
        foreach ($this->records as $record) {
            if ($record->content == $content) {
                throw new \Exception($this->name . "/" . $this->type . " has duplicate records.");
            }
        }

        $record = new Record($content, $disabled, $setptr);
        array_push($this->records, $record);
    }

    public function deleteRecord($content) {
        foreach ($this->records as $idx => $record) {
            if ($record->content == $content) {
                unset($this->records[$idx]);
            }
        }
    }

    public function addComment($content, $account, $modified_at = FALSE) {
        $comment = new Comment($content, $account, $modified_at);
        array_push($this->comments, $comment);
    }

    public function export() {
        $ret = Array();
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

    public function exportRecords() {
        $ret = Array();
        foreach ($this->records as $record) {
            if ($this->type != "A" and $this->type != "AAAA") {
                $record->setptr = FALSE;
            }
            array_push($ret, $record->export());
        }

        return $ret;
    }

    public function exportComments() {
        $ret = Array();
        foreach ($this->comments as $comment) {
            array_push($ret, $comment->export());
        }

        return $ret;
    }

}
