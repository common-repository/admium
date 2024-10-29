<?php


class TokenValidator {

  protected $php;
  protected $admium_globals;
  protected $wpapi;
  function TokenValidator($php_interface, $admium_globals, $wpapi) {
    $this->php = $php_interface;
    $this->admium_globals = $admium_globals;
    $this->wpapi = $wpapi;
  }

  /* This function checks that a given authentication token is valid.

     $token is the token to check.
     $token_name is the name of the query arg to put the token in.
     $validation_url is the URL to use for validating the token.
   */
  public function validate($token, $test = false) {
    $token_name = $this->admium_globals->token_name();

    $validation_url = $this->admium_globals->token_validation_url($test);

    $request_url = $validation_url . '?' . $token_name . '=' . $token;
    $response = $this->wpapi->wp_remote_get($request_url);

    if( $this->wpapi->is_wp_error( $response ) ) {
       // TODO: handle this
       return false;
    }

    $content = $response['body'];

    if ($content == $this->admium_globals->validation_failed_response()) {
      return false;
    }

    list($email, $date) = split(',', $content);
    return array($email, $date);
  }

}


?>
