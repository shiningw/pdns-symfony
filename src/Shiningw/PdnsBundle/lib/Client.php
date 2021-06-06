<?php
namespace Shiningw\PdnsBundle\lib;

class Client
{

    const HTTP_REQUEST_TIMEOUT = -1;

    protected $charset = 'UTF-8';
    public $responses;
    protected $options = array();
    protected $headers = array();

    public function __construct()
    {
        $this->responses = new \stdClass();
        $this->method = 'GET';
    }

    public function setOption($name, $value)
    {

        $this->options[$name] = $value;
    }

    public function setMethod($method)
    {
        $this->method = $method;
    }

    public function setHeaders($name, $value)
    {

        $this->options['headers'][$name] = $value;
        $this->headers = $this->options['headers'];
    }

    public function setPostData($data)
    {

        $this->options['data'] = $this->urlEncode($data);
    }

    public static function parseResponseStatus($response)
    {
        $response_array = explode(' ', trim($response), 3);
        // Set up empty values.
        $result = array(
            'reason_phrase' => '',
        );
        $result['http_version'] = $response_array[0];
        $result['response_code'] = $response_array[1];
        if (isset($response_array[2])) {
            $result['reason_phrase'] = $response_array[2];
        }
        return $result;
    }

    public function timer_start($name)
    {
        global $timers;

        $timers[$name]['start'] = microtime(true);
        $timers[$name]['count'] = isset($timers[$name]['count']) ? ++$timers[$name]['count'] : 1;
    }

    public function timer_read($name)
    {
        global $timers;

        if (isset($timers[$name]['start'])) {
            $stop = microtime(true);
            $diff = round(($stop - $timers[$name]['start']) * 1000, 2);

            if (isset($timers[$name]['time'])) {
                $diff += $timers[$name]['time'];
            }
            return $diff;
        }
        return $timers[$name]['time'];
    }

    protected function urlEncode($paras = array())
    {

        $str = '';

        foreach ($paras as $k => $v) {

            $str .= "$k=" . urlencode($this->characet($v, $this->charset)) . "&";
        }
        return substr($str, 0, -1);
    }

    public function characet($data, $targetCharset = 'UTF-8')
    {

        if (!empty($data)) {

            if (strcasecmp($this->charset, $targetCharset) != 0) {

                $data = mb_convert_encoding($data, $targetCharset);
            }
        }

        return $data;
    }

    public function Request($url, $params = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, 0);
        $this->headers += array(
            'User-Agent' => 'phpclient',
        );

        foreach ($this->headers as $name => $value) {
            $headers[] = $name . ': ' . $value;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $this->content = null;

        if (isset($this->headers['Content-Type']) && strtolower($this->headers['Content-Type'])
            == 'application/json' && isset($params)) {
            $this->content = json_encode($params);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->content);
        }

        if (in_array($this->method, array('DELETE', 'PATCH', 'PUT'))) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->method);
        }
      //  if (isset($this->content)) {
      //      curl_setopt($ch, CURLOPT_POSTFIELDS, $this->content);
      //  }

        if ($this->method == "POST" && !isset($this->content)) {
            curl_setopt($ch, CURLOPT_POST, 1);
            if (isset($params)) {
                $postBody = "";
                $multipart = null;
                $encodeParams = array();

                foreach ($params as $k => $v) {
                    if ("@" != substr($v, 0, 1)) {

                        $postBody .= "$k=" . urlencode($this->characet($v, $this->charset)) . "&";
                        $encodeParams[$k] = $this->characet($v, $this->charset);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, substr($postBody, 0, -1));
                        $headers = array('content-type: application/x-www-form-urlencoded;charset=' . $this->charset);
                    } else {
                        $encodeParams[$k] = new \CURLFile(substr($v, 1));
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $encodeParams);
                        $headers = array('content-type: multipart/form-data;charset=' . $this->charset);
                    }
                }

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
