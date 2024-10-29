<?php


require_once 'PHPUnit.php';
require_once dirname(__FILE__) . '/../class-token-validator.php';
require_once dirname(__FILE__) . '/../class-admium-globals.php';
require_once dirname(__FILE__) . '/../class-php-wrapper.php';
require_once dirname(__FILE__) . '/../class-wordpress-api-wrapper.php';


class TokenValidatorTest extends PHPUnit_Framework_TestCase {
  protected $tv;
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

    $this->tv = new TokenValidator($this->php, $this->ag, $this->wpapi);
  }

  /****************************************************************************
                          Tests for validate()
   ***************************************************************************/
   
   function testValidateReturnsFalseWhenServerRespondsNegatively() {
     $invalid_response = 'invalid';
     
     $this->wpapi->expects($this->once())
                ->method('wp_remote_get')
                ->will($this->returnValue(array('body' => $invalid_response)));

     $this->wpapi->expects($this->once())
                 ->method('is_wp_error')
                 ->will($this->returnValue(false));
      
     $this->ag->expects($this->once())
              ->method('validation_failed_response')
              ->will($this->returnValue($invalid_response));

     $retval = $this->tv->validate('');
     $this->assertTrue($retval == false);
   }
   
   function testValidateReturnsEmailAndExpirationWhenServerResponseAffirmatively() {
     $email = 'a@b.com';
     $expiry = '2011-10-13';
     
     $this->wpapi->expects($this->once())
                 ->method('wp_remote_get')
                 ->will($this->returnValue(array('body' => $email . ',' . $expiry)));
      
      $this->wpapi->expects($this->once())
                  ->method('is_wp_error')
                  ->will($this->returnValue(false));
                  
      $retval = $this->tv->validate('');      
      $this->assertTrue($retval == array($email, $expiry));
    }
    
    function testValidateURLIsProductionServerByDefault() {      
      $this->ag->expects($this->once())
               ->method('token_validation_url')
               ->with(false);
      $this->tv->validate('');
    }
    
    function testValidateURLIsTestServerWhenTestArgTrue() {      
      $this->ag->expects($this->once())
               ->method('token_validation_url')
               ->with(true);
      $this->tv->validate('', true);
    }
   
}


?>
