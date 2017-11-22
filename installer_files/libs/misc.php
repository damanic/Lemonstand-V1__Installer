<?php

	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");

	if (function_exists('set_magic_quotes_runtime'))
		@set_magic_quotes_runtime(0);
	
	if (!ini_get('safe_mode'))
		@set_time_limit(3600);

	ini_set('date.timezone', 'GMT');
	
	ini_set('display_errors', 0);
	error_reporting(0);
	
	define( "PATH_INSTALL", str_replace("\\", "/", realpath(dirname(__FILE__)."/../../") ) );
	include "installer_config.php";
	include "install_crypt.php";

	$APP_CONF = array();
	$Phpr_NoSession = false;

	if (isset($_POST['validation']))
	{
		try
		{
			show_installer_step(true);
			die();
		}
		catch (Exception $ex)
		{
			header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);

			if ($ex instanceof ValidationException)
				echo json_encode(array('error'=>$ex->getMessage(), 'field'=>$ex->field));
			else
				echo json_encode(array('error'=>$ex->getMessage()));

			die();
		}
	}

	class ValidationException extends Exception
	{
		public $field;

		public function __construct( $message, $field )
		{
			parent::__construct( $message );
			$this->field = $field;
		}
	}

	function getRequestUri()
	{
		$providers = array( 'REQUEST_URI', 'PATH_INFO', 'ORIG_PATH_INFO' );
		foreach ( $providers as $provider )
		{
			$val = getenv( $provider );
			if ( $val != '' )
				return $val;
		}
		
		return null;
	}
	
	function installer_root_url($target_url)
	{
		if (substr($target_url, 0, 1) == '/')
			$target_url = substr($target_url, 1);
		
		$url = getRequestUri();
		$url = dirname($url);
		$url = str_replace('\\', '/', $url);
		
		if (substr($url, -1) != '/')
			$url .= '/';

		return $url.$target_url;
	}

	function strleft($s1, $s2) 
	{
		return substr($s1, 0, strpos($s1, $s2));
	}
	
	function get_root_url($protocol = null)
	{
		if ($protocol === null)
		{
			$s = (empty($_SERVER["HTTPS"]) || ($_SERVER["HTTPS"] === 'off')) ? '' : 's';
			$protocol = strleft(strtolower($_SERVER["SERVER_PROTOCOL"]), "/").$s;
		}

		$port = ($_SERVER["SERVER_PORT"] == "80") ? ""
			: (":".$_SERVER["SERVER_PORT"]);
			
		return $protocol."://".$_SERVER['SERVER_NAME'].$port;
	}

	function _post($name, $default = null)
	{
		if ( isset( $_POST[$name] ) )
		{
			$result = $_POST[$name];

			if ( get_magic_quotes_gpc() )
				$result = stripslashes( $result );

			return $result;
		}

		return $default;
	}

	function _h($str)
	{
		return htmlentities( $str, ENT_COMPAT, 'UTF-8' );
	}

	function error_marker($error_field, $this_field)
	{
		return $error_field == $this_field ? 'error' : null;
	}

	function render_partial($name, $params = array())
	{
		if (isset($_POST['validation']))
			return;

		$file = PATH_INSTALL.'/installer_files/libs/partials/'.$name.'.htm';
		if (!file_exists($file))
			throw new Exception("Partial not found: $name");
	
		extract($params);
		include $file;
	}

	function installer_css_browser_selector($ua=null) 
	{
		if ($ua)
			$ua = strtolower($ua);
		else {
			if (array_key_exists('HTTP_USER_AGENT', $_SERVER))
				$ua = strtolower($_SERVER['HTTP_USER_AGENT']);
		}

		$g = 'gecko';
		$w = 'webkit';
		$s = 'safari';
		$b = array();

		// browser
		if(!preg_match('/opera|webtv/i', $ua) && preg_match('/msie\s(\d)/', $ua, $array)) {
				$b[] = 'ie ie' . $array[1];
		}	else if(strstr($ua, 'firefox/2')) {
				$b[] = $g . ' ff2';		
		}	else if(strstr($ua, 'firefox/3.5')) {
				$b[] = $g . ' ff3 ff3_5';
		}	else if(strstr($ua, 'firefox/3')) {
				$b[] = $g . ' ff3';
		} else if(strstr($ua, 'gecko/')) {
				$b[] = $g;
		} else if(preg_match('/opera(\s|\/)(\d+)/', $ua, $array)) {
				$b[] = 'opera opera' . $array[2];
		} else if(strstr($ua, 'konqueror')) {
				$b[] = 'konqueror';
		} else if(strstr($ua, 'chrome')) {
				$b[] = $w . ' ' . $s . ' chrome';
		} else if(strstr($ua, 'iron')) {
				$b[] = $w . ' ' . $s . ' iron';
		} else if(strstr($ua, 'applewebkit/')) {
				$b[] = (preg_match('/version\/(\d+)/i', $ua, $array)) ? $w . ' ' . $s . ' ' . $s . $array[1] : $w . ' ' . $s;
		} else if(strstr($ua, 'mozilla/')) {
				$b[] = $g;
		}

		// platform				
		if(strstr($ua, 'j2me')) {
				$b[] = 'mobile';
		} else if(strstr($ua, 'iphone')) {
				$b[] = 'iphone';		
		} else if(strstr($ua, 'ipod')) {
				$b[] = 'ipod';		
		} else if(strstr($ua, 'mac')) {
				$b[] = 'mac';		
		} else if(strstr($ua, 'darwin')) {
				$b[] = 'mac';		
		} else if(strstr($ua, 'webtv')) {
				$b[] = 'webtv';		
		} else if(strstr($ua, 'win')) {
				$b[] = 'win';		
		} else if(strstr($ua, 'freebsd')) {
				$b[] = 'freebsd';		
		} else if(strstr($ua, 'x11') || strstr($ua, 'linux')) {
				$b[] = 'linux';		
		}

		return join(' ', $b);
	}
	
	function show_installer_step($validation = false)
	{
		$this_step = _post('step');

		switch ($this_step)
		{
			case 'welcome' : 
				if (!_post('agree'))
				{
					render_partial('welcome', array('error'=>'You must agree to the License Agreement to continue.')); 
				}
				else
					render_partial('license_information'); 
			break;
			case 'requirements' : 
				save_delete_files_flag();

				render_partial('license_information'); 
			break;
			case 'license_information' : 
				$error = false;
				try
				{
					$hash = validate_license_information();
				} catch (Exception $ex)
				{
					$error = $ex;
				}

				if ($error)
					render_partial('license_information', array('error'=>$error)); 
				else
					render_partial('system_configuration');
			break;
			case 'system_configuration' :
				$error = false;
				try
				{
					validate_system_configuration();
				} catch (Exception $ex)
				{
					if ($validation)
						throw $ex;

					$error = $ex;
				}

				if ($error)
					render_partial('system_configuration', array('error'=>$error)); 
				else
					render_partial('admin_user');
			break;
			case 'urls' : 
				$error = false;
				try
				{
					validate_urls();
				} catch (Exception $ex)
				{
					$error = $ex;
				}

				if ($error)
					render_partial('urls', array('error'=>$error)); 
				else
					render_partial('image_magick');
			break;
			case 'image_magick' : 
				$error = false;
				try
				{
					validate_image_magick();
				} catch (Exception $ex)
				{
					$error = $ex;
				}

				if ($error)
					render_partial('image_magick', array('error'=>$error)); 
				else
					render_partial('permissions');
			break;
			case 'permissions' : 
				$error = false;
				try
				{
					validate_permissions();
				} catch (Exception $ex)
				{
					$error = $ex;
				}

				if ($error)
					render_partial('permissions', array('error'=>$error)); 
				else
					render_partial('admin_user');
			break;
			case 'user_accounts' : 
				$error = false;
				try
				{
					validate_user_accounts();
				} catch (Exception $ex)
				{
					if ($validation)
						throw $ex;

					$error = $ex;
				}

				if ($error)
					render_partial('admin_user', array('error'=>$error)); 
				else
					render_partial('demo_data_and_themes');
			break;
			case 'admin_user' :
				$error = false;
				try
				{
					validate_admin_user();
				} catch (Exception $ex)
				{
					$error = $ex;
				}

				if ($error)
					render_partial('admin_user', array('error'=>$error)); 
				else
					render_partial('config_user');
			break;
			case 'config_user' :
				$error = false;
				try
				{
					validate_config_user();
				} catch (Exception $ex)
				{
					$error = $ex;
				}

				if ($error)
					render_partial('config_user', array('error'=>$error)); 
				else
					render_partial('default_theme');
			break;
			case 'default_theme' :
				save_default_theme_flag();
				
				render_partial('encryption_key');
			break;
			case 'themes_and_demo_data' :
				save_default_theme_flag();
				save_demo_data_flag();
				save_delete_files_flag();

				$error = false;
				$delete_files_on_install = false;

				try
				{
					$delete_files_on_install = validate_encryption_key();
				} catch (Exception $ex)
				{
					$error = $ex;
				}

				if ($error)
					render_partial('demo_data_and_themes', array('error'=>$error)); 
				else
				{
					render_partial('complete', array('files_deleted'=>false));
				}
			break;
			case 'encryption_key':
				$error = false;
				$delete_files_on_install = false;
				try
				{
					$delete_files_on_install = validate_encryption_key();
				} catch (Exception $ex)
				{
					$error = $ex;
				}

				if ($error)
					render_partial('encryption_key', array('error'=>$error)); 
				else
				{
					$files_deleted = !file_exists(PATH_INSTALL.'/installer_files') && !file_exists(PATH_INSTALL.'/install.php');
					render_partial('complete', array('files_deleted'=>$files_deleted));
				}
					
				if ($delete_files_on_install)
				{
					@installer_remove_dir(PATH_INSTALL.'/installer_files');
					@unlink(PATH_INSTALL.'/install.php');
				}
			break;
			
			default:
				$requirements = check_requirements();
				$requirements_met = true;

				foreach ($requirements as $name=>$met)
				{
					if (!$met)
						$requirements_met = false;
				}

				if (!$requirements_met)
				{
					render_partial('requirements_warnings', array('warnings'=>$warnings, 'requirements'=>$requirements, 'requirements_met'=>$requirements_met)); 
					return;
				}

				$error = false;
				$eula_text = null;
				try
				{
					$eula_text = installer_get_eula_text();
				} catch (exception $ex)
				{
					$error = $ex->getMessage();
				}
			
				render_partial('welcome', array('eula_text'=>$eula_text, 'error'=>$error)); 
		}
	}
	
	function installer_get_eula_text()
	{
		global $installer_config;

		$url = $installer_config['LEMONSTAND_SERVER_URL'].'/lemonstand_eula/std-eula';

		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_URL, 'http://'.$url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 3600);
		$text = curl_exec($ch);
		$info = curl_getinfo($ch);
		$status_code = $info['http_code'];

		if ($status_code !== 200)
			throw new exception('Error loading LemonStand End User License Agreement text. Error message: '.$text);

		$pos = strpos($text, '|');
		if ($pos === false)
			throw new exception('Error loading LemonStand End User License Agreement text.');

		$version = substr($text, 0, $pos);
		$content = substr($text, $pos+1);

		$data = array(
			'version'=>$version,
			'content'=>$content
		);
		Install_Crypt::create()->encrypt_to_file(PATH_INSTALL.'/installer_files/temp/eula.dat', $data, '');
		
		return $content;
	}
	
	function request_server_data($url)
	{
		global $installer_config;
		
		$uc_url = $installer_config['LEMONSTAND_SERVER_URL'].'/lemonstand_update_gateway';

		if (!strlen($uc_url))
			throw new Exception('LemonStand eCommerce Inc. server URL is not specified in the configuration file.');

		$result = null;
		try
		{
			$ch = curl_init(); 
			curl_setopt($ch, CURLOPT_URL, 'http://'.$uc_url.'/'.$url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 3600);
			curl_setopt($ch, CURLOPT_POSTFIELDS, '');
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			$result = curl_exec($ch);
		} catch (Exception $ex) {}

		if (!$result || !strlen($result))
			throw new Exception("Error connecting to the LemonStand eCommerce Inc. server.");

		$result_data = false;
		try
		{
			$result_data = @unserialize($result);
		} catch (Exception $ex) {
			throw new Exception("Invalid response from the LemonStand eCommerce Inc. server.");
		}

		if ($result_data === false)
			throw new Exception("Invalid response from the LemonStand eCommerce Inc. server.");
			
		if ($result_data['error'])
			throw new Exception($result_data['error']);
			
		return $result_data;
	}

	function save_delete_files_flag()
	{
		$data = array(
			'delete_install_files'=>_post('delete_install_files') ? 1 : 0
		);
		Install_Crypt::create()->encrypt_to_file(PATH_INSTALL.'/installer_files/temp/params0.dat', $data, _post('install_key'));
	}
	
	function save_default_theme_flag()
	{
		$data = array(
			'import_default_theme'=>_post('import_default_theme') ? 1 : 0,
			'import_default_theme_twig'=>_post('import_default_theme_twig') ? 1 : 0,
		);
		Install_Crypt::create()->encrypt_to_file(PATH_INSTALL.'/installer_files/temp/params7.dat', $data, _post('install_key'));
	}

	function save_demo_data_flag()
	{
		$data = array(
			'import_demo_data'=>_post('import_demo_data') ? 1 : 0
		);
		Install_Crypt::create()->encrypt_to_file(PATH_INSTALL.'/installer_files/temp/params10.dat', $data, _post('install_key'));
	}
	
	function validate_license_information()
	{
		$holder_name = trim(_post('holder_name'));
		if (!strlen($holder_name))
			throw new ValidationException('Please enter the license holder name.', 'holder_name');

		$serial_number = trim(_post('serial_number'));
		if (!strlen($serial_number))
			throw new ValidationException('Please enter the serial number.', 'serial_number');

		$hash = md5($serial_number.$holder_name);
		$data = urlencode(base64_encode(get_root_url()));

		$result = request_server_data('get_install_hashes/'.$hash.'/'.$data);
		if (!is_array($result['data']))
			throw new Exception("Invalid server response");

		$file_hashes = $result['data']['file_hashes'];
		$license_key = $result['data']['key'];

		$tmp_path = PATH_INSTALL.'/installer_files/temp';
		if (!is_writable($tmp_path))
			throw new Exception("Cannot create a temporary file. Path is not writable: $tmp_path");

		$files = array();
		try
		{
			foreach ($file_hashes as $code=>$file_hash)
			{
				$tmp_file = $tmp_path.'/'.$code.'.arc';
				$result = request_server_data('get_install_file/'.$hash.'/'.$code);

				$tmp_save_result = false;
				try
				{
					$tmp_save_result = @file_put_contents($tmp_file, $result['data']);
				} catch (Exception $ex)
				{
					throw new Exception("Error creating temporary file in ".$tmp_path);
				}
				
				$files[] = $tmp_file;
		
				if (!$tmp_save_result)
					throw new Exception("Error creating temporary file in ".$tmp_path);
			
				$downloaded_hash = md5_file($tmp_file);
				if ($downloaded_hash != $file_hash)
					throw new Exception("Downloaded archive is corrupted. Please try again.");
			}
		} catch (Exception $ex)
		{
			foreach ($files as $file)
			{
				if (file_exists($file))
					@unlink($file);
			}
			
			throw $ex;
		}

		$install_hashes = array(
			'hash'=>$hash,
			'key'=>$license_key,
			'delete_install_files'=>_post('delete_install_files') ? 1 : 0
		);
		Install_Crypt::create()->encrypt_to_file(PATH_INSTALL.'/installer_files/temp/params4.dat', $install_hashes, _post('install_key'));
	}
	
	function validate_config_information()
	{
		global $APP_CONF;
		
		$mysql_host = trim(_post('mysql_host'));
		$db_name = trim(_post('db_name'));
		$mysql_user = trim(_post('mysql_user'));
		$mysql_password = trim(_post('mysql_password'));
		$time_zone = trim(_post('time_zone'));

		if (!strlen($mysql_host))
			throw new ValidationException('Please specify MySQL host', 'mysql_host');
			
		/*
		 * Validate database connection
		 */
		
		if ( !($db_link = installer_connect_mysql( $mysql_host, $mysql_user, $mysql_password )) ) {
			$message = 'Unable to connect to specified MySQL host.';
			$error = installer_get_mysql_error($db_link);
			if ($error)
				$message .= ' MySQL returned the following error: '.$error.'.';

			throw new ValidationException($message, 'mysql_host');
		}

		/*
		 * Validate database
		 */

		if (!strlen($db_name))
			throw new ValidationException('Please specify MySQL database name', 'db_name');
		
		if ( !installer_select_db($db_link, $db_name) ) {
			$message = 'Unable to select specified database "'.$db_name.'"';
			$error = installer_get_mysql_error();
			if ($error)
				$message .= ' MySQL returned the following error: '.$error.'.';

			throw new ValidationException($message, 'db_name');
		}

		/*
		 * Check whether the database is empty
		 */
		
		$num = installer_get_table_num($db_link, $db_name);

		if ($num)
			throw new ValidationException( 'Database "'.$db_name.'" is not empty. Please empty the database or specify another database.', 'db_name' );

		$inst_params = array(
			'host'=>$mysql_host,
			'db_name'=>$db_name,
			'mysql_user'=>$mysql_user,
			'mysql_password'=>$mysql_password,
			'time_zone'=>$time_zone
		);

		Install_Crypt::create()->encrypt_to_file(PATH_INSTALL.'/installer_files/temp/params.dat', $inst_params, _post('install_key'));
	}

	function validate_system_configuration()
	{
		validate_config_information();
		validate_urls();
		validate_image_magick();
		validate_permissions();
		validate_encryption_key(false, true);

		$params = array('ec'=>trim(_post('encryption_key')));
		Install_Crypt::create()->encrypt_to_file(PATH_INSTALL.'/installer_files/temp/params9.dat', $params, _post('install_key'));
	}

	function validate_user_accounts()
	{
		try 
		{
			validate_admin_user();
		} catch (Exception $ex)
		{
			$ex->field = 'admin_'.$ex->field;
			throw $ex;
		}

		$original_password = _post('password');
		$original_password_confirm = _post('password_confirm');

		try
		{
			$_POST['password'] = _post('config_password');
			$_POST['password_confirm'] = _post('config_password_confirm');

			validate_config_user();
		} catch (Exception $ex)
		{
			$_POST['password'] = $original_password;
			$_POST['password_confirm'] = $original_password_confirm;

			if ($ex->field !== 'config_username')
				$ex->field = 'config_'.$ex->field;

			throw $ex;
		}
	}
	
	function validate_urls()
	{
		$backend_url = mb_strtolower(trim(_post('backend_url')));
		$config_url = mb_strtolower(trim(_post('config_url')));
		
		if (!strlen($backend_url))
			throw new ValidationException('Please specify a URL key which you will use to access the Administration Area.', 'backend_url');

		if (!strlen($config_url))
			throw new ValidationException('Please specify a URL key which you will use to access the Configuration Tool.', 'config_url');

		if (!preg_match('/^[0-9a-z_]+$/i', $backend_url))
			throw new ValidationException('URL keys can contain only Latin characters, digits and underscore characters.', 'backend_url');

		if (!preg_match('/^[0-9a-z_]+$/i', $config_url))
			throw new ValidationException('URL keys can contain only Latin characters, digits and underscore characters.', 'config_url');
			
		$invalid_urls = array('config', 'modules', 'resources', 'uploaded', 'controllers', 'init', 'handlers', 'logs', 'phproad', 'temp');
		foreach ($invalid_urls as $invalid_url)
		{
			if ($invalid_url == $backend_url)
				throw new ValidationException('Please do not use the following words as URL keys: '.implode(', ', $invalid_urls), 'backend_url');

			if ($invalid_url == $config_url)
				throw new ValidationException('Please do not use the following words as URL keys: '.implode(', ', $invalid_urls), 'config_url');
		}
			
		$params = array(
			'backend_url'=>$backend_url,
			'config_url'=>$config_url
		);

		Install_Crypt::create()->encrypt_to_file(PATH_INSTALL.'/installer_files/temp/params5.dat', $params, _post('install_key'));
	}
	
	function validate_image_magick()
	{
		$enable_im = _post('enable_im');
		$convert_path = null;
		if ($enable_im)
		{
			$convert_path = trim(_post('convert_path'));
			if (strlen($convert_path))
			{
				$convert_path = str_replace("\\", "/", $convert_path);

				if (substr($convert_path, -8) == '/convert')
				{
					if (!file_exists($convert_path))
						throw new ValidationException( 'The convert script not found at the specified location.', 'convert_path' );

					$convert_path = substr($convert_path, 0, -8);
				} else 
				{
					if (substr($convert_path, -1) == '/')
						$convert_path = substr($convert_path, 0, -1);

					$test_path = $convert_path.'/convert';
					if (!file_exists($test_path))
						throw new ValidationException( 'The convert script not found at the specified location.', 'convert_path' );
				}
			}
		}
		
		$params = array(
			'enable_im'=>$enable_im ? 1 : 0,
			'convert_path'=>$convert_path
		);

		Install_Crypt::create()->encrypt_to_file(PATH_INSTALL.'/installer_files/temp/params6.dat', $params, _post('install_key'));
	}
	
	function validate_admin_user()
	{
		$firstname = trim(_post('firstname'));
		$lastname = trim(_post('lastname'));
		$email = trim(_post('email'));
		
		$username = trim(_post('username'));
		$password = trim(_post('password'));
		$confirm = trim(_post('password_confirm'));

		if (!strlen($firstname))
			throw new ValidationException('Please specify a user first name', 'firstname');

		if (!strlen($lastname))
			throw new ValidationException('Please specify a user last name', 'lastname');

		if (!strlen($email))
			throw new ValidationException('Please specify a user email address', 'email');
			
		if (!preg_match("/^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*(\.[a-zA-Z]{2,6})$/", $email))
			throw new ValidationException('Please specify a valid email address', 'email');

		if (!strlen($username))
			throw new ValidationException('Please specify a user name', 'username');

		if (!strlen($password))
			throw new ValidationException('Please specify a password', 'password');

		if (!strlen($confirm))
			throw new ValidationException('Please specify a password confirmation', 'password_confirm');
			
		if ($password != $confirm)
			throw new ValidationException('The confirmation password does not match the password.', 'password_confirm');

		$params = array(
			'login'=>$username,
			'password'=>$password,
			'firstname'=>$firstname,
			'lastname'=>$lastname,
			'email'=>$email
		);

		Install_Crypt::create()->encrypt_to_file(PATH_INSTALL.'/installer_files/temp/params2.dat', $params, _post('install_key'));
	}
	
	function validate_permissions()
	{
		$folder_mask = trim(_post('folder_mask'));
		$file_mask = trim(_post('file_mask'));

		if (!strlen($folder_mask))
			throw new ValidationException('Please specify folder permission mask', 'folder_mask');

		if (!strlen($file_mask))
			throw new ValidationException('Please specify user last name', 'file_mask');

		if (!preg_match("/^[0-9]{3}$/", $folder_mask) || $folder_mask > 777)
			throw new ValidationException('Please specify a valid folder permission mask', 'folder_mask');

		if (!preg_match("/^[0-9]{3}$/", $file_mask) || $file_mask > 777)
			throw new ValidationException('Please specify a valid file permission mask', 'file_mask');

		$params = array(
			'folder_mask'=>$folder_mask,
			'file_mask'=>$file_mask
		);

		Install_Crypt::create()->encrypt_to_file(PATH_INSTALL.'/installer_files/temp/params8.dat', $params, _post('install_key'));
	}
	
	function validate_config_user()
	{
		$username = trim(_post('config_username'));
		$password = trim(_post('password'));
		$confirm = trim(_post('password_confirm'));

		if (!strlen($username))
			throw new ValidationException('Please specify user name', 'config_username');

		if (!strlen($password))
			throw new ValidationException('Please specify password', 'password');

		if (!strlen($confirm))
			throw new ValidationException('Please specify password confirmation', 'password_confirm');
			
		if ($password != $confirm)
			throw new ValidationException('The confirmation password does not match the password.', 'password_confirm');

		$params = array(
			'login'=>$username,
			'password'=>$password
		);

		Install_Crypt::create()->encrypt_to_file(PATH_INSTALL.'/installer_files/temp/params3.dat', $params, _post('install_key'));
	}
	
	function validate_encryption_key($cli_mode = false, $validation = false)
	{
		global $APP_CONF;
		global $Phpr_NoSession;
		global $Phpr_DisableEvents;
		
		$cli_mode_permissions = 0777;

		if ($cli_mode || $validation)
		{
			$enc_key = trim(_post('encryption_key'));
			$confirmation = trim(_post('confirmation'));

			if (!strlen($enc_key))
				throw new ValidationException('Please specify encryption key', 'encryption_key');
				
			if (strlen($enc_key) < 6)
				throw new ValidationException('The encryption key should be at least 6 characters in length.', 'encryption_key');
				
			if (!strlen($confirmation))
				throw new ValidationException('Please specify encryption key confirmation', 'confirmation');
				
			if ($enc_key != $confirmation)
				throw new ValidationException('The confirmation encryption key does not match the encryption key.', 'confirmation');

			if ($validation)
				return;
		} else 
		{
			$crypt = Install_Crypt::create();
			$ec_params = $crypt->decrypt_from_file(PATH_INSTALL.'/installer_files/temp/params9.dat', _post('install_key'));
			$enc_key = $ec_params['ec'];
		}

		if ($cli_mode)
		{
			cli_print_line('');
			cli_print_line("INSTALLING LEMONSTAND...");
		}
		
		/*
		 * Find existing .htaccess file and check whether it defines the PHP5 handler
		 */

		$php5_handler = null;
		$original_htaccess_contents = null;
		if (file_exists(PATH_INSTALL.'/.htaccess'))
		{
			$original_htaccess_contents = $ht_contents = file_get_contents(PATH_INSTALL.'/.htaccess');
			$matches = array();
			if (preg_match('/AddHandler\s+(.*)\s+\.php/im', $ht_contents, $matches))
				$php5_handler = trim($matches[0]);
		}

		/*
		 * Install the application
		 */

		require PATH_INSTALL.'/installer_files/libs/ziphelper.php';

		try
		{
			$crypt = Install_Crypt::create();
			$permission_params = $crypt->decrypt_from_file(PATH_INSTALL.'/installer_files/temp/params8.dat', _post('install_key'));

			$file_permissions_octal = '0'.$permission_params['file_mask'];
			$folder_permissions_octal = '0'.$permission_params['folder_mask'];

			$file_permissions = eval('return '.$file_permissions_octal.';');
			$folder_permissions = eval('return '.$folder_permissions_octal.';');

			$path = PATH_INSTALL.'/installer_files/temp';
			$d = dir($path);
			while ( false !== ($entry = $d->read()) ) 
			{
				$file_path = $path.'/'.$entry;

				if ($entry == '.' || $entry == '..' || is_dir($file_path) || substr($file_path, -4) != '.arc')
					continue;

				$zip_file_permissions = $cli_mode ? null : $file_permissions;
				$zip_folder_permissions = $cli_mode ? null : $folder_permissions;
				ZipHelper::unzip(PATH_INSTALL, $file_path, $zip_file_permissions, $zip_folder_permissions);
			}

			$d->close();

			$dir_permissions = $cli_mode ? $cli_mode_permissions : $folder_permissions;
			set_dir_access(PATH_INSTALL.'/temp', $dir_permissions);
			if ($cli_mode)
				@chmod(PATH_INSTALL.'/temp', $cli_mode_permissions);

			/*
			 * Install GIT UpdateCenter
			 */
			install_updatecenter($dir_permissions);

			/*
			 * Generate the config file
			 */

			$system_params = $crypt->decrypt_from_file(PATH_INSTALL.'/installer_files/temp/params.dat', _post('install_key'));
			$urls_params = $crypt->decrypt_from_file(PATH_INSTALL.'/installer_files/temp/params5.dat', _post('install_key'));
			$im_params = $crypt->decrypt_from_file(PATH_INSTALL.'/installer_files/temp/params6.dat', _post('install_key'));

			$config = file_get_contents(PATH_INSTALL.'/installer_files/config.tpl');
			$config = str_replace('%TIMEZONE%', $system_params['time_zone'], $config);
			
			$config = str_replace('%FILEPERM%', $file_permissions_octal, $config);
			$config = str_replace('%FOLDERPERM%', $folder_permissions_octal, $config);

			$config = str_replace('%ADMIN_URL%', '/'.$urls_params['backend_url'], $config);
			$config = str_replace('%CONFIG_URL%', '/'.$urls_params['config_url'], $config);

			$config = str_replace('%IM_ENABLED%', $im_params['enable_im'] ? 'true' : 'false', $config);
			$config = str_replace('%CONVERT_PATH%', $im_params['convert_path'] ? "'".$im_params['convert_path']."'" : 'null', $config);

			if (function_exists('mysqli_connect'))
				$config = str_replace('%CONFIG_DRIVER%', '$CONFIG[\'driver\'] = \'Db_MySQLi\';', $config);
			else
				$config = str_replace('%CONFIG_DRIVER%', '', $config);

			$config_path = PATH_INSTALL.'/config/config.php';
			if (!is_writable(PATH_INSTALL.'/config'))
				throw new Exception('Unable to create configuration file: '.$config_path.' - the config directory is not writable for PHP. Please try to use a less restrictive folder permission mask. You will need to empty the installation directory and restart the installer.');
			
			if (@file_put_contents($config_path, $config) === false)
				throw new Exception('Unable to create configuration file: '.$config_path);

			if (!$cli_mode)
				@chmod($config_path, $file_permissions);

			/*
			 * Generate the index.php file
			 */
			
			$index_path = PATH_INSTALL.'/index.php';
			if (!file_exists($index_path))
			{
				$index_content = file_get_contents(PATH_INSTALL.'/installer_files/index.tpl');
				if (@file_put_contents($index_path, $index_content) === false)
					throw new Exception('Unable to create index.php file: '.$index_content);

				if (!$cli_mode)
					@chmod($index_path, $file_permissions);
			}

			/*
			 * Create resource directories and files
			 */

			installer_make_dir(PATH_INSTALL.'/resources/css', $dir_permissions);
			installer_make_dir(PATH_INSTALL.'/resources/images', $dir_permissions);
			installer_make_dir(PATH_INSTALL.'/resources/javascript', $dir_permissions);
			installer_make_dir(PATH_INSTALL.'/uploaded/public', $dir_permissions);
			
			if (!@copy(PATH_INSTALL.'/installer_files/resources/css/ls_default.css', PATH_INSTALL.'/resources/css/ls_default.css'))
				throw new Exception('Unable to create file: '.PATH_INSTALL.'/resources/css/ls_default.css');

			if (!@copy(PATH_INSTALL.'/installer_files/resources/images/ls_home_logo.png', PATH_INSTALL.'/resources/images/ls_home_logo.png'))
				throw new Exception('Unable to create file: '.PATH_INSTALL.'/resources/images/ls_home_logo.png');

			if (!@copy(PATH_INSTALL.'/installer_files/default_pages/error_page.tpl', PATH_INSTALL.'/controllers/application/error_page.htm'))
				throw new Exception('Unable to create file: '.PATH_INSTALL.'/controllers/application/error_page.htm');

			@chmod(PATH_INSTALL.'/resources/css/ls_default.css', $dir_permissions);
			@chmod(PATH_INSTALL.'/resources/images/ls_home_logo.png', $dir_permissions);
			@chmod(PATH_INSTALL.'/controllers/application/error_page.htm', $dir_permissions);

			if ($cli_mode)
			{
				@chmod(PATH_INSTALL.'/resources', $cli_mode_permissions);
				
				if (file_exists(PATH_INSTALL.'/uploaded'))
					@chmod(PATH_INSTALL.'/uploaded', $cli_mode_permissions);

				if (file_exists(PATH_INSTALL.'/uploaded/thumbnails'))
					@chmod(PATH_INSTALL.'/uploaded/thumbnails', $cli_mode_permissions);

				if (file_exists(PATH_INSTALL.'/logs'))
					@chmod(PATH_INSTALL.'/logs', $cli_mode_permissions);
			}

			/*
			 * Create database objects
			 */
			
			$APP_CONF = array();

			$Phpr_InitOnly = true;
			$Phpr_DisableEvents = true;

			if ($cli_mode)
			{
				$APP_CONF['ERROR_LOG_FILE'] = PATH_INSTALL.'/logs/install_errors.txt';
				$Phpr_NoSession = true;
			}

			include PATH_INSTALL.'/index.php';
			Backend::$events->events_disabled = true;

			$config_user_params = $crypt->decrypt_from_file(PATH_INSTALL.'/installer_files/temp/params3.dat', _post('install_key'));
			$admin_user_params = $crypt->decrypt_from_file(PATH_INSTALL.'/installer_files/temp/params2.dat', _post('install_key'));
			$install_hashes = $crypt->decrypt_from_file(PATH_INSTALL.'/installer_files/temp/params4.dat', _post('install_key'));
			$import_theme = $crypt->decrypt_from_file(PATH_INSTALL.'/installer_files/temp/params7.dat', _post('install_key'));
			$import_demo_data = $crypt->decrypt_from_file(PATH_INSTALL.'/installer_files/temp/params10.dat', _post('install_key'));
			
			$eula = $crypt->decrypt_from_file(PATH_INSTALL.'/installer_files/temp/eula.dat', '');
			$import_theme_twig = $import_theme['import_default_theme_twig'];
			$import_theme = $import_theme['import_default_theme'];

			if (!$cli_mode)
			{
				$delete_files_data = $crypt->decrypt_from_file(PATH_INSTALL.'/installer_files/temp/params0.dat', _post('install_key'));
				$delete_files_on_install = $delete_files_data['delete_install_files'];
			} else
				$delete_files_on_install = false;

			$license_hash = $install_hashes['hash'];
			$license_key = $install_hashes['key'];

			$framework = Phpr_SecurityFramework::create();

			$config_content = array(
				'config_user'=>$config_user_params['login'],
				'config_pwd'=>$framework->salted_hash($config_user_params['password'], $enc_key),
				'config_key'=>$enc_key
			);
			
			$framework->set_config_content($config_content);
			$framework->reset_instance();

			$db_params = array(
				'host'=>$system_params['host'],
				'database'=>$system_params['db_name'],
				'user'=>$system_params['mysql_user'],
				'password'=>$system_params['mysql_password'],
			);

			Db_SecureSettings::set($db_params);
			
			$framework = Phpr_SecurityFramework::create()->reset_instance();

			Db_UpdateManager::update();
			Db_ModuleParameters::set('core', 'enc_test', Phpr_SecurityFramework::create()->salted_hash('lemonstand', $enc_key));
			Db_ModuleParameters::set('core', 'hash', base64_encode($framework->encrypt($license_hash)));
			Db_ModuleParameters::set('core', 'license_key', $license_key);

			/*
			 * Configure for GITHUB updates
			 */
			configure_update_center();

			/*
			 * Create administrator account
			 */
			
			$user = new Users_User();
			$user->firstName = $admin_user_params['firstname'];
			$user->lastName = $admin_user_params['lastname'];
			$user->email = $admin_user_params['email'];
			$user->login = $admin_user_params['login'];
			$user->password = $admin_user_params['password'];
			$user->password_confirm = $admin_user_params['password'];
			$user->shop_role_id = 1;

			$user->save();
			
			Db_DbHelper::query("insert into groups_users(user_id, group_id) values(LAST_INSERT_ID(), (select id from groups where code='administrator'))");
			
			/*
			 * Create default pages
			 */
			
			$home_page = new Cms_Page();
			$home_page->title = 'Welcome to the LemonStand eCommerce system!';
			$home_page->label = 'The home page';
			$home_page->url = '/';
			$home_page->content = file_get_contents(PATH_INSTALL.'/installer_files/default_pages/index.tpl');
			$home_page->created_user_id = 1;
			$home_page->action_reference = 'Custom';
			$home_page->security_mode_id = 'everyone';
			$home_page->protocol = 'any';
			$home_page->navigation_visible = 1;
			$home_page->save();

			$not_found_page = new Cms_Page();
			$not_found_page->title = 'Page not found!';
			$not_found_page->label = 'The 404 page';
			$not_found_page->url = '/404';
			$not_found_page->content = file_get_contents(PATH_INSTALL.'/installer_files/default_pages/404.tpl');
			$not_found_page->created_user_id = 1;
			$not_found_page->action_reference = 'Custom';
			$not_found_page->security_mode_id = 'everyone';
			$not_found_page->protocol = 'any';
			$not_found_page->navigation_visible = 1;
			$not_found_page->save();
			
			/*
			 * Enable theming
			 */
			
			if (!$import_theme && !$import_theme_twig)
				$theme = Cms_Theme::create()->enable_theming(array('name'=>'Default', 'code'=>'default', 'templating_engine'=>'php'));

			/*
			 * Import the demo themes
			 */

			if ($import_theme || $import_theme_twig)
			{
				Db_UpdateManager::resetCache();
				try
				{
					$php_theme = null;
					if ($import_theme) 
					{
						$php_theme = Cms_Theme::create()->enable_theming(array('name'=>'Zest (PHP)', 'code'=>'zest-php', 'templating_engine'=>'php'));
						Cms_ExportManager::create()->import(PATH_INSTALL.'/installer_files/demo_website_pages.lca', $php_theme->id);
					}

					if ($import_theme_twig)
					{
						$twig_params = array('name'=>'Zest (Twig)', 'code'=>'zest-twig', 'templating_engine'=>'twig');

						if (!$import_theme)
							$theme = Cms_Theme::create()->enable_theming($twig_params);
						else {
							$theme = Cms_Theme::create();
							$theme->init_columns_info();
							$theme->save($twig_params);
						}

						Cms_ExportManager::create()->import(PATH_INSTALL.'/installer_files/demo_website_pages_twig.lca', $theme->id);
					}

					if ($php_theme)
						$php_theme->make_default();
				} catch (Exception $ex){}
			}

			/*
			 * Create demo data
			 */

			if ($import_demo_data['import_demo_data'])
				installer_create_demo_data();

			/*
			 * Set the default back-end color theme
			 */

			$theme_params = System_ColorThemeParams::get();
			$theme_params->theme_id = 'greymatter-blue';
			$theme_params->save();
			
			/*
			 * Push EULA data
			 */

			Db_ModuleParameters::set('core', 'lei', array('v'=>$eula['version'], 'c'=>$eula['content']));

			Core_EulaManager::commit($admin_user_params['login'], $admin_user_params['firstname'], $admin_user_params['lastname']);

			/*
			 * Create .htaccess file
			 */
			
			if (!@copy(PATH_INSTALL.'/installer_files/htaccess.tpl', PATH_INSTALL.'/.htaccess'))
				throw new Exception('Unable to create the .htaccess file: '.PATH_INSTALL.'/.htaccess');
				
			if ($php5_handler)
			{
				$ht_contents = file_get_contents(PATH_INSTALL.'/.htaccess');
				$ht_contents = $php5_handler."\n\n".$ht_contents;
				@file_put_contents(PATH_INSTALL.'/.htaccess', $ht_contents);
			}

			/*
			 * Finalize installation
			 */

			install_cleanup();

			return $delete_files_on_install;
		}
		catch (Exception $ex)
		{
			$ht_file = PATH_INSTALL.'/.htaccess';
			if (file_exists($ht_file))
				@unlink($ht_file);

			if ($original_htaccess_contents)
				@file_put_contents($ht_file, $original_htaccess_contents);
		
			throw $ex;
		}
	}
	
	function installer_make_dir($path, $permissions)
	{
		if (!file_exists($path))
			@mkdir($path);
		
		@chmod($path, $permissions);
	}
	
	function installer_remove_dir($sDir) 
	{
		if (is_dir($sDir)) 
		{
			$sDir = rtrim($sDir, '/');
			$oDir = dir($sDir);
			
			while (($sFile = $oDir->read()) !== false) 
			{
				if ($sFile != '.' && $sFile != '..') 
					(!is_link("$sDir/$sFile") && is_dir("$sDir/$sFile")) ? installer_remove_dir("$sDir/$sFile") : @unlink("$sDir/$sFile");
			}
			$oDir->close();
			
			@rmdir($sDir);
			return true;
		}

		return false;
	}
	
	function memory_str_to_bytes($val) 
	{
		$val = trim($val);
		$last = strtolower($val[strlen($val)-1]);
		switch($last) 
		{
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}

		return $val;
	}
	
	function check_requirements()
	{
		$result = array();
		
		$result['PHP 5.2.5 or higher'] = version_compare(PHP_VERSION , "5.2.5", ">=");
		$result['PHP CURL library'] = function_exists('curl_init');
		$result['PHP OpenSSL library'] = function_exists('openssl_open');
		$result['PHP Mcrypt library'] = function_exists('mcrypt_encrypt');
		$result['PHP MySQL functions'] = function_exists('mysql_connect') || function_exists('mysqli_connect');
		$result['PHP Multibyte String functions'] = function_exists('mb_convert_encoding');
		$result['Short PHP tags allowed'] = ini_get('short_open_tag');

		$result['Permissions for PHP to write to the installation directory'] = is_writable(PATH_INSTALL);

		if (ini_get('safe_mode'))
			$result['PHP Safe Mode detected '] = false;
		
		return $result;
	}
	
	function check_optionals()
	{
		$result = array();

		$result['PHP SOAP library - required for some payment gateways, specifically for the E-xact Web Service'] = class_exists('SoapClient');

		return $result;
	}
	
	function check_warnings()
	{
		$result = array();
		
		$mem_limit = ini_get('memory_limit');
		if (strlen($mem_limit))
		{
			$mem_limit_bytes = memory_str_to_bytes($mem_limit);
			if ($mem_limit_bytes < 134217728)
				$result['Actual memory limit value ('.$mem_limit.') is lower than the recommended memory limit - 128M.'] = 'While your installation could function normally, we strongly recommend your system settings be updated to maintain system stability.';
		}

		return $result;
	}
	
	function gen_install_key()
	{
		$letters = 'abcdefghijklmnopqrstuvwxyz';
		$result = null;
		for ($i = 1; $i <= 6; $i++)
			$result .= $letters[rand(0,25)];

		return md5($result.time());
	}
	
	function install_cleanup()
	{
		$path = PATH_INSTALL.'/installer_files/temp';
		if (!file_exists($path))
			return;

		$d = dir($path);
		while ( false !== ($entry = $d->read()) ) 
		{
			$file_path = $path.'/'.$entry;
			
			if ($entry == '.' || $entry == '..' || $entry == '.htaccess' || is_dir($file_path))
				continue;

			@unlink($file_path);
		}

		$d->close();
	}

	function set_dir_access($path, $permissions)
	{
		$path .= '/.htaccess';
		
		$data = "order deny,allow\ndeny from all";
		@file_put_contents($path, $data);
		
		@chmod($path, $permissions);
	}

	function output_install_page()
	{
		try
		{
			show_installer_step();
		} catch (Exception $ex)
		{
			install_cleanup();
			render_partial('exception', array('exception'=>$ex));
		}
	}

	function installer_create_products_from_json($json_str, $category, $group)
	{
		$products = json_decode($json_str);
		$tax_class_id = Shop_TaxClass::get_default_class_id();
		$product_type = Shop_ProductType::get_default_type();
		$group_session_id = microtime(true) + 'gr';
		
		foreach ($products as $index=>$product_data) 
		{
			$plain_data = array();
			$images = $product_data->images;
			foreach ($product_data as $field=>$value)
			{
				if (!is_array($value) && !is_object($value))
					$plain_data[$field] = $value;
			}
	
			$session_id = $index + microtime(true);
		
			$test_product = new Shop_Product();
			$test_product->init_columns_info();
			$test_product->tax_class_id = $tax_class_id;
			$test_product->product_type_id = $product_type->id;
			$test_product->categories = array($category);
			
			foreach ($images as $image)
			{
				$file = Db_File::create();
				$file->is_public = true;
				
				$file->fromFile(PATH_INSTALL.'/installer_files/demo_data/'.$image);

				$file->master_object_class = 'Shop_Product';
				$file->field = 'images';
				$file->save();
				$test_product->images->add($file, $session_id);
			}
			
			$test_product->save($plain_data, $session_id);
			
			$group->all_products->add($test_product, $group_session_id);
		}
		
		$group->save(null, $group_session_id);
	}
	
	function installer_create_posts_from_json($json_str, $category)
	{
		$posts = json_decode($json_str);
		$date = Phpr_Date::userDate(Phpr_DateTime::gmtNow());
		foreach ($posts as $index=>$post_data)
		{
			$post = Blog_Post::create();
			$plain_data = array();
			foreach ($post_data as $field=>$value)
			{
				if (!is_array($value) && !is_object($value))
					$plain_data[$field] = $value;
			}
			
			if (isset($plain_data['content']))
			{
				if (cli_detect())
					$plain_data['content'] = preg_replace("/<img[^>]+\>/i", "", $plain_data['content']); 
				else
					$plain_data['content'] = str_replace('{site_url}', installer_root_url('/'), $plain_data['content']);
			}

			$session_id = $index + microtime(true);
			
			$post->init_columns_info();
			$post->is_published = true;
			$post->published_date = $date;
			$post->categories = array($category);
			$post->save($plain_data, $session_id);
		}
	}

	function installer_create_demo_data()
	{
		$blog_files = array('helmet-small.jpg', 'glove-small.jpg');

		foreach ($blog_files as $file)
		{
			if (!@copy(PATH_INSTALL.'/installer_files/demo_data/'.$file, PATH_INSTALL.'/resources/images/'.$file))
				throw new Exception('Unable to create file: '.PATH_INSTALL.'/resources/images/'.$file);
		}

		$category = Shop_Category::create();
		$category->name = "Sporting goods";
		$category->url_name = "sporting-goods";
		$category->save();
		
		$group = Shop_CustomGroup::create();
		$group->name = 'Featured products';
		$group->code = 'featured_products';
		$group->save();

		$products_json = file_get_contents(PATH_INSTALL.'/installer_files/demo_data/products.json');
		installer_create_products_from_json($products_json, $category, $group);

		$category = Blog_Category::create();
		$category->name = "News";
		$category->url_name = "news";
		$category->save();

		$blog_json = file_get_contents(PATH_INSTALL.'/installer_files/demo_data/posts.json');
		installer_create_posts_from_json($blog_json, $category);
	}

	function installer_connect_mysql($mysql_host, $mysql_user, $mysql_password)
	{
		if (function_exists('mysqli_connect'))
			return @mysqli_connect($mysql_host, $mysql_user, $mysql_password);

		return @mysql_pconnect($mysql_host, $mysql_user, $mysql_password);
	}

	function installer_select_db($db_link, $db_name)
	{
		if (function_exists('mysqli_select_db'))
			return mysqli_select_db($db_link, $db_name);

		return mysql_select_db($db_name);
	}

	function installer_get_table_num($db_link, $db_name)
	{
		if (function_exists('mysqli_connect')) {
			$result = @mysqli_query($db_link, "SHOW TABLES"); 
			$num = @mysqli_num_rows($result);
			@mysqli_free_result($result);

			return $num;
		}

		$result = @mysql_list_tables( $db_name );
		$num = @mysql_num_rows($result);
		@mysql_free_result($result);

		return $num;
	}

	function installer_get_mysql_error($db_link)
	{
		if (function_exists('mysqli_error'))
			return mysqli_error($db_link);

		return mysql_error();
	}

	/*
	 * Command line interface functions
	 */

	function cli_detect()
	{
		$sapi = php_sapi_name();
		
		if ($sapi == 'cli')
			return true;
		
		if (array_key_exists('SHELL', $_SERVER) && strlen($_SERVER['SHELL']))
			return true;
			
		if (!array_key_exists('DOCUMENT_ROOT', $_SERVER) || !strlen($_SERVER['DOCUMENT_ROOT']))
			return true;

		return false;
	}
	
	function cli_install()
	{
		cli_print_line("");
		cli_print_line('WELCOME TO LEMONSTAND INSTALLATION!');
		cli_print_line("");

		cli_eula();
		cli_system_requirements();
		cli_license_information();
		cli_db_parameters();
		cli_admin_urls();
		cli_image_magick();
		cli_permissions();
		cli_admin_user();
		cli_config_user();
		cli_default_theme();

		if (!cli_encryption_key())
		{
			cli_print_line("Exiting the installer.");
			install_cleanup();
			exit(0);
		}
		
		cli_complete();
		
		install_cleanup();
		exit(0);
	}
	
	function cli_eula()
	{
		cli_print_line("Loading End User License Agreement...");
		cli_print_line("");
		
		try
		{
			$eula_text = installer_get_eula_text();
			cli_print_line("PLEASE READ THE FOLLOWING LICENSE AGREEMENT.");
			cli_print_line("");
			
			$lines = explode("\n", $eula_text);
			foreach ($lines as $line)
				cli_print_line(wordwrap($line, 80, "\n"));
			
			cli_print_line("");
			cli_print_line("");
		}
		catch (exception $ex)
		{
			cli_print_error($ex->getMessage());
			install_cleanup();
			exit(0);
		}
		
		$agree = cli_read_bool_option('I AGREE WITH ALL THE TERMS OF THE LICENSE AGREEMENT [Y/N]: ');
		if (!$agree)
		{
			cli_print_line('You must agree to the License Agreement to continue.');
			install_cleanup();
			exit(0);
		}
	}
	
	function cli_system_requirements()
	{
		cli_print_line("Checking the system requirements...");
		$requrements = check_requirements();
		$success = true;
		foreach ($requrements as $name=>$value)
		{
			cli_print_line("   ".$name.': '.($value ? 'OK' : 'FAILED'));
			
			if (!$value)
				$success = false;
		}
		
		$warnings = check_warnings();
		if ($warnings)
		{
			cli_print_line('');
			foreach ($warnings as $name=>$description)
				cli_print_line("   WARNING: ".$name.' '.$description);
		}
		
		if (!$success)
		{
			cli_print_line('');
			cli_print_line('We are sorry. Your system does not meet the minimum requirements for the installation.');
			exit(0);
		} else
		{
			cli_print_line('');
			cli_print_line('Congratulations! Your system met the requirements.');
			cli_print_line('');
		}
	}
	
	function cli_license_information()
	{
		cli_print_line('STEP 1 of 9: License Information');

		$success = false;
		
		while (!$success)
		{
			$_POST['holder_name'] = cli_read_option('License holder name: ', 'Please specify the license holder name');
			$_POST['serial_number'] = cli_read_option('Serial number: ', 'Please specify the serial number');
			
			try
			{
				cli_print_line("Validating the license information. The operation could take several minutes...");
				validate_license_information();
				$success = true;
			} catch (Exception $ex)
			{
				cli_print_error($ex->getMessage());
				cli_print_line("Please try again.");
			}
		}
		
		cli_print_line('');
	}
	
	function cli_db_parameters()
	{
		cli_print_line('STEP 2 of 9: Database Connection Parameters');
		cli_print_line('Please prepare an empty MySQL database for LemonStand.');
		cli_print_line('A MySQL user which you will specify in this step must have all privileges in the specified database.');

		$success = false;

		$time_zones = timezone_identifiers_list();
		if (in_array('US/Central', $time_zones))
			$_POST['time_zone'] = 'US/Central';
		else
			$_POST['time_zone'] = 'America/New_York';

		while (!$success)
		{
			$_POST['mysql_host'] = cli_read_option('MySQL Host: ', 'Please specify MySQL host');
			$_POST['db_name'] = cli_read_option('Database Name: ', 'Please specify a database name');
			$_POST['mysql_user'] = cli_read_option('MySQL User: ');
			$_POST['mysql_password'] = cli_read_option('MySQL Password: ');
			
			try
			{
				validate_config_information();
				$success = true;
			} catch (Exception $ex)
			{
				cli_print_error($ex->getMessage());
			}
		}
		
		cli_print_line('');
	}
	
	function cli_admin_urls()
	{
		$red = "\033[31m";
		$no_color ="\033[0m";
		
		cli_print_line('STEP 3 of 9: Administration URLs');
		cli_print_line('There are two special URLs in LemonStand: the Administration Area URL and the Configuration Tool URL. The Administration Area is a back-end user interface which you will use for building the website and managing your online store. The Configuration Tool is a web interface for managing the system configuration - the database connection parameters and encryption.');
		cli_print_line('');
		cli_print_line('By default the URLs of the Administration Area and the Configuration Tool are http://your-host-name/'.$red.'backdoor'.$no_color.' and http://your-host-name/'.$red.'config_tool'.$no_color.'. It is recommended to choose URL keys other than the default values ("backdoor" and "config_tool").');
		cli_print_line('');
		cli_print_line('URL keys can contain only Latin characters, digits and underscore characters. Should you change the URL keys after the installation, you can do it by correcting the config/config.php file.');
		
		$success = false;
		
		while (!$success)
		{
			$_POST['backend_url'] = cli_read_option('Administration Area URL key: ', 'Please specify a URL key which you will use to access the Administration Area');
			$_POST['config_url'] = cli_read_option('Configuration Tool URL key: ', 'Please specify a URL key which you will use to access the Configuration Tool');
			
			try
			{
				validate_urls();
				$success = true;
			} catch (Exception $ex)
			{
				cli_print_error($ex->getMessage());
			}
		}

		cli_print_line('');
	}
	
	function cli_image_magick()
	{
		cli_print_line('STEP 4 of 9: ImageMagick Configuration');
		cli_print_line('ImageMagick is an image processing library which dramatically increases quality of thumbnails and product images in LemonStand. If ImageMagick is not available, the default PHP image processing functions will be used.');

		$_POST['enable_im'] = $im_available = cli_read_bool_option('ImageMagick is available [Y/N]: ') ? 1 : 0;

		while (!$success)
		{
			try
			{
				if ($im_available)
					$_POST['convert_path'] = cli_read_option('Path to the convert script (leave empty if the convert script is accessible from anywhere): ');
				
				validate_image_magick();
				$success = true;
			} catch (Exception $ex)
			{
				cli_print_error($ex->getMessage());
			}
		}

		cli_print_line('');
	}
	
	function cli_permissions()
	{
		$red = "\033[31m";
		$no_color ="\033[0m";

		cli_print_line('STEP 5 of 9: Folder and File permissions');
		cli_print_line('Please specify permission masks for folders and files which LemonStand will create in the "resources" and other directories. The default permission value (777) is the most universal, but at the same time it provides less protection for your files. You may need to consult with your system administrator or hosting support team in order to find suitable permission masks for a web application. You can change the permission masks after the installation in the config/config.php file.');
		
		cli_print_line('Leave the fields empty for the default value ('.$red.'777'.$no_color.') or specify permission masks as a number, for example 777 or 755');

		$success = false;
		
		while (!$success)
		{
			$_POST['folder_mask'] = cli_read_option('Folder permission mask (777 by default): ');
			$_POST['file_mask'] = cli_read_option('File permission mask (777 by default): ');
			
			if (!strlen($_POST['folder_mask']))
				$_POST['folder_mask'] = 777;

			if (!strlen($_POST['file_mask']))
				$_POST['file_mask'] = 777;
			
			try
			{
				validate_permissions();
				$success = true;
			} catch (Exception $ex)
			{
				cli_print_error($ex->getMessage());
			}
		}
		
		cli_print_line('');
	}

	function cli_admin_user()
	{
		cli_print_line('STEP 6 of 9: Administrator Account');
		cli_print_line('Please remember the user name and password you enter here. You will need this information to log into the LemonStand administration area after the installation.');

		$success = false;
		
		while (!$success)
		{
			$_POST['firstname'] = cli_read_option('First Name: ', 'Please specify user first name');
			$_POST['lastname'] = cli_read_option('Last Name: ', 'Please specify user last name');
			$_POST['email'] = cli_read_option('Email: ', 'Please specify email address');
			$_POST['username'] = cli_read_option('User Name: ', 'Please specify user name');
			$_POST['password'] = cli_read_option('Password: ', 'Please specify user password');
			$_POST['password_confirm'] = cli_read_option('Password Confirmation: ', 'Please specify password confirmation');
			
			try
			{
				validate_admin_user();
				$success = true;
			} catch (Exception $ex)
			{
				cli_print_error($ex->getMessage());
			}
		}
		
		cli_print_line('');
	}
	
	function cli_config_user()
	{
		cli_print_line('STEP 7 of 9: Configuration Tool User Account');
		cli_print_line('Specify a user name (login) and password for the LemonStand Configuration Tool account. Please remember the user name and password you enter here. You will need this information to log into the LemonStand Configuration Tool if you want to change the database connection parameters or encryption key. You can change the Configuration Tool user name and password later.');

		$success = false;
		
		while (!$success)
		{
			$_POST['config_username'] = cli_read_option('User Name: ', 'Please specify user name');
			$_POST['password'] = cli_read_option('Password: ', 'Please specify user password');
			$_POST['password_confirm'] = cli_read_option('Password Confirmation: ', 'Please specify password confirmation');
			
			try
			{
				validate_config_user();
				$success = true;
			} catch (Exception $ex)
			{
				cli_print_error($ex->getMessage());
			}
		}

		cli_print_line('');
	}
	
	function cli_default_theme()
	{
		cli_print_line('STEP 8 of 9: Themes and Demo Data');
		cli_print_line('Import Zest, a clean, responsive LemonStand theme');
		cli_print_line('You can import two versions of the Zest theme - PHP-based and Twig based. Twig is the alternative LemonStand templating engine. It is simpler and less verbose than PHP and more suitable for designers.');
		
		$_POST['import_default_theme'] = cli_read_bool_option('Import PHP version the Zest theme [Y/N]: ') ? 1 : 0;
		$_POST['import_default_theme_twig'] = cli_read_bool_option('Import Twig version the Zest theme [Y/N]: ') ? 1 : 0;
		save_default_theme_flag();

		cli_print_line('You can also import demo products, categories and blog posts to play with the system before you enter your own data. It is easy to delete demo products, categories and blog posts from the Administration Area.');
		$_POST['import_demo_data'] = cli_read_bool_option('Import demo data [Y/N]: ') ? 1 : 0;
		save_demo_data_flag();

		cli_print_line('');
		return true;
	}

	function cli_encryption_key()
	{
		cli_print_line('STEP 9 of 9: Encryption Key');
		cli_print_line('The encryption key is a keyword which will be used to encrypt sensitive data in the database. Please remember the encryption key, because it may be needed if you want to reinstall the application or move the database to another server. Without the encryption key it will not be possible to decrypt data and sensitive information could be lost.');
		cli_print_line('The encryption key should be at least 6 characters in length.');

		$success = false;
		
		while (!$success)
		{
			$_POST['encryption_key'] = cli_read_option('Encryption Key: ', 'Please specify encryption key');
			$_POST['confirmation'] = cli_read_option('Encryption Key Confirmation: ', 'Please specify encryption key confirmation');
			
			try
			{
				validate_encryption_key(true);
				$success = true;
			} catch (Exception $ex)
			{
				cli_print_error($ex->getMessage());
				if (!($ex instanceof ValidationException))
					return false;
			}
		}

		cli_print_line('');
		return true;
	}

	function cli_complete()
	{
		cli_print_line('Installation Complete!');
		cli_print_line('LemonStand installation has been successfully completed.');
		
		$delete_installer_files = cli_read_bool_option('Do you want the installer to delete all installation-related files? (Y/N): ');
		if ($delete_installer_files)
		{
			try
			{
				@installer_remove_dir(PATH_INSTALL.'/installer_files');
				@unlink(PATH_INSTALL.'/install.php');
			} catch (Exception $ex) {}
		}

		$files_deleted = !file_exists(PATH_INSTALL.'/installer_files') && !file_exists(PATH_INSTALL.'/install.php');
		
		if (!$files_deleted)
		{
			cli_print_warning('The installation files were not deleted. Please delete the install.php script and the installer_files directory manually.');
			cli_print_line('');
		}

		$backend_url = Phpr::$config->get('BACKEND_URL');
		cli_print_line('Now you can log into the application using the Administration Area URL (http://your-host-name.com/'.$backend_url.').');

		cli_print_line('');
		cli_print_line('TIME ZONE');
		cli_print_line('');
		cli_print_line('Currently the '.Phpr::$config->get('TIMEZONE').' time zone is specified for your LemonStand installation. You can change a time zone in the config/config.php file.');
		cli_print_line('');
		
		cli_print_line('SECURITY CONSIDERATIONS');
		cli_print_line('');
		cli_print_line('We recommend that you move the config.dat file, located in the /config directory, to another directory that is unreachable from the Internet. After moving the file, please specify its absolute path in the SECURE_CONFIG_PATH parameter in the /config/config.php file.');
		cli_print_line('');
		cli_print_line('Thank you for choosing LemonStand eCommerce software!');
	}

	function cli_print($str)
	{
		fwrite(STDOUT, $str); 
	}

	function cli_print_line($str)
	{
		fwrite(STDOUT, $str."\n"); 
	}

	function cli_print_error($str)
	{
		fwrite(STDOUT, "\033[31m"); 
		fwrite(STDOUT, 'ERROR: '.$str."\n"); 
		fwrite(STDOUT, "\033[0m");
	}

	function cli_print_warning($str)
	{
		fwrite(STDOUT, "\033[31m"); 
		fwrite(STDOUT, 'WARNING: '.$str."\n"); 
		fwrite(STDOUT, "\033[0m");
	}
	
	function cli_read_line()
	{
		return fgets(STDIN); 
	}
	
	function cli_read_option($label, $required_message = null)
	{
		while (true)
		{
			cli_print($label);
			$value = trim(cli_read_line());

			if (!strlen($value))
			{
				if ($required_message === null)
					return $value;
				else
					cli_print_line($required_message);
			}
				else return $value;
		}
	}
	
	function cli_read_bool_option($label)
	{
		while (true)
		{
			cli_print($label);
			$value = strtolower(trim(cli_read_line()));

			if (!strlen($value))
				cli_print_line("Please enter Y or N and press the Return key");
			else
			{
				if ($value == 'y')
					return true;

				if ($value == 'n')
					return false;
					
				cli_print_line("Please enter Y or N and press the Return key");
			}
		}
	}

	function install_updatecenter($dir_permissions){
		$updates_dir = PATH_INSTALL.'/installer_files/updates';
		$from_dir = $updates_dir.'/damanic-updatecenter/updatecenter';
		$to_dir = PATH_INSTALL.'/modules/updatecenter';
		recurse_copy($from_dir,$to_dir,$dir_permissions);
		recurse_copy($updates_dir.'/repositories',$to_dir.'/repositories', $dir_permissions);
	}

	function configure_update_center(){
		if(!UpdateCenter_Helper::check_core_compatible()){
			$updater = new UpdateCenter_CoreUpdate();
			$updater->make_compatible();
		}
		$config  = UpdateCenter_Config::get();
		if(!$config->has_active_repository()){
			$config->repository_config = 'damanic';
			$config->save();
		}

	}

	function recurse_copy($src,$dst,$dir_permissions) {
		$dir = opendir($src);
		if (!file_exists($dst)) installer_make_dir($dst,$dir_permissions);
		while(false !== ( $file = readdir($dir)) ) {
			if (( $file != '.' ) && ( $file != '..' )) {
				if ( is_dir($src . '/' . $file) ) {
					recurse_copy($src . '/' . $file,$dst . '/' . $file, $dir_permissions);
				}
				else {
					@copy($src . '/' . $file,$dst . '/' . $file);
					@chmod($dst . '/' . $file, $dir_permissions);

				}
			}
		}
		closedir($dir);
	}
	
?>