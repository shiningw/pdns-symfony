<?php

namespace Shiningw\PdnsBundle\lib;

use Shiningw\PdnsBundle\Zone\RRSet;

class PdnsRecord extends PdnsApi
{

    public $records = array();
    //instance representing a specific resource records set
    /* @RRSet Shiningw\PdnsBundle\Zone\RRSet */
    protected $RRSet = null;

    // a collection of Resource Record Sets
    protected $rrsets = array();
    protected $baseUrl;

    //a specific zone data including all resources records
    protected $zoneData;
    //ISP code 1 = ctcc,2 = cucc,3= cmcc
    public $isp = 1;
    // current dns record name
    public $name = null;
    // current dns record type
    public $type = null;

    public function __construct($apiKey = null, $zone_id = null, $baseUrl = null)
    {
        $this->baseUrl = $baseUrl ? $baseUrl : 'http://127.0.0.1:8081/api/v1';
        parent::__construct($apiKey);
        $this->zone_id = $zone_id;
        $this->apiKey = $apiKey;
        $this->disabled = false;
        $this->ttl = 600;
        $this->setptr = false;
        $this->setChangeType = 'REPLACE';
        $this->zoneData = $this->setZoneID($zone_id)->loadZone();
        $this->load($this->zoneData);
    }

    public function load($data, $name = null, $type = null)
    {

        //convert existing resource records to a RRSet object
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
        //search in the existing rrsets and save the rrset matching the name and type;
        if (isset($name) && isset($type)) {
            $this->setName($name);
            $this->setType($type);
            $this->searchRRSet($name, $type);
        }
        unset($this->zoneData);
        return $this;
    }

    protected function setRRSet(RRSet $rrset)
    {
        $this->RRSet = $rrset;
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
                $this->setRRSet($rrset);
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
        if (isset($this->RRSet) && $this->RRSet instanceof RRSet) {
            $this->RRSet->deleteRecord($content);
            return $this->saveRRSets($this->getRrset());
        }
        $resp = (object) array("ok" => 0, "msg" => "No matching Record!");
        return $resp;
    }

    protected function push()
    {
        $resp = $this->saveRRSets($this->getRrset());
        return $resp;
    }

    //get rrset data and return it in an array
    protected function getRrset()
    {
        if (!isset($this->RRSet)) {
            throw new \Exception("RRSet object is empty");
        }
        return array($this->RRSet->export());
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

    public function getZoneName($zone_id)
    {
        $zone_id = trim($zone_id, ".");
        $zone_id = explode('.', $zone_id);
        //$zone_id = array_slice($zone_id, -2, 2);
        array_shift($zone_id);
        return implode('.', $zone_id);
    }

}
