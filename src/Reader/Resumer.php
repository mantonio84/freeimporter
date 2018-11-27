<?php

namespace Mantonio84\FreeImporter\Reader;

class Resumer extends Reader implements \Countable, \ArrayAccess {
    
    public function __construct($resumeData){
        $data=json_decode(base64_decode($resumeData),true);
        if (!is_array($data)) throw new \Exception("Invalid resumer data given (0)!");
        if (!array_key_exists("chk",$data)) throw new \Exception("Invalid resumer data given (1)!");
        if (!array_key_exists("container",$data)) throw new \Exception("Invalid resumer data given (2)!");
        if (!array_key_exists("header",$data)) throw new \Exception("Invalid resumer data given (3)!");
        if (!array_key_exists("fileHash",$data)) throw new \Exception("Invalid resumer data given (3)!");
        $this->container=$data['container'];
        $this->header=$data['header'];
        $this->fileHash=$data['fileHash'];
    }
}
?>