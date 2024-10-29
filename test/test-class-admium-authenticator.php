<?php


require_once 'PHPUnit.php';
require_once dirname(__FILE__) . '/../class-cookie-manager.php';
require_once dirname(__FILE__) . '/../class-token-validator.php';
require_once dirname(__FILE__) . '/../class-admium-authenticator.php';
require_once dirname(__FILE__) . '/../class-admium-globals.php';


class AuthenticationTest extends PHPUnit_Framework_TestCase {  
  
  protected $token_validator;
  protected $cookie_manager;
  
  
  protected function setUp() {  
    $this->token_validator = $this->getMockBuilder('TokenValidator')
                                  ->disableOriginalConstructor()
                                  ->getMock();
    $this->cookie_manager = $this->getMockBuilder('CookieManager')
                                 ->disableOriginalConstructor()
                                 ->getMock();
    $this->ag = $this->getMockBuilder('AdmiumGlobals')
                     ->disableOriginalConstructor()
                     ->getMock();
    $this->authenticator = new AdmiumAuthenticator($this->token_validator, $this->cookie_manager, $this->ag);
  }


  /* When the URL has an Admium token in the query args (but no test param),
     the plugin should call validate in non-test mode.
   */
  function testValidateCallIsMadeIfTokenInQueryArgs() {
    $token = 'asdf';

    $this->ag->expects($this->exactly(3))
             ->method('token_name')
             ->will($this->returnValue('admium_token'));
    $this->ag->expects($this->exactly(1))
             ->method('test_parameter_name')
             ->will($this->returnValue('test'));

    $wp_query = (object) array('query_vars' => array($this->ag->token_name() => $token));

    $this->token_validator->expects($this->once())
                          ->method('validate')
                          ->with($this->equalTo($token),
                                 $this->equalTo(false));

    $this->authenticator->authenticate($wp_query);
  }

  /* When the URL has no Admium token in the query args, the plugin should
     not attempt to validate a token.
   */
  function testValidateCallNotMadeIfNoTokenInQueryArgs() {
    $wp_query = (object) array('query_vars' => array());
    
    $this->token_validator->expects($this->never())
                          ->method('validate');
    
    $this->authenticator->authenticate($wp_query);
  }
  
  /* When the URL has a test parameter (and Admium token) in the query args,
     the plugin should call the validate method in test mode.
   */
  function testValidateCallInTestModeIsMadeIfTestParameterInQueryArgs() {
    $token = 'asdf';
    
    $this->ag->expects($this->exactly(3))
             ->method('token_name')
             ->will($this->returnValue('admium_token'));
    $this->ag->expects($this->exactly(2))
             ->method('test_parameter_name')
             ->will($this->returnValue('test'));
    
    $wp_query = (object) array('query_vars' => array($this->ag->token_name() => $token,
                                                     $this->ag->test_parameter_name() => 'true'));
            
    $this->token_validator->expects($this->once())
                          ->method('validate')
                          ->with($this->equalTo($token),
                                 $this->equalTo(true));
    
    $this->authenticator->authenticate($wp_query);
  }
  
  
  /* When the URL has an Admium token in the query args, and that token
     successfully validates, the plugin should issue a cookie marking the
     user as a subscriber, with the email and expiry date returned by the
     validate function.
  */
  function testSessionCookieIsIssuedWithCorrectEmailAndExpiryOnValidToken() {
    $token = 'asdf';
    $email = 'a@b.com';
    $expiry = '2012-11-14';
    $wp_query = (object) array('query_vars' => array($this->ag->token_name() => $token));
        
    $this->token_validator->expects($this->once())
                          ->method('validate')
                          ->will($this->returnValue(array($email, $expiry)));
    
    $this->cookie_manager->expects($this->once())
                         ->method('issue_cookie')
                         ->with($this->equalTo($email),
                                $this->equalTo($expiry));
    
    $this->authenticator->authenticate($wp_query);
  }
  
  
  /* When the URL has an Admium token in the query args, and that token
     fails to validate, the plugin should not issue a cookie marking the
     user as a subscriber.
  */
  function testSessionCookieNotIssuedOnInvalidToken() {
    $token = 'asdf';
    $wp_query = (object) array('query_vars' => array($this->ag->token_name() => $token));
        
    $this->token_validator->expects($this->once())
                          ->method('validate')
                          ->will($this->returnValue(false));
    
    $this->cookie_manager->expects($this->never())
                         ->method('issue_cookie');
    
    $this->authenticator->authenticate($wp_query);
  }
  
  
  /* When the URL has an Admium token in the query args, and that token
     successfully validates, the function "is_subscriber()" should return true.
  */
  function testIsSubscriberIsTrueOnValidToken() {
    $token = 'asdf';
    $wp_query = (object) array('query_vars' => array($this->ag->token_name() => $token));
        
    $this->token_validator->expects($this->once())
                          ->method('validate')
                          ->will($this->returnValue(true));
    
    $this->authenticator->authenticate($wp_query);
    $this->assertTrue($this->authenticator->is_subscriber());
  }
  
  
  /* When the URL has an Admium token in the query args, and that token
     fails to validate, the return value of the function "is_subscriber()"
     should not change.
  */
  function testIsSubscriberIsUnchangedOnInvalidToken() {
    $token = 'asdf';
    $wp_query = (object) array('query_vars' => array($this->ag->token_name() => $token));
        
    $this->token_validator->expects($this->once())
                          ->method('validate')
                          ->will($this->returnValue(false));
    
    $before = $this->authenticator->is_subscriber();
    $this->authenticator->authenticate($wp_query);
    $this->assertTrue($this->authenticator->is_subscriber() == $before);
  }
  
  
  /* When the URL has no Admium token in the query args, the return value of
     the function "is_subscriber()" should not change.
  */
  function testIsSubscriberIsUnchangedOnNoToken() {
    $token = 'asdf';
    $wp_query = (object) array('query_vars' => array());
    
    $before = $this->authenticator->is_subscriber();
    $this->authenticator->authenticate($wp_query);
    $this->assertTrue($this->authenticator->is_subscriber() == $before);
  }
  
  
  /* When the Admium session cookie is set, the function "is_subscriber()"
     should return true.
  */
  function testIsSubscriberIsTrueOnSessionCookiePresent() {
    $wp_query = (object) array('query_vars' => array());
    
    $this->cookie_manager->expects($this->once())
                         ->method('has_valid_admium_cookie')
                         ->will($this->returnValue(true));
    
    $this->authenticator->authenticate($wp_query);
    
    $this->assertTrue($this->authenticator->is_subscriber());
  }
  
  
  /* When the Admium session cookie is not set, the function "is_subscriber()"
     should be unchanged after admium_handle_cookies runs.
  */
  function testIsSubscriberIsUnchangedOnSessionCookieAbsent() {
    $wp_query = (object) array('query_vars' => array());
    
    $this->cookie_manager->expects($this->once())
                         ->method('has_valid_admium_cookie')
                         ->will($this->returnValue(false));
    
    $before = $this->authenticator->is_subscriber();
    $this->authenticator->authenticate($wp_query);
    $this->assertTrue($this->authenticator->is_subscriber() == $before);
  }
    
}


?>
