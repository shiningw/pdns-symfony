<?php

declare (strict_types = 1);

namespace Shiningw\PdnsBundle\Controller;

use Shiningw\PdnsBundle\lib\PdnsApi;
use Shiningw\PdnsBundle\lib\PdnsRecord;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class PdnsController extends Controller
{

    protected $pdnsApi;
    private $apiKey;
    private $nameServers;

    public function __construct(string $apiKey, array $nameServers)
    {
        $this->apiKey = $apiKey;
        $this->nameServers = $nameServers;
        $this->pdnsApi = new PdnsApi($apiKey);
    }

    public function zoneListAction(Request $request)
    {

        //$this->pdnsApi = clone $api;
        $zones = $this->pdnsApi->listZones();
        $data = array();

        foreach ($zones as $value) {
            $d['domain'] = $value->name;
            $d['kind'] = $value->kind;
            $d['serial'] = $value->serial;
            $d['masters'] = $value->masters;
            $data[] = $d;
        }

        return $this->render('@Pdns/zonelist.html.twig', array(
            'zones' => $data, 'zones_test' => $zones,
        ));
    }

    public function recordListAction($domain)
    {
        $domain = trim($domain);
        $data = $this->pdnsApi->setZoneID($domain)->listRecords();

        return $this->render('PdnsBundle::records.html.twig', array('data' => $data, 'zone_id' => $this->pdnsApi->getZoneID()));
    }

    public function createAction(Request $request)
    {
        $name = $request->request->get('name');
        $dnstype = $request->request->get('dnstype');
        $content = $request->request->get('content');
        $ttl = $request->request->get('ttl');
        //zone_id is the top-level domain name
        $zone_id = $request->request->get('zone_id');
        //append dot
        if (in_array($dnstype, array('CNAME', 'MX', 'NS')) && (substr($content, -1, 1)) != '.') {
            $content .= '.';
        }
        if (strtoupper($dnstype) == 'TXT' && !in_array($content[0], array('"', "'"))) {
            $content = '"' . $content . '"';
        }
        // add priority number if dns type is MX and the value is missing a number preceding the content
        if (strtoupper($dnstype) == 'MX' && count(explode('/\s/', $content)) < 2) {
            $content = '10 ' . $content;
        }
        //make name canonical
        if (stripos($zone_id, $name) === false || strpos($name, '.') === false) {
            //$name = implode('.', array($name, $zone_id));
        }

        $zoneData = $this->pdnsApi->setZoneID($zone_id)->loadZone();
        $newrecord = new PdnsRecord($this->apiKey, $zone_id);
        $newrecord->init($zoneData, $name, $dnstype);
        $resp = $newrecord->setName($name)
            ->setType($dnstype)
            ->setTTL($ttl)
            ->setContent($content)
            ->create();
        if (!in_array($resp->code, array(200, 204))) {
            $resp->msg = json_decode($resp->data)->error;
        } else {
            $resp->msg = sprintf("successfully added %s", $name);
        }

        return new JsonResponse($resp);
    }

    public function updateAction(Request $request)
    {

        $record = $request->request->get('pk');
        $value = $request->request->get('value');
        $zone_id = $request->request->get('zone_id');
        $inputname = $request->request->get('name');
        $ttl = ($inputname == 'ttl') ? $value : $record['ttl'];
        $recordname = ($inputname == 'name') ? $value : $record['name'];

        $zoneData = $this->pdnsApi->setZoneID($zone_id)->loadZone();
        $newrecord = new PdnsRecord($this->apiKey, $zone_id);
        $newrecord->init($zoneData, $record['name'], $record['type']);
        $newrecord->updateType = $inputname;
        $content = $record['content'];
        $dnstype = $record['type'];
        if (strtoupper($dnstype) == 'TXT' && !in_array($value, array('"', "'"))) {
            $value = '"' . $value . '"';
        }
        $data = $newrecord
            ->setContent($content)
            ->setTTL($ttl)
            ->setName($record['name'])
            ->update($value);
        $data->content = $record;
        return new JsonResponse($data);
    }

    public function deleteAction(Request $request)
    {

        $name = trim($request->request->get('name'));
        $dnstype = trim($request->request->get('dnstype'));
        $content = trim($request->request->get('content'));
        $zone_id = trim($request->request->get('zonename'));
        if (strtoupper($dnstype) == 'TXT' && !in_array($content[0], array('"', "'"))) {
            $content = '"' . $content . '"';
        }
        $zoneData = $this->pdnsApi->setZoneID($zone_id)->loadZone();
        $newrecord = new PdnsRecord($this->apiKey, $zone_id);
        $resp = $newrecord->init($zoneData, $name, $dnstype)->delete($content);

        return new JsonResponse($resp);

        //return $this->render('PdnsBundle::test.html.twig', array('data' => $pk));
    }

    public function newZone(Request $request)
    {
        foreach ($this->nameServers as $key => &$server) {
            $this->appendDot($server);
        }
        $name = $request->request->get('zonename');

        if (!$this->checkDomain($name)) {
            return new JsonResponse(array('msg' => 'Illegal Domain Name','code' => 444));
        }
        $data = array(
            'name' => $name,
            'nameservers' => $this->nameServers,
            'kind' => 'Native',
            'masters' => array(),
        );
        $this->appendDot($data['name']);
        $res = $this->pdnsApi->createZone($data);
        return new JsonResponse($res);
    }

    public function removeZone(Request $request)
    {
        $zone_id = $request->request->get('zonename');
        if ($this->checkDomain($zone_id)) {
            $res = $this->pdnsApi->setZoneID($zone_id)->removeZone();
            return new JsonResponse($res);
        }
        return new JsonResponse(array('msg' => 'invalid domain name','code' => 444));
    }

    private function appendDot(&$name)
    {
        //append dot to zone id if it doesn't end with a dot
        if (substr($name, -1, 1) != '.') {
            $name .= '.';
        }
    }
    protected function checkDomain($domain)
    {
        $regex = "/^([0-9A-Za-z]{1}[0-9A-Za-z]+[.]{1})+[a-zA-Z]+[.]?$/";
        return (bool) preg_match($regex, $domain);
    }

}
