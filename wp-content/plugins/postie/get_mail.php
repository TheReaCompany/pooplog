<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" 
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html  xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
  <meta http-equiv="content-type"
 content="application/xhtml+xml; charset=utf-8" />
  <title>Postie - fetching mail</title>
</head>
<body>
<?php

//Load up some usefull libraries
include_once (dirname(dirname(dirname(dirname(__FILE__)))) .  DIRECTORY_SEPARATOR."wp-config.php");
require_once (dirname(__FILE__). DIRECTORY_SEPARATOR . 'mimedecode.php');
require_once (dirname(__FILE__). DIRECTORY_SEPARATOR . 'postie-functions.php');
	

/* END OF USER VARIABLES */
//some variables
//error_reporting(2037);

//Retreive emails 
print("<pre>\n");
print("This is the postie plugin\n");
print("time:" . time() . "\n");
include('Revision');
$config = GetConfig();
//print_r($config);
$emails = FetchMail($config['MAIL_SERVER'], $config['MAIL_SERVER_PORT'],
    $config['MAIL_USERID'], $config['MAIL_PASSWORD'], $config['INPUT_PROTOCOL'],
    $config['TIME_OFFSET'], $config['TEST_EMAIL'],
    $config['DELETE_MAIL_AFTER_PROCESSING']);
if ($emails!==false) {
  //loop through messages
  foreach ($emails as $email) {
    if (function_exists('memory_get_usage'))
      echo "memory at start of e-mail processing:" . memory_get_usage() . "\n";
      //sanity check to see if there is any info in the message
      if ($email == NULL ) { 
        $message= __('Dang, message is empty!', 'postie'); 
        continue; 
      } else if ($email=='already read') {
        $message = "\n" . __("There does not seem to be any new mail.", 'postie') .
        "\n";
        continue;
      }
      $message='';
      $mimeDecodedEmail = DecodeMimeMail($email, true);
      $from = RemoveExtraCharactersInEmailAddress(trim($mimeDecodedEmail->headers["from"]));
      /*
      if ($from != "") {
          continue;
      }
      */

      //Check poster to see if a valid person
      $poster = ValidatePoster($mimeDecodedEmail, $config);
      if (!empty($poster)) {
          if ($config['TEST_EMAIL']) 
            DebugEmailOutput($email,$mimeDecodedEmail); 
          PostEmail($poster,$mimeDecodedEmail, $config);
      }
      else {
          print("<p>Ignoring email - not authorized.\n");
      }
    if (function_exists('memory_get_usage'))
      echo "memory at end of e-mail processing:" . memory_get_usage() . "\n";
  } // end looping over messages
} else {
  $message = "\n" . __("There does not seem to be any new mail.", 'postie');
}
print $message;
print("</pre>\n");
    
/* END PROGRAM */

// end of script
?>
</body>
</html>
