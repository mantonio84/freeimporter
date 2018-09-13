<?php

namespace Mantonio84\FreeImporter\Reader;

class CSV extends Reader implements \Countable, \ArrayAccess {    
    
    public function __construct(string $filePath, string $escape = "\\", array $otherDelimeters=array()){
        $f=fopen($filePath,"r");
        if ($f===false) throw new Exception("Unable to open file '".$filePath."'!");
        $mx=-1;
        $toresize=false;
        $format=null;
        while (!feof($f)) {
            $rawLine=trim(fgets($f));
            if (strlen($rawLine)<3) continue;
            if ($format===null) $format=$this->guessLineFormat($rawLine,$otherDelimeters);
            if ($format===null) continue;
            $line=str_getcsv($rawLine,$format->delimeter,$format->enclosure,$escape);
            if ($this->isBlankCSVLive($line)) continue;           
            $c=count($line);             
            $mx=max($mx,$c);            
            if (($mx>-1) and ($c!=$mx)) $toresize=true;
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
    
    protected function guessLineFormat(string $line,array $otherDelimeters=array()){        
        $line=trim($line);
        if (strlen($line)<3) return null;
        $delimeters=[",",";","/","|"];
        if (!empty($otherDelimeters)){    
            $otherDelimeters=array_filter($otherDelimeters,function ($itm){
               return (strlen($itm)==0); 
            }); 
            $delimeters = array_values(array_unique(array_merge($delimeters,$otherDelimeters)));
        }        
        $foundDelimeter=null;
        foreach ($delimeters as $d){            
            $a=str_getcsv($line,$d);
            if (is_array($a)){
                if (count($a)>1){
                    $foundDelimeter=$d;
                    break;
                }
            }            
        }
        if ($foundDelimeter===null) return null;           
        $fields=str_getcsv($line,$foundDelimeter); 
        $foundEnclosure=null;       
        foreach ($fields as $f){
            if (strlen($f)<2){
                $foundEnclosure="";
                break;
            }else{
                $firstChar=$f[0];
                $lastChar=$f[-1];
                if ($firstChar==$lastChar){
                    if (!ctype_alpha($firstChar)){
                        if ($foundEnclosure===null){
                            $foundEnclosure=$firstChar;
                        }else{
                            if ($foundEnclosure!=$firstChar){
                                $foundEnclosure="";
                                break;
                            }
                        }
                    }else{
                        $foundEnclosure="";
                        break;
                    }
                }else{
                    break;
                }   
            }            
        }
        if ($foundEnclosure===null) $foundEnclosure="\"";
        $ret=new \stdClass;
        $ret->delimeter=$foundDelimeter;
        $ret->enclosure=$foundEnclosure;
        return $ret;
    }
    
    protected function isBlankCSVLive($line){
        if (!is_array($line)) return true;
        if (empty($line)) return true;
        if (count($line)==1){
            if (reset($line)===null) return true;
        }        
        return false;
    }
}
?>