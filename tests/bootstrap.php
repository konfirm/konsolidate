<?php

$corePath = '..';

require_once('vendor/autoload.php');
require_once(sprintf('%s/konsolidate.class.php', $corePath));

$konsolidate = new Konsolidate(array(
  'Core' => sprintf('%s/core', $corePath)
));

$GLOBALS['konsolidate'] = $konsolidate;
