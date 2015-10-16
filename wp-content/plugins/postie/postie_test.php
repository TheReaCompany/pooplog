<?php
// try to connect to server with different protocols/ and userids
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR ."postie-functions.php");
include_once (dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR. "wp-config.php");
//require_once('admin.php');
require_once("postie-functions.php");
$config = GetConfig();
$title = __("Postie Diagnosis");
$parent_file = 'options-general.php?page=postie/postie.php';
get_currentuserinfo();
?>
<?php 
  if (!current_user_can('manage_options')) {
    echo "<h2> Sorry only admin can run this file</h2>";
    exit();
  }
?>

<?
    $images = array("Test.png",
                    "Test.jpg",
                    "Test.gif");
?>
<div class="wrap"> 
    <h1>Postie Configuration Test</h1>
    <?php
        if (TestForMarkdown()) {
            print("<h1>Warning!</h1>
                    <p>You currently have the Markdown plugin installed. It will cause problems if you send in HTML
                    email. Please turn it off if you intend to send email using HTML</p>");

        }
    ?>
    <?php 
        
        if (!TestPostieDirectory()) {
            print("<h1>Warning!</h1>
                    <p>Postie expects to be in its own directory named postie.</p>");
        }
        else  {
            print("<p>Postie is in ".dirname(__FILE__)."</p>");
        }
         ?>

    <br/>
    <h2>Iconv Library Test<h2>
    <p><i>Only required if you want to support ISO-2022-JP</i>
    <?php echo HasIconvInstalled();?>
    </p>
    <br/>
    <h2>Clock Tests<h2>
    <p>This shows what time it would be if you posted right now</p>
    <?php
     $content ="";
     $data = DeterminePostDate($content);

    ?>
    <p><?php print("GMT:". $data[1]);?></p>
    <p><?php print("Current:". $data[0]);?></p>
    <h2>Mail Tests</h2>
    <p>These try to confirm that the email configuration is correct.</p>

    <table>
    <tr>
        <th>Test</th>
        <th>Result</th>
    </tr>
    <tr>
        <th>Connect to Mail Host</th>
        <td>
           <?php
                switch( strtolower($config["INPUT_PROTOCOL"]) ) {
                    case 'imap':
                    case 'imap-ssl':
                    case 'pop3-ssl':
                        if (!HasIMAPSupport()) {
                            print("Sorry - you do not have IMAP php module installed - it is required for this mail setting.");
                        }
                        else {
                            require_once("postieIMAP.php");
                            $mail_server = &PostieIMAP::Factory($config["INPUT_PROTOCOL"]);
                            if (!$mail_server->connect($config["MAIL_SERVER"], $config["MAIL_SERVER_PORT"],$config["MAIL_USERID"],$config["MAIL_PASSWORD"])) {
                                print("Unable to connect. The server said - ".$mail_server->error());
                                print("<br/>Try putting in your full email address as a userid and try again.");
                            }
                            else {
                                print("Yes");
                            }
                        }
                        break;
                    case 'pop3':
                    default: 
                        require_once(ABSPATH.WPINC.DIRECTORY_SEPARATOR.'class-pop3.php');
                        $pop3 = &new POP3();
                        if (!$pop3->connect($config["MAIL_SERVER"], $config["MAIL_SERVER_PORT"])) {
                                print("Unable to connect. The server said - ".$pop3->ERROR);
                                print("<br/>Try putting in your full email address as a userid and try again.");
                        }
                        else {
                            print("Yes");
                        }
                        break;

                }
           ?>
            </td>
    </tr>


    </table>
</div>
