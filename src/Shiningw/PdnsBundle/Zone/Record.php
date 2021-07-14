<?php

namespace Shiningw\PdnsBundle\Zone;

class Record
{

    public function __construct($content, $disabled = false, $setptr = false)
    {
        $this->content = $content;
        $this->disabled = $disabled;
        $this->setptr = $setptr;
    }
    public function setContent($content)
    {
        $this->content = $content;
    }

    public function export()
    {
        $ret;

        $ret['content'] = $this->content;
        $ret['disabled'] = (bool) $this->disabled;
        if ($this->setptr) {
            $ret['set-ptr'] = (bool) true;
        }

        return $ret;
    }

}
