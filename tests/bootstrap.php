<?php

$corePath = '..';

require_once('vendor/autoload.php');
require_once(sprintf('%s/konsolidate.class.php', $corePath));

$konsolidate = new Konsolidate(array(
  'Core' => sprintf('%s/core', $corePath)
));

$GLOBALS['konsolidate'] = $konsolidate;
$GLOBALS['DB_CONNECTION_STRING'] = sprintf('mysql://%s:%s@%s/%s',
  $GLOBALS['DB_USER'],
  $GLOBALS['DB_PASSWD'],
  $GLOBALS['DB_HOST'],
  $GLOBALS['DB_DBNAME']
);
