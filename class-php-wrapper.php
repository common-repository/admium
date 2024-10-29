<?php


/* This class wraps PHP builtin methods, so they can be mocked in testing.
 */
class PHPWrapper {
  function md5($x) {
    return md5($x);
  }

  function mt_rand() {
    return mt_rand();
  }

  function mktime($hour, $minute, $second, $month, $day, $year) {
    return mktime($hour, $minute, $second, $month, $day, $year);
  }

  function setcookie($name, $value, $expire, $path, $domain, $secure=false, $httponly=false) {
    return setcookie($name, $value, $expire, $path, '');
  }

  // Non-PHP builtins
  function generate_32_char_random_string() {
    return md5(mt_rand());
  }

  function get_cookie($name) {
    return $_COOKIE[$name];
  }
}


?>
