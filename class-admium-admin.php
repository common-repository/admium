<?php
/* This file holds stuff related to the admin interface.
 */


class AdmiumAdmin {

  protected $admium_globals;
  protected $wpapi;
  function AdmiumAdmin($admium_globals, $wpapi) {
    $this->admium_globals = $admium_globals;
    $this->wpapi = $wpapi;
  }

  function set_hooks() {
    // Add menu pages to configure the plugin.
    $this->wpapi->add_action('admin_menu', array($this, 'plugin_admin_menu'));

    // Add interface to mark posts or pages as subscriber-only.
    $this->wpapi->add_action('add_meta_boxes', array($this, 'plugin_post_boxes'));
    $this->wpapi->add_action('save_post', array($this, 'save_post_data'));
    $this->wpapi->add_action('save_post', array($this, 'save_quick_edit_data'));

    // Filter to add new column to posts list
    $this->wpapi->add_filter('manage_post_posts_columns', array($this, 'add_post_columns'));

    // Action to add content to custom posts column
    $this->wpapi->add_action('manage_posts_custom_column', array($this, 'render_post_columns'), 10, 2);

    // Action to change quick edit box
    $this->wpapi->add_action('quick_edit_custom_box', array($this, 'add_quick_edit'), 10, 2);

    // Action to update quick edit inputs
    $this->wpapi->add_action('admin_footer', array($this, 'quick_edit_javascript'));
    $this->wpapi->add_filter('post_row_actions', array($this, 'expand_quick_edit_link'), 10, 2);

    // Action to put up a notice that configuration is not complete
    if ( !$this->wpapi->get_option($this->admium_globals->api_token_name())
      && !isset($_POST['submit'])
      && $_GET['page'] != $this->admium_globals->admin_page_name() ) {
      $this->wpapi->add_action('admin_notices', array($this, 'configuration_incomplete_warning'));
    }
  }

  function render_plugin_page_setup_view() {
    # Retrieve HTML of setup view from main server.
    $test = array_key_exists($this->admium_globals->test_parameter_name(), $_GET);
    $setup_url = $this->admium_globals->setup_plugin_url($test);
    $response = $this->wpapi->wp_remote_get($setup_url);

    # Render setup view.
    echo $response['body'];

    # Pre-fill some fields in the form included in that HTML.
    $email = $this->wpapi->get_option('admin_email');
    $site_name = $this->wpapi->get_option('blogname');
    $site_url = $this->wpapi->home_url();
?>
    <script>
      jQuery("#publisher_email").val("<?php echo $email; ?>");
      jQuery("#publisher_site_name").val("<?php echo $site_name; ?>");
      jQuery("#publisher_site_url").val("<?php echo $site_url; ?>");
    </script>
<?php
  }

  function render_plugin_page_after_setup_view($api_token) {
      $test = array_key_exists($this->admium_globals->test_parameter_name(), $_GET);
      $not_first_view = $this->wpapi->get_option($this->admium_globals->not_first_view_flag_name());
      $this->wpapi->update_option($this->admium_globals->not_first_view_flag_name(), true);

      $page_url = $this->admium_globals->plugin_page_url($api_token, $not_first_view, $test);
      echo "<iframe id='admium_page' src='" . $page_url . "' width='100%' height='90%'></iframe>";
  }

  function render_plugin_page_getting_started() {
      $test = array_key_exists($this->admium_globals->test_parameter_name(), $_GET);
      $page_url = $this->admium_globals->plugin_getting_started($test);
      echo "<iframe id='admium_page' src='" . $page_url . "' width='100%' height='90%'></iframe>";
  }

  function admium_plugin_page() {
    if ( isset($_POST['publisher'] ) ) {
      # Handle submission of the setup view.
      $test = array_key_exists($this->admium_globals->test_parameter_name(), $_GET);
      $response = $this->wpapi->wp_remote_post(
        $this->admium_globals->setup_post_url($test),
        array(
          'method' => 'POST',
          'timeout' => 45,
          'redirection' => 5,
          'httpversion' => '1.0',
          'blocking' => true,
          'headers' => array(),
          'body' => $_POST,
          'cookies' => array()
        )
      );

      if ($response['response']['code'] == 200) {
        # It worked!
        # TODO: handle errors with updating option.
        list($token, $site_id) = explode(",", $response['body']);
        $this->wpapi->update_option($this->admium_globals->api_token_name(), $token);
        $this->wpapi->update_option($this->admium_globals->admium_site_id(), $site_id);
        $this->render_plugin_page_after_setup_view($token);
      } else if ($response['response']['code'] == 401) {
        # Invalid API token was submitted.
        echo "<div class='error'>Invalid access key entered. Try again or <a href='mailto:" . $this->admium_globals->support_email() . "'>email support</a>.</div>";
        $this->render_plugin_page_setup_view();
      } else if ($response['response']['code'] == 403) {
        # Failed to validate a model or somethin'.
        echo "<div class='error'>Invalid info entered. Try again or <a href='mailto:" . $this->admium_globals->support_email() . "'>email support</a>.</div>";
        $this->render_plugin_page_setup_view();
      } else {
        # Who knows what.
        echo "<div class='error'>An unknown error occurred. Try again or <a href='mailto:" . $this->admium_globals->support_email() . "'>email support</a>.</div>";
        var_dump($response);
        $this->render_plugin_page_setup_view();
      }
    } else if ( !$this->wpapi->get_option($this->admium_globals->api_token_name()) ) {
      $this->render_plugin_page_setup_view();
    } else {
      $api_token = $this->wpapi->get_option($this->admium_globals->api_token_name());
      $this->render_plugin_page_after_setup_view($api_token);
    }
  }

