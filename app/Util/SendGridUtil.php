<?php
namespace App\Util;

use Exception;

class SendGridUtil
{
 public static function send($domain, $fromName, $fromEmail, $toName, $toEmail, $subject, $body): array
 {
  $key = env('SENDGRID_API_KEY');

  $email = new \SendGrid\Mail\Mail();
  $email->setFrom('act-seats@castoware.com', "ACT Website: {$domain}");
  $email->setReplyTo($fromEmail, $fromName);
  $email->setSubject($subject);
  $email->addTo($toEmail, $toName);
  $email->addContent('text/html', $body);

  $sendgrid = new \SendGrid($key);

  try {
   $response = $sendgrid->send($email);

   $sendStatus = [
    'statusCode' => $response->statusCode(),
    'headers'    => $response->headers(),
    'body'       => $response->body(),
   ];

   return $sendStatus;
  } catch (Exception $e) {
   return ['error' => $e->getMessage()];
  }
 }
}
