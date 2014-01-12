<?php

class HtmlMailMan extends MailMan{
	public function mail($to, $subj, $body, $headers = false, $params = false){
		$mail = new PHPMailer();
		
		$mail->IsSMTP();                                      // set mailer to use SMTP
		$mail->Host = "mail.inversiondesigns.com";  // specify main and backup server
		$mail->SMTPAuth = true;     // turn on SMTP authentication
		$mail->Username = "bot@promotro.com";  // SMTP username
		$mail->Password = "r4tb4ndit"; // SMTP password
		
		$mail->AddAddress($to);

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
			$mail->From = "bot@inversiondesigns.com";
			$mail->FromName = "Inversion Bot";
			$mail->AddReplyTo("noreply@inversiondesigns.com", "No Reply");
		}
		
		//$mail->WordWrap = 50;                                 // set word wrap to 50 characters
		$mail->IsHTML(true);                                  // set email format to HTML
		
		$mail->Subject = $subj;
		$mail->Body    = $body;
		$mail->AltBody = strip_tags($body);
		
		
		$result = $mail->Send();
		
		$mail->close();
		
		return $result;
	}
	
	
	public function close(){
		
	}
}


?>