  // This function renders a notice at the top of the admin UI that
  // this plugin requires more configuration.
  function configuration_incomplete_warning() {
?>
    <div class='updated fade'>
      <p>
        <strong>Admium is almost ready.</strong>
        You must <a id='admium_setup' href="admin.php?page=<?php echo $this->admium_globals->admin_page_name(); ?>">click here</a> to finish setup.
      </p>
    </div>
<?php
  }

  // This function a makes a new menu item with submenu items in the WordPress admium UI.
  function plugin_admin_menu() {
    add_menu_page('Admium', 'Admium', 'administrator', 'admium_menu', array($this, 'admium_plugin_page'), plugins_url('admium/icon_zone_money.png'));
    $this->wpapi->add_submenu_page('admium_menu', 'Admium Dashboard', 'Dashboard', 'administrator', 'admium_menu', array($this, 'admium_plugin_page'));
    $this->wpapi->add_submenu_page('admium_menu', 'Getting Started', 'Getting Started', 'administrator', 'admium_menu_getting_started', array($this, 'render_plugin_page_getting_started'));
    $this->wpapi->add_submenu_page('admium_menu', 'Admium Settings', 'Settings', 'administrator', 'admium_menu_settings', array($this, 'menu_page'));
  }

  // This function makes a page in the WordPress admin UI.
  function menu_page() {
    $hidden_field_name = 'mt_submit_hidden';

    $call_to_subscribe = get_option($this->admium_globals->call_to_subscribe_option_name());

    if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) {
      // This is a POST, so update the option.
      $call_to_subscribe = stripslashes( $_POST[ $this->admium_globals->call_to_subscribe_option_name() ] );
      update_option( $this->admium_globals->call_to_subscribe_option_name(), $call_to_subscribe );

      // Notify the user we updated the option.
      ?>
      <div class="updated"><p><strong><?php _e('Settings saved.', 'admium-settings' ) ?></strong></p></div>
      <?php
    }

    // Render a form for the user to update options.
    ?>
    <div class="wrap">
    <h2><?php echo __( 'Admium Plugin Settings', 'admium-settings' ) ?></h2>

    <form name="settings" method="post" action="">
    <input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">

    <p><?php _e("Call to subscribe content (HTML):", 'admium-settings' ) ?><br/>
    <textarea name="<?php echo $this->admium_globals->call_to_subscribe_option_name() ?>" rows="15" cols="80"><?php echo get_option($this->admium_globals->call_to_subscribe_option_name()) ?></textarea>
    </p><hr />

    <p class="submit">
    <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
    </p>

