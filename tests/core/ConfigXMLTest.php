<?php

use PHPUnit\Framework\TestCase;

class ConfigXMLTest extends TestCase {
  protected $directory;

  protected function setUp() {
    $this->directory = realpath('./data/config/xml');

    $this->konsolidate = $GLOBALS['konsolidate'];
  }

  /**
   * @covers /Config/XML/load
   * @group Core
   * @group Config
   * @group ConfigXML
   * @author john@konsolidate.nl
   */
  public function testLoad() {
    $file = $this->directory . '/config.xml';

    $result = $this->konsolidate->call('/Config/XML/load', $file);

    // Positive tests
    $this->assertTrue($result);
    $this->assertSame($this->konsolidate->get('/Config/XML/unittest', 'failed'), 'success');
  }
}
