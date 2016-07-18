<?php

use PHPUnit\Framework\TestCase;

class ValidateTest extends TestCase {
  public function setUp() {
    $this->konsolidate = $GLOBALS['konsolidate'];
  }

  /**
   * @covers /Validate/isInteger
   * @group Core
   * @author john@konsolidate.nl
   */
  public function testIsInteger() {
    // Positive tests
    $this->assertTrue($this->konsolidate->call('/Validate/isInteger', -42));
    $this->assertTrue($this->konsolidate->call('/Validate/isInteger', 42, true));

    // Negative tests
    $this->assertFalse($this->konsolidate->call('/Validate/isInteger', M_PI), 'Float M_PI has been validated as an integer.');
    $this->assertFalse($this->konsolidate->call('/Validate/isInteger', 'fortytwo'), 'String "fortytwo" has been validated as an integer.');
    $this->assertFalse($this->konsolidate->call('/Validate/isInteger', '42'), 'String "42" has been validated as an integer.');
    $this->assertFalse($this->konsolidate->call('/Validate/isInteger', array()), 'Empty array has been validated as an integer.');
  }

  /**
   * @covers /Validate/isPositiveInteger
   * @group Core
   * @author john@konsolidate.nl
   */
  public function testIsPositiveInteger() {
    // Positive tests
    $this->assertTrue($this->konsolidate->call('/Validate/isPositiveInteger', 42));
    $this->assertTrue($this->konsolidate->call('/Validate/isPositiveInteger', 42, true));

    // Negative tests
    $this->assertFalse($this->konsolidate->call('/Validate/isInteger', M_PI), 'Float M_PI has been validated as a positive integer.');
    $this->assertFalse($this->konsolidate->call('/Validate/isPositiveInteger', '42'), 'String "42" has been validated as a positive integer');
    $this->assertFalse($this->konsolidate->call('/Validate/isPositiveInteger', -42), '-42 has been validated as a positive integer');
    $this->assertFalse($this->konsolidate->call('/Validate/isPositiveInteger', -42, true), '-42 has been validated as a positive integer');
  }

  /**
   * @covers /Validate/isNegativeInteger
   * @group Core
   * @author john@konsolidate.nl
   */
  public function testIsNegativeInteger() {
    // Positive tests
    $this->assertTrue($this->konsolidate->call('/Validate/isNegativeInteger', -42));

    // Negative tests
    $this->assertFalse($this->konsolidate->call('/Validate/isNegativeInteger', 42), '42 has been validated as a negative integer');
    $this->assertFalse($this->konsolidate->call('/Validate/isInteger', M_PI), 'Float M_PI has been validated as a negative integer');
    $this->assertFalse($this->konsolidate->call('/Validate/isNegativeInteger', '-42'), 'String "-42" has been validated as a negative integer');
    $this->assertFalse($this->konsolidate->call('/Validate/isNegativeInteger', 'fortytwo'), 'String "fortytwo" has been validated as a negative integer');
    $this->assertFalse($this->konsolidate->call('/Validate/isNegativeInteger', array()), 'Empty array has been validated as a negative integer');
  }

  /**
   * @covers /Validate/isNumber
   * @group Core
   * @author john@konsolidate.nl
   */
  public function testIsNumber() {
    // Positive tests
    $this->assertTrue($this->konsolidate->call('/Validate/isNumber', 42));
    $this->assertTrue($this->konsolidate->call('/Validate/isNumber', -42));
    $this->assertTrue($this->konsolidate->call('/Validate/isNumber', '42'));
    $this->assertTrue($this->konsolidate->call('/Validate/isNumber', '-42'));
    $this->assertTrue($this->konsolidate->call('/Validate/isNumber', M_PI));

    // Negative tests
    $this->assertFalse($this->konsolidate->call('/Validate/isNumber', '42'), 'String "42" has been validated as a number');
    $this->assertFalse($this->konsolidate->call('/Validate/isNumber', 'fortytwo'));
    $this->assertFalse($this->konsolidate->call('/Validate/isNumber', array()));
  }

  /**
   * @covers /Validate/isBetween
   * @group Core
   * @author john@konsolidate.nl
   */
  public function testIsBetween() {
    // Positive tests
    $this->assertTrue($this->konsolidate->call('/Validate/isBetween', 1, 1, 3));
    $this->assertTrue($this->konsolidate->call('/Validate/isBetween', 2, 1, 3));
    $this->assertTrue($this->konsolidate->call('/Validate/isBetween', 2, 1, 3, false));
    $this->assertTrue($this->konsolidate->call('/Validate/isBetween', 3, 1, 3));

    // Negative tests
    $this->assertFalse($this->konsolidate->call('/Validate/isBetween', 0, 1, 3), '0 has been validated as being between 1 and 3');
    $this->assertFalse($this->konsolidate->call('/Validate/isBetween', 4, 1, 3), '4 has been validated as being between 1 and 3');
    $this->assertFalse($this->konsolidate->call('/Validate/isBetween', 1, 1, 3, false), '1 has been validated as being between 1 and 3, not including 1 and 3');
    $this->assertFalse($this->konsolidate->call('/Validate/isBetween', 1, 3, 3, false), '3 has been validated as being between 1 and 3, not including 1 and 3');
  }

  /**
   * @covers /Validate/isFilled
   * @group Core
   * @author john@konsolidate.nl
   */
  public function testIsFilled() {
    // Positive tests
    $this->assertTrue($this->konsolidate->call('/Validate/isFilled', 0));
    $this->assertTrue($this->konsolidate->call('/Validate/isFilled', 1));
    $this->assertTrue($this->konsolidate->call('/Validate/isFilled', 'konsolidate'));

    // Negative tests
    $this->assertFalse($this->konsolidate->call('/Validate/isFilled', ''), 'Empty string has been validated as being filled');
    $this->assertFalse($this->konsolidate->call('/Validate/isFilled', null), 'Null has been validated as being filled');
  }

  /**
   * @covers /Validate/isEmail
   * @group Core
   * @author john@konsolidate.nl
   */
  public function testIsEmail() {
    // Positive tests
    $this->assertTrue($this->konsolidate->call('/Validate/isEmail', 'info@konsolidate.nl'));
    $this->assertTrue($this->konsolidate->call('/Validate/isEmail', 'a.b@c.com'));
    $this->assertTrue($this->konsolidate->call('/Validate/isEmail', '_@d.info'));;
    $this->assertTrue($this->konsolidate->call('/Validate/isEmail', 'vincent@vangogh.museum'));
    $this->assertTrue($this->konsolidate->call('/Validate/isEmail', 'we@love.technology'));
    $this->assertTrue($this->konsolidate->call('/Validate/isEmail', 'we@love.中文网'));

    // Negative tests
    $this->assertFalse($this->konsolidate->call('/Validate/isFilled', ''), 'Empty string has been validated as being filled');
  }
}
