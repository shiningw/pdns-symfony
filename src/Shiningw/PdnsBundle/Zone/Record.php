<?php

namespace Shiningw\PdnsBundle\Zone;

class Record {

    public function __construct($content, $disabled = FALSE, $setptr = FALSE) {
        $this->content = $content;
        $this->disabled = $disabled;
        $this->setptr = $setptr;
    }

    public function export() {
        $ret;

        $ret['content'] = $this->content;
        $ret['disabled'] = (bool) $this->disabled;
        if ($this->setptr) {
            $ret['set-ptr'] = (bool) TRUE;
        }

        return $ret;
    }

}
