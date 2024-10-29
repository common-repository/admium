<?php

  require_once 'class-wordpress-api-wrapper.php';
  require_once 'class-admium-globals.php';

  $wpapi = new WordPressAPIWrapper();
  $admium_globals = new AdmiumGlobals($wpapi);

  if(!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
  }
   
  $wpapi->delete_option($admium_globals->call_to_subscribe_option_name());
  $wpapi->delete_option($admium_globals->not_first_view_flag_name());
  $wpapi->delete_option($admium_globals->api_token_name());
  $wpapi->delete_option($admium_globals->admium_site_id());

?>