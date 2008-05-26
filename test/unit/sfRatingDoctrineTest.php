<?php
require_once('init.php');

// start tests
$t = new lime_test(21, new lime_output_color());


$t->diag('create test objects');

try
{
  $obj1 = new $test_class;
  $obj1->$test_method('A test object');
  $obj1->save();

  $obj2 = new $test_class;
  $obj2->$test_method('Another test object');
  $obj2->save();
}
catch (Exception $e)
{
  $t->fail($e->getMessage());
}

$obj1_pk = $obj1->getPrimaryKey();
$t->ok(!is_null($obj1_pk), 'object saved');
$obj2_pk = $obj2->getPrimaryKey();
$t->ok(!is_null($obj2_pk), 'second object saved');

$t->isnt($obj1_pk, $obj2_pk, 'objects has different ids');


$t->diag('create test objects');

$t->is($obj1->hasBeenRated(), false, 'hasBeenRated() Object has not been rated yet');


$t->diag('check hasBeenRated(), hasBeenRatedByUser() and setRating()');

// Tests will be IP address based
$user_1_id = 1;
$user_2_id = 2;
$user_3_id = 3;

$t->ok(!$obj1->hasBeenRatedByUser($user_1_id), 'hasBeenRatedByUser() object has not been rated by user 1 yet');

# User 1 overrate object 1
try
{
  $obj1->setRating(11, $user_1_id);
  $t->fail('setRating() It is possible to overrate an object :(');
}
catch (Exception $e)
{
  $t->pass('setRating() It is impossible to overrate an object');
}

# User 1 rate with a negative value
try
{
  $obj1->setRating(-1, $user_1_id);
  $t->fail('setRating() It is possible to underrate an object :(');
}
catch (Exception $e)
{
  $t->pass('setRating() It is impossible to underrate an object');
}

# User 1 rate with a string
try
{
  $obj1->setRating('rototo', $user_1_id);
  $t->fail('setRating() It is possible to misrate an object :(');
}
catch (Exception $e)
{
  $t->pass('setRating() It is impossible to misrate an object');
}

sfConfig::set('app_rating_default_max', 10);

# User 1 rate object 1 correctly
$u1_rating = 10;
$t->ok($obj1->setRating($u1_rating, $user_1_id), 'setRating() Object rated OK by user 1 to '.$u1_rating);
$t->ok($obj1->hasBeenRated(), 'hasBeenRated() Object has been rated');
$t->is($obj1->hasBeenRatedByUser($user_1_id), true, 'hasBeenRatedByUser() Object has been rated by user 1');
$t->is($obj1->hasBeenRatedByUser($user_2_id), false, 'hasBeenRatedByUser() Object has not been rated by user 2 yet');

$t->is($obj1->getRating(), $u1_rating, 'getRating() rating retrieval OK');
$t->is($obj1->getUserRating($user_2_id), false, 'getUserRating() user rating retrieval OK');
$t->is($obj1->getUserRating($user_1_id), $u1_rating, 'getUserRating() user rating retrieval OK');

# User 2 rate object 1
$t->cmp_ok($obj->getUserRating($user_2_id), '===', false, 'getUserRating() user has not been rated');
$u2_rating = 5;
$t->ok($obj1->setRating($u2_rating, $user_2_id), 'setRating() Object rated by user 2 to '.$u2_rating);
$t->ok($obj1->hasBeenRated(), 'hasBeenRated() Object has been rated');
$t->ok($obj1->hasBeenRatedByUser($user_2_id), 'hasBeenRatedByUser() Object has been rated by user 2');

$t->is($obj1->getRating(), 7.5, 'getRating() rating retrieval OK');
$t->is($obj1->getUserRating($user_2_id), $u2_rating, 'getUserRating() user rating retrieval OK');

# User 1 rates object 2
$obj2->setRating(5, $user_1_id);
$t->is($obj2->getUserRating($user_1_id), 5, 'getUserRating() user rating retrieval OK');
$t->is($obj2->getRating(), 5, 'getRating() rating ok');

$t->is($obj2->clearRatings(), 1, 'clearRatings() clear rating ok');
$t->is($obj2->getRating(), null, 'getRatings() clear rating ok');

# User 2 changes his rating for object 1
$u2_rating = 8;
$t->ok($obj1->setRating($u2_rating, $user_2_id), 'setRating() User 2 changes his rating to '.$u2_rating);
$t->ok($obj1->hasBeenRatedByUser($user_2_id), 'hasBeenRatedByUser() Object is still rated by user 2');

$t->is($obj1->getRating(), 9, 'getRating() rating retrieval = 9');
$t->is($obj1->getUserRating($user_2_id), $u2_rating, 'getUserRating() user rating retrieval OK');

# User 1 changes his rating
$u1_rating = 2;
$t->ok($obj1->setRating($u1_rating, $user_1_id), 'setRating() User 1 changes his rating to '.$u1_rating);
$t->ok($obj1->hasBeenRatedByUser($user_1_id), 'hasBeenRatedByUser() Object is still rated by user 1');

$t->is($obj1->getRating(), 5, 'getRating() rating retrieval OK');
$t->is($obj1->getUserRating($user_1_id), $u1_rating, 'getUserRating() user rating retrieval OK');

# User 1 cancel his rating
$t->ok($obj1->clearUserRating($user_2_id), 'cleanUserRating() User 2 cleans his rating');
$t->ok(!$obj1->hasBeenRatedByUser($user_2_id), 'hasBeenRatedByUser() Object has now not been rated by user 2');
$t->is($obj1->getRating(), $u1_rating, 'getRating() Object rating has been updated');

$t->ok($obj1->clearRatings(), 'cleanRatings() All ratings are cleared');
$t->is($obj1->getRating(), NULL, 'getRating() Rating is now NULL for this object');

// Rating based on a 12 max rating
$obj1->clearRatings();
$obj2->clearRatings();
sfConfig::set(
    sprintf('propel_behavior_sfPropelActAsRatableBehavior_%s_max_rating', 
            get_class($obj1)), 12);

$obj1->setRating(6, $user_1_id);
$obj1->setRating(6, $user_2_id);
$t->is($obj1->getRating(), 6, 'getRating() base12 ok');
$obj1->setRating(12, $user_2_id);
$t->is($obj1->getRating(), 9, 'getRating() base12 ok');
$obj1->setRating(3, $user_1_id);
$t->is($obj1->getRating(), 7.5, 'getRating() base12 ok');

// Testing ratings details retrieval
$obj1->setRating(6, $user_1_id);
$obj1->setRating(6, $user_2_id);
$obj1->setRating(7, $user_3_id);
$details = $obj1->getRatingDetails();
$t->is(count($details), 2, 'getRatingDetails() count ok');
$t->is_deeply($details, array(6 => 2, 7 => 1), 'getRatingDetails() results are conform');

$full_details = $obj1->getRatingDetails(true);
$t->is(count($full_details), 12, 'getRatingDetails(true) count ok');
$expected = array(0=>0, 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>2, 7=>1, 8=>0, 9=>0, 10=>0, 11=>0, 12=>0);
$t->is_deeply($full_details, $expected, 'getRatingDetails(true) results are conform');

// Testing cascade deletion
$obj1_key = $obj1->getPrimaryKey();
$obj1->delete();
$c = new Criteria();
$c->add(sfRatingPeer::RATABLE_ID, $obj1_key);
$count = sfRatingPeer::doCount($c);
$t->is($count, 0, 'doCount() No more rating records for deleted object');

// Delete remaining object
$obj2->delete();

$t->diag('Tests are now terminated');