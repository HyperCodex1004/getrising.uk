<?php
  /**
  * Requires the "PHP Email Form" library
  * The library should be uploaded to: vendor/php-email-form/php-email-form.php
  */
  $receiving_email_address = 'info@getrising.uk';

  if( file_exists($php_email_form = '../assets/vendor/php-email-form/php-email-form.php' )) {
    include( $php_email_form );
  } else {
    die( 'Unable to load the "PHP Email Form" Library!');
  }

  // hCaptcha secret key
  $secretKey = "ES_17a2dbf32121457eafd6950bf615dc41";

  // Get hCaptcha response from the form
  $hcaptchaResponse = $_POST['h-captcha-response'];

  // Honeypot
  if (!empty($_POST['website'])) {
      // Log the attempt
    $log_entry = sprintf(
        "[%s] Spam detected from IP: %s | Value: %s\n",
        date('Y-m-d H:i:s'),
        $_SERVER['REMOTE_ADDR'],
        $_POST['website']
    );

    file_put_contents(__DIR__ . '/honeylog.txt', $log_entry, FILE_APPEND);

    http_response_code(403);
    exit('Spam detected. Form rejected.');
}

  // Verify the hCaptcha response
  $url = 'https://hcaptcha.com/siteverify';
  $data = [
      'secret' => $secretKey,
      'response' => $hcaptchaResponse,
      'remoteip' => $_SERVER['REMOTE_ADDR']
  ];

  // Make a POST request to hCaptcha
  $options = [
      'http' => [
          'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
          'method'  => 'POST',
          'content' => http_build_query($data),
      ],
  ];
  $context  = stream_context_create($options);
  $response = file_get_contents($url, false, $context);
  $responseKeys = json_decode($response, true);

  // Check if hCaptcha verification was successful
  if (!$responseKeys['success']) {
    die('CAPTCHA verification failed. Please try again.');
  }

  // Proceed with email processing
  $contact = new PHP_Email_Form;
  $contact->ajax = true;
  
  $contact->to = $receiving_email_address;
  $contact->from_name = $_POST['name'];
  $contact->from_email = $_POST['email'];
  $contact->subject = $_POST['subject'];

  // Uncomment below code if you want to use SMTP to send emails. You need to enter your correct SMTP credentials
  /*
  $contact->smtp = array(
    'host' => 'example.com',
    'username' => 'example',
    'password' => 'pass',
    'port' => '587'
  );
  */

  $contact->add_message( $_POST['name'], 'From');
  $contact->add_message( $_POST['email'], 'Email');
  $contact->add_message( $_POST['message'], 'Message', 10);

  echo $contact->send();
?>
