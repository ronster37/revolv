<?php
	//CLASS TO STORE USER DATA
	class UserInfo {

		private $name;
		private $relationship;
		private $id;

		public function __construct($id, $name, $relationship) {
			$this->id = $id;
			$this->name = $name;
			$this->relationship = $relationship;
		}

		public function getName() {
			return $this->name;
		}

		public function getRelationship() {
			return $this->relationship;
		}

		public function getId() {
			return $this->id;
		}
	}

?>