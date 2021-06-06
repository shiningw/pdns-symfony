<?php

namespace Shiningw\PdnsBundle\lib;

use Shiningw\PdnsBundle\Zone\RRSet;

class PdnsRecord extends PdnsApiBase
{

    public $records = array();
    //instance representing a specific resource records set
    /* @RRSet Shiningw\PdnsBundle\Zone\RRSet */
    public $RRSet;

    // a collection of Resource Record Sets
    protected $rrsets = array();
    protected $baseUrl;

    //a specific zone data including all resources records
    protected $zoneData;

    public function __construct($apiKey = null, $domain = null, $baseUrl = null)
    {
        $this->baseUrl = $baseUrl ? $baseUrl : 'http://127.0.0.1:8081/api/v1/servers/localhost/zones';
        parent::__construct($apiKey);
        $this->domain = $domain;
        $this->disabled = false;
        $this->ttl = 600;
        $this->setptr = false;
        $this->setChangeType = 'REPLACE';
        $pdnsApi = new PdnsApi($apiKey);
        $this->zoneData = $pdnsApi->setZoneID($domain)->loadZone();
        $this->load($this->zoneData);
    }

    public function load($data, $name = null, $type = null)
    {

        //keep the existing resource records
        if (isset($data['rrsets'])) {
            foreach ($data['rrsets'] as $rrset) {
                $RRSet = new RRSet($rrset['name'], $rrset['type']);
                foreach ($rrset['comments'] as $comment) {
                    $RRSet->addComment($comment['content'], $comment['account'], $comment['modified_at']);
                }
                foreach ($rrset['records'] as $record) {
                    $RRSet->addRecord($record['content'], $record['disabled']);
                }
                $RRSet->setTtl($rrset['ttl']);
                array_push($this->rrsets, $RRSet);
            }
        }
        if (isset($name) && isset($type)) {
            $this->name = $name;
            $this->type = $type;

            //search within the existing rrsets to see if it has any rrset matching the name and type;
            $this->RRSet = $this->searchRRSet($name, $type);
        }
        unset($this->zoneData);
        return $this;
    }

    protected function searchRRSet($name, $type)
    {
        if (!isset($this->rrsets)) {
            throw new \Exception("Please import data first");
        }
        foreach ($this->rrsets as $rrset) {
            //a resource record can be identified by a different record name or record type
            if ($rrset->name == $name && $rrset->type == $this->type) {
                $this->RRSet = $rrset;
                //return $rrset;
            }
        }
        return $this;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function setTTL($ttl = 600)
    {
        $this->ttl = $ttl;
        return $this;
    }

    public function setPTR($setptr = false)
    {
        $this->setptr = $setptr;
        return $this;
    }

    public function setChangeType($changeType)
    {
        $this->changeType = $changeType;
        return $this;
    }

    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    public function setDisabled($bool = false)
    {

        $this->disabled = $bool;
        return $this;
    }

    public function deleteByContent($content)
    {
        $this->RRSet->deleteRecord($content);
        $this->postData = array('rrsets' => array($this->RRSet->export()));
        return $this->push();
    }

    protected function push()
    {
        $url = $this->baseUrl . '/' . $this->getZoneName($this->name);
        $this->client->setMethod('PATCH');
        $resp = $this->client->Request($url, $this->postData);
        if (!in_array($resp->code, array(200, 204))) {
            $resp->msg = json_decode($resp->data);
        } else {
            $resp->msg = 'success';
        }
        $resp->extra = $this->postData;
        return $resp;
    }

    protected function buildRRSet()
    {
        //if there already exists a resource record, just add more records.Otherwise,build a new RRSet object
        if ($this->RRSet) {
            $this->RRSet->addRecord($this->content, $this->disabled, $this->setptr);
            $this->RRSet->setTtl($this->ttl);
            $this->RRSet->setName($this->name);
        } else {
            $this->RRSet = new RRSet($this->name, $this->type, $this->content, $this->disabled, $this->ttl, $this->setptr);
        }
        return $this->RRSet;
    }

    public function getRecord($name, $type, $content)
    {
        if (!isset($this->RRSet)) {
            $this->searchRRSet($name, $type);
        }
        $RRSet = $this->RRSet;

        foreach ($RRSet->exportRecords() as $record) {
            if ($record['content'] == $content) {
                $record['name'] = $RRSet->name;
                $record['ttl'] = $RRSet->ttl;
                $record['type'] = $RRSet->type;
                $id = json_encode($record);
                $record['id'] = $id;
                return $record;
            }
        }
    }

    public function rrsets2records()
    {
        $ret = array();

        foreach ($this->rrsets as $RRSet) {
            foreach ($RRSet->exportRecords() as $record) {
                $record['name'] = $RRSet->name;
                $record['ttl'] = $RRSet->ttl;
                $record['type'] = $RRSet->type;
                $id = json_encode($record);
                $record['id'] = $id;
                array_push($ret, $record);
            }
        }

        return $ret;
    }

    public function listRecords()
    {
        return $this->rrsets2records();
    }

    public function getZoneName($domain)
    {
        $domain = trim($domain, ".");
        $domain = explode('.', $domain);
        //$domain = array_slice($domain, -2, 2);
        array_shift($domain);
        return implode('.', $domain);
    }

    public function isRecordExist()
    {

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

}
