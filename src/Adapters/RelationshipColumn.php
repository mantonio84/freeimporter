<?php
namespace App\ImporterProtes;

use Mantonio84\FreeImporter\Adapters\SimpleColumn;
use Mantonio84\FreeImporter\Adapters\ColumnAdapter;
use Illuminate\Database\Eloquent\Builder;

class RelationshipColumn extends SimpleColumn implements ColumnAdapter {
    
    private $query=null;
    private $searchFieldName=null;
    private $searchFieldOperator=null;
    private $valueFieldName=null;
    private $foundValues=array();
    
    public function __construct($source,$target,Builder &$query,$searchFieldName,string $valueFieldName="id", string $searchFieldOperator="="){                
        parent::__construct($source,$target);
        $this->query=&$query;
        $this->searchFieldName=$searchFieldName;
        $this->searchFieldOperator=$searchFieldOperator;
        $this->valueFieldName=$valueFieldName;
    }
    
    public function validate($input){        
        if (empty($input)) return false;        
        if ((!is_string($input)) and (!is_float($input)) and (!is_int($input))) return false;
        if (array_key_exists($input,$this->foundValues)) return true;
        if (is_string($this->searchFieldName)){
            $md=$this->query->where($this->searchFieldName,$this->searchFieldOperator,$input)->first();    
        }else if (is_array($this->searchFieldName)){
            $op=$this->searchFieldOperator;
            $names=$this->searchFieldName;                    
            $md=$this->query->where(function ($q) use ($op,$names,$input){
                foreach ($names as $n) $q->orWhere($n,$op,$input);
            })->first();
        }else{
            return false;
        }
        
        if ($md===null) return false;
        $a=$this->valueFieldName;
        $a=$md->{$a};
        $this->foundValues[$input]=$a;
        return true;
    }
    
     public function value($input){
        return $this->foundValues[$input];
     }

}
?>