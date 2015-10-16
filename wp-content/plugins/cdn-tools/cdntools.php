<?php
/*
Plugin Name: CDN Tools
Plugin URI: http://langui.sh/cdn-tools
Description: CDN Tools is a plugin designed to help you drastically speed up your blog's load time by loading content onto a distribution network.  You can use a commercial CDN or just load some of your larger JS libraries for free from Google's servers!  At this time Cloud Files is the only supported CDN.
Author: Paul Kehrer
Version: 0.99
Author URI: http://langui.sh/
*/ 

/*  Copyright 2009  Paul Kehrer

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
ini_set('memory_limit', '64M'); //up the memory limit for large CDN uploads...probably need to make this a configurable pref
set_time_limit(90); //90 seconds max...

define('CDNTOOLS_VERSION','0.99');

$dir_array = explode('/',dirname(__FILE__));
define('CDNTOOLS_DIR_NAME',$dir_array[count($dir_array)-1]);

define('CDNTOOLS_DEBUG',false);

if(CDNTOOLS_DEBUG == true) {
	ini_set('display_errors',1);
}


$cdntools = new cdntools();

class cdntools	{
	private $wp_scripts = null;
	private $gscripts = null;
	private $cdn_options = null;
	private $cdntools_googleajax = null;
	private $cdntools_primarycdn = null;
	private $cdntools_loadedfiles = null;
	private $cdntools_authname = null;
	private $cdntools_authkey = null;
	private $cdntools_adminscripts = null;
	private $cdntools_sideload_uploads = null;
	private $cdntools_logretention = null;
	private $cdntools_servicenet = null;
	private $cdntools_baseuris = array();
	private $uploaded_file_path = null;
	private $deleted_file_path_arr = array();
	private $wp_upload_url_path = null;
	private $wp_upload_basedir = null;
	private $uploads_use_yearmonth_folders = null;
	private $cdntools_advanced_options = null;
	private $cdntools_prefix = null;
	
	function __construct() {
		//set up our hooks
		$this->bind_actions();
		$this->bind_filters();
		
		/*
		set up the scripts we can fetch from google's cdn.  while scriptaculous and 
		jqueryui are available, they aren't modular so let's ignore them.
		*/
		$this->gscripts = array(
			'dojo' => 'dojo.xd',
			'jquery' => 'jquery.min',
			'mootools' => 'mootools-yui-compressed',
			'prototype' => 'prototype'
		);

		//set cdn prefix, useful for multiple blogs pointing at one cdn account
		$cdntools_prefix = get_option('cdntools_prefix');
		if(strlen($cdntools_prefix) == 0) {
			$cdntools_prefix='cdntools_';
		}
		define('CDNTOOLS_PREFIX',$cdntools_prefix);
		$this->cdntools_prefix = $cdntools_prefix;
		
		//obtain our options
		$this->cdntools_googleajax = get_option('cdntools_googleajax');
		$this->cdntools_primarycdn = get_option('cdntools_primarycdn');
		$this->cdntools_loadedfiles = unserialize(get_option('cdntools_loadedfiles')); //if nothing is loaded this will trigger an E_NOTICE
		$baseuris = unserialize(get_option('cdntools_baseuris'));
		if( is_array($baseuris) ) {
			$this->cdntools_baseuris = $baseuris;
		}
		$this->cdntools_authname = get_option('cdntools_authname');
		$this->cdntools_authkey = get_option('cdntools_authkey');
		$this->cdntools_adminscripts = get_option('cdntools_adminscripts');
		$this->cdntools_sideload_uploads = get_option('cdntools_sideload_uploads');
		$this->cdntools_logretention = get_option('cdntools_logretention');
		$this->cdntools_servicenet = get_option('cdntools_servicenet');
		$this->cdntools_advanced_options = get_option('cdntools_advanced_options');
		$wp_upload_dir = wp_upload_dir();
		$this->wp_upload_url_path = $wp_upload_dir['baseurl'];
		$this->wp_upload_basedir = $wp_upload_dir['basedir'];
		$this->uploads_use_yearmonth_folders = get_option('uploads_use_yearmonth_folders');
		if($this->cdntools_primarycdn != false) {
			require(WP_PLUGIN_DIR.'/'.CDNTOOLS_DIR_NAME."/cdn_classes/{$this->cdntools_primarycdn}/loader.php");
		}
	}
	
	function bind_actions() {
		add_action('wp_ajax_cdn_attachment_upload_ajax', array($this, 'cdn_attachment_upload_ajax'));
		add_action('admin_menu', array($this,'add_admin_pages'));  //create admin page
		add_action('wp_default_scripts', array($this, 'rewrite_wp_default_scripts'),900);
		add_action('wp_head', array($this,'wp_head_action'));  //add a tag to denote cdntools use
		add_action('post-flash-upload-ui', array($this,'cdn_post_upload_ui'));
		add_action('post-html-upload-ui', array($this,'cdn_post_upload_ui'));
		add_action('add_attachment', array($this,'cdn_attachment_upload'));
		add_action('delete_attachment', array($this,'cdn_attachment_delete'));
		add_action( 'admin_init', array($this,'cdntools_admin_init'));
	}

	function bind_filters() {
		add_filter('init',array($this,'disable_script_concatenation'));
		add_filter( 'print_scripts_array',array($this,"jquery_noconflict"),100);
		add_filter('wp_handle_upload', array($this,'handle_upload_filter')); //grab data about uploads
		add_filter('wp_delete_file', array($this,'delete_file_filter')); //we need info about this damn file before it's deleted and the delete_attachment hook fires too late in WP 2.7
	//	add_filter('attachment_fields_to_edit', array($this,'attachment_fields_to_edit_filter'));
		add_filter('the_content', array($this,'cdn_media_url_rewrite'), 1000);  //this needs to run after any other filter that may alter URLs
		add_filter('wp_generate_attachment_metadata', array($this,'cdn_upload_resized_images'));
		add_filter('update_attached_file', array($this,'update_attached_file'), 10, 2); //upload edited images
		add_filter('wp_update_attachment_metadata', array($this,'cdn_upload_resized_images')); //hook same function to upload the edited image resizes
	}
	
	//this will capture image edited files and probably other stuff
	function update_attached_file($filepath,$attachment_id) {
		$full_filepath = $this->file_path($filepath); //make the file path absolute if required
		$this->cdn_attachment_upload(0,$full_filepath); //pass a zero post_id since we don't know it and it doesn't matter.  post_id can't be removed from the function though since the hook passes it
		return $filepath;
	}
	
	//wp 2.8 concatenates scripts in the admin panel and this messes up the google ajax rewriting.  hook init and disable it for now.  revisit at some point
	function disable_script_concatenation() {
		global $concatenate_scripts;
		$concatenate_scripts = false;
	}
	
	function cdn_upload_resized_images($metadata) {
		//uses wp_generate_attachment_metadata filter hook
		//sizes is a multi-dimensional array with elements of this structure
		/*[thumbnail] => Array
			(
				[file] => picture-1-150x150.png
				[width] => 150
				[height] => 150
			)*/
		if(is_array($metadata) && isset($metadata['sizes'])) {
			$file_array = explode('/',$metadata['file']);
			foreach($metadata['sizes'] as $data) {
				array_pop($file_array);
				array_push($file_array,$data['file']);
				$filepath = $this->file_path(implode('/',$file_array));
				$this->cdn_attachment_upload(0,$filepath); //pass a zero post_id since we don't know it and it doesn't matter.  post_id can't be removed from the function though since the hook passes it
			}
		}
		return $metadata;
	}


	function handle_upload_filter($data) {
		//grab the data we need via filter.  hacky hack hack and against the idea of filters.
		$this->uploaded_file_path = $data['file'];
		return $data;
	}
	
	function delete_file_filter($file) {
		//grab the data we need via filter.  hacky hack hack and against the idea of filters. only required in wp 2.7
		if(strpos($file,$this->wp_upload_basedir) === false) {
			//some data comes through with relative path only.  we need absolute
			$absolute_file = $this->wp_upload_basedir.'/'.$file;
			$this->deleted_file_path_arr[] = $absolute_file;
		} else {
			$this->deleted_file_path_arr[] = $file;
		}
		//return the var unaltered
		return $file;
	}
	
	function cdn_media_url_rewrite($data) {
		if($this->cdntools_sideload_uploads == 1 && !empty($this->cdntools_baseuris) && ($this->cdntools_primarycdn) ) {
			//only rewrite if the pref is set and the baseuris array isn't empty and they have a CDN selected
			$file_upload_url = $this->wp_upload_url_path.'/';
			$file_upload_url = str_replace('/','\/',str_replace('://','://(www.)?',$file_upload_url));
			preg_match_all('/'.$file_upload_url.'([^"\']+)/',$data,$matches);
			$patterns = array();
			$replacements = array();
			foreach($matches[2] as $value) {
				if($this->cdntools_primarycdn == 'cloudfiles') { //this needs to be abstracted
					if($this->uploads_use_yearmonth_folders) {
					    $path_array = explode('/',$value);
					    $container_name = CDNTOOLS_PREFIX.'wp_uploads_'.$path_array[0].'_'.$path_array[1];
					    $object_name = array_pop($path_array);
					} else {
					    $container_name = CDNTOOLS_PREFIX.'wp_uploads';
						$object_name = $value;
					}
				} elseif ($this->cdntools_primarycdn == 'amazon') {
				    $container_name = $thebucket; //TODO: how to store the s3 bucket name?!
					$object_name = $value;
				}
				$replacements[] = $this->cdntools_baseuris[$container_name].'/'.$object_name;
			}
			foreach($matches[0] as $urls) {
			    $patterns[] = '/'.str_replace('/','\/',$urls).'/';
			}
			$data = preg_replace($patterns,$replacements,$data);
		}
		return $data;
	}
	
	//incomplete function.  rewrite
	function attachment_fields_to_edit_filter($data) {
		/*if(isset($data['url']['html'])) {
			$data['url']['html'] = "<input type='text' class='urlfield' name='attachments[boom][cdnurl]' value='" . attribute_escape($this->cdn_attachment_url('boom')). "' />".$data['url']['html'];
		}*/
		return $data;
	}
	
	function cdn_attachment_url($postid) {
		return "yeah";
	}
	

	//file_to_upload must be a string with absolute path.  in contrast to delete, you must call this multiple times if you want to upload multiple files
	function cdn_attachment_upload($post_id,$file_to_upload = null) {
		if($this->cdntools_sideload_uploads == 1 && $this->cdntools_authname != null & $this->cdntools_authkey != null) {
			if($file_to_upload == null) {
				//if no file string passed, use the one generated by the upload filter
				$file_to_upload = $this->uploaded_file_path;
			}
			$cdn_loader = new cdn_loader($this->cdntools_authname,$this->cdntools_authkey,$this->cdntools_servicenet);
			$return_array = $cdn_loader->attachment_upload($file_to_upload, $this->cdntools_logretention);
			if (!is_array($return_array)) {
				return $return_array;
			} else if ( !array_key_exists($return_array['container_name'],$this->cdntools_baseuris) ) {
				$this->cdntools_baseuris[$return_array['container_name']] = $return_array['baseuri'];
			}
			update_option('cdntools_baseuris',serialize($this->cdntools_baseuris));
			return true;
		}
	}
	
	function cdn_attachment_upload_ajax() {
		error_reporting(E_ERROR | E_WARNING | E_PARSE);
		ini_set('display_errors',1);
		$cdn_loader = new cdn_loader($this->cdntools_authname,$this->cdntools_authkey,$this->cdntools_servicenet);
		$upload_return = $this->cdn_attachment_upload($_POST['post_id'],$_POST['path']);
		if ($upload_return !== true) {
			echo $upload_return; //return the bubbled up exception
		} else {
			echo 'true';
		}
	}
	
	//this function is mostly stolen from get_attached_file in post.php in wordpress.  it attempts to correct for absolute vs relative paths.
	//if the if conditional is met it's a relative path
	function file_path($file) {
		if ( 0 !== strpos($file, '/') && !preg_match('|^.:\\\|', $file) && ( ($uploads = wp_upload_dir()) && false === $uploads['error'] ) ) {
			$file = $uploads['basedir'] . "/$file"; 
		}
		if(strpos($file,$uploads['basedir']) === 0) {
			/*
			if we find an absolute path but the upload basedir isn't in the string, this likely means that the blog has 
			been moved from one host to another...example: user had path /var/www/html/wp-content/uploads/2009/file.png 
			but moves to a host with the data in /home/wwwuser/htdocs/wp-content/uploads.  now the paths are broken.  
			this isn't the fault of CDN Tools but we have to (try to) deal with WP's mess
			*/
			//we need to determine the substring before the relative upload dir, then fix the path.  this is not easy...
		}
		return $file;
	}


	//files_to_delete must be an array of absolute paths.
	//three scenarios to call this hook:
	//files to delete is non-null, which isn't used in the code right now
	//wp 2.7 (need deleted_file_path_arr)
	//wp 2.8 (need to fetch info using post_id)
	function cdn_attachment_delete($post_id,$files_to_delete = null) { //$post_id will not be useful until 2.8 but it is provided by the action hook
		if($this->cdntools_sideload_uploads == 1 && $this->cdntools_authname != null & $this->cdntools_authkey != null) {
			if(!is_array($files_to_delete)) {
				//if no file array passed, use the one generated by the delete filter, this is WP2.7 compatibility
				if(!empty($this->deleted_file_path_arr)) {
					$files_to_delete = $this->deleted_file_path_arr;
				} else {
					//wp2.8 fires the action before the filter so we can't depend on that.  here's how we'll get this data
					global $wpdb;
					$result = $wpdb->get_results( $wpdb->prepare( "select meta_value from $wpdb->postmeta WHERE meta_key = %s and post_id = %d", '_wp_attached_file' , $post_id ) );
					$files_to_delete = array();
					foreach($result as $attachment) {
						//we only need the basedir from our upload info var
						$fullpath = $this->wp_upload_basedir.'/'.$attachment->meta_value;
						$files_to_delete[] = $fullpath;

						//check for metadata that has resized images
						//consider using wp_delete_file filter here instead of these methods.  see line 2902 from wp-includes/post.php
						$metadata = unserialize( $wpdb->get_var( $wpdb->prepare( "select meta_value from $wpdb->postmeta WHERE meta_key = %s and post_id = %d", '_wp_attachment_metadata',$post_id ) ) );
						if(is_array($metadata) && isset($metadata['sizes'])) {
							$file_array = explode('/',$metadata['file']);
							foreach($metadata['sizes'] as $data) {
								array_pop($file_array);
								array_push($file_array,$data['file']);
								$filepath = $this->file_path(implode('/',$file_array));
								$files_to_delete[] = $filepath;
							}
						}
						//check for edited image data.  yes i don't understand the meta_key name either
						//consider using wp_delete_file filter here instead of these methods.  see line 2902 from wp-includes/post.php
						$backup_metadata = unserialize( $wpdb->get_var( $wpdb->prepare( "select meta_value from $wpdb->postmeta WHERE meta_key = %s and post_id = %d", '_wp_attachment_backup_sizes',$post_id ) ) );
						if(is_array($backup_metadata)) {
							foreach($backup_metadata as $data) {
								array_pop($file_array); //same file_array from the attachment_metadata
								array_push($file_array,$data['file']);
								$filepath = $this->file_path(implode('/',$file_array));
								$files_to_delete[] = $filepath;
							}
						}
					}
				}
			}
			$cdn_loader = new cdn_loader($this->cdntools_authname,$this->cdntools_authkey,$this->cdntools_servicenet);
			foreach($files_to_delete as $path) {
				$cdn_loader->attachment_delete($path);
			}
			$this->deleted_file_path_arr = array(); //empty it out.
		}
	}
	
	function cdn_post_upload_ui() {
		if($this->cdntools_sideload_uploads == 1 && $this->cdntools_authname != null & $this->cdntools_authkey != null) {
			echo '<p style="color:green">CDN Side Loading Enabled.  All files will be uploaded to your CDN as well as the local WP uploads directory.</p>';
		}
	}
		
	function cdn_upload_all_attachments() {
		global $wpdb;
		$result = $wpdb->get_results( $wpdb->prepare( "select post_id,meta_value from $wpdb->postmeta WHERE meta_key = %s", '_wp_attached_file' ) );
		$uploadinfo = array();
		foreach($result as $attachment) {
			//we only need the basedir from our upload info var
			$fullpath = $this->file_path($attachment->meta_value);
			$uploadinfo[] = array('post_id'=>$attachment->post_id,'path'=>$fullpath);
			
			//check for metadata that has resized images
			$metadata = unserialize( $wpdb->get_var( $wpdb->prepare( "select meta_value from $wpdb->postmeta WHERE meta_key = %s and post_id = %d", '_wp_attachment_metadata',$attachment->post_id ) ) );
			if(is_array($metadata) && isset($metadata['sizes'])) {
				$file_array = explode('/',$metadata['file']);
				foreach($metadata['sizes'] as $data) {
					array_pop($file_array);
					array_push($file_array,$data['file']);
					$filepath = $this->file_path(implode('/',$file_array));
					$uploadinfo[] = array('post_id'=>$attachment->post_id,'path'=>$filepath);
				}
			}
			$backup_metadata = unserialize( $wpdb->get_var( $wpdb->prepare( "select meta_value from $wpdb->postmeta WHERE meta_key = %s and post_id = %d", '_wp_attachment_backup_sizes',$attachment->post_id ) ) );
			if(is_array($backup_metadata)) {
				foreach($backup_metadata as $data) {
					array_pop($file_array); //same file_array from the attachment_metadata
					array_push($file_array,$data['file']);
					$filepath = $this->file_path(implode('/',$file_array));
					$uploadinfo[] = array('post_id'=>$attachment->post_id,'path'=>$filepath);
				}
			}

		}
		return $this->array2json($uploadinfo);
	}
	
	/* 
	loop through all registered scripts and compare to the files we have loaded to the CDN.
	*/
	function rewrite_wp_default_scripts(&$wp_scripts) {
		if($this->cdntools_baseuris[CDNTOOLS_PREFIX.'wp_js'] != null && is_array($this->cdntools_loadedfiles)) {
			foreach($wp_scripts->registered as $scriptobj) {
				if(in_array($scriptobj->handle,$this->cdntools_loadedfiles)) {
					$scriptobj->src = $this->cdntools_baseuris[CDNTOOLS_PREFIX.'wp_js'].'/'.$scriptobj->handle.".js";
				}
			}
		}
		//check to see if we need to use googleajax CDN.  google overrides previous cdn'd scripts
		if($this->cdntools_googleajax) {
			foreach($wp_scripts->registered as $object) {
				if(array_key_exists($object->handle,$this->gscripts)) {
					$libname = $object->handle;
					$jsname = $this->gscripts[$libname];
					$ver = $object->ver;
					$transport = (is_ssl())?'https':'http'; //make it ssl if the site is ssl.
					$object->src = "$transport://ajax.googleapis.com/ajax/libs/$libname/$ver/$jsname.js";
				}
			}
		}
	}
	
	function wp_head_action() {
		echo '<!--CDN Tools v'.CDNTOOLS_VERSION."-->\n";
	}
	
	function cdnify_css($content) {
		global $wp_styles;
		//nobody uses wp_styles yet...moving on. come back to this in 2.8
		//function wp_register_style( $handle, $src, $deps = array(), $ver = false, $media = 'all' ) {
	}
		
	//inspiration from use-google-libraries
	function jquery_noconflict($js) {
		$jquery_key = array_search( 'jquery', $js );
		if ($jquery_key === false || $this->cdntools_googleajax == false) {
			return $js;
		}
		//register the no conflict script
		wp_register_script('jquery-noconflict',WP_PLUGIN_URL.'/'.CDNTOOLS_DIR_NAME.'/cdn_classes/jquery-noconflict.js');
		array_splice( $js, $jquery_key, 1, array('jquery','jquery-noconflict'));
		return $js;
	}
	
	function cdn_js_load() {
		global $wp_scripts;
		$loadedfiles = array();
		foreach($wp_scripts->registered as $scriptobj) {
			if(strpos($scriptobj->src,"http") === false && strlen($scriptobj->src) > 0) {
				//don't touch URL pathed scripts for now.  probably added by plugins
				if($this->cdntools_adminscripts == 1 || ($this->cdntools_adminscripts == false && strpos($scriptobj->src,"wp-admin") === false)) {
					//don't load admin scripts into the array if the pref is set
					$filestoload[] = $scriptobj->handle;
				}
			}
		}
		if( is_array($filestoload) ) {
			$cdn_loader = new cdn_loader($this->cdntools_authname,$this->cdntools_authkey,$this->cdntools_servicenet);
			$returndata = $cdn_loader->load_js_files($filestoload);
			if ( $returndata[0] != '' && is_array($returndata[1]) ) {
				$this->cdntools_baseuris[CDNTOOLS_PREFIX.'wp_js'] = $returndata[0];				
				update_option('cdntools_baseuris',serialize($this->cdntools_baseuris));
				update_option('cdntools_loadedfiles',serialize($returndata[1]));
			}
		}
	}
	
	function cdn_remove_container($container_name) {
		$cdn_loader = new cdn_loader($this->cdntools_authname,$this->cdntools_authkey,$this->cdntools_servicenet);
		$cdn_loader->remove_container($container_name);
		unset($this->cdntools_baseuris[$container_name]);
		update_option('cdntools_baseuris',serialize($this->cdntools_baseuris));
		if($container_name == CDNTOOLS_PREFIX.'wp_js') {
			update_option('cdntools_loadedfiles','');
			$this->cdntools_laodedfiles = '';
		}
	}
	
	function cdntools_admin_init() {
		register_setting( 'cdn-options-group', 'cdntools_googleajax', 'absint' );
		register_setting( 'cdn-options-group', 'cdntools_primarycdn', 'wp_filter_nohtml_kses' );
		register_setting( 'cdn-options-group', 'cdntools_authname', 'wp_filter_nohtml_kses' );
		register_setting( 'cdn-options-group', 'cdntools_authkey', 'wp_filter_nohtml_kses' );
		register_setting( 'cdn-options-group', 'cdntools_adminscripts', 'absint' );
		register_setting( 'cdn-options-group', 'cdntools_sideload_uploads', 'absint' );
		register_setting( 'cdn-options-group', 'cdntools_logretention', 'absint' );
		register_setting( 'cdn-options-group', 'cdntools_servicenet', 'absint' );
		register_setting( 'cdn-options-group', 'cdntools_advanced_options', 'absint' );
		register_setting( 'cdn-options-group', 'cdntools_prefix', 'wp_filter_nohtml_kses' );
	}
	

	function add_admin_pages() {
		add_submenu_page('options-general.php', "CDN Tools", "CDN Tools", 10, "cdn-tools.php",
		array($this,"settings_output"));
	}

	function settings_output() {
		global $wp_scripts;
		if ( isset( $_POST['action'] ) ) {
			//flush wp super cache
			if(function_exists('wp_cache_no_postid')) {
				wp_cache_no_postid(0);
			}
			switch ( $_POST['action'] ) {
				case 'cdn_js_load':
					/*$this->cdn_js_load();
					$this->cdntools_baseuris = unserialize(get_option('cdntools_baseuris'));
					$this->cdntools_loadedfiles = unserialize(get_option('cdntools_loadedfiles')); //if nothing is loaded this will trigger an E_NOTICE*/
					//REMOVED FOR WP 2.8 COMPATIBILITY
				break;
				case 'cdn_remove_js':
					$this->cdn_remove_container(CDNTOOLS_PREFIX.'wp_js');
					unset($this->cdntools_baseuris[CDNTOOLS_PREFIX.'wp_js']);
					$this->cdntools_loadedfiles = null;
				break;
				case 'cdn_remove_attachments':
					foreach($this->cdntools_baseuris as $container_name=>$baseuri) {
						if($container_name != CDNTOOLS_PREFIX.'wp_js') {
							$this->cdn_remove_container($container_name);
						}
						$this->cdntools_sideload_uploads = 0;
						update_option('cdntools_sideload_uploads',0);
					}
				break;
				case 'cdn_upload_all_attachments':
					$this->cdntools_sideload_uploads = 1;
					update_option('cdntools_sideload_uploads',1);
					$json_arr = $this->cdn_upload_all_attachments();
				break;
				case 'cdn_upload_all_files':
					$this->cdntools_sideload_uploads = 1;
					update_option('cdntools_sideload_uploads',1);
					$json_arr = $this->cdn_upload_all_attachments();
					//$this->cdn_js_load(); //REMOVED FOR WP 2.8 COMPATIBILITY
					$this->cdntools_baseuris = unserialize(get_option('cdntools_baseuris'));
					$this->cdntools_loadedfiles = unserialize(get_option('cdntools_loadedfiles')); //if nothing is loaded this will trigger an E_NOTICE
				break;
				case 'cdn_remove_all_files':
					foreach($this->cdntools_baseuris as $container_name=>$baseuri) {
						$this->cdn_remove_container($container_name);
					}
					$this->cdntools_sideload_uploads = 0;
					update_option('cdntools_sideload_uploads',0);
					$this->cdntools_loadedfiles = unserialize(get_option('cdntools_loadedfiles')); //if nothing is loaded this will trigger an E_NOTICE
				break;
				case 'reset_cdntools':
					delete_option('cdntools_googleajax');
					delete_option('cdntools_primarycdn');
					delete_option('cdntools_loadedfiles');
					delete_option('cdntools_baseuris');
					delete_option('cdntools_authname');
					delete_option('cdntools_authkey');
					delete_option('cdntools_adminscripts');
					delete_option('cdntools_sideload_uploads');
					delete_option('cdntools_logretention');
					delete_option('cdntools_servicenet');
					delete_option('cdntools_advanced_options');
					delete_option('cdntools_prefix');
					$this->cdntools_googleajax = null;
					$this->cdntools_primarycdn = null;
					$this->cdntools_loadedfiles = null;
					$this->cdntools_baseuris = array();
					$this->cdntools_authname = null;
					$this->cdntools_authkey = null;
					$this->cdntools_adminscripts = null;
					$this->cdntools_sideload_uploads = null;
					$this->cdntools_logretention = null;
					$this->cdntools_servicenet = null;
					$this->cdntools_advanced_options = null;
			}
		}
		
		?>
		<div class="wrap">
			<h2>CDN Tools</h2>
			<form method="post" action="options.php">
				<?php settings_fields('cdn-options-group'); ?>
				<table class="form-table">
					<tr>
						<th>Use Google AJAX CDN:</th>
						<td><select name="cdntools_googleajax">
							<?php
							$true = null;
							$false = null;
							$set = 'selected="selected"';
							($this->cdntools_googleajax)?$true=$set:$false=$set;
							?>
							<option value="1" <?php echo $true?>>True</option>
							<option value="0" <?php echo $false?>>False</option>
							</select></td>
					</tr>
					<tr>
						<td colspan="2">Google libraries will replace prototype, jquery, dojo, and mootools.  Google will trump any other CDN you have enabled as well.  This is free so you should enable this.</td>
					</tr>
					<tr>
						<th>Primary CDN:</th>
						<td><select name="cdntools_primarycdn">
							<?php
							$amazon = null;
							$cloudfiles = null;
							$none = null;
							switch($this->cdntools_primarycdn) {
								case 'cloudfiles':
									$cloudfiles = 'selected="selected"';
									$cdn_name = 'Cloud Files';
								break;
								case 'cloudfront':
									$amazon = 'selected="selected"';
									$cdn_name = 'Amazon S3/CloudFront';
								break;
								default:
									$none = 'selected="selected"';
									$cdn_name = 'None Selected';
								break;
							}
							?>
							<option value="0" <?php echo $none?>>None</option>
							<option value="cloudfiles" <?php echo $cloudfiles?>>Cloud Files</option>
							<!--option value="amazon" <?php echo $amazon?>>Amazon S3/CloudFront</option-->
							</select></td>
					</tr>
					<tr>
						<td colspan="2">Select none if you do not have a CDN account and wish to only use the Google CDN feature for JS.  Once you have selected a CDN and entered your credentials, click save changes and then click the "Load Files" button that appears near CDN Status.</td>
					</tr>
					<tr class="cdn-auth">
						<th>Username:</th>
						<td><input type="text" name="cdntools_authname" value="<?php echo $this->cdntools_authname; ?>" /></td>
					</tr>
					<tr class="cdn-auth">
						<th>API Key:</th>
						<td><input type="text" style="width:260px" name="cdntools_authkey" value="<?php echo $this->cdntools_authkey; ?>" /></td>
					</tr>
					<tr>
						<th>
							Advanced Options:
						</th>
						<td><select name="cdntools_advanced_options">
							<?php
							$advanced_true = null;
							$advanced_false = null;
							if($this->cdntools_advanced_options) {
								$advanced_true = 'selected="selected"';
							} else {
								$advanced_false = 'selected="selected"';
							}
							?>
							<option value='0' <?php echo $advanced_false;?>>Disabled</option>
							<option value='1' <?php echo $advanced_true;?>>Enabled</option>
							</select>
						</td>
					</tr>
					<tr>
						<td colspan="2">Enabling advanced options will allow you to modify settings that are outside the standard use cases.  You can also troubleshoot/reset various aspects of the plugin if you are having issues.</td>
					</tr>
					<?php
					if($this->cdntools_advanced_options) {
						$advanced_display = '';
					} else {
						$advanced_display = 'display:none';
					}
					?>
					<tr style="<?php echo $advanced_display; ?>">
						<th>Enable CDN Side Loading:</th>
						<?php
						$sideloadcheck = ($this->cdntools_sideload_uploads)?'checked="checked"':'';
						?>
						<td><input type="checkbox" name="cdntools_sideload_uploads" value="1" <?php echo $sideloadcheck; ?> /></td>
					</tr>
					<tr style="<?php echo $advanced_display; ?>">
						<td colspan="2">Check this to enable side loading of uploads to your CDN.  Uploads will also be kept locally in the event that you wish to turn off the CDN/remove this plugin.  Once you configure your CDN you probably want to turn this on.</td>
					</tr>
					<tr style="<?php echo $advanced_display; ?>">
						<th>Enable CDN Log Retention:</th>
						<?php
						$logretentioncheck = ($this->cdntools_logretention)?'checked="checked"':'';
						?>
						<td><input type="checkbox" name="cdntools_logretention" value="1" <?php echo $logretentioncheck; ?> /></td>
					</tr>
					<tr style="<?php echo $advanced_display; ?>">
						<td colspan="2">Check this to enable CDN log retention. If enabled, logs will be periodically (at unpredictable intervals) compressed and uploaded to a ".CDN_ACCESS_LOGS" container in the form of "container_name.YYYYMMDDHH-XXXX.gz". After enabling  or disabling this option, you will need to re-upload your attachments to Cloud Files (click the "Load Attachments" button below).</td>
					</tr>
					<tr style="<?php echo $advanced_display; ?>">
						<th>Enable Servicenet Connection:</th>
						<?php
						$servicenetcheck = ($this->cdntools_servicenet)?'checked="checked"':'';
						?>
						<td><input type="checkbox" name="cdntools_servicenet" value="1" <?php echo $servicenetcheck; ?> /></td>
					</tr>
					<tr style="<?php echo $advanced_display; ?>">
						<td colspan="2">Check this to enable the Servicenet connection to Cloud Files. If enabled, traffic to Cloud Files will use Rackspace's internal network to upload data to Cloud Files. This internal connection will almost always be faster than non-servicenet connections, and bandwidth on servicenet is free. However, servicenet connections will only work if this blog is hosted on a server that is hosted by Rackspace or Slicehost.  <b>Rackspace Cloud Sites does not currently support the servicenet.</b></td>
					</tr>
					<!--tr style="<?php echo $advanced_display; ?>">
						<th>Load wp-admin Javascripts:</th>
						<?php
						$adminupcheck = ($this->cdntools_adminscripts)?'checked="checked"':'';
						?>
						<td><input type="checkbox" name="cdntools_adminscripts" value="1" <?php echo $adminupcheck; ?> /></td>
					</tr>
					<tr style="<?php echo $advanced_display; ?>">
						<td colspan="2">Check this if you want the wp-admin javascript files to be put on your CDN.  Typically you will want to leave this unchecked.</td>
					</tr-->
					<tr style="<?php echo $advanced_display; ?>">
						<th>CDN Container Prefix:</th>
						<td><input type="text" name="cdntools_prefix" value="<?php echo $this->cdntools_prefix;?>" />
						</td>
					</tr>
					<tr style="<?php echo $advanced_display; ?>">
						<td colspan="2">This option is only useful if you want to load multiple blogs into a single CDN account.  Before changing this you should remove all files from the CDN or you will have issues!</td>
					</tr>
					<tr>
						<td>
							<input type="hidden" name="action" value="update" />
							<input type="submit" class="button-primary" value="Save Changes" />
						</td>
					</tr>
				</table>
			</form>
				<?php 
				if (!function_exists('curl_init')) {
					echo '<p style="color:red">To upload files to the CDN, you must have curl support installed for PHP.</p>';
				}?>

 				<div id="cdn_status" style="width:75%">
					<table>
						<tr>
							<td style="font-size:24px;">CDN Status</td>
							<td style="font-size:24px"> | </td>
							<td style="color:blue;text-align:center;font-size:24px"><?php echo $cdn_name; ?></td>
							<td style="font-size:24px"> | </td>
							<td id="load_remove_button"><?php if ( empty($this->cdntools_baseuris) && !$none ) {
							?>
								<form method="post" action="">
									<input type="hidden" name="action" value="cdn_upload_all_files" />
									<p><input style="font-size:48em" type="submit" class="button-primary" value="Load Files" onclick="return(confirm('This action will load every WordPress attachment up into your CDN.  The page will take a bit to reload and then an AJAX progress meter will start.  Do not navigate away from the page during the upload!'))" /></p>
								</form>
							<?php
							} else if (!$none) { ?>
								<form method="post" action="">
									<input type="hidden" name="action" value="cdn_remove_all_files" />
									<p><input type="submit" class="button-primary" value="Remove Files" onclick="return(confirm('This action will remove all files from your CDN.  You should only do this if you wish to remove the plugin or to troubleshoot.  And yes, this will take awhile!'))" /></p>
								</form>
							<?php
							}?>
							</td>
							<td id="load_percent" style="color:green;font-weight:bold;font-size:24px;display:none">0.00%
							</td>
							<td id="loading" style="display:none">
								<img src="images/wpspin_light.gif" alt="" style="border:0" />
							</td>
						</tr>
					</table>
			<?php
			if ($none) {?>
				<p style="font-size:11px;color:#333333">Options to load/unload attachment data will appear here when you have configured a CDN for use.</p>
			<?php
			} else {
				//$js_load_status = ( is_array( $this->cdntools_loadedfiles ) )?'<span style="color:green">JS Loaded</span>':'<span style="color:red">JS Unloaded</span>';
				//REMOVED FOR WP 2.8 COMPATIBILITY
				$attachment_load_status = ( count( $this->cdntools_baseuris ) > 1 || ( !empty( $this->cdntools_baseuris ) && !isset($this->cdntools_baseuris[CDNTOOLS_PREFIX.'wp_js']) ) )?'<span style="color:green">Attachment Data Present</span>':'<span style="color:red">No Attachment Data</span>';
				$sideload_status = ($this->cdntools_sideload_uploads)?'<span style="color:green">Side Loading Enabled</span>':'<span style="color:red">Side Loading Disabled</span>';
				?>
					<p><?php echo $sideload_status.' | '.$attachment_load_status; ?></p>
				<?php
				if($this->cdntools_advanced_options) {
					$display = 'display:block;';
				} else {
					$display = 'display:none;';
				}
				?>
				<div style="<?php echo $display; ?>" id="cdntools_advanced_options_div">
					<p style="font-size:24px;color:orange">Other Advanced Tools</p>
					<form method="post" action="">
						<input type="hidden" name="action" value="cdn_upload_all_attachments" />
						<p><input type="submit" class="button-primary" value="Load Attachments" onclick="return(confirm('This action will load every wordpress attachment up into your CDN.  This uses AJAX so while the percentage is going up don\'t reload or navigate away!'))" /></p>
					</form>

					<form method="post" action="">
						<input type="hidden" name="action" value="cdn_remove_attachments" />
						<p><input type="submit" class="button-primary" value="Remove Attachments" onclick="return(confirm('Are you sure you want to remove all attachments from the CDN?  This will disable automatic sideloading as well.'))" /></p>
					</form>
					
					<!--form method="post" action="">
						<p>
							<input type="hidden" name="action" value="cdn_js_load" />
							<input type="submit" class="button-primary" value="Load JS" onclick="return(confirm('Loading Javascript to the CDN could take a few minutes, please be patient and only click OK if you are ready!'))"/>
						</p>
					</form>
					<form method="post" action="">
						<input type="hidden" name="action" value="cdn_remove_js" />
						<p><input type="submit" class="button-primary" value="Remove JS" onclick="return(confirm('Are you sure you want to remove all Javascript from your CDN?'))" /></p>
					</form-->
					<form method="post" action="">
						<input type="hidden" name="action" value="reset_cdntools" />
						<p><input type="submit" class="button-primary" value="Reset CDN Tools" onclick="return(confirm('This will remove all settings and reset CDN Tools to a default state.  Before doing this you should remove all files from the CDN.  Sure you want to do it?'))" /></p>
					</form>
				</div>
			<?php
			}?>
			</div>
		</div>
		
		<?php if($json_arr) {?>
			<script type="text/javascript">
			
				uploadArr = eval('(<?php echo $json_arr; ?>)');
				activeNum = 0;
				numComplete = 0;
				itsBusted = 0;
				total = uploadArr.length;
				if (uploadArr.length > 0) {
					jQuery('#loading').show();
					jQuery('#load_percent').show();
					jQuery('#load_remove_button').hide();
					setConfirmUnload(true);
				}	

		
				fillConnectionQueue();

				//this will prevent too many ajax requests from stacking up.
				function fillConnectionQueue() {
					while(activeNum < 4 && uploadArr.length > 0 && itsBusted == 0) {
						var attachment = uploadArr.shift();
						activeNum++;
						queueAjax(attachment);
					}
				}

				function queueAjax(attachment) {
					jQuery.ajax({
						type: "post",
						url: "admin-ajax.php",
						data: 
						{
						'action': 'cdn_attachment_upload_ajax',
						'path': attachment['path'],
						'post_id': attachment['post_id'],
						'cookie': encodeURIComponent(document.cookie)
						},
						timeout: 95000,
						error: function(request,error) {
							alert('A timeout on upload has occurred.  Please contact the developer and provide them this information:\n'+error+'\n'+decodeURIComponent(this.data))
							activeNum--;
							fillConnectionQueue();
						},
						success: function(response) {
							activeNum--;
							fillConnectionQueue();
							//wp returns a 0 after all ajax calls for reasons that are beyond my
							//ability to comprehend.  let's strip it off.
							var truncated_response = response.substring(0, response.length - 1);
							if(truncated_response == 'true') {
								numComplete++;
								var percent_complete = Math.round(parseInt(numComplete)/parseInt(total) * 10000)/100;
								jQuery('#load_percent').html(percent_complete+'%');
								if(percent_complete == 100) {
									jQuery('#loading').hide();
									setConfirmUnload(false);
									window.location.href=window.location.href; //reload
								}
							} else {
								if(!confirm('A file failed to upload.  CDN Tools will behave inconsistently if all files do not upload successfully!  You should contact the developer with this info:\n'+truncated_response+'\n\n Click Okay to continue uploading, or cancel to abort.')) {
									itsBusted = 1;
									setConfirmUnload(false); //something failed, we don't want to have that confirm any more
								}
							}
				  		}
					});
				}
				function setConfirmUnload(val) {
					window.onbeforeunload = (val) ? unloadMessage : null;
				}
				
				function unloadMessage() {
					return 'Leaving this page will cause the content load to be incomplete!';
				}
			</script>
		<?php }?>
		
		
		
		<?php 
		if(CDNTOOLS_DEBUG == true) {?>
			<select>
				<option>List of Loaded JS</option>
				<?php
				foreach($this->cdntools_loadedfiles as $value) {
					echo '<option>'.$value.'</option>'."\n";
				} 
				echo '</select>';
			print_r($this->cdntools_baseuris);
			foreach($wp_scripts->registered as $object) {
				echo $object->handle.' '.$object->src.' ';
				print_r($object->deps);
				echo '<br>';
			}
		}
	}
	
	/*array2json provided by bin-co.com under BSD license*/
	private function array2json($arr) { 
		if(function_exists('json_encode')) return stripslashes(json_encode($arr)); //Latest versions of PHP already have this functionality. 
		$parts = array(); 
		$is_list = false; 

		//Find out if the given array is a numerical array 
		$keys = array_keys($arr); 
		$max_length = count($arr)-1; 
		if(($keys[0] == 0) and ($keys[$max_length] == $max_length)) {//See if the first key is 0 and last key is length - 1 
			$is_list = true; 
			for($i=0; $i<count($keys); $i++) { //See if each key correspondes to its position 
				if($i != $keys[$i]) { //A key fails at position check. 
					$is_list = false; //It is an associative array. 
					break; 
				} 
			} 
		} 

		foreach($arr as $key=>$value) { 
			if(is_array($value)) { //Custom handling for arrays 
				if($is_list) $parts[] = $this->array2json($value); /* :RECURSION: */ 
				else $parts[] = '"' . $key . '":' . $this->array2json($value); /* :RECURSION: */ 
			} else { 
				$str = ''; 
				if(!$is_list) $str = '"' . $key . '":'; 

				//Custom handling for multiple data types 
				if(is_numeric($value)) $str .= $value; //Numbers 
				elseif($value === false) $str .= 'false'; //The booleans 
				elseif($value === true) $str .= 'true'; 
				else $str .= '"' . addslashes($value) . '"'; //All other things 
				// :TODO: Is there any more datatype we should be in the lookout for? (Object?) 

				$parts[] = $str; 
			} 
		} 
		$json = implode(',',$parts); 

		if($is_list) return '[' . $json . ']';//Return numerical JSON 
		return '{' . $json . '}';//Return associative JSON 
	} 
}

?>
