<?php
namespace Drupal\Tests\smplphotoalbum\Functional;

use Drupal\Tests\BrowserTestBase;

class FilterTest extends BrowserTestBase{
  /**
   * Modules to enable.
   *
   * @var array<string>
   */
  protected static $modules = ['smplphotoalbum'];
  /**
   * Schema hiba miatt
   * @var 
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Theme to enable. This field is mandatory.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';
  /**
   * Test user
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Create a user. 
    $this->adminUser = $this->drupalCreateUser( [ "administer site configuration", ] ); 
    var_dump($this->adminUser);
    $this->drupalLogin($this->adminUser); 
  }

  /**
   * Load Main page
   * 
   * @return void
   */
  public function testLoad(){
    $this->drupalGet("node/1");
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $text = $this->getSession()->getPage()->getContent();
    //var_dump($text);
    $this->assertStringContainsString("SmplTableAll",$text, "There is no 'SmplTableAll' in the HTMl code" );
  }
}
