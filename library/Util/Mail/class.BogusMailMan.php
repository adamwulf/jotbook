<?php

class BogusMailMan extends MailMan{
	
	private $mails = array();
	
	public function mail($to, $subj, $body, $headers = false, $params = false){
		$this->mails[] = array("to" => $to,
				       "subject" => $subj,
				       "body" => $body,
				       "headers" => $headers,
				       "params" => $params);
		return true;
	}
	
	public function getLetters(){
		return $this->mails;
	}
	
	public function reset(){
		$this->mails = array();
	}
	
	public function close(){
	}
}


?>