<?php

namespace Shiningw\PdnsBundle\lib;

use Shiningw\PdnsBundle\Zone\RRSet;

class PdnsRecord extends PdnsApiBase {

    public $rrsets = array(), $records = array();
    public $oldContent;
    //a specific resource records set
    public $rrset;

    public function __construct($apikey = NULL, $domain = null) {

        parent::__construct();
        $this->setApiKey($apikey);
        $this->domain = $domain;
        $this->setBaseurl('http://127.0.0.1:8081/api/v1/servers/localhost/zones');
        $this->disabled = false;
        $this->ttl = 600;
        $this->setptr = false;
        $this->setChangeType = 'REPLACE';
    }

    public function init($data, $name = null, $type = null) {
        if (isset($data['rrsets'])) {
            foreach ($data['rrsets'] as $rrset) {
                $toadd = new RRSet($rrset['name'], $rrset['type']);
                foreach ($rrset['comments'] as $comment) {
                    $toadd->addComment($comment['content'], $comment['account'], $comment['modified_at']);
                }
                foreach ($rrset['records'] as $record) {
                    $toadd->addRecord($record['content'], $record['disabled']);
                }
                $toadd->setTtl($rrset['ttl']);
                array_push($this->rrsets, $toadd);
            }
        }

        if (isset($name) && isset($type)) {
            //$name, $type, $content, $disabled = FALSE, $ttl = 3600, $setptr = FALSE
            $this->name = $name;
            $this->type = $type;
            $this->rrset = $this->getRRSet();
        }
        return $this;
    }

    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    public function setType($type) {
        $this->type = $type;
        return $this;
    }

    public function setTTL($ttl) {
        $this->ttl = $ttl;
        return $this;
    }

    public function setPTR($setptr) {
        $this->setptr = $setptr;
        return $this;
    }

    public function setChangeType($changeType) {
        $this->changeType = $changeType;
        return $this;
    }

    public function setContent($content) {
        $this->content = $content;
        return $this;
    }

    public function setDisabled($bool) {

        $this->disabled = $bool;
        return $this;
    }

    protected function preCreate() {
        $rrset = $this->addRecord();

        $this->postData = array('rrsets' => array($this->rrset->export()));

        return $this;
    }

    public function create() {
        $this->preCreate();
        return $this->execute();
    }

    protected function preUpdate() {

        $this->rrset->deleteRecord($this->oldContent);
        $this->addRecord();
    }

    public function update($value) {
        // $this->name = $currRecord['name'];
        //$this->type = $currRecord['type'];
        //$this->content = $currRecord['content'];
        $this->oldContent = $this->content;

        if ($this->updateType == 'ttl') {
            $this->ttl = $value;
            //$this->rrset->setTTL($value);
            //update records
        } elseif ($this->updateType == 'content') {
            $this->setContent($value);
        } elseif ($this->updateType == 'name') {
            //$this->rrset->setName($value);
            //need to delete the existing record to change the name
            $this->delete($this->content);
            $this->setName($value);
        }
        $this->preUpdate();

        $this->postData = array('rrsets' => array($this->rrset->export()));


        return $this->execute();
    }

    public function delete($content) {
        $this->rrset->deleteRecord($content);
        $this->postData = array('rrsets' => array($this->rrset->export()));
        return $this->execute();
    }

    protected function execute() {
        $url = $this->baseUrl . '/' . $this->removeSubDomain($this->name);
        $resp = $this->client->Request($url, 'PATCH', $this->postData);
        if (!in_array($resp->code, array(200, 204))) {
            $resp->msg = json_decode($resp->data)->error;
            //$resp->msg = $this->postData;
        } else {
            $resp->msg = 'success';
        }
        $resp->extra = $this->postData;
        return $resp;
    }

    public function getRRSet() {
        if (!isset($this->rrsets)) {
            throw new \Exception("Please import data first");
        }
        foreach ($this->rrsets as $rrset) {
            if ($rrset->name == $this->name && $rrset->type == $this->type) {
                $this->rrset = $rrset;
                return $rrset;
            }
        }

        return false;
    }

    public function addRecord($checkdup = true) {
        //$rrset = $this->getRRSet($this->name, $this->type);

        if ($this->rrset) {
            $this->rrset->addRecord($this->content, $this->disabled, $this->setptr);
            $this->rrset->setTtl($this->ttl);
            $this->rrset->setName($this->name);
        } else {
            $this->addRRSet($this->name, $this->type, $this->content, $this->disabled, $this->ttl, $this->setptr);
        }
        return $this->rrset;
        //return $this->getRecord($name, $type, $content);
    }

    private function addRRSet($name, $type, $content, $disabled = FALSE, $ttl = 3600, $setptr = FALSE) {
        if (($rrset = $this->getRRSet($name, $type)) !== FALSE) {
            try {
                $rrset->addRecord($content, $disabled);
            } catch (\Exception $e) {
                echo "error: " . $e->getMessage();
            }
            return $rrset;
        }
        $rrset = new RRSet($name, $type, $content, $disabled, $ttl, $setptr);
        array_push($this->rrsets, $rrset);
        $this->rrset = $rrset;
        return $rrset;
    }

    public function getRecord($name, $type, $content) {
        $rrset = $this->getRRSet($name, $type);
        foreach ($rrset->exportRecords() as $record) {
            if ($record['content'] == $content) {
                $record['name'] = $rrset->name;
                $record['ttl'] = $rrset->ttl;
                $record['type'] = $rrset->type;
                $id = json_encode($record);
                $record['id'] = $id;
                return $record;
            }
        }
    }

    public function rrsets2records() {
        $ret = Array();

        foreach ($this->rrsets as $rrset) {
            foreach ($rrset->exportRecords() as $record) {
                $record['name'] = $rrset->name;
                $record['ttl'] = $rrset->ttl;
                $record['type'] = $rrset->type;
                $id = json_encode($record);
                $record['id'] = $id;
                array_push($ret, $record);
            }
        }

        return $ret;
    }

    public function isRecordExist() {

        $res = $this->client->Request($this->baseUrl . '/' . $this->domain);
        foreach ((json_decode($res->data)) as $records) {
            foreach ($records as $record) {
                if (isset($record->name) && $record->name == $this->name) {
                    foreach ($record->records as $value) {
                        if ($value->content == $this->content) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    public function removeSubDomain($domain) {
        $domain = trim($domain, ".");
        $domain = explode('.', $domain);
        //$domain = array_slice($domain, -2, 2);
         array_shift($domain);
        return implode('.', $domain);
    }

}
