<?php

namespace mantonio84\FreeImporter\Reader;

class CSV extends Reader implements \Countable, \ArrayAccess {
    
    public function __construct($filePath, $delimiter = ",", $enclosure = '"', $escape = "\\"){
        $f=fopen($filePath,"r");
        if ($f===false) throw new Exception("Unable to open file '".$filePath."'!");
        $mx=-1;
        $toresize=false;
        while (($line = fgetcsv($handle, 0, $delimiter, $enclosure, $escape)) !== FALSE) {
            if (empty($line)) continue;
            if (($mx>-1) and (count($line)!=$mx)) $toresize=true;            
            $mx=max($mx,count($line));            
            $this->container[]=$line;
        }
        fclose($f);
        if ($toresize){
            foreach ($this->container as &$line){
                $d=$mx-count($line);
                if ($d>0) $line=array_merge($line,array_fill(0,$d,null));                
            }
        }
    }
}
?>