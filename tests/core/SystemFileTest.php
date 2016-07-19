<?php

use PHPUnit\Framework\TestCase;

class SystemFileTest extends TestCase {
  protected $directory;

  protected function setUp() {
    $this->directory = realpath('./data/system/file');

    $this->konsolidate = $GLOBALS['konsolidate'];
  }

  /**
   * @covers /System/File/read
   * @group Core
   * @group System
   * @group SystemFile
   * @author john@konsolidate.nl
   */
  public function testRead() {
    $file = $this->directory . '/read.txt';
    $content = $this->konsolidate->call('/System/File/read', $file);

    // Positive tests
    $this->assertStringEqualsFile($file, $content);

    // Negative tests
    $this->assertStringNotEqualsFile($file, md5($content), 'MD5 hashed string has been validated as equal to contents of file');
  }

  /**
   * @covers /System/File/write
   * @group Core
   * @group System
   * @group SystemFile
   * @author john@konsolidate.nl
   */
  public function testWrite() {
    $time = sprintf('Write microtime: %f', microtime(true));
    $file = $this->directory . '/write.txt';
    $content = $this->konsolidate->call('/System/File/write', $file, $time);

    // Positive tests
    $this->assertStringEqualsFile($file, $time);

    // Negative tests
    $this->assertStringNotEqualsFile($file, md5($time), 'MD5 hashed string has been validated as equal to contents of file');
  }

  /**
   * @covers /System/File/mode
   * @group Core
   * @group System
   * @group SystemFile
   * @author john@konsolidate.nl
   */
  public function testMode() {
    $file = $this->directory . '/mode.txt';
    $result = $this->konsolidate->call('/System/File/mode', $file, 0777);

    // Positive tests
    $this->assertTrue($result);
  }

  /**
   * @covers /System/File/unlink
   * @group Core
   * @group System
   * @group SystemFile
   * @author john@konsolidate.nl
   */
  public function testUnlink() {
    $file = $this->directory . '/unlink.txt';
    $this->assertFileExists($file);
    $this->konsolidate->call('/System/File/unlink', $file);

    // Positive tests
    $this->assertFileNotExists($file);
  }

  /**
   * @covers /System/File/rename
   * @group Core
   * @group System
   * @group SystemFile
   * @author john@konsolidate.nl
   */
  public function testRename() {
    $file = $this->directory . '/rename.txt';
    $renamed = $this->directory . '/rename-success.txt';

    $this->assertFileExists($file);
    $this->assertFileNotExists($renamed);

    $this->konsolidate->call('/System/File/rename', $file, $renamed);

    // Positive tests
    $this->assertFileNotExists($file);
    $this->assertFileExists($renamed);
  }

  /**
   * @covers /System/File/open
   * @group Core
   * @group System
   * @group SystemFile
   * @author john@konsolidate.nl
   */
  public function testOpen() {
  }

  /**
   * @covers /System/File/get
   * @group Core
   * @group System
   * @group SystemFile
   * @author john@konsolidate.nl
   */
  public function testGet() {
  }

  /**
   * @covers /System/File/put
   * @group Core
   * @group System
   * @group SystemFile
   * @author john@konsolidate.nl
   */
  public function testPut() {
  }

  /**
   * @covers /System/File/next
   * @group Core
   * @group System
   * @group SystemFile
   * @author john@konsolidate.nl
   */
  public function testNext() {
  }

  /**
   * @covers /System/File/close
   * @group Core
   * @group System
   * @group SystemFile
   * @author john@konsolidate.nl
   */
  public function testClose() {
  }

  /**
   * @covers /System/File/etFilePointer
   * @group Core
   * @group System
   * @group SystemFile
   * @author john@konsolidate.nl
   */
  public function testGetFilePointer() {
  }

  protected function tearDown() {
    // Write
    file_put_contents($this->directory . '/write.txt', 'Test file for SystemFileTest->testWrite()');

    // Mode
    if (file_exists($this->directory . '/mode.txt')) {
      chmod($this->directory . '/mode.txt', 0664);
    }

    // Unlink
    file_put_contents($this->directory . '/unlink.txt', 'Test file for SystemFileTest->testUnlink()');

    // Rename
    if (file_exists($this->directory . '/rename-success.txt')) {
      rename($this->directory . '/rename-success.txt', $this->directory . '/rename.txt');
    }
  }
}
