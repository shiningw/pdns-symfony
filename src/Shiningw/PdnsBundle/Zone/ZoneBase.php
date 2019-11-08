<?php

namespace Shiningw\PdnsBundle\Zone;

use Shiningw\PdnsBundle\Zone\RRSet;

class ZoneBase {

    protected $id, $name, $kind, $url, $serial, $dnssec;
    protected $soa_edit, $soa_edit_api, $keyinfo, $account, $zone;
    protected $nameservers = array();
    public $rrsets = array();
    protected $masters = Array();

    public function __construct($data) {
        $this->import($data);
    }

    public function import($data) {
        $this->setId($data['id']);
        $this->setName($data['name']);
        $this->setKind($data['kind']);
        $this->setDnssec($data['dnssec']);
        $this->setAccount($data['account']);
        $this->setSerial($data['serial']);
        $this->url = $data['url'];
        if (isset($data['soa_edit']) && $data['soa_edit'] != "")
            $this->setSoaEdit($data['soa_edit']);
        if (isset($data['soa_edit_api']) && $data['soa_edit_api'] != "")
            $this->setSoaEditApi($data['soa_edit_api'], True);

        foreach ($data['masters'] as $master) {
            $this->addMaster($master);
        }

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
    }

    public function importData($data) {
        $this->zone = $data;
    }

    public function setKeyinfo($info) {
        $this->keyinfo = $info;
    }

    public function addNameserver($nameserver) {
        foreach ($this->nameservers as $ns) {
            if ($nameserver == $ns) {
                throw new Exception("We already have this as a nameserver");
            }
        }
        array_push($this->nameservers, $nameserver);
    }

    public function setSerial($serial) {
        $this->serial = $serial;
    }

    public function setSoaEdit($soaedit) {
        $this->soa_edit = $soaedit;
    }

    public function setSoaEditApi($soaeditapi, $overwrite = False) {
        if (isset($this->soa_edit_api) and $this->soa_edit_api != "") {
            if ($overwrite === False) {
                return False;
            }
        }
        $this->soa_edit_api = $soaeditapi;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function setKind($kind) {
        $this->kind = $kind;
    }

    public function setAccount($account) {
        $this->account = $account;
    }

    public function setDnssec($dnssec) {
        $this->dnssec = $dnssec;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function addMaster($ip) {
        foreach ($this->masters as $master) {
            if ($ip == $master) {
                throw new \Exception("We already have this as a master");
            }
        }
        array_push($this->masters, $ip);
    }

    public function eraseMasters() {
        $this->masters = Array();
    }

    public function addRRSet($name, $type, $content, $disabled = FALSE, $ttl = 3600, $setptr
    = FALSE) {
        if ($this->getRRSet($name, $type) !== FALSE) {
            throw new \Exception("This rrset already exists.");
        }
        $rrset = new RRSet($name, $type, $content, $disabled, $ttl, $setptr);
        array_push($this->rrsets, $rrset);
    }

    public function addRecord($name, $type, $content, $disabled = FALSE, $ttl = 3600, $setptr
    = FALSE) {
        $rrset = $this->getRRSet($name, $type);

        if ($rrset) {
            $rrset->addRecord($content, $disabled, $setptr);
            $rrset->setTtl($ttl);
        } else {
            $this->addRRSet($name, $type, $content, $disabled, $ttl, $setptr);
        }

        return $this->getRecord($name, $type, $content);
    }

    public function delRecord($name, $type, $content) {
        $rrset = $rrset = $this->getRRSet($name, $type);

        if ($rrset)
            $rrset->deleteRecord($content);
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

    public function getRRSet($name, $type) {
        foreach ($this->rrsets as $rrset) {
            if ($rrset->name == $name and $rrset->type == $type) {
                return $rrset;
            }
        }

        return FALSE;
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

    public function export() {
        $ret = Array();
        $ret['account'] = $this->account;
        $ret['nameservers'] = $this->nameservers;
        $ret['kind'] = $this->kind;
        $ret['name'] = $this->name;
        if (isset($this->soa_edit) && $this->soa_edit != "") {
            $ret['soa_edit'] = $this->soa_edit;
        }
        if (isset($this->soa_edit_api) && $this->soa_edit_api != "") {
            $ret['soa_edit_api'] = $this->soa_edit_api;
        }
        if ($this->zone) {
            $ret['zone'] = $this->zone;
            return $ret;
        }

        $ret['dnssec'] = $this->dnssec;
        if ($this->dnssec) {
            $ret['keyinfo'] = $this->keyinfo;
        }
        $ret['id'] = $this->id;
        $ret['masters'] = $this->masters;
        $ret['rrsets'] = $this->exportRRSets();
        $ret['serial'] = $this->serial;
        $ret['url'] = $this->url;

        return $ret;
    }

    private function exportRRSets() {
        $ret = Array();
        foreach ($this->rrsets as $rrset) {
            array_push($ret, $rrset->export());
        }

        return $ret;
    }
    
    public function exportRecords() {
        return $this->rrsets;
    }

}

?>
