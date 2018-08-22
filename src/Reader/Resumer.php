<?php

namespace mantonio84\FreeImporter\Reader;

class Resumer extends Reader implements \Countable, \ArrayAccess {
    
    public function __construct($filePath){
        $data=json_decode(file_get_contents($filePath),true);
        if (!is_array($data)) throw new Exception("Invalid resumer file given (0)!");
        if (!array_key_exists("chk",$data)) throw new Exception("Invalid resumer file given (1)!");
        if (!array_key_exists("container",$data)) throw new Exception("Invalid resumer file given (2)!");
        if (!array_key_exists("header",$data)) throw new Exception("Invalid resumer file given (3)!");
        $this->container=$data['container'];
        $this->header=$data['header'];
    }
}
?>