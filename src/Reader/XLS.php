<?php

namespace Mantonio84\FreeImporter\Reader;
use \PhpOffice\PhpSpreadsheet\IOFactory;
use \PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class Excel extends Reader implements \Countable, \ArrayAccess {
    
    public function __construct($filePath,$sheetName=null,$interval="auto",$rowsFilter=null){        
        $spreadsheet = IOFactory::load($filePath);    
        if (!is_null($sheetName)){                      
            $sheet=$spreadsheet->getSheetByName($sheetName);
        }else{
            $sheet=$spreadsheet->getActiveSheet();
        }
        if ($interval==="auto"){            
            $highestRow = $sheet->getHighestRow();            
            $highestColumn = Coordinate::columnIndexFromString($sheet->getHighestColumn());            
            $tow=false;
            $lc=null;
            $hc=null;
            for ($row = 1; $row <= $highestRow; $row++) {
                $currentRowData=array();
                $ad="A".$row.":".Coordinate::stringFromColumnIndex($highestColumn).$row;
                $currentRowData=$sheet->rangeToArray(
                    $ad,     // The worksheet range that we want to retrieve
                    NULL,    // Value that should be returned for empty cells
                    TRUE,    // Should formulas be calculated (the equivalent of getCalculatedValue() for each cell)
                    TRUE,    // Should values be formatted (the equivalent of getFormattedValue() for each cell)
                    TRUE     // Should the array be indexed by cell row and cell column
                );                
                $ep=$this->getRowBounds($currentRowData);
                if ((!$tow) and ($ep!==false)) $tow=true;                
                if ($tow){
                    if ($ep===false) break;
                    $this->container[]=$currentRowData;
                    if (($ep[0]<$lc) or ($lc===null)) $lc=$ep[0];
                    if (($ep[1]>$hc) or ($hc===null)) $hc=$ep[1];
                }
            }
            if ((!is_null($lc)) and (!is_null($lc))){
                if (($lc!=1) or ($hc!=$highestColumn)){                    
                    $l=$hc-$lc+1;
                    $this->container=array_map(function ($itm) use ($lc,$l){
                        return array_slice($itm,$lc-1,$l);
                    },$this->container);
                }
            }
        }else if (($this->isValidAddress($interval)) and (strpos($interval,"!")!==false) and (strpos($interval,":")!==false)){                                             
            $this->container=$sheet->rangeToArray(
                $interval,     // The worksheet range that we want to retrieve
                NULL,        // Value that should be returned for empty cells
                TRUE,        // Should formulas be calculated (the equivalent of getCalculatedValue() for each cell)
                TRUE,        // Should values be formatted (the equivalent of getFormattedValue() for each cell)
                TRUE         // Should the array be indexed by cell row and cell column
            );        
        }else{
            $interval="A1:".$sheet->getHighestColumn().$sheet->getHighestRow();
            $this->container=$sheet->rangeToArray(
                $interval,     // The worksheet range that we want to retrieve
                NULL,        // Value that should be returned for empty cells
                TRUE,        // Should formulas be calculated (the equivalent of getCalculatedValue() for each cell)
                TRUE,        // Should values be formatted (the equivalent of getFormattedValue() for each cell)
                TRUE         // Should the array be indexed by cell row and cell column
            ); 
        }    
        if (is_callable($rowsFilter)) $this->container=array_values(array_filter(array_map($rowsFilter,$this->container)));        
    }
    
    private function getRowBounds(array $row){
        if (empty($row)) return false;
        $row=array_filter($row);
        if (empty($row)) return false;
        $row=array_keys($row);
        $lc=intval(reset($row))+1;
        $hc=intval(end($row))+1;
        return [$lc,$hc];
    }
    
    private function isValidAddress($excelAddress)
    {
        if (!is_string($excelAddress)) return false;
        $intervals = explode("/", $excelAddress);
        $intervalPattern = '/^(.+!)?([a-z]+[1-9][0-9]*)(:[a-z]+[1-9][0-9]*)?$/i';
        foreach ($intervals as $interval) {
            $results = preg_match_all($intervalPattern, $interval);
            if (!$results) {
                return false;
            }
        }
        return true;
    }
}
?>