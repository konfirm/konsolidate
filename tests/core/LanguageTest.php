<?php

class LanguageTest extends PHPUnit_Extensions_Database_TestCase {
  // only instantiate pdo once for test clean-up/fixture load
  static private $pdo = null;

  // only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test
  private $conn = null;

  private $locale = 'en_us';

  private $directory;

  protected function setUp() {
    $this->directory = realpath('./data/language');

    $this->konsolidate = $GLOBALS['konsolidate'];

    parent::setUp();
  }

  final public function getConnection() {
    if ($this->conn === null) {
      if (self::$pdo == null) {
        self::$pdo = new PDO($GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD']);
      }

      $this->conn = $this->createDefaultDBConnection(self::$pdo, $GLOBALS['DB_DBNAME']);
    }

    return $this->conn;
  }

  /**
   * @covers /DB/start
   * @group Core
   * @group DB
   * @author john@konsolidate.nl
   */
  public function getDataSet() {
    return $this->createXMLDataSet('./data/language/switch.xml');
  }

  /**
   * @covers /Language/setLocale
   * @group Core
   * @group Language
   * @author john@konsolidate.nl
   */
  public function testSetLocale() {
    // Positive tests
    $this->konsolidate->call('/Language/setLocale', $this->locale);
    $this->assertSame($this->konsolidate->get('/Language/_locale', 'empty'), $this->locale);
    $this->assertNotEmpty($this->konsolidate->get('/Language/_locale', ''));
  }

  public function testGetLocale() {
    // Positive tests
    $this->konsolidate->call('/Language/setLocale', $this->locale);
    $this->assertSame($this->konsolidate->call('/Language/getLocale', 'empty'), $this->locale);
    $this->assertNotEmpty($this->konsolidate->call('/Language/getLocale'));
  }

  public function testSetEngine() {
    $engine = 'bogusEngine';

    // Positive tests
    $this->konsolidate->call('/Language/setEngine', $engine);
    $this->assertSame($this->konsolidate->get('/Language/_engine', 'empty'), $engine);
    $this->assertNotEmpty($this->konsolidate->get('/Language/_engine'));
  }

  public function testTranslate() {
    // Depends on DB module
    $this->konsolidate->call('/DB/setConnection', 'konsolidate', $GLOBALS['DB_CONNECTION_STRING']);

    // Positive tests
    $this->konsolidate->call('/Language/setLocale', $this->locale);
    $translated = $this->konsolidate->call('/Language/translate', 'konsolidate');
    var_dump('TRANSLATED - ' . $translated);
    // $translated = $this->konsolidate->call('/Language/Switch/translate', 'foo');
  }
}