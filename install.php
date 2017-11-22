<?php

	$is_php4 = version_compare(PHP_VERSION, '5.0.0', '<');

	define('LS_INST_PHP4', $is_php4);

	if (!LS_INST_PHP4)
	{
		require "installer_files/libs/misc.php";

		if (cli_detect())
		{
			cli_install();
			die();
		}
	} else
	{
		$sapi = php_sapi_name();
		
		if ($sapi == 'cli'
			|| (array_key_exists('SHELL', $_SERVER) && strlen($_SERVER['SHELL']))
			|| (!array_key_exists('DOCUMENT_ROOT', $_SERVER) || !strlen($_SERVER['DOCUMENT_ROOT']))
		)
		{
			fwrite(STDOUT, "\n\nWELCOME TO THE LEMONSTAND INSTALLATION!\n\n"); 
			fwrite(STDOUT, "We detected that your server is using PHP 4. We are sorry, but LemonStand requires PHP 5.\n"); 
			fwrite(STDOUT, "To complete the installation you will need to upgrade this server to run PHP 5 and all other required libraries, or restart the installation on another server which meets all of the server requirements.\n"); 
			
			die;
		}
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
		<title>LemonStand Installer</title>
		<link rel="stylesheet" href="installer_files/resources/css/foundation.min.css" type="text/css"/>
		<link rel="stylesheet" href="installer_files/resources/css/style.css" type="text/css"/>
		<script src="installer_files/resources/javascript/jquery.js" type="text/javascript" charset="utf-8"></script>
		
		<script src="installer_files/resources/javascript/modernizr.foundation.js" type="text/javascript" charset="utf-8"></script>
		<script src="installer_files/resources/javascript/foundation.min.js" type="text/javascript" charset="utf-8"></script>
		<script src="installer_files/resources/javascript/jquery-ui-1.10.2.custom.min.js" type="text/javascript" charset="utf-8"></script>
		<script src="installer_files/resources/javascript/app.js" type="text/javascript" charset="utf-8"></script>
	</head>
	<body class="<?= installer_css_browser_selector() ?>">
		<header>
			<div class="row">
				<div class="twelve columns">
					<h1>LemonStand Installer</h1>
				</div>
			</div>
		</header>

		<div class="row">
			<?php
				if (!LS_INST_PHP4)
					output_install_page();

				if (LS_INST_PHP4):
			?>
				<div class="twelve columns">
					<h2>PHP 4 Detected</h2>

					<h3>We detected that your server is using PHP 4. We are sorry, but LemonStand requires PHP 5.</h3>
					<p>To complete the installation you will need to upgrade this server to run PHP 5 and all other required libraries, or restart the installation on another server which meets all of the server requirements.</p>
					<h4>To install LemonStand your server must meet the following requirements:</h4>

					<ul class="bullets">
						<li>PHP 5.2.5 or higher</li>
						<li>PHP CURL library</li>
						<li>PHP OpenSSL library</li>
						<li>PHP Mcrypt library</li>
						<li>PHP MySQL functions</li>
						<li>PHP Multibyte String functions</li>
						<li>Permissions for PHP to write to the installation directory</li>
					</ul>
				</div>
			<?php endif ?>
		</div>
		
		<footer>
			<div class="row">
				<div class="twelve columns">
					<p>Copyright &copy; <?= date('Y', time()) ?> <a href="http://lemonstand.com">LemonStand eCommerce Inc.</a> - All Rights Reserved</p>
				</div>
			</div>
		</footer>
	</body>
</html>