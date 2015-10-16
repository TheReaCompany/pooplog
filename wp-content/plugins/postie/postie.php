<?php
/*
Plugin Name: Postie
Plugin URI: http://blog.robfelty.com/plugins/postie
Description: Signifigantly upgrades the posting by mail features of Word Press (See <a href='options-general.php?page=postie/postie.php'>Settings and options</a>) to configure your e-mail settings. See the <a href='http://wordpress.org/extend/plugins/postie/other_notes'>Readme</a> for usage. Visit the <a href='http://forum.robfelty.com/forum/postie'>postie forum</a> for support.
Version: 1.3.4
Author: Robert Felty
Author URI: http://blog.robfelty.com/
*/

/*
$Id: postie.php 160759 2009-10-05 20:25:31Z robfelty $
* -= Requests Pending =-
* German Umlats don't work
* Problems under PHP5 
* Problem with some mail server
* Multiple emails should tie to a single account
* Each user should be able to have a default category
* WP Switcher not compatible
* Setup poll
    - web server
    - mail clients
    - plain/html
    - phone/computer
    - os of server
    - os of client
    - number of users posting
* Test for calling from the command line
* Support userid/domain  as a valid username
* WP-switcher not compatiable http://www.alexking.org/index.php?content=software/wordpress/content.php#wp_120
* Test out a remote cron system
* Add support for http://unknowngenius.com/wp-plugins/faq.html#one-click
*    www.cdavies.org/code/3gp-thumb.php.txt
*    www.cdavies.org/permalink/watchingbrowserembeddedgpvideosinlinux.php
* Support private posts
* Make it possible to post without a script at all
*/

//Older Version History is in the HISTORY file
//error_reporting(E_ALL);
//ini_set("display_errors", 1);

define("POSTIE_ROOT",dirname(__FILE__));
define("POSTIE_URL", WP_PLUGIN_URL . '/' . basename(dirname(__FILE__)));


function postie_loadjs_add_page() {
	$postiepage = add_options_page('Postie', 'Postie', 8, POSTIE_ROOT.'/postie.php', 'postie_loadjs_options_page');
	add_action( "admin_print_scripts-$postiepage", 'postie_loadjs_admin_head' );
}

function postie_loadjs_options_page() {
	require_once POSTIE_ROOT.'/config_form.php';
}

function postie_loadjs_admin_head() {
	$plugindir = get_settings('home').'/wp-content/plugins/'.dirname(plugin_basename(__FILE__));
	wp_enqueue_script('loadjs', $plugindir . '/js/simpleTabs.jquery.js');
	echo '<link type="text/css" rel="stylesheet" href="' .get_bloginfo('url') .'/wp-content/plugins/postie/css/style.css" />'."\n";
	echo '<link type="text/css" rel="stylesheet" href="' .get_bloginfo('url') .'/wp-content/plugins/postie/css/simpleTabs.css" />'."\n";
}


if (isset($_GET["postie_read_me"])) {
    include_once(ABSPATH . "wp-admin/admin.php");
    $title = __("Edit Plugins");
    $parent_file = 'plugins.php';
    include(ABSPATH . 'wp-admin/admin-header.php');
    postie_read_me();
    include(ABSPATH . 'wp-admin/admin-footer.php');
}
//Add Menu Configuration
if (is_admin()) {
  require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR ."postie-functions.php");
  //add_action("admin_menu","PostieMenu");
  add_action('admin_menu', 'postie_loadjs_add_page');
  if(function_exists('load_plugin_textdomain')){
    $plugin_dir = WP_PLUGIN_DIR . '/' . basename(dirname(__FILE__));
    function postie_load_domain() {
      load_plugin_textdomain( 'postie', $plugin_dir."/languages/",
      basename(dirname(__FILE__)). '/languages/');
    }
    add_action('init', 'postie_load_domain'); 
  }
  postie_warnings(); 
}
register_activation_hook(__FILE__, 'UpdateArrayConfig');
/* Version info
$Id: postie.php 160759 2009-10-05 20:25:31Z robfelty $
*/
function postie_warnings() {
  $config=GetConfig();
  if ($config['MAIL_SERVER']=='' && !isset($_POST['submit'])) {
    function postie_enter_info() {
      echo "
      <div id='postie-info-warning' class='updated fade'><p><strong>".
      __('Postie is almost ready.', 'postie')."</strong> "
      .sprintf(__('You must <a href="%1$s">enter your email settings</a> for it to work.','postie'), "options-general.php?page=postie/postie.php")."</p></div> ";
    }
    add_action('admin_notices', 'postie_enter_info');
  }
  if (!function_exists('imap_mime_header_decode') && $_GET['activate']==true) {
    function postie_imap_warning() {
      echo "<div id='postie-imap-warning' class='error'><p><strong>";
      echo __('Warning: the IMAP php extension is not installed.', 'postie');
      echo __('Postie may not function correctly without this extension (especially for non-English messages).', 'postie');
      echo "</strong> ";
      //echo __('Warning: the IMAP php extension is not installed. Postie may not function correctly without this extension (especially for non-English messages) .', 'postie')."</strong> ".
      echo sprintf(__('Please see the <a href="%1$s">FAQ </a> for more information.'), "options-general.php?page=postie/postie.php", 'postie')."</p></div> ";
    }
    add_action('admin_notices', 'postie_imap_warning');
  }
}
?>
