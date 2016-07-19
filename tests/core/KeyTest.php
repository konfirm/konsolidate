<?php

use PHPUnit\Framework\TestCase;

class KeyTest extends TestCase {
  protected function setUp() {
    $this->konsolidate = $GLOBALS['konsolidate'];
  }

  /**
   * @covers /Key/create
   * @group Core
   * @group Key
   * @author john@konsolidate.nl
   */
  public function testCreate() {
    // Create random format
    $format = str_shuffle('XXXXXXXXXXXXXXXXXXXX---');
    $this->konsolidate->set('/Key/format', $format);
    $key = $this->konsolidate->call('/Key/create');

    // Positive tests
    $this->assertEquals($this->konsolidate->get('/Key/_format'), $format);
    $this->assertRegExp(sprintf('/%s/', str_replace('X', '\w{1}', $format)), $key);

    // Negative tests
    $format = substr($format, 1);
    $this->assertNotEquals($this->konsolidate->get('/Key/_format'), $format);
  }
}
