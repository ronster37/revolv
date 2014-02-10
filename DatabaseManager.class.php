<?php

	//HANDLES MYSQL QUERIES
	class DatabaseManager {
		private $con = NULL;

		public function __construct() {
        	$this->con = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME) OR die("{\"fail\" : \"Could not connect to MySQL: ". mysql_error() . "\"}");

			if(mysqli_connect_errno($this->con)) {
				echo json_encode(array("fail" => "Connect failed: " . mysqli_connect_error()));
	    		exit(0);
			}
    	}

    	public function query($query, $params) {
    		$retval = 0;

    		if($stmt = $this->con->prepare($query)) {
    			call_user_func_array( array($stmt, 'bind_param'), $this->refValues($params));
				if($stmt->execute()) {
					$retval = $stmt->affected_rows;
				}
				$stmt->close();
			} else {
				die(json_encode(array("fail" => "There was an error with the prepared statement: " . $this->con->error)));
			}

			return $retval;
    	}

    	public function &queryForResult($query, $params) {

    		$result = FALSE;

    		if($stmt = $this->con->prepare($query)) {
    			if(count($params) > 1) {
    				call_user_func_array( array($stmt, 'bind_param'), $this->refValues($params));
    			}
    			
				if($stmt->execute()) {
					$result = $stmt;
				} else {
					$stmt->close();
				}
			} else {
				die(json_encode(array("fail" => "There was an error with the prepared statement: " . $this->con->error)));
			}

			return $result;
    	}

		private function refValues($arr) { 
	        $refs = array();

	        foreach ($arr as $key => $value) {
	            $refs[$key] = &$arr[$key]; 
	        }

	        return $refs; 
		}

    	public function __destruct() {
    		if($this->con) {
    			$this->con->close();
    		}
    	}

    }

?>