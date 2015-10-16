<?php
//if ($_POST['fetchmails']) {
  include_once (dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR."wp-config.php");
  require_once (dirname(__FILE__). DIRECTORY_SEPARATOR . '../postie/mimedecode.php');
  require_once (dirname(__FILE__). DIRECTORY_SEPARATOR . '../postie/postie-functions.php');
  fetch_mails();
  exit;
//}
function init() {
/* Sets up database table if it doesn't already exist */
  global $wpdb, $aandcpostie_version;
  $table_name=$wpdb->prefix . 'postie_addresses';
  if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
    $sql = "CREATE TABLE " . $table_name . " (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        server text NOT NULL,
        port smallint(4) DEFAULT '110' NOT NULL,
        email text NOT NULL,
        password VARCHAR(64) NOT NULL,
        protocol text NOT NULL,
        offset text NOT NULL,
        UNIQUE KEY id (id)
      );";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    $addresses = array(
                 0=> array(
                     'server'=>'something.com',
                     'port' =>'995',
                     'email' => 'username',
                     'password' => 'mypassword',
                     'protocol' => 'pop3-ssl',
                     'offset' => '-5'
                     ),
                 1=> array(
                     'server'=>'another.com',
                     'port' =>'993',
                     'email' => 'anotheruser',
                     'password' => 'anotherpassword',
                     'protocol' => 'imap-ssl',
                     'offset' => '-5'
                     ) 
                 );
  insert_new_addresses($table_name, $addresses);
}
function insert_new_addresses($table_name, $addresses) {
  /* insert addresses into table */
  global $wpdb;
  $fetch_query = 'SELECT id FROM ' .  $table_name;
  $existingAddresses=$wpdb->get_col($fetch_query);
  foreach ($addresses as $address) {
    extract($address);
    if (!in_array("$id", $existingAddresses)) {
      $query = "INSERT INTO " . $table_name .
          " (server, port, email, password, protocol, offset) " .
          "VALUES ('$server', $port, '$email', PASSWORD('$password'), '$protocol',
          '$offset')";
    } else {
      $query = "UPDATE $table_name set server='$server', port='$port',
          email='$email', password='$password', 
          protocol='$protocol', offset='$offset' WHERE id='$id'";
    }
    $results = $wpdb->query($wpdb->prepare($query));
  }
}

function fetch_mails() {
  global $wpdb;
  /* checks mail from various mailboxes and posts those e-mails */
  //Load up some usefull libraries
    
  //Retreive emails 
  $fetch_query = 'SELECT * FROM ' .  $wpdb->prefix . 'aac_postie';
  $mailboxes=$wpdb->get_results($fetch_query);

  foreach ($mailboxes as $mailbox) {
    $emails = FetchMail($mailbox->server, $mailbox->port,
        $mailbox->email, $mailbox->password, $mailbox->protocol);
    //loop through messages
    foreach ($emails as $email) {
      //sanity check to see if there is any info in the message
      if ($email == NULL ) {
        print 'Dang, message is empty!'; 
        continue; 
      }
      
      $mimeDecodedEmail = DecodeMimeMail($email);
      $from = RemoveExtraCharactersInEmailAddress(
          trim($mimeDecodedEmail->headers["from"]));

      //Check poster to see if a valid person
      $poster = ValidatePoster($mimeDecodedEmail);
      if (!empty($poster)) {
        DebugEmailOutput($email,$mimeDecodedEmail); 
        PostEmail($poster,$mimeDecodedEmail);
      }
      else {
        print("<p>Ignoring email - not authorized.\n");
      }
    } // end looping over messages
  }
}
?>
