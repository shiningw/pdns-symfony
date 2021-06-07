<?php

namespace Shiningw\PdnsBundle\lib;

class PdnsApi extends PdnsApiBase
{

    protected $zone;
    protected $zone_id;
    protected $path;
    protected $searchData;

    public function __construct($apiKey = null, $baseUrl = null)
    {
        parent::__construct($apiKey);
        $this->apiKey = $apiKey;
        $this->baseUrl = $baseUrl ? $baseUrl : 'http://127.0.0.1:8081/api/v1';
        $this->path = "/servers/localhost/zones";
        $this->zoneUrl = $this->getZoneUrl();
    }

    public function getZoneUrl()
    {
        return sprintf("%s%s", $this->baseUrl, $this->path);
    }
    public function setZoneID($zone_id)
    {
        // append a dot to every zone id
        if (substr($zone_id, -1) != '.') {
            $zone_id .= '.';
        }
        $this->zone_id = $zone_id;
        return $this;
    }

    public function setBaseUrl($url)
    {
        $this->baseUrl = $url;
        return $this;
    }

    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    public function getZoneID()
    {
        return $this->zone_id;
    }

    public function listZones($q = false)
    {

        $res = $this->send($this->baseUrl . $this->path);
        return json_decode($res->data);
    }

    public function search(string $string)
    {
        $data = null;
        if ($string) {
            $path = "/servers/localhost/search-data?q=*" . $q . "*&max=25&object_type=record";
            $resp = $this->send($this->baseUrl . $path);

            if ($resp->code == 200 && isset($resp->data)) {
                $data = json_decode($resp->data, 1);
            }
            $this->searchData = $data;
        }
        return $data;
    }

    public function loadZone()
    {
        $path = $this->path . "/" . $this->zone_id;
        $res = $this->send($this->getZoneUrl() . "/" . $this->zone_id);
        return json_decode($res->data, 1);
    }

    public function createZone($zone)
    {
        $resp = $this->send($this->baseUrl . $this->path, $zone, "POST");
        return $this->getResponse($resp);
    }

    public function saveZone($zone)
    {
        $zonedata = $zone;
        unset($zonedata['id']);
        unset($zonedata['url']);
        unset($zonedata['rrsets']);

        if (!isset($zone['serial']) || !is_int($zone['serial'])) {
            $this->setMethod('POST');
            $res = $this->client->Request($this->baseUrl . $this->path, $zonedata);
            return $res->data;
        }

        if (is_string($zone['url']) && $zone['url'][0] != '/') {
            $path = '/' . $zone['url'];
        } else {
            $path = $zone['url'];
        }

        $address = substr($this->baseUrl, 0, strpos($this->baseUrl, "/"));
        $requestUrl = $address . $path;
        $this->send($requestUrl, $zonedata, "PUT");
        // Then, update the rrsets
        if (count($zone['rrsets']) > 0) {
            $content = array('rrsets' => $zone['rrsets']);
            $resp = $this->send($requestUrl, $content, "PATCH");
        }

        return $this->getResponse($resp);
        //return $this->loadzone($zone['id']);
    }

    public function removeZone()
    {
        $path = $this->path . "/" . $this->zone_id;
        $requestUrl = $this->baseUrl . $path;
        return $this->getResponse($this->send($requestUrl, null, "DELETE"));
    }

    protected function getResponse($resp)
    {
        if (!in_array($resp->code, array(200, 201, 204))) {
            $msg = json_decode($resp->data);
            if (isset($msg->error)) {
                $resp->msg = $msg->error;
            }
            $resp->ok = 0;
        } else {
            $resp->msg = 'Success';
            $resp->ok = 1;
        }
        return $resp;
    }
    public function send(string $url, array $data = null, string $method = null)
    {
        if (isset($method)) {
            $this->client->setMethod($method);
        }
        return $this->client->Request($url, $data);
    }

    public function saveRRSets(array $rrsets)
    {
        $url = $this->getZoneUrl() . "/" . $this->zone_id;
        $postData = array('rrsets' => $rrsets);
        $resp = $this->send($url, $postData, "PATCH");
        return $this->getResponse($resp);
    }

}
