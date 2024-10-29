<?php


class AdmiumAuthenticator {

  protected $token_validator;
  protected $cookie_manager;
  protected $admium_globals;
  protected $wpapi;
  protected $is_subscriber_flag;
  protected $viewing_as_subscriber_flag;
  function AdmiumAuthenticator($token_validator, $cookie_manager, $admium_globals, $wpapi) {
    $this->token_validator = $token_validator;
    $this->cookie_manager = $cookie_manager;
    $this->admium_globals = $admium_globals;
    $this->wpapi = $wpapi;
    $this->is_subscriber_flag = false;
    $this->viewing_as_subscriber_flag = false;
  }

  /* This function processes each query made to the WordPress installation to
     check if an Admium authentication token or authentication cookie is
     present, and acts accordingly:

     - If there's an authentication cookie, it validates the cookie.
     - If there's an authentication token, it validates the token, and if
       valid, issues an authentication cookie.

     $wp_query is an object with a property called query_vars that maps GET
               parameter names to values.
   */
  function authenticate($wp_query) {
    // Handle subscriber cookie.
    if ($this->cookie_manager->has_valid_admium_cookie()) {
      $this->is_subscriber_flag = true;
      return;
    };

    // Handle token-based authentication.
    if (array_key_exists($this->admium_globals->token_name(), $wp_query->query_vars)) {
      $token = $wp_query->query_vars[$this->admium_globals->token_name()];
      $test = array_key_exists($this->admium_globals->test_parameter_name(), $wp_query->query_vars);
      $result = $this->token_validator->validate($token, $test);
      if ($result != false) {
        list($email, $expiry_secs) = $result;
        $this->cookie_manager->issue_cookie($email, $expiry_secs);

        $this->is_subscriber_flag = true;
        return;
      }
    }

    // Handle "view as subscriber" mode.
    if ($this->wpapi->is_administrator()) {
      $vas_param_present = array_key_exists($this->admium_globals->view_as_subscriber_parameter_name(), $wp_query->query_vars);

      if ($vas_param_present) {
        $vas = $wp_query->query_vars[$this->admium_globals->view_as_subscriber_parameter_name()];
        if ($vas === 'on') {
          $this->cookie_manager->issue_view_as_subscriber_cookie('on');
          $this->is_subscriber_flag = true;
          $this->viewing_as_subscriber_flag = true;
          return;
        } elseif ($vas === 'off') {
          $this->cookie_manager->issue_view_as_subscriber_cookie('off');
          $this->viewing_as_subscriber_flag = false;
        }
      } else {
        if ($this->cookie_manager->view_as_subscriber_mode_is_on()) {
          $this->is_subscriber_flag = true;
          $this->viewing_as_subscriber_flag = true;
          return;
        }
      }
    }

  }

  /* Indicates whether "view as subscriber" mode is on.
   */
  function is_viewing_as_subscriber() {
    return $this->viewing_as_subscriber_flag;
  }

  /* Indicates whether the current reader is a subscriber.
   */
  function is_subscriber() {
    return $this->is_subscriber_flag;
  }

}


?>
