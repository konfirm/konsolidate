<?php

use PHPUnit\Framework\TestCase;

class ConfigINITest extends TestCase {
  protected $directory;

  protected function setUp() {
    $this->directory = realpath('./data/config/ini');

    $this->konsolidate = $GLOBALS['konsolidate'];
  }

  /**
   * @covers /Config/INI/load
   * @group Core
   * @group Config
   * @group ConfigINI
   * @author john@konsolidate.nl
   */
  public function testLoad() {
    $file = $this->directory . '/load.ini';

    $result = $this->konsolidate->call('/Config/INI/load', $file);

    // Positive tests
    $this->assertArrayHasKey('ini', $result);
    $this->assertArrayHasKey('unittest', $result['ini']);
    $this->assertSame($this->konsolidate->get('/Config/INI/unittest', 'failed'), 'success');
  }

  /**
   * @covers /Config/INI/loadAndDefine
   * @group Core
   * @group Config
   * @group ConfigINI
   * @author john@konsolidate.nl
   */
  public function testLoadAndDefine() {
    $file = $this->directory . '/loadAndDefine.ini';

    $this->konsolidate->call('/Config/INI/loadAndDefine', $file);

    // Positive tests
    $this->assertTrue(defined('INI_UNITTEST'));
    $this->assertSame(INI_UNITTEST, 'Loaded and defined');
  }
}
