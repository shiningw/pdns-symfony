<?php
namespace Shiningw\PdnsBundle\lib;

use Shiningw\PdnsBundle\lib\PdnsApi;
use Shiningw\PdnsBundle\Zone\RRSet;

class ListRecord extends PdnsRecord
{
    public function __construct($apiKey = null, $domain = null, $baseUrl = null)
    {
        parent::__construct($apiKey, $domain, $baseUrl);
    }

    public function list(){
       return $this->listRecords();
    }

}
