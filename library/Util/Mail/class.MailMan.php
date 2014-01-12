<?php

class MailMan{
	public function mail($to, $subj, $body, $headers = false, $params = false){
		if($headers !== false && $params !== false){
			return @mail($to, $subj, $body, $headers, $params);
		}else if($headers !== false){
			return @mail($to, $subj, $body, $headers);
		}else{
			return @mail($to, $subj, $body);
		}
	}
	
	
	public function close(){
	}
}


?>