<?php
/*
Plugin Name: Cronless Postie
Plugin URI: http://blog.robfelty.com/plugins/postie
Description: Checks e-mail periodically using wordpress's built-in scheduling mechanism
Author: Robert Felty
Version: 1.3.4
Author URI: http://blog.robfelty.com
*/ 

include_once (dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR. "wp-config.php");
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR ."postie-functions.php");
function check_postie() {
    $host = get_option('siteurl');
    preg_match("/http:\/\/(.[^\/]*)(.*)/",$host,$matches);
    $host = $matches[1];
    $url = "";
    if (isset($matches[2])) {
        $url .=  $matches[2];
    }
    $url .= "/wp-content/plugins/postie/get_mail.php";
    $port = 80;
	$fp=fsockopen($host,$port,$errno,$errstr);
    fputs($fp,"GET $url HTTP/1.0\r\n");
    fputs($fp,"User-Agent:  Cronless-Postie\r\n");
    fputs($fp,"Host: $host\r\n");
    fputs($fp,"\r\n");
    $page = '';
    while(!feof($fp)) {
        $page.=fgets($fp,128);
    }
#var_dump($page);
    fclose($fp);
}
if (isset($_GET["cronless_postie_read_me"])) {
    include_once(ABSPATH . "wp-admin/admin.php");
    $title = __("Edit Plugins");
    $parent_file = 'plugins.php';
    include(ABSPATH . 'wp-admin/admin-header.php');
    cronless_read_me();
    include(ABSPATH . 'wp-admin/admin-footer.php');
    exit();
}


function postie_cron() {
  global $wpdb;
  $config=GetConfig();
  if (!$config['CRONLESS'] || $config['CRONLESS']=='') {
    $config['CRONLESS']='hourly';
    $theQuery=$wpdb->prepare("INSERT INTO ". POSTIE_TABLE . "
        (label,value) VALUES
        ('CRONLESS','". $config['CRONLESS'] ."');");
    $q = $wpdb->query($theQuery);
    //WriteConfig($config);
  }
  wp_schedule_event(time(),$config['CRONLESS'],'check_postie_hook');
}
function postie_decron() {
  global $wpdb;
  wp_clear_scheduled_hook('check_postie_hook');
  $config=GetConfig();
  $config['CRONLESS']='';
  $theQuery=$wpdb->prepare("INSERT INTO ". POSTIE_TABLE . "
      (label,value) VALUES
      ('CRONLESS','". $config['CRONLESS'] ."');");
  $q = $wpdb->query($theQuery);
  //WriteConfig($config);
}

/* here we add some more options for how often to check for e-mail */
function more_reccurences() {
  return array(
  'weekly' => array('interval' => 604800, 'display' => 'Once Weekly'),
  'twiceperhour' => array('interval' => 1800, 'display' => 'Twice per hour '),
  'tenminutes' =>array('interval' => 600, 'display' => 'Every 10 minutes')
  );
}
add_filter('cron_schedules', 'more_reccurences');
register_activation_hook(__FILE__,'postie_cron');
register_deactivation_hook(__FILE__,'postie_decron');
add_action('check_postie_hook', 'check_postie');
//add_action('init','postie_cron');
?>
