<?php

	if (!isset($CONFIG))
		$CONFIG = array();
		
	/*
	 * Backend URL prefix
	 */
	$CONFIG['BACKEND_URL'] = '%ADMIN_URL%';
	
	/*
	 * Config URL prefix
	 */
	$CONFIG['CONFIG_URL'] = '%CONFIG_URL%';
	
	if ( !defined('PATH_APP') )
		return;

	$CONFIG['DEV_MODE'] = false;

	%CONFIG_DRIVER%

	/*
	 * Tracing and error logging features
	 */
	
	$CONFIG['ERROR_LOG'] = true;
	$CONFIG['ERROR_REPORTING'] = E_ALL | E_STRICT;
	$CONFIG['ERROR_IGNORE'] = array( 'Phpr_ApplicationException' );
	$CONFIG['HIDE_ERROR_DETAILS'] = false;
	$CONFIG['LOG_TO_DB'] = true;

	if (!isset($CONFIG['TRACE_LOG']['INFO']))
		$CONFIG['TRACE_LOG']['INFO'] = PATH_APP.'/logs/info.txt';

#	$CONFIG['TRACE_LOG']['SQL'] = PATH_APP.'/logs/sql.txt';
	
	/*
	 * Redirecting and cookies
	 */

	$CONFIG['REDIRECT'] = 'location';
	$CONFIG['FRONTEND_AUTH_COOKIE_LIFETIME'] = 5;
	$CONFIG['AUTH_COOKIE_LIFETIME'] = 1;

	/*
	 * Files
	 */
	
	$CONFIG['FILESYSTEM_CODEPAGE'] = 'Windows-1251';

	/*
	 * Language
	 */
	
	$CONFIG['LANGUAGE'] = 'en';

	/*
	 * ImageMagick
	 */

	$CONFIG['IMAGEMAGICK_ENABLED'] = %IM_ENABLED%;
	$CONFIG['IMAGEMAGICK_PATH'] = %CONVERT_PATH%;
	$CONFIG['IMAGE_JPEG_QUALITY'] = 85;

	/*
	 * System time zone
	 */
	
	$CONFIG['TIMEZONE'] = '%TIMEZONE%';
	
	/*
	 * Editable file formats
	 */
	
	$CONFIG['EDITABLE_FILES'] = array('css', 'js');

	/*
	 * File and folder permissions
	 */
	
	$CONFIG['FILE_PERMISSIONS'] = %FILEPERM%;
	$CONFIG['FOLDER_PERMISSIONS'] = %FOLDERPERM%;
	
	/*
	 * URL Separator
	 */
	
	$CONFIG['URL_SEPARATOR'] = '-';
	
	/*
	 * Secure configuration file path
	 *
	 * Important! Please specify an absulute path to the secure configuration file, relative to 
	 * the system root directory. For example: '/home/someuser/secure/lemonstand_config.dat'
	 */
	
#	$CONFIG['SECURE_CONFIG_PATH'] = '/home/someuser/secure/lemonstand_config.dat';

	/*
	 * Cron access 
	 */

	$CONFIG['CRON_ALLOWED_IPS'] = array();
	
	/*
	 * Caching - see http://v1.lemonstand.com/docs/caching_api/ for details
	 */
	
#	$CONFIG['CACHING'] = array(
#		'CLASS_NAME'=> 'Core_FileCache',
#		'DISABLED' => false,
#		'PARAMS' => array(
#			'CACHE_DIR' => '/home/someuser/ls_file_cache',
#			'TTL' => 3600
#		)
#	);

	/*
	 * Grouped products are disabled in favor of Option Matrix
	 * See http://v1.lemonstand.com/docs/understanding_option_matrix/
	 */

	$CONFIG['DISABLE_GROUPED_PRODUCTS'] = true;
	$CONFIG['CACHE_SHIPPING_METHODS'] = true;
	$CONFIG['ENABLE_BACKEND_TOUR'] = true;

	$CONFIG['UPDATE_CENTER'] = 'v1.lemonstand.com/lemonstand_update_gateway';
	$CONFIG['LS_EULA_GATEWAY'] = 'https://v1.lemonstand.com/lemonstand_eula/';

?>