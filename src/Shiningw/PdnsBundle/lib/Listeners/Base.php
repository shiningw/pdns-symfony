<?php
namespace Shiningw\PdnsBundle\lib\Listeners;

use Shiningw\PdnsBundle\lib\Database;

class Base
{
    //database handler
    protected $dbh;
    protected $ispMaps;

    public function __construct()
    {
        $this->dbh = new Database();
        $this->ispMaps = array(1 => "ctcc", 2 => "cucc", 3 => "cmcc");
    }
}
