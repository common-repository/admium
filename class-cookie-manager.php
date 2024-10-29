<?php


class CookieManager {

  protected $php;
  protected $admium_globals;
  protected $wpapi;
  function CookieManager($php_interface, $admium_globals, $wpapi) {
    $this->php = $php_interface;  
    $this->admium_globals = $admium_globals;
    $this->wpapi = $wpapi;
  }

  /* This function generally follows the strategy outlined in this article:
     http://fishbowl.pastiche.org/2004/01/19/persistent_login_cookie_best_practice/

     $email is the email address of the subscriber.
     $expiry_secs is the number of seconds from now the cookie should expire.
   */
  public function issue_cookie($email, $expiry_secs) {
    $secret = $this->php->generate_32_char_random_string();
    $table_name = $this->admium_globals->db_session_table_name();
    $data = array('email' => $email,
                  'secret' => $secret);
    $successful = $this->wpapi->db_insert($table_name, $data);
    if ($successful == false) { return; }

    $expiry = gmmktime() + $expiry_secs;
    $success = $this->php->setcookie($this->admium_globals->cookie_name(),
                                     $secret,
                                     $expiry,
                                     $this->wpapi->cookie_path(),
                                     $this->wpapi->cookie_domain());
  }

  /* This function sets the view as subscriber cookie to the specified mode.
   *
   * $mode should be either "on" or "off".
   */
  public function issue_view_as_subscriber_cookie($mode="on") {
    $expiry_secs = 60 * 60 * 24 * 30;
    $expiry = gmmktime() + $expiry_secs;
    $success = $this->php->setcookie($this->admium_globals->view_as_subscriber_cookie_name(),
                                     $mode,
                                     $expiry,
                                     $this->wpapi->cookie_path(),
                                     $this->wpapi->cookie_domain());
  }

  public function has_valid_admium_cookie() {
    $secret = $this->php->get_cookie($this->admium_globals->cookie_name());
    if ($secret == null) return false;

    // Check if the secret exists in the database, and who it's for.
    $table_name = $this->admium_globals->db_session_table_name();
    $sql = "SELECT email from $table_name where secret = '$secret'";
    $email = $this->wpapi->db_get_row($sql);
    if ($email == '') return false;

    return true;
  }

  /* This function returns true if view as subscriber mode is on.
   */
  public function view_as_subscriber_mode_is_on() {
    $mode = $this->php->get_cookie($this->admium_globals->view_as_subscriber_cookie_name());
    if ($mode === 'on') return true;

    return false;
  }

}


?>
