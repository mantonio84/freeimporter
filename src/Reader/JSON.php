<?php

namespace Mantonio84\FreeImporter\Reader;

class JSON extends Reader implements \Countable, \ArrayAccess {
    
    public function __construct($filePath){
        $data=json_decode(file_get_contents($filePath),true);
        if (!is_array($data)) throw new Exception("Invalid JSON file given!");        
        $this->container=array_values($data); 
        $this->cleanUpContainer();
        $this->calculateFileHash($filePath);       
    }
}
?>