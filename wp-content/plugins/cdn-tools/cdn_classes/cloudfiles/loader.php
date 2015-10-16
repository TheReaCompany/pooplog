<?php

require(dirname(__FILE__).'/cloudfiles.php');

class cdn_loader {
	private $authname = null;
	private $authkey = null;
	private $upload_path = null;
	private $uploads_use_yearmonth_folders = null;
	private $conn = null;
	
	function __construct($authname,$authkey,$use_servicenet=False) {
		$this->authname = $authname;
		$this->authkey = $authkey;
		$wpuploadinfo = wp_upload_dir();
		//we only need the basedir
		$this->upload_path = $wpuploadinfo['basedir'];
		$this->uploads_use_yearmonth_folders = get_option('uploads_use_yearmonth_folders');
		$this->authconn($use_servicenet);
		
	}
	
	public function authconn($use_servicenet) {
		$auth = new CF_Authentication($this->authname,$this->authkey);
		try {
			$auth->authenticate();
		}
		catch(AuthenticationException $e) {
			//for some reason this returns two error msgs.  one is echo'd even without the below echo
			echo $e->getMessage();
			return false;
		}
		$this->conn = new CF_Connection($auth,$servicenet=$use_servicenet);
	}
	
	public function load_js_files($filestoload) {
		global $wp_scripts;
		
		$wp_js = $this->conn->create_container(CDNTOOLS_PREFIX.'wp_js');
		
		$loadedfiles = array();
		foreach($wp_scripts->registered as $scriptobj) {
			if(in_array($scriptobj->handle,$filestoload)) {
				$object = $wp_js->create_object($scriptobj->handle.'.js');
				$object->load_from_filename(ABSPATH.$scriptobj->src);
				$loadedfiles[] = $scriptobj->handle;
			}
		}
		$baseuri = $wp_js->make_public(86400); //ttl of one day
		return array($baseuri,$loadedfiles);
	}

	public function attachment_upload($filepath,$keep_logs=False) {
		$file = str_replace($this->upload_path.'/','',$filepath);
		if($this->uploads_use_yearmonth_folders) {
		    $path_array = explode('/',$file);
		    $container_name = CDNTOOLS_PREFIX.'wp_uploads_'.$path_array[0].'_'.$path_array[1];
		    $object_name = $path_array[2];
		} else {
		    $container_name = CDNTOOLS_PREFIX.'wp_uploads';
		    $object_name = $file;
		}
		//need try/catch blocks here, all possible exceptions!
		$wp_uploads = $this->conn->create_container($container_name);
		$wp_uploads->log_retention($keep_logs);
		$object = $wp_uploads->create_object($object_name);
		try {
			$object->load_from_filename($filepath);
		}
		catch(InvalidResponseException $e) {
			return $e->getMessage();
		}
		catch(IOException $e) {
			return $e->getMessage();
		}
		$baseuri = $wp_uploads->make_public(86400); //ttl of one day
		return array('baseuri'=>$baseuri,'container_name'=>$container_name,'object_name'=>$object_name);
	}

	public function attachment_delete($filepath) {
		$file = str_replace($this->upload_path.'/','',$filepath);
		if($this->uploads_use_yearmonth_folders) {
		    $path_array = explode('/',$file);
		    $container_name = CDNTOOLS_PREFIX.'wp_uploads_'.$path_array[0].'_'.$path_array[1];
		    $object_name = $path_array[2];
		} else {
		    $container_name = CDNTOOLS_PREFIX.'wp_uploads';
		    $object_name = $file;
		}
		//need try/catch blocks here
		$wp_uploads = $this->conn->create_container($container_name);
		try {
			$object = $wp_uploads->delete_object($object_name);
		}
		catch(NoSuchObjectException $e) {
			//we don't care about something not existing when we try to delete it, so let's just 
			//eat the exception. yum yum yum
		}
	}
	
	public function load_css_files($filestoload) {
		//stub
	}
		
	public function remove_container($container_name) {
		$wp_js = $this->conn->create_container($container_name);
		
		//check for objects and remove them all if they exist
		$existing_objects = $wp_js->list_objects();
		if(is_array($existing_objects)) {
			foreach($existing_objects as $name) {
				$wp_js->delete_object($name);
			}
		}
		$this->conn->delete_container($container_name);
		return true;
	}
}