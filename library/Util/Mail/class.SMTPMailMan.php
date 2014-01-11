<?php

class SMTPMailMan extends MailMan{
	
	
	private $host;
	private $user;
	private $pass;
	
	private $html;
	
	public function __construct($host, $user, $pass){
		$this->host = $host;
		$this->user = $user;
		$this->pass = $pass;
		
		$this->html = true;

		$mail = new PHPMailer();
		$mail->IsSMTP();					// set mailer to use SMTP
		$mail->Host = $this->host;			// specify main and backup server
		$mail->SMTPAuth = true;				// turn on SMTP authentication
		$mail->Username = $this->user;		// SMTP username
		$mail->Password = $this->pass;		// SMTP password
		$this->smtp_mail = $mail;
	}
	
	public function setHtmlHuh($b){
		if(!is_bool($b)){
			throw new IllegalArgumentException("argument to " . __METHOD__ . " must be a boolean");
		}
		$this->html = $b;
	}
	
	
	public function mail($to, $subj, $body, $headers = false, $params = false, $attachments = false){
		$mail = $this->smtp_mail;
		$mail->SMTPDebug = 1;
		$mail->ClearAllRecipients();
		$mail->ClearReplyTos();
		$mail->ClearAttachments();
		$mail->ClearCustomHeaders();
		
		
		$mail->AddAddress($to);
		
		if($params && $params["cc"]){
			$mail->AddCC($params["cc"]);
		}

		if(is_string($headers)){
			$headers = explode("\n", $headers);
			foreach($headers as $header){
				$h = explode(":", $header, 2);
				if(stripos($h[0], "From") !== false){
					$from = $h[1];
					if(stripos($from, "<") !== false){
						$start = strpos($from, "<")+1;
						$mail->From = substr($from, $start,strpos($from, ">") - $start);
						$mail->FromName = substr($from, 0, strpos($from, "<"));
					}else{
						$mail->From = $h[1];
						$mail->FromName = $h[1];
					}
				}else{
					$mail->AddCustomHeader(trim($header));
				}
			}
		}else{
			$mail->From = $this->user;
			$mail->FromName = $this->user;
			$mail->AddReplyTo($this->user, "No Reply");
		}
		
		//$mail->WordWrap = 50;                                 // set word wrap to 50 characters
		$mail->IsHTML($this->html);                                  // set email format to HTML
		
		$mail->Subject = $subj;

		$mail->Body    = $body;
		if($this->html){
			$mail->AltBody = strip_tags($body);
		}else{
			$mail->AltBody = "";
		}
		
		if(is_array($attachments)){
			foreach($attachments as $a){
				if(!$mail->AddAttachment($a)){
					throw new Exception("cannot add attachment: " . $a);
				}
			}
		}
		
		$result = $mail->Send();
		
		if(!$result){
			throw new Exception("mail exception: " . $mail->ErrorInfo);
		}
		
		return $result;
	}
	
	public function getErrorInfo(){
		return $this->smtp_mail->ErrorInfo;
	}
	
	public function close(){
		$this->smtp_mail->SmtpClose();
	}
}


?>