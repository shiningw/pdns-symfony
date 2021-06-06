<?php

namespace Shiningw\PdnsBundle\lib;

use Symfony\Component\HttpKernel\KernelInterface;

class Utils
{
    private $rootDir;
    public function __construct(KernelInterface $kernel)
    {
        $this->rootDir = $kernel->getProjectDir();
    }

    public function check_plain($string)
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
    public function sanitize($string)
    {
        $string = trim($string);
        $string = strtolower($string);
        return $this->check_plain($string);
    }

    public function removeParts($string,$delim = "/",$count =2){
        $parts = explode($delim,$string);
        $parts = array_slice($parts,0,-$count);
        return implode($delim,$parts);
    }

    public function isRecordType($type)
    {
        $validTypes = array("A", "AAAA", "CNAME", "MX", "TXT", "SOA", "SPF", "SRV", "PTR");
        $type = strtoupper($type);
        if (in_array($type, $validTypes)) {
            return true;
        } else {
            return false;
        }
    }
    public function isTTL($ttl)
    {
        return is_numeric($ttl);
    }
    public function isDomainName($string)
    {
        $regex = "/^([0-9A-Za-z]{1}[0-9A-Za-z]+[.]{1})+[a-zA-Z]+[.]?$/";
        return (bool) preg_match($regex, $string);
    }
    public static function log($var,$name = null){
        $str = print_r($var,true);
        $filename = isset($name)?$name:date("Y-m-d");
        file_put_contents(__DIR__."/".$filename,$str);
    }
}
