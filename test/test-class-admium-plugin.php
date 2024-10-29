<?php


require_once 'PHPUnit.php';
require_once dirname(__FILE__) . '/../class-admium-plugin.php';
require_once dirname(__FILE__) . '/../class-cookie-manager.php';
require_once dirname(__FILE__) . '/../class-token-validator.php';
require_once dirname(__FILE__) . '/../class-admium-authenticator.php';
require_once dirname(__FILE__) . '/../class-php-wrapper.php';
require_once dirname(__FILE__) . '/../class-wordpress-api-wrapper.php';
require_once dirname(__FILE__) . '/../class-admium-globals.php';
require_once dirname(__FILE__) . '/../class-admium-admin.php';


class AdmiumTest extends PHPUnit_Framework_TestCase {

  protected $wpapi;
  protected $php;
  protected $ag;
  protected $cm;
  protected $tv;
  protected $aa;
  protected $plugin;
  protected function setUp() {
    $this->wpapi = $this->getMockBuilder('WordPressAPIWrapper')
                        ->disableOriginalConstructor()
                        ->getMock();
    $this->php = $this->getMock('PHPWrapper');
    $this->ag = $this->getMockBuilder('AdmiumGlobals')
                     ->disableOriginalConstructor()
                     ->getMock();
    $this->cm = $this->getMockBuilder('CookieManager')
                     ->disableOriginalConstructor()
                     ->getMock();
    $this->tv = $this->getMockBuilder('TokenValidator')
                     ->disableOriginalConstructor()
                     ->getMock();
    $this->aa = $this->getMockBuilder('AdmiumAuthenticator')
                     ->disableOriginalConstructor()
                     ->getMock();

    $this->plugin = new AdmiumPlugin($this->wpapi, $this->php, $this->ag, $this->cm, $this->tv, $this->aa);
  }

  function testInstantiationWorks() {
  }

  function testSetHooksWorks() {
    $this->wpapi->expects($this->once())
                ->method('is_admin')
                ->will($this->returnValue(true));

    $this->wpapi->expects($this->any())
                ->method('add_action');

    $this->wpapi->expects($this->any())
                ->method('add_filter');

    $this->wpapi->expects($this->any())
                ->method('add_option');

    $this->wpapi->expects($this->once())
                ->method('is_admin')
                ->will($this->returnValue(true));

    $this->plugin->set_hooks();
  }

}

?>
