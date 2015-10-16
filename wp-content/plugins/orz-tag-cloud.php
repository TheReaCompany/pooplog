<?php
/*
Plugin Name: Orzeszek Tag Cloud
Plugin URI: http://www.orzeszek.org/blog/
Version: 1.1
Author: Orzeszek
Author URI: http://www.orzeszek.org/blog/
Description: Changes the font sizes used by the tag cloud widget.
*/

function orz_tag_cloud_filter($args = array()) {
   $args['smallest'] = 12;
   $args['largest'] = 12;
   $args['unit'] = 'px';
   return $args;
}

add_filter('widget_tag_cloud_args', 'orz_tag_cloud_filter', 90);
?>