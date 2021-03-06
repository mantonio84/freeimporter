<?php
namespace Mantonio84\FreeImporter\Adapters;

interface ColumnAdapter {
    
    public function held($sourceColumnHeader);
    public function target();
    public function validate($input);
    public function value($input);        
    public function label();
    public function name();
    public function __toString();
}

?>