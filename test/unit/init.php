<?php
// Define your test Propel class with behavior applied here
define('TEST_CLASS', 'tblSCCite');
// Define a setter method for this article, other than primary key
define('TEST_METHOD', 'setTitle');

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
try
{
  sfContext::getInstance()->getDatabaseManager()->initialize();
}
catch (Exception $e)
{
  $t->fail($e->getMessage());
  return 0;
}

// check test class and method
if (!defined('TEST_CLASS') or !class_exists(TEST_CLASS))
{
  // Don't run tests
  throw new sfRatingException('test class not found');
}
$test_class = TEST_CLASS;

$obj = new $test_class;
if (!is_callable(array($obj, TEST_METHOD)))
{
  // Don't run tests at all
  throw new sfRatingException('test method not found');
}
$test_method = TEST_METHOD;