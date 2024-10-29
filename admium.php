<?php
/*
Plugin Name: Admium Plugin
Plugin URI: https://admium.herokuapp.com
Description: This plugin lets you manage subscriber-only content with Admium.
Version: 2.2.1
Author: Admium.net
*/


require_once 'class-wordpress-api-wrapper.php';
require_once 'class-php-wrapper.php';
require_once 'class-admium-globals.php';
require_once 'class-cookie-manager.php';
require_once 'class-token-validator.php';
require_once 'class-admium-authenticator.php';
require_once 'class-admium-admin.php';
require_once 'class-admium-plugin.php';
require_once ABSPATH . 'wp-includes/pluggable.php'; // So we can use current_user_can().


$wpapi = new WordPressAPIWrapper();
$php_wrapper = new PHPWrapper();
$admium_globals = new AdmiumGlobals($wpapi);
$cookie_manager = new CookieManager($php_wrapper, $admium_globals, $wpapi);
$token_validator = new TokenValidator($php_wrapper, $admium_globals, $wpapi);
$admium_authenticator = new AdmiumAuthenticator($token_validator, $cookie_manager, $admium_globals, $wpapi);
$admium_plugin = new AdmiumPlugin($wpapi, $php_wrapper, $admium_globals, $cookie_manager, $token_validator, $admium_authenticator);
$admium_plugin->set_hooks();

?>
