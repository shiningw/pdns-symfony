<?php

declare (strict_types = 1);

namespace Shiningw\PdnsBundle\Controller;

use Psr\Log\LoggerInterface;
use Shiningw\PdnsBundle\lib\CreateRecord;
use Shiningw\PdnsBundle\lib\Database;
use Shiningw\PdnsBundle\lib\DeleteRecord;
use Shiningw\PdnsBundle\lib\ListRecord;
use Shiningw\PdnsBundle\lib\PdnsApi;
use Shiningw\PdnsBundle\lib\UpdateRecord;
use Shiningw\PdnsBundle\lib\Utils;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class PdnsController extends Controller
{

    protected $pdnsApi;
    private $apiKey;
    private $nameServers;
    private $ispMaps = array(1 => "ctcc", 2 => "cucc", 3 => "cmcc");

    public function __construct(string $apiKey, array $nameServers, Utils $util)
    {
        $this->apiKey = $apiKey;
        $this->nameServers = $nameServers;
        $this->pdnsApi = new PdnsApi($apiKey);
        $this->db = new Database();
        $this->tool = $util;
    }

    public function zoneListAction(Request $request)
    {

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
        $domain = $this->tool->sanitize($domain);
        if (!$this->tool->isDomainName($domain)) {
            return new JsonResponse(array("msg" => "invalid domain"));
        }
        $instance = new ListRecord($this->apiKey, $domain);
        $data = $instance->list();
        return $this->render('PdnsBundle::records.html.twig', array('data' => $data, 'zone_id' => $domain));
    }

    public function createAction(Request $request, LoggerInterface $logger)
    {
        $name = $this->tool->sanitize($request->request->get('name'));
        $content = $this->tool->sanitize($request->request->get('content'));
        $recordtype = trim($request->request->get('recordtype'));
        $ttl = $request->request->get('ttl');
        $isp = $request->request->get('isp');
        $zone_id = $this->tool->sanitize($request->request->get('zone_id'));
        if (!$this->tool->isRecordType($recordtype)) {
            return new JsonResponse(array("msg" => "invalid dns type"));
        }
        if (!$this->tool->isTTL($ttl)) {
            return new JsonResponse(array("msg" => "invalid ttl"));
        }
        //zone_id is the top-level domain name
        if (!$this->tool->isDomainName($zone_id)) {
            return new JsonResponse(array("msg" => "invalid zone Name"));
        }
        //append dot
        if (in_array(strtoupper($recordtype), array('CNAME', 'MX', 'NS')) && (substr($content, -1, 1)) != '.') {
            $content .= '.';
        }
        if (strtoupper($recordtype) == 'TXT' && !in_array($content[0], array('"', "'"))) {
            $content = '"' . $content . '"';
        }
        // add priority number if dns type is MX and the value is missing a number preceding the content
        if (strtoupper($recordtype) == 'MX' && count(explode('/\s/', $content)) < 2) {
            $content = '10 ' . $content;
        }
        //$zoneData = $this->pdnsApi->setZoneID($zone_id)->loadZone();
        $newrecord = new CreateRecord($this->apiKey, $zone_id, null, $logger);
        $resp = $newrecord->setName($name)
            ->setType($recordtype)
            ->setTTL($ttl)
            ->setContent($content)
            ->create();
        if (!in_array($resp->code, array(200, 204))) {
            $resp->msg = json_decode($resp->data)->error;
        } else {
            if (substr($name, -1, 1) == '.') {
                $name = substr($name, 0, -1);
            }
            $record_id = $this->db->getRecord($name, $content, $recordtype);
            $ispData = array("record_id" => $record_id, "name" => $name, "isp" => $isp, "isp_name" => $this->ispMaps[$isp]);
            $this->db->insertRecord($ispData);
            $resp->msg = sprintf("successfully added %s %d", $name, $record_id);
        }

        return new JsonResponse($resp);
    }

    public function updateAction(Request $request, LoggerInterface $logger)
    {

        $record = $request->request->get('pk');
        $value = $request->request->get('value');
        $zone_id = $request->request->get('zone_id');
        $inputname = $request->request->get('name');
        $ttl = ($inputname == 'ttl') ? $value : $record['ttl'];
        $recordname = ($inputname == 'name') ? $value : $record['name'];

        $instance = new UpdateRecord($this->apiKey, $zone_id, null, $logger);
        //$newrecord->load($zoneData, $record['name'], $record['type']);
        $instance->updateType = $inputname;
        $content = $record['content'];
        $recordtype = $record['type'];
        if (strtoupper($recordtype) == 'TXT' && !in_array($value, array('"', "'"))) {
            $value = '"' . $value . '"';
        }
        $data = $instance
            ->setContent($content) //old content
            ->settype($recordtype)
            ->setTTL($ttl)
            ->setName($record['name'])
            ->update($value);

        $data->content = $record;//for debug purpose
        return new JsonResponse($data);
    }

    public function deleteAction(Request $request)
    {

        $name = $this->tool->sanitize($request->request->get('name'));
        $recordtype = trim($request->request->get('recordtype'));
        $content = $this->tool->sanitize($request->request->get('content'));
        $zone_id = $this->tool->sanitize($request->request->get('zonename'));
        if (strtoupper($recordtype) == 'TXT' && !in_array($content[0], array('"', "'"))) {
            $content = '"' . $content . '"';
        }
        //$zoneData = $this->pdnsApi->setZoneID($zone_id)->loadZone();
        $resp = (new DeleteRecord($this->apiKey, $zone_id))
            ->setName($name)
            ->setType($recordtype)
            ->delete($content);
        return new JsonResponse($resp);
        //return $this->render('PdnsBundle::test.html.twig', array('data' => $pk));
    }

    public function newZone(Request $request)
    {
        foreach ($this->nameServers as $key => &$server) {
            $this->appendDot($server);
        }
        $zone_id = $this->tool->sanitize($request->request->get('zonename'));

        if (!$this->tool->isDomainName($zone_id)) {
            return new JsonResponse(array('msg' => 'Illegal Domain Name', 'code' => 444));
        }
        $data = array(
            'name' => $zone_id,
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
        $zone_id = $this->tool->sanitize($request->request->get('zonename'));
        if ($this->tool->isDomainName($zone_id)) {
            $res = $this->pdnsApi->setZoneID($zone_id)->removeZone();
            return new JsonResponse($res);
        }
        return new JsonResponse(array('msg' => 'invalid domain name', 'code' => 444));
    }

    private function appendDot(&$name)
    {
        //append dot to zone id if it doesn't end with a dot
        if (substr($name, -1, 1) != '.') {
            $name .= '.';
        }
    }
}
