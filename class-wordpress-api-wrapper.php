<?php


/* This class wraps WordPress methods and globals, so they can be mocked in
   testing.
 */
class WordPressAPIWrapper {
  function WordPressAPIWrapper() {
  }

  function cookie_path() { return COOKIEPATH; }
  function cookie_domain() { return COOKIE_DOMAIN; }
  function db_prefix() { global $wpdb; return $wpdb->prefix;  }
  function db_insert($table_name, $data) { global $wpdb; return $wpdb->insert($table_name, $data); }
  function db_get_row($sql) { global $wpdb; return $wpdb->get_row($sql); }
  function wp_remote_get($url) { return wp_remote_get($url); }
  function wp_remote_post($url, $args) { return wp_remote_post($url, $args); }
  function is_wp_error($http_response) { return is_wp_error($http_response); }
  function is_admin() { return is_admin(); }
  function register_activation_hook($file, $function) { return register_activation_hook($file, $function); }
  function add_action($tag, $function_to_add, $priority = 10, $accepted_args = 1) { return add_action($tag, $function_to_add, $priority, $accepted_args); }
  function add_option($name, $value) { return add_option($name, $value); }
  function delete_option($option) { return delete_option($option); }
  function add_filter($tag, $function_to_add, $priority = 10, $accepted_args = 1) { return add_filter($tag, $function_to_add, $priority, $accepted_args); }
  function current_screen() { global $current_screen; return $current_screen; }
  function wp_query() { global $wp_query; return $wp_query; }
  function get_option($show, $default = false) { return get_option($show, $default); }
  function update_option($option_name, $newvalue) { return update_option($option_name, $newvalue); }
  function add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function) {
    return add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function);
  }

  function home_url() {
    return home_url();
  }

  function add_shortcode($atts, $content) {
    return add_shortcode($atts, $content);
  }

  function is_administrator() {
    return current_user_can('manage_options');
  }
}


?>
