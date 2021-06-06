<?php

namespace Shiningw\PdnsBundle\Zone;

class Comment {

    public function __construct($content, $account, $modified_at) {
        $this->content = $content;
        $this->account = $account;
        $this->modified_at = $modified_at;
    }

    public function export() {
        $ret = array();
        $ret['content'] = $this->content;
        $ret['account'] = $this->account;
        $ret['modified_at'] = $this->modified_at;
        return $ret;
    }

}
