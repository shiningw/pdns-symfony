<?php

namespace Shiningw\PdnsBundle\lib;

class Client {

    const HTTP_REQUEST_TIMEOUT = -1;

    protected $charset = 'UTF-8';
    public $responses;
    protected $options = array();
    protected $headers = array();
    
    protected $statusCode = array(
            100 => 'Continue',
            101 => 'Switching Protocols',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            307 => 'Temporary Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Time-out',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Large',
            415 => 'Unsupported Media Type',
            416 => 'Requested range not satisfiable',
            417 => 'Expectation Failed',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Time-out',
            505 => 'HTTP Version not supported',
        );

    public function __construct() {
        $this->responses = new \stdClass();
    }

    protected function defaultOptions() {
        return array(
            'headers' => array(
                'User-Agent' => 'Drupal',
            ),
            'method' => 'GET',
            'data' => NULL,
            'max_redirects' => 3,
            'timeout' => 30.0,
            'context' => NULL,
        );
    }

    public function setOption($name, $value) {

        $this->options[$name] = $value;
    }

    public function setHeaders($name, $value) {

        $this->options['headers'][$name] = $value;
        $this->headers = $this->options['headers'];
    }

    public function setPostData($data) {

        $this->options['data'] = $this->urlEncode($data);
    }

    protected function urlEncode($paras = array()) {


        $str = '';

        foreach ($paras as $k => $v) {

            $str .= "$k=" . urlencode($this->characet($v, $this->charset)) . "&";
        }
        return substr($str, 0, -1);
    }

    public function characet($data, $targetCharset = 'UTF-8') {


        if (!empty($data)) {

            if (strcasecmp($this->charset, $targetCharset) != 0) {

                $data = mb_convert_encoding($data, $targetCharset);
            }
        }


        return $data;
    }

    public function Request($url, $method = 'GET', $params = null) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $this->headers += array(
            'User-Agent' => 'phpclient',
        );

        foreach ($this->headers as $name => $value) {

            $headers[] = $name . ': ' . $value;
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if (isset($this->headers['Content-type']) && strtolower($this->headers['Content-type'])
                == 'application/json') {
            if (isset($params)) {
                $this->content = json_encode($params);
            }
            $params = null;
        }

        switch ($method) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, 1);
                break;
            case 'GET':
                curl_setopt($ch, CURLOPT_POST, 0);
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                break;
            case 'PATCH':
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $this->content);
                break;
        }


        if ($method == "POST") {
            if (isset($params)) {
                $postBody = "";
                $multipart = NULL;
                $encodeParams = Array();

                foreach ($params as $k => $v) {
                    if ("@" != substr($v, 0, 1)) {

                        $postBody .= "$k=" . urlencode($this->characet($v, $this->charset)) . "&";
                        $encodeParams[$k] = $this->characet($v, $this->charset);
                    } else {
                        $multipart = true;
                        $encodeParams[$k] = new \CURLFile(substr($v, 1));
                    }
                }

                if ($multipart) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $encodeParams);
                    $headers = array('content-type: multipart/form-data;charset=' . $this->charset);
                } else {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, substr($postBody, 0, -1));
                    $headers = array('content-type: application/x-www-form-urlencoded;charset=' . $this->charset);
                }
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $this->content);
            }
        }
        $res = curl_exec($ch);

        if (curl_errno($ch)) {

            throw new \Exception(curl_error($ch), 0);
        } else {
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $this->responses->code = $code;
        }

        curl_close($ch);
        $this->responses->data = $res;
        return $this->responses;
    }

}
