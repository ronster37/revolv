<?php
	//CLASS TO STORE PREVIORS PAIRS
	class SantaPair {

		private $santa;
		private $giftee;
		private $years;

		kjpublic function __construct($santa, $giftee, $years) {
			$this->santa = $santa;
			$this->giftee = $giftee;
			$this->years = $years;
		}

		public function getSanta() {
			return $this->santa;
		}

		public function getGiftee() {
			return $this->giftee;
		}

		public function getYears() {
			return $this->years;
		}
	}

?>