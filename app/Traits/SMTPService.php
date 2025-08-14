<?php

namespace App\Traits;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Throwable;
use Illuminate\Support\Facades\Log;

trait SMTPService 
{
	protected $ADMIN_EMAILS = 'oscar.lopez@astrogrowshop.cl';

    protected function setMailer(){
		$mail = new PHPMailer(true);
		$mail->isSMTP();                                            
		$mail->Host       = 'tls://smtp.gmail.com:587';                     
		$mail->SMTPAuth   = true;                                  
		$mail->Username   = 'fulfillment.emails@astrogrowshop.cl';                    
		$mail->Password   = 'jswklfryhpkmuelg';                               
		$mail->Port       = 587;                                   
		$mail->setFrom('fulfillment.emails@astrogrowshop.cl', 'Astro fulfillment (laravel)');
		
		$mail->isHTML(true);
		$mail->addReplyTo('fulfillment.emails@astrogrowshop.cl', 'Astro fulfillment');
		$mail->CharSet = 'UTF-8';
		// $mail->SMTPDebug = true;                   //Enable verbose debug output

		return $mail;
	}

	protected function sendEmail($body, $asunto){
		try{

			$mail = $this->setMailer();
	
			$mail->Subject = $asunto;
			$mail->Body = $body;
	
			$addresses = explode(',', $this->ADMIN_EMAILS);
			foreach ($addresses as $address) {
				$mail->AddAddress($address);
			}
	
			$mail->send(); 

		}catch(Throwable $e){
			Log::error('Error del SMTP: ' . $e->getMessage());
		}
	}
}