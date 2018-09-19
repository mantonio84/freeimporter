<?php
namespace Mantonio84\FreeImporter\Reader;

use Mantonio84\FreeImporter\Utils\FixedWidthFile;

class TXT extends Reader implements \Countable, \ArrayAccess {
    
    public function __construct(string $filePath, int $maxLineWidth=8192) {    
        $tool=new FixedWidthFile($filePath,$maxLineWidth,false);
        $this->container=$tool->getfileData();
        $this->calculateFileHash($filePath);        
    } 
    
    
}


?>