<?php

namespace Shiningw\PdnsBundle\lib;

use Shiningw\PdnsBundle\Zone\ZoneBase;

class PdnsApi extends PdnsApiBase {

    protected $zone;
    protected $zone_id;

    public function __construct($apiKey = null, $baseUrl = null) {
        parent::__construct($apiKey);
        $this->apiKey = $apiKey;
        $this->baseUrl = $baseUrl?$baseUrl:'http://127.0.0.1:8081/api/v1';
    }
    public function setZoneID($zone_id) {
        // append a dot to every zone id
        if (substr($zone_id, -1) != '.') {
            $zone_id .= '.';
        }
        $this->zone_id = $zone_id;
        return $this;
    }

    public function getZoneID() {
        return $this->zone_id;
    }

    public function listZones($q = FALSE) {

        if ($q) {
            $path = "/servers/localhost/search-data?q=*" . $q . "*&max=25";
            $res = $this->client->Request($this->baseUrl . $path);
            $ret = Array();
            $seen = Array();

            foreach ($res->data as $result) {
                if (isset($seen[$result['zone_id']])) {
                    continue;
                }
                $zone = $this->loadzone($result['zone_id']);
                unset($zone['rrsets']);
                array_push($ret, $zone);
                $seen[$result['zone_id']] = 1;
            }

            return $ret;
        }
        $path = "/servers/localhost/zones";
        $res = $this->client->Request($this->baseUrl . $path);

        return json_decode($res->data);
    }

    public function loadZone() {
        $path = "/servers/localhost/zones/" . $this->zone_id;
        $res = $this->client->Request($this->baseUrl . $path);

        return json_decode($res->data, 1);
    }

    public function createZone($zone) {
        $path = "/servers/localhost/zones";
        $this->client->setMethod('POST');
        $data = $this->client->Request($this->baseUrl . $path, $zone);

        if ($data->code > 205) {
            $data->msg = 'failure!!';
        } else {
            $data->msg = 'success';
        }
        return $data;
    }

    public function saveZone($zone) {
        $zonedata = $zone;
        unset($zonedata['id']);
        unset($zonedata['url']);
        unset($zonedata['rrsets']);

        if (!isset($zone['serial']) || !is_int($zone['serial'])) {
            $path = '/servers/localhost/zones';
            $this->setMethod('POST');
            $res = $this->client->Request($this->baseUrl . $path, $zonedata);
            return $res->data;
        }


        if (is_string($zone['url']) && $zone['url'][0] != '/') {
            $path = '/' . $zone['url'];
        } else {
            $path = $zone['url'];
        }

        $address = substr($this->baseUrl,0,strpos($this->baseUrl,"/"));
        $requestUrl = $address . $path;
        $this->client->setMethod('PUT');
        $this->client->Request($requestUrl, $zonedata);
        // Then, update the rrsets
        if (count($zone['rrsets']) > 0) {

            $content = Array('rrsets' => $zone['rrsets']);
            // file_put_contents('../var/textf.txt',  $requestUrl);
            $this->setMethod('PATCH');
            $data = $this->client->Request($requestUrl, $content);
        }
        if (!in_array($data->code, array(200, 204))) {
            $data->msg = json_decode($data->data)->error;
        } else {
            $data->msg = 'success';
        }

        return $data;
        return $this->loadzone($zone['id']);
    }

    public function removeZone() {
        $path = "/servers/localhost/zones/" . $this->zone_id;
        $requestUrl = $this->baseUrl . $path;

        $data = $this->client->Request($requestUrl, 'DELETE');

        if (!in_array($data->code, array(200, 204))) {
            $data->msg = json_decode($data->data)->error;
        } else {
            $data->msg = 'success';
        }

        return $data;
    }

    public function saveRRSets($rrsets) {
        $path = '/servers/localhost/zones/' . $this->zone_id;
        $this->client->setMethod('PATCH');
        $res = $this->client->request($this->baseUrl . $path, array('rrsets' => $rrsets));
        return $res;
    }

}
