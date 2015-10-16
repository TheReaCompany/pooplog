<?php
/**
 * Shadowbox is an online media viewing application that supports all of the web's
 * most popular media publishing formats. Shadowbox is written entirely in
 * JavaScript and CSS and is highly customizable. Using Shadowbox, website authors
 * can display a wide assortment of media in all major browsers without navigating
 * users away from the linking page.
 *
 * @author Matt Martz <matt@sivel.net>
 * @version 3.0.3
 * @package shadowbox-js
 */

/*
Plugin Name:  Shadowbox JS
Plugin URI:   http://sivel.net/wordpress/shadowbox-js/
Description:  A javascript media viewer similar to Lightbox and Thickbox. Supports all types of media, not just images. 
Version:	  3.0.3
Author:		  Matt Martz
Author URI:   http://sivel.net/
Text Domain:  shadowbox-js
Domain Path:  shadowbox-js/localization
License:	  LGPL

	Shadowbox JS (c) 2008-2010 Matt Martz (http://sivel.net/)
	Shadowbox JS is released under the GNU General Public License (LGPL)
	http://www.gnu.org/licenses/lgpl-2.1.txt

	Shadowbox (c) 2007-2010 Michael J. I. Jackson (http://www.shadowbox-js.com/)
	Shadowbox is licensed under the Shadowbox.js License version 1.0
	http://www.shadowbox-js.com/LICENSE

	JW FLV Media Player (c) 2008 LongTail As Solutions (http://www.longtailvideo.com/)
	JW FLV Media Player is licensed under the Creative Commons Attribution-Noncommercial-Share Alike 3.0 Unported License
	http://creativecommons.org/licenses/by-nc-sa/3.0/
*/

/**
 * Shadowbox class for common actions between admin and frontend
 *
 * This class contains all of the shared functions required for Shadowbox to work 
 * on the frontend and admin of WordPress
 *
 * @since 3.0.0.1
 * @package shadowbox-js
 * @subpackage frontend
 */
class Shadowbox {

	/**
	 * Plugin Version
	 *
	 * Holds the current plugin version.
	 *
	 * @since 3.0.0.4
	 * @var int
	 */
	var $version = '3.0.3';

	/**
	 * Plugin Options Version
	 *
	 * Holds the current options version.  Does not hold the current plugin version.
	 *
	 * @since 3.0.0.0
	 * @var int
	 */
	var $dbversion = '3.0.3';

	/**
	 * Shadowbox Version
	 *
	 * Holds the current shadowbox.js version.
	 *
	 * @since 3.0.0.4
	 * @var int
	 */
	var $sbversion = '3.0.3';

	/**
	 * Options array containing all options for this plugin
	 *
	 * @since 3.0.0.1
	 * @var string
	 */
	var $options;

	/**
	 * Setup shared functionality for ADmin and Front End
	 *
	 * @return none
	 * @since 3.0.0.1
	 */
	function __construct () {
		$this->options = get_option ( 'shadowbox' );
	} 

	/**
	 * Get specific option from the options table
	 *
	 * @param string $option Name of option to be used as array key for retrieving the specific value
	 * @return mixed 
	 * @since 2.0.3
	 */
	function get_option ( $option ) {
		if ( isset ( $this->options[$option] ) )
			return $this->options[$option];
		else
			return false;
	}

	/**
	 * Get the full URL to the plugin
	 *
	 * @return string
	 * @since 2.0.3
	 */
	function plugin_url () {
		$plugin_url = plugins_url ( plugin_basename ( dirname ( __FILE__ ) ) );
		return $plugin_url;
	}

	/**
	 * Return an md5 based off of the current options of the plugin and the
	 * current version of shadowbox.js.
	 *
	 * This is used for creating unique cache files and for cache busting.
	 *
	 * @since 3.0.3
	 * @return string
	 */
	function md5 () {
		return md5 ( serialize ( $this->options ) . $this->sbversion );
	}

	/**
	 * Deactivate this plugin and die
	 *
	 * Used to deactivate the plugin when files critical to it's operation can not be loaded
	 *
	 * @since 3.0.0.4
	 * @return none
	 */
	function deactivate_and_die ( $file ) {
		load_plugin_textdomain ( 'shadowbox-js' , false , 'shadowbox-js/localization' );
		$message = sprintf ( __( "Shadowbox JS has been automatically deactivated because the file <strong>%s</strong> is missing. Please reinstall the plugin and reactivate." ) , $file );
		if ( ! function_exists ( 'deactivate_plugins' ) )
			include ( ABSPATH . 'wp-admin/includes/plugin.php' );
		deactivate_plugins ( __FILE__ );
		wp_die ( $message );
	}

}

/**
 * Instantiate the ShadowboxFrontend or ShadowboxAdmin Class
 *
 * Deactivate and die if files can not be included
 */
if ( is_admin () ) {
	if ( @include ( dirname ( __FILE__ ) . '/inc/admin.php' ) ) {
		$ShadowboxAdmin = new ShadowboxAdmin ();
	} else {
		Shadowbox::deactivate_and_die ( dirname ( __FILE__ ) . '/inc/admin.php' );
	}
} else {
	if ( @include ( dirname ( __FILE__ ) . '/inc/frontend.php' ) ) {
		$ShadowboxFrontend = new ShadowboxFrontend ();
	} else {
		Shadowbox::deactivate_and_die ( dirname ( __FILE__ ) . '/inc/frontend.php' );
	}
}

?>
