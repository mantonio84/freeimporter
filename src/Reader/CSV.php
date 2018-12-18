<?php

namespace Mantonio84\FreeImporter\Reader;

class CSV extends Reader implements \Countable, \ArrayAccess {    
    
    public function __construct(string $filePath, bool $multiLineGuess=false, string $escape = "\\", array $otherDelimeters=array(), bool $removeFakeEnclusures=false, $fromLine=null, $toLine=null, int $maxLineWidth=8192){  
        $mx=-1;
        $toresize=false;
        $format=null;
        $lineNumber=-1;
        if ((is_int($fromLine)) and (is_int($toLine))){
            if ($fromLine>$toLine){
                //Sei un grandissimo fesso!
                $fromLine=null;
                $toLine=null;
            }
        }
        ini_set("auto_detect_line_endings", true);
        $f=fopen($filePath,"r");
        if ($f===false) throw new Exception("Unable to open file '".$filePath."'!");
        while (!feof($f)) {
            $rawLine=trim(fgets($f,$maxLineWidth));            
            if (strlen($rawLine)<3) continue;
            $lineNumber++;
            if (is_int($fromLine)){
                if ($lineNumber<$fromLine) continue;
            }
            if (is_int($toLine)){
                if ($lineNumber>$toLine) break;
            }
            if (($format===null) or ($multiLineGuess===true)) $format=$this->guessLineFormat($rawLine,$otherDelimeters);
            if ($format===null) continue;
            $line=str_getcsv($rawLine,$format->delimeter,$format->enclosure,$escape);                                    
            if ($this->isBlankCSVLive($line)) continue;                                   
            $c=count($line);             
            $mx=max($mx,$c);            
            if (($mx>-1) and ($c!=$mx)) $toresize=true;
            if ($removeFakeEnclusures===true){
                foreach ($line as &$f){
                    $e=$this->guessFieldEnclosure($f);
                    if (!is_null($e)) $f=substr($f,1,-1);
                }    
            }
            $this->container[]=$line;
                        
        }
        fclose($f);
        if ($toresize){
            foreach ($this->container as &$line){
                $d=$mx-count($line);
                if ($d>0) $line=array_merge($line,array_fill(0,$d,null));                
            }
        }
        $this->cleanUpContainer();
        $this->calculateFileHash($filePath);               
    }
    
    private function guessDelimiter(string $line,array $otherDelimeters=array()){
        $line=trim($line);
        if (strlen($line)<3) return null;
        $delimeters=[";",",","/","|"];
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
        return $foundDelimeter;
    }
    
    private function guessRowEnclosure(array $fields, string $default="\""){
        $foundEnclosure=null; 
        foreach ($fields as $f){
            $fe=$this->guessFieldEnclosure($f);
            if ($foundEnclosure===null){
                $foundEnclosure=$fe;
            }else{
                if ($fe!=$foundEnclosure){
                    $foundEnclosure=null;
                    break;
                }
            }
        } 
        if ($foundEnclosure===null){
            return $default;
        }else{
            return $foundEnclosure;
        }
    }
    
    private function guessFieldEnclosure(string $field){
        if (strlen($field)<3) return null;
        $ret=null;
        $firstChar=$field[0];
        $lastChar=$field[-1];
        if ($firstChar==$lastChar){
            if ((!ctype_alnum($firstChar)) and ($firstChar!=" ")){
                $ret=$firstChar;
            }
        }
        return $ret;
    }
    
    protected function guessLineFormat(string $line,array $otherDelimeters=array()){        
        $line=trim($line);
        if (strlen($line)<3) return null;
        $foundDelimeter=$this->guessDelimiter($line,$otherDelimeters);
        if ($foundDelimeter===null) return null;           
        $fields=str_getcsv($line,$foundDelimeter); 
        $foundEnclosure=$this->guessRowEnclosure($fields);                       
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