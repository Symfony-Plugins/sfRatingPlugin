<?php
require_once('init.php');

// start tests
$t = new lime_test(21, new lime_output_color());


$t->diag('check getMaxRating()');

// default
$t->is(sfRatingToolkit::getMaxRating(new $test_class), 5, 'retrieve correct default value');

sfConfig::set('app_rating_default_max', 8);
$t->is(sfRatingToolkit::getMaxRating(new $test_class), 8, 'retrieve correct default value, even when set');

sfConfig::set('app_rating_default_max', "8");
$t->isa_ok(sfRatingToolkit::getMaxRating(new $test_class), 'integer', 'MAX_RATING is an integer');

// get
sfConfig::set('app_rating_max_'.$test_class, 5);
$t->is(sfRatingToolkit::getMaxRating(new $test_class), 5, 'retrieve correct value');

sfConfig::set('app_rating_max_'.$test_class, 10);
$t->is(sfRatingToolkit::getMaxRating(new $test_class), 10, 'retrieve correct value, even when changed');

// interval
try
{
  sfConfig::set('app_rating_max_'.$test_class, 11);
  sfRatingToolkit::getMaxRating(new $test_class);

  $t->fail('no code should be executed after throwing an sfRatingException');
}
catch (sfRatingException $e)
{
  $t->pass('throw exception when greather than 10');
}

try
{
  sfConfig::set('app_rating_max_'.$test_class, 0);
  sfRatingToolkit::getMaxRating(new $test_class);

  $t->fail('no code should be executed after throwing an sfRatingException');
}
catch (sfRatingException $e)
{
  $t->pass('throw exception when less than 1');
}


$t->todo('check getUserId()');

$t->is(sfRatingToolkit::getUserId(), null, 'no auth');


$t->todo('check addTokenToSession()');


$t->diag('check generateToken()');

$t->is(sfRatingToolkit::generateToken($test_class, 23), md5($test_class.'-23-r4t4bl3'), 'without config salt');

$salt = '3kbt2';
sfConfig::set('app_rating_salt', $salt);
$t->is(sfRatingToolkit::generateToken($test_class, 54), md5($test_class.'-54-'.$salt), 'with config salt');


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