    </form>
    </div>
    <?php
  }

  // Add a interface to the post and page edit/create pages to mark posts or pages subscriber-only.
  function plugin_post_boxes() {
    add_meta_box( 'subscriber-only', 'Admium subscription options', array($this, 'add_visibility_options'), 'post', 'normal' );
    add_meta_box( 'subscriber-only', 'Admium subscription options', array($this, 'add_visibility_options'), 'page', 'normal' );
  }

  // Render the code for a checkbox to marks posts as subscriber-only.
  function add_visibility_options() {
    global $post_ID;

    $visibility = get_post_meta($post_ID, $this->admium_globals->admium_post_visibility_field_name(), 'true');
    //$showcalltosubscribe = get_post_meta($post_ID, $this->admium_globals->admium_post_visibility_calltosubscribe(), 'true');
    ?>
    <h4>Post Visiblity:</h4>
    <select name='<?php echo $this->admium_globals->admium_post_visibility_field_name(); ?>'
    id='<?php echo $this->admium_globals->admium_post_visibility_field_name(); ?>'>
      <option value="<?php echo AdmiumGlobals::VISIBILITY_ALL; ?>"
      <?php if($visibility == AdmiumGlobals::VISIBILITY_ALL) echo "selected='selected'"; ?>>
      All
      </option>
      <option value="<?php echo AdmiumGlobals::VISIBILITY_NONSUBSCRIBER; ?>"
      <?php if($visibility == AdmiumGlobals::VISIBILITY_NONSUBSCRIBER) echo "selected='selected'"; ?>>
      Non-subscriber only
      </option>
      <option value="<?php echo AdmiumGlobals::VISIBILITY_SUBSCRIBER; ?>"
      <?php if($visibility == AdmiumGlobals::VISIBILITY_SUBSCRIBER) echo "selected='selected'"; ?>>
      Subscriber only
      </option>
      <option value="<?php echo AdmiumGlobals::VISIBILITY_SUBWITHCALL; ?>"
      <?php if($visibility == AdmiumGlobals::VISIBILITY_SUBWITHCALL) echo "selected='selected'"; ?>>
      Subscriber only; show call to subscribe to non-subscribers
      </option>
   </select>
  <?php 
  }

  function add_post_columns($columns) {
    $columns['admium_visibility'] = 'Visibility';
    return $columns;
  }

  function render_post_columns($column_name, $id) {
    switch ($column_name) {
    case 'admium_visibility':
      $visibility = get_post_meta($id, 'admium-visibility', TRUE);
      $vis_set = NULL;
      if ($visibility) {
        $vis_set = get_post($visibility);
      }
      if (is_object($vis_set)) {
        switch($visibility) {
          case AdmiumGlobals::VISIBILITY_ALL:
            echo "All";
            break; // all
          case AdmiumGlobals::VISIBILITY_NONSUBSCRIBER:
            echo "Non subscriber only";
            break; // non-sub
          case AdmiumGlobals::VISIBILITY_SUBSCRIBER:
            echo "Subscriber only";
            break; // subscriber
          case AdmiumGlobals::VISIBILITY_SUBWITHCALL:
            echo "Subscriber only; nag non-subscribers";
            break;
        } // end switch
      } else {
        echo 'All';
      }
      break;
    }
  }

  function add_quick_edit($column_name, $post_type) {
    if ($column_name != 'admium_visibility') return;
    ?>
    <fieldset class="inline-edit-col-left">
      <div class="inline-edit-col">
        <span class="title">Admium visibility</span>
        <select name='<?php echo $this->admium_globals->admium_post_visibility_field_name(); ?>' id='<?php echo $this->admium_globals->admium_post_visibility_field_name(); ?>'>
          <option value="<?php echo AdmiumGlobals::VISIBILITY_ALL; ?>">
          All
          </option>
          <option value="<?php echo AdmiumGlobals::VISIBILITY_NONSUBSCRIBER; ?>">
          Non-subscriber only
          </option>
          <option value="<?php echo AdmiumGlobals::VISIBILITY_SUBSCRIBER; ?>">
          Subscriber only
          </option>
          <option value="<?php echo AdmiumGlobals::VISIBILITY_SUBWITHCALL; ?>">
            Subscriber only; nag non-subs
          </option>
        </select>
      </div>
     </fieldset>
    <?php
  }

  function quick_edit_javascript() {
    $current_screen = $this->wpapi->current_screen();
    if (($current_screen->id != 'edit-post') || ($current_screen->post_type != 'post')) return; 

    ?>
    <script type="text/javascript">
    <!--
    function set_admium_visibility(vis) {
      // revert Quick Edit menu so that it refreshes properly
      inlineEditPost.revert();
      var visibility = document.getElementById('admium-visibility');
      for (i = 0; i < visibility.options.length; i++) {
        if (visibility.options[i].value == vis) {
          visibility.options[i].setAttribute("selected", "selected");
        } else {
          visibility.options[i].removeAttribute("selected");
        }
      }
    }
    //-->
    </script>
    <?php
  }

  function expand_quick_edit_link($actions, $post) {
    $current_screen = $this->wpapi->current_screen();
    if (($current_screen->id != 'edit-post') || ($current_screen->post_type != 'post')) return $actions;

    $vis = get_post_meta( $post->ID, 'admium-visibility', TRUE);
    $actions['inline hide-if-no-js'] = '<a href="#" class="editinline" title="';
    $actions['inline hide-if-no-js'] .= esc_attr( __( 'Edit this item inline' ) ) . '" ';
    $actions['inline hide-if-no-js'] .= " onclick=\"set_admium_visibility('{$vis}')\">";
    $actions['inline hide-if-no-js'] .= __( 'Quick&nbsp;Edit' );
    $actions['inline hide-if-no-js'] .= '</a>';
    return $actions;
  }

  function save_quick_edit_data($post_id) {
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return $post_id;

    $visibility = $_POST[ $this->admium_globals->admium_post_visibility_field_name() ];
    update_post_meta($post_id, $this->admium_globals->admium_post_visibility_field_name(), $visibility);

    // $showcalltosubscribe = $_POST[ $this-admium_globals->admium_post_visibility_calltosubscribe() ];
    // update_post_meta($post_id, $this-admium_globals->admium_post_visibility_calltosubscribe(), $showcalltosubscribe);
  }

  // Store in the database whether a post is subscriber-only, based on checkbox.
  function save_post_data( $post_id ) {
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return $post_id;

    $visibility = $_POST[ $this->admium_globals->admium_post_visibility_field_name() ];
    update_post_meta($post_id, $this->admium_globals->admium_post_visibility_field_name(), $visibility);

    // $showcalltosubscribe = $_POST[ $this-admium_globals->admium_post_visibility_calltosubscribe() ];
    // update_post_meta($post_id, $this-admium_globals->admium_post_visibility_calltosubscribe(), $showcalltosubscribe);
  }

}


?>
