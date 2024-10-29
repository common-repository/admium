<?php
/* This file provides two widgets, both based on the default WordPress Text
   Widget. The first widget only shows its content to subscribers, and the
   second only shows its content to nonsubscribers.
   
   See http://codex.wordpress.org/Widgets_API for details on widgets.
   
   See http://core.trac.wordpress.org/browser/tags/3.1.2/wp-includes/default-widgets.php
   for the definition of WP_Widget_Text, the default WordPress Text Widget.
   
   See http://core.trac.wordpress.org/browser/tags/3.1.2/wp-includes/widgets.php
   for the definition of WP_Widget. 
 */


class Admium_Widget_SubscriberOnlyText extends WP_Widget_Text {
  function Admium_Widget_SubscriberOnlyText() {
    $widget_ops = array('classname' => 'widget_text', 'description' => __('Arbitrary text or HTML, shown only to Admium subscribers'));
    $control_ops = array('width' => 400, 'height' => 350);
    $this->WP_Widget('subscriber_only_text', __('Admium Subscriber-only Text'), $widget_ops, $control_ops);
  }

  function widget($args, $instance) {
    global $admium_plugin;
    if ($admium_plugin->is_subscriber() == false) return;

    parent::widget($args, $instance);
  }
}


class Admium_Widget_NonSubscriberOnlyText extends WP_Widget_Text {
  function Admium_Widget_NonSubscriberOnlyText() {
    $widget_ops = array('classname' => 'widget_text', 'description' => __('Arbitrary text or HTML, shown only to Admium nonsubscribers'));
    $control_ops = array('width' => 400, 'height' => 350);
    $this->WP_Widget('nonsubscriber_only_text', __('Admium Nonsubscriber-only Text'), $widget_ops, $control_ops);
  }

  function widget($args, $instance) {
    global $admium_plugin;
    if ($admium_plugin->is_subscriber() == true) return;

    parent::widget($args, $instance);
  }
}


?>
