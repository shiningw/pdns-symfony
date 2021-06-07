<?php
namespace Shiningw\PdnsBundle\lib;

use Shiningw\PdnsBundle\lib\PdnsApi;
use Shiningw\PdnsBundle\Zone\RRSet;

class ListRecord extends PdnsRecord
{
    public function __construct($apiKey = null, $zone_id = null, $baseUrl = null)
    {
        parent::__construct($apiKey, $zone_id, $baseUrl);
    }

    public function list(){
       return $this->listRecords();
    }

}
