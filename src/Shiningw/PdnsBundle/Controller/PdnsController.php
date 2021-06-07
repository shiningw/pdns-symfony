<?php

declare (strict_types = 1);

namespace Shiningw\PdnsBundle\Controller;

use Psr\Log\LoggerInterface;
use Shiningw\PdnsBundle\lib\CreateRecord;
use Shiningw\PdnsBundle\lib\DeleteRecord;
use Shiningw\PdnsBundle\lib\ListRecord;
use Shiningw\PdnsBundle\lib\UpdateRecord;
use Shiningw\PdnsBundle\lib\PdnsApi;
use Shiningw\PdnsBundle\lib\Utils;
use Shiningw\PdnsBundle\lib\PdnsZone;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Shiningw\PdnsBundle\lib\Events\CreateRecordEvent;
use Shiningw\PdnsBundle\lib\Listeners\CreateRecordListener;
use Shiningw\PdnsBundle\lib\Listeners\DeleteRecordListener;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PdnsController extends Controller
{

    protected $pdnsApi;
    private $apiKey;
    private $nameServers;

    public function __construct(string $apiKey, array $nameServers,Utils $util, EventDispatcherInterface $dispatcher)
    {
        $this->apiKey = $apiKey;
        $this->nameServers = $nameServers;
        $this->tool = $util;
        $this->pdnszone = new PdnsZone($this->apiKey);
        $this->dispatcher = $dispatcher;
    }

    public function zoneListAction(Request $request)
    {
        $zones = $this->pdnszone->list();
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

    public function recordListAction($zone_id)
    {
        $zone_id = $this->tool->sanitize($zone_id);
        if (!$this->tool->isDomainName($zone_id)) {
            return new JsonResponse(array("msg" => "invalid Zone Name"));
        }
        $instance = new ListRecord($this->apiKey, $zone_id);
        $data = $instance->list();
        return $this->render('PdnsBundle::records.html.twig', array('data' => $data, 'zone_id' => $zone_id));
    }

    public function createAction(Request $request)
    {
        $name = $this->tool->sanitize($request->request->get('name'));
        $content = $this->tool->sanitize($request->request->get('content'));
        $recordtype = trim($request->request->get('recordtype'));
        $ttl = $request->request->get('ttl');
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
        $newrecord = new CreateRecord($this->apiKey, $zone_id, null, $this->dispatcher);
        $resp = $newrecord->setName($name)
            ->setType($recordtype)
            ->setTTL($ttl)
            ->setContent($content)
            ->create();
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

        $instance = new UpdateRecord($this->apiKey, $zone_id, null, $this->dispatcher);
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
        $data->content = $record; //for debug purpose
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
        $instance = new DeleteRecord($this->apiKey, $zone_id,null,$this->dispatcher);
        $resp = $instance
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
        $zonename = $this->tool->sanitize($request->request->get('zonename'));

        if (!$this->tool->isDomainName($zonename)) {
            return new JsonResponse(array('msg' => 'Illegal Zone Name', 'code' => 444));
        }
        $data = array(
            'name' => $zonename,
            'nameservers' => $this->nameServers,
            'kind' => 'Native',
            'masters' => array(),
        );
        $this->appendDot($data['name']);
        $resp = $this->pdnszone->create($data);
       //$pdnszone = new PdnsZone($this->apiKey,$data['name']);

        return new JsonResponse($resp);
    }

    public function removeZone(Request $request)
    {
        $zone_id = $this->tool->sanitize($request->request->get('zonename'));
        if ($this->tool->isDomainName($zone_id)) {
            $res = $this->pdnszone->setName($zone_id)->remove();
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
