<?php


date_default_timezone_set('America/Los_Angeles');


require_once 'PHPUnit.php';
require_once dirname(__FILE__) . '/../class-cookie-manager.php';
require_once dirname(__FILE__) . '/../class-php-wrapper.php';
require_once dirname(__FILE__) . '/../class-wordpress-api-wrapper.php';
require_once dirname(__FILE__) . '/../class-admium-globals.php';


class CookieManagerTest extends PHPUnit_Framework_TestCase {
  protected $cm;
  protected $php;
  protected $ag;
  protected $wpapi;
  protected function setUp() {
    $this->php = $this->getMock('PHPWrapper');
    $this->wpapi = $this->getMockBuilder('WordPressAPIWrapper')
                         ->disableOriginalConstructor()
                         ->getMock();
    $this->ag = $this->getMockBuilder('AdmiumGlobals')
                     ->disableOriginalConstructor()
                     ->getMock();

    $this->cm = new CookieManager($this->php, $this->ag, $this->wpapi);
  }

  /****************************************************************************
                          Tests for issue_cookie()
   ***************************************************************************/

  function testIssueCookieSetsCorrectCookieExpiry() {
    $expiry_secs = 123;
    $unix_expiry = gmmktime()+$expiry_secs;

    $this->php->expects($this->once())
              ->method('setcookie')
              ->with($this->anything(),            // name
                     $this->anything(),            // value
                     $this->equalTo($unix_expiry), // expiry
                     $this->anything(),            // path
                     $this->anything());           // domain

    $this->wpapi->expects($this->once())
                ->method('db_insert')
                ->will($this->returnValue(true));

    $this->cm->issue_cookie(null, $expiry_secs);
  }

  function testIssueCookieSetsCorrectCookieName() {
    $name = 'cookiename';
    $this->ag->expects($this->once())
             ->method('cookie_name')
             ->will($this->returnValue($name));

    $this->php->expects($this->once())
              ->method('setcookie')
              ->with($this->equalTo($name), // name
                     $this->anything(),     // value
                     $this->anything(),     // expiry
                     $this->anything(),     // path
                     $this->anything());    // domain

    $this->wpapi->expects($this->once())
                ->method('db_insert')
                ->will($this->returnValue(true));

    $this->cm->issue_cookie(null, null);
  }

  function testIssueCookieSetsCorrectCookieValue() {
    $secret = 'secretkey';

    $this->php->expects($this->once())
              ->method('generate_32_char_random_string')
              ->will($this->returnValue($secret));

    $this->php->expects($this->once())
              ->method('setcookie')
              ->with($this->anything(),       // name
                     $this->equalTo($secret), // value
                     $this->anything(),       // expiry
                     $this->anything(),       // path
                     $this->anything());      // domain
    
    $this->wpapi->expects($this->once())
                ->method('db_insert')
                ->will($this->returnValue(true));
                
    $this->cm->issue_cookie(null, null);
  }
  
  function testIssueCookieSetsWordPressCookiePath() {
    $path = '/path/to/nowhere/';
    
    $this->wpapi->expects($this->once())
                ->method('cookie_path')
                ->will($this->returnValue($path));
    
    $this->php->expects($this->once())
              ->method('setcookie')
              ->with($this->anything(),       // name
                     $this->anything(),       // value
                     $this->anything(),       // expiry
                     $this->equalTo($path),   // path
                     $this->anything());      // domain
    
    $this->wpapi->expects($this->once())
                ->method('db_insert')
                ->will($this->returnValue(true));
                
    $this->cm->issue_cookie(null, null);
  }
  
  function testIssueCookieSetsWordPressCookieDomain() {
    $domain = '.domain.com';
    
    $this->wpapi->expects($this->once())
                ->method('cookie_domain')
                ->will($this->returnValue($domain));
    
    $this->php->expects($this->once())
              ->method('setcookie')
              ->with($this->anything(),        // name
                     $this->anything(),        // value
                     $this->anything(),        // expiry
                     $this->anything(),        // path
                     $this->equalTo($domain)); // domain
    
    $this->wpapi->expects($this->once())
                ->method('db_insert')
                ->will($this->returnValue(true));
                
    $this->cm->issue_cookie(null, null);
  }
  
  function testIssueCookiePutsGivenEmailInDatabase() {
    $email = 'dude@admium.net';
    
    $this->wpapi->expects($this->once())
                ->method('db_insert')
                ->with($this->anything(),        // table name
                       $this->contains($email)); // data array
    
    $this->wpapi->expects($this->once())
                ->method('db_insert')
                ->will($this->returnValue(true));
                
    $this->cm->issue_cookie($email, null);
  }
  
  function testIssueCookiePutsGeneratedSecretInDatabase() {
    $secret = 'secretkey';
    
    $this->php->expects($this->once())
              ->method('generate_32_char_random_string')
              ->will($this->returnValue($secret));
    
    $this->wpapi->expects($this->once())
                ->method('db_insert')
                ->with($this->anything(),         // table name
                       $this->contains($secret)); // data array
    
    $this->wpapi->expects($this->once())
                ->method('db_insert')
                ->will($this->returnValue(true));
                
    $this->cm->issue_cookie(null, null);
  }
  
  function testIssueCookieUsesDatabaseTableIndicatedInGlobals() {
    $table_name = 'name_of_the_table';
    
    $this->ag->expects($this->once())
             ->method('db_session_table_name')
             ->will($this->returnValue($table_name));
    
    $this->wpapi->expects($this->once())
                ->method('db_insert')
                ->with($this->equalTo($table_name), // table name
                       $this->anything());          // data array
    
    $this->wpapi->expects($this->once())
                ->method('db_insert')
                ->will($this->returnValue(true));
               
    $this->cm->issue_cookie(null, null);
  }
  
  function testIssueCookieDoesntIssueCookieIfDatabaseInsertFails() {
    $this->wpapi->expects($this->once())
                ->method('db_insert')
                ->will($this->returnValue(false));
    
    $this->php->expects($this->never())
              ->method('setcookie');
    
    $this->cm->issue_cookie(null, null);
  }
  
  /****************************************************************************
                          Tests for has_valid_admium_cookie()
   ***************************************************************************/
  
  function testHasValidAdmiumCookieIsFalseWithoutCookie() {
    $this->php->expects($this->once())
              ->method('get_cookie')
              ->will($this->returnValue(null));
    
    $this->assertFalse($this->cm->has_valid_admium_cookie());
  }
  
  function testHasValidAdmiumCookieIsFalseWhenCookieValueNotInDatabase() {
    $cookie_secret = 'x';
    
    $this->php->expects($this->once())
              ->method('get_cookie')
              ->will($this->returnValue($cookie_secret));
    
    $this->wpapi->expects($this->once())
                ->method('db_get_row')
                ->will($this->returnValue(''));
    
    $this->assertFalse($this->cm->has_valid_admium_cookie());
  }
  
  function testHasValidAdmiumCookieIsTrueWhenCookieValueMatchesDatabaseEntry() {
    $cookie_secret = 'x';
    $email = 'a@b.com';
    
    $this->php->expects($this->once())
              ->method('get_cookie')
              ->will($this->returnValue($cookie_secret));
    
    $this->wpapi->expects($this->once())
                ->method('db_get_row')
                ->will($this->returnValue($email));
    
    $this->assertTrue($this->cm->has_valid_admium_cookie());
  }
  
}

?>
