<?php

namespace Shiningw\PdnsBundle\Zone;

class Zone
{
    public $name,$kind,$masters = array(),$nameservers = array();

    public function __construct($name, $nameservers = null, $masters = null, $kind = null)
    {
        $this->name = $name;
        $this->kind = $kind;
        $this->masters = $masters;
        $this->nameservers = $nameservers;
    }

    public function setname($name){
        $this->name = $name;
    }
    public function setKind($kind){
        $this->kind = $kind;
    }
    public function setNameservers($nameservers){
        $this->nameservers = $nameservers;
    }

    public function setMasters($masters){
        $this->masters = $masters;
    }

    public function export()
    {
        $ret;
        $ret['name'] = $this->name;
        $ret['kind'] = $this->kind;
        $ret['masters'] = $this->masters;
        $ret['nameservers'] = $this->nameservers;
        return $ret;
    }

}
