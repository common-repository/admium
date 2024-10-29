<?php


class AdmiumPlugin {

  protected $wpapi;
  protected $php_wrapper;
  protected $admium_globals;
  protected $cookie_manager;
  protected $token_validator;
  protected $admium_authenticator;
  function AdmiumPlugin($wpapi, $php_wrapper, $admium_globals, $cookie_manager, $token_validator, $admium_authenticator) {
    $this->wpapi = $wpapi;
    $this->php_wrapper = $php_wrapper;
    $this->admium_globals = $admium_globals;
    $this->cookie_manager = $cookie_manager;
    $this->token_validator = $token_validator;
    $this->admium_authenticator = $admium_authenticator;
  }

  function install() {
    $table_name = $this->wpapi->db_prefix() . $this->admium_globals->db_session_table_suffix();

    $sql = "CREATE TABLE " . $table_name . " (
        email varchar(256) NOT NULL,
        secret char(32) NOT NULL,
        issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (secret)
    );";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
  }

  function is_subscriber() {
    return $this->admium_authenticator->is_subscriber();
  }

  /* This function tells WordPress to allow our query vars; by default it strips
     out query vars that it doesn't recognize. 
     See http://www.voidtrance.net/2010/02/passing-and-receiving-query-variables.
   */
  function allow_query_vars($query_vars) {
    $query_vars[] = $this->admium_globals->token_name();
    $query_vars[] = $this->admium_globals->test_parameter_name();
    $query_vars[] = $this->admium_globals->view_as_subscriber_parameter_name();
    return $query_vars;
  }

  // Show post contents to subscribers, and the call-to-subscribe to non-subscribers.
  function wrap_post($content) {
    global $post;
    $visibility = get_post_meta($post->ID, $this->admium_globals->admium_post_visibility_field_name(), 'true');
    //$showcalltosubscribe = get_post_meta($post->ID, $admium_globals->admium_post_visibility_calltosubscribe(), 'true');

    switch($visibility) {
      case AdmiumGlobals::VISIBILITY_ALL:
        return $content;
        break; // all
      case AdmiumGlobals::VISIBILITY_NONSUBSCRIBER:
        if(!$this->is_subscriber())
          return $content;
        break; // non-sub
      case AdmiumGlobals::VISIBILITY_SUBSCRIBER:
        if($this->is_subscriber()) {
          return $content;
        }
        break; // subscriber
      case AdmiumGlobals::VISIBILITY_SUBWITHCALL:
        if($this->is_subscriber()) {
          return $content;
        } else { // non-subscriber
          $calltosubscribe = get_option($this->admium_globals->call_to_subscribe_option_name());
          return $calltosubscribe;
        }
        break;
    } // end switch
  }

  // Include the JavaScript to launch the modal subscribe popup.
  function include_popup_javascript() {
    $wp_query = $this->wpapi->wp_query();
    $test = array_key_exists($this->admium_globals->test_parameter_name(), $wp_query->query_vars);
    $site_id = $this->wpapi->get_option($this->admium_globals->admium_site_id());
    echo "<script src='" . $this->admium_globals->popup_js_url($site_id, $test) . "'></script>";
  }

  function register_widgets() {
    # The require statement is kept in here so that this file may be
    # included without including all of WordPress.
    require_once 'class-admium-widgets.php';
    register_widget('Admium_Widget_SubscriberOnlyText');
    register_widget('Admium_Widget_NonSubscriberOnlyText');
  }

  function subscribe_link_shortcode($atts, $content) {
    $site_id = $this->wpapi->get_option($this->admium_globals->admium_site_id());
    $test = array_key_exists($this->admium_globals->test_parameter_name(), $_GET);
    $url = $this->admium_globals->popup_subscribe_url($site_id, $test);
    return "<a href='" . $url . "' onclick='admium_popup(\"" . $url . "\"); event.returnValue=false; return false;'>" . $content . "</a>";
  }

  function add_view_as_subscriber_toggle_to_admin_bar() {
    global $wp_admin_bar;

    $vas_link_prefix = $this->wpapi->home_url() . "?" . $this->admium_globals->view_as_subscriber_cookie_name() . "=";
    if ($this->admium_authenticator->is_viewing_as_subscriber()) {
      $wp_admin_bar->add_menu( array(
        'id' => 'admium_view_as_subscriber',
        'title' => 'View as Admium Non-subscriber',
        'href' => $vas_link_prefix . 'off'
      ) );
    } else {
      $wp_admin_bar->add_menu( array(
        'id' => 'admium_view_as_subscriber',
        'title' => 'View as Admium Subscriber',
        'href' => $vas_link_prefix . 'on'
      ) );
    }


    /*if ($_COOKIE[$this->wp_api->view_as_subscriber_cookie_name()] == true) {
      $wp_admin_bar->add_menu( array(
        'href' => $link
      ) );
    }*/
  }

  function set_hooks() {
    if ( $this->wpapi->is_admin() ) {
      $admin = new AdmiumAdmin($this->admium_globals, $this->wpapi);
      $admin->set_hooks();
    }

    // Do 1-time initialization of the plugin, like setting up database tables.
    $this->wpapi->register_activation_hook('/admium/admium.php', array($this, 'install'));

    // Install widgets.
    $this->wpapi->add_action('widgets_init', array($this, 'register_widgets'));

    // Store HTML content that's shown to get the user to subscribe.
    $this->wpapi->add_option($this->admium_globals->call_to_subscribe_option_name(), $this->admium_globals->default_call_to_subscribe());

    // Wrap all posts marked "subscriber-only" with the call to subscribe.
    $this->wpapi->add_filter('the_content', array($this, 'wrap_post'));

    // Include the Admium javascript, in the header.
    $this->wpapi->add_action('wp_head', array($this, 'include_popup_javascript'));

    // Tell WordPress to allow the query vars we need.
    $this->wpapi->add_filter('query_vars', array($this, 'allow_query_vars'));

    // Authenticate the user.
    $this->wpapi->add_action('parse_query', array($this->admium_authenticator, 'authenticate'));

    // Handle shortcodes.
    $this->wpapi->add_shortcode('admium_subscribe_link', array($this, 'subscribe_link_shortcode'));

    // Make shortcodes functional within widgets.
    $this->wpapi->add_filter('widget_text', 'do_shortcode', 9);

    if ($this->wpapi->is_administrator()) {
      // Put "view as subscriber" toggle in the admin bar.
      $FAR_RIGHT_POSITION = 777;
      $this->wpapi->add_action('admin_bar_menu', array($this, 'add_view_as_subscriber_toggle_to_admin_bar'), $FAR_RIGHT_POSITION);
    }
  }

}


?>
