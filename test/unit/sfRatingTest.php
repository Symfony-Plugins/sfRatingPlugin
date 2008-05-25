<?php
$sf_root_dir = realpath(dirname(__FILE__).'/../../../../');
$apps_dir = glob($sf_root_dir.'/apps/*', GLOB_ONLYDIR);
$app = substr($apps_dir[0], 
              strrpos($apps_dir[0], DIRECTORY_SEPARATOR) + 1, 
              strlen($apps_dir[0]));
if (!$app)
{
  throw new Exception('No app has been detected in this project');
}

// Symfony test env bootstrap
require_once($sf_root_dir.'/test/bootstrap/functional.php');

$sf_symfony_lib_dir = sfConfig::get('sf_symfony_lib_dir');
require_once ($sf_symfony_lib_dir . '/util/sfCore.class.php');

sfCore::initSimpleAutoload(array(SF_ROOT_DIR . '/lib', SF_ROOT_DIR . '/plugins', $sf_symfony_lib_dir));

// initialize the db connections
sfContext::getInstance()->getDatabaseManager()->initialize();

// start tests
$t = new lime_test(20, new lime_output_color());

$object_name = 'tblSCCite';

$t->diag('check getMaxRating()');

// default
$t->is(sfRatingToolkit::getMaxRating(new $object_name), 5, 'retrieve correct default value');

sfConfig::set('app_rating_default_max', 8);
$t->is(sfRatingToolkit::getMaxRating(new $object_name), 8, 'retrieve correct default value, even when set');

sfConfig::set('app_rating_default_max', "8");
$t->isa_ok(sfRatingToolkit::getMaxRating(new $object_name), 'integer', 'MAX_RATING is an integer');

// get
sfConfig::set('app_rating_max_'.$object_name, 5);
$t->is(sfRatingToolkit::getMaxRating(new $object_name), 5, 'retrieve correct value');

sfConfig::set('app_rating_max_'.$object_name, 10);
$t->is(sfRatingToolkit::getMaxRating(new $object_name), 10, 'retrieve correct value, even when changed');

// interval
try
{
  sfConfig::set('app_rating_max_'.$object_name, 11);
  sfRatingToolkit::getMaxRating(new $object_name);

  $t->fail('no code should be executed after throwing an sfRatingException');
}
catch (sfRatingException $e)
{
  $t->pass('throw exception when greather than 10');
}

try
{
  sfConfig::set('app_rating_max_'.$object_name, 0);
  sfRatingToolkit::getMaxRating(new $object_name);

  $t->fail('no code should be executed after throwing an sfRatingException');
}
catch (sfRatingException $e)
{
  $t->pass('throw exception when less than 1');
}

$t->todo('check getUserId()');
$t->todo('check addTokenToSession()');


$t->diag('check generateToken()');

$t->is(sfRatingToolkit::generateToken($object_name, 23), md5($object_name.'-23-r4t4bl3'), 'without config salt');

$salt = '3kbt2';
sfConfig::set('app_rating_salt', $salt);
$t->is(sfRatingToolkit::generateToken($object_name, 54), md5($object_name.'-54-'.$salt), 'with config salt');


$t->todo('check retrieveFromToken()');
$t->todo('check getObjectRatingField()');
$t->todo('check getObjectReferenceField()');
$t->todo('check getReferenceKey()');


$t->diag('check getPrecision()');

$t->is(sfRatingToolkit::getPrecision(), 2, 'without any default');

sfConfig::set('app_rating_default_precision', 3);
$t->is(sfRatingToolkit::getPrecision(), 3, 'with config default');

$t->is(sfRatingToolkit::getPrecision(4), 4, 'with argument');

sfConfig::set('app_rating_precision', 6);
$t->is(sfRatingToolkit::getPrecision(), 6, 'with global');

sfConfig::set('app_rating_precision', 2);
$t->is(sfRatingToolkit::getPrecision(4), 2, 'with global and argument');
