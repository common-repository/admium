<?php
/* This file holds global variables defined and used by this plugin.
 */


class AdmiumGlobals {
  protected $wpapi;

  const VISIBILITY_ALL = 0;
  const VISIBILITY_NONSUBSCRIBER = 1;
  const VISIBILITY_SUBSCRIBER = 2;
  const VISIBILITY_SUBWITHCALL = 3;

  function AdmiumGlobals($wpapi) {
    $this->wpapi = $wpapi;
  }

  function cookie_name() { return 'admium'; }
  function test_parameter_name() { return 'test'; }

  function db_session_table_name() {
    return $this->wpapi->db_prefix() . $this->db_session_table_suffix();
  }

  function db_session_table_suffix() {
    return 'admium_sessions';
  }

  function server_url($test = false) {
    $api_version_prefix = '/v2';
    
    if ($test == true)
      return 'https://admium-test.herokuapp.com' . $api_version_prefix;

    return 'https://admium.herokuapp.com' . $api_version_prefix;
  }

  function token_name() { return 'admium_token'; }
  function token_validation_url($test = false) {
    return $this->server_url($test) . '/vwr/validate';
  }

  function popup_js_url($site_id, $test = false) {
    return $this->server_url($test) . '/sites/' . $site_id . '/js';
  }

  function call_to_subscribe_option_name() { return 'admium_call_to_subscribe'; }
  function admium_post_visibility_field_name() { return 'admium-visibility'; }

  function validation_failed_response() { return 'ADMIUM_INVALID'; }

  function default_call_to_subscribe() { return '[admium_subscribe_link]Subscribe now![/admium_subscribe_link]'; }

  function admin_page_name() { return 'admium_menu'; }

  function api_token_name() { return 'ADMIUM_API_TOKEN'; }

  function admium_site_id() { return 'ADMIUM_SITE_ID'; }

  function setup_plugin_url($test = false) {
    return $this->server_url($test) . '/plugin_wordpress';
  }

  function plugin_getting_started($test = false) {
    return $this->server_url($test) . '/gettingstarted_wordpress';
  }

  function plugin_page_url($token, $not_first_view = true, $test = false) {
    $url = $this->server_url($test) . '/plugin_wordpress';
    $url = $url . '?api_key=' . $token;

    $first_view = $not_first_view != 1;
    if ($first_view) {
      $url = $url . '&first_view=true';
    }

    return $url;
  }

  function setup_post_url($test = false) {
    return $this->server_url($test) . '/create_with_site_and_price';
  }
  
  function popup_subscribe_url($site_id, $test = false) {
    return $this->server_url($test) . "/popup/" . $site_id . "/";
  }

  function support_email() {
    return 'support@admium.net';
  }

  function not_first_view_flag_name() {
    return 'admium-plugin-page-after-first-view';
  }

  function view_as_subscriber_cookie_name() {
    return 'admium_view_as_subscriber';
  }

  function view_as_subscriber_parameter_name() {
    return 'admium_view_as_subscriber';
  }
}


?>
