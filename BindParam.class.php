<?php 
	
	//HELPER CLASS USED FOR PREPARED STATEMENTS
	class BindParam {
	    private $values = array(), $types = ''; 
	    
	    public function add( $type, $value ){ 
	        $this->values[] = $value; 
	        $this->types .= $type; 
	    } 
	    
	    public function get(){ 
	        return array_merge(array($this->types), $this->values); 
	    }

	    public function getValue() {
	    	return $this->values;
	    }

	    public function getTypes() {
	    	return $this->types;
	    }
	}

?> 