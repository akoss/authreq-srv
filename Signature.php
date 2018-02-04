<?php 
class Signature { 

	public $message_id;
	public $signature;
	public $pem;
	public $timestamp;
	public $success;

    function __construct() {
    }

	public function setupWith($message_id, $signature, $pem){
		$this->message_id = $message_id; 
		$this->signature = $signature;
		$this->pem = $pem;
		$this->timestamp = time();
		$this->success = false; 
	}

	public function validate($bencodedOriginalMessage) {
		$result = openssl_verify($bencodedOriginalMessage, hex2bin($this->signature), $this->pem, "sha256");
		return ($result == 1);
	}
}

class DatabaseSignature extends Signature {

	private $db; 
	public $saved = false;

	public $signature_id;
	public $device_id;

    function __construct($db) {
        parent::__construct();
        $this->db = $db; 
    }

	public function save() {
		$message_id = $this->db->quote($this->message_id);
		$signature = $this->db->quote($this->signature);
		$device_id = $this->db->quote($this->device_id);
		$timestamp = $this->db->quote($this->timestamp);
		$success = $this->db->quote($this->success);

		$query = "INSERT INTO `signature` (`message_id`,`signature`,`device_id`,`timestamp`,`success`) VALUES (" . $message_id . "," . $signature . "," . $device_id . "," . $timestamp . "," . $success . ")";

		$result = $this->db->query($query) == 1;

		if($result) {
			$this->signature_id = $this->db->insert_id();
		}

		return $result;
	}

	public static function loadFromDatabase($db, $signature_id) {
		$sigid = $db->quote($signature_id);
		$records = $db->select("SELECT * FROM `signature` WHERE signature_id = " . $sigid . ";");

		if(count($records) == 1) {
			$record = $records[0];
			$self = new DatabaseSignature($db);
			$self->message_id = $record['message_id'];
			$self->signature = $record['signature'];
			$self->device_id = $record['device_id'];
			$self->timestamp = $record['timestamp'];
			$self->success = $record['success'];
		} else {
			return null;
		}

		$records = $db->select("SELECT * FROM `device` WHERE device_id = " . $device_id . ";");
		if(count($records) == 1) {
			$record = $records[0];
			$self->pem = $record['pem'];
		} else {
			return null;
		}

		return $self;
	}

	public function setupWith($message_id, $signature, $device_id){

		$this->device_id = $device_id;

		$records = $this->db->select("SELECT * FROM `device` WHERE device_id = " . $device_id . ";");
		if(count($records) == 1) {
			$record = $records[0];
			$pem = $record['pem'];
		} else {
			$pem = null;
		}

		parent::setupWith($message_id, $signature, $pem);
		$this->success = $this->validate();
		$this->saved = ($this->save() == 1);
	}

	public function validate() {
		$record = DatabaseSignatureRequest::loadFromDatabase($this->db, $this->message_id);
		$bencodedOriginalMessage = $record->getBencode();

		return parent::validate($bencodedOriginalMessage);
	}
}

?>