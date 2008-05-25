<?php
/*
 * This file is part of the sfPropelActAsRatableBehavior package.
 *
 * (c) 2007 Nicolas Perriault <nperriault@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

sfPropelBehavior::registerHooks('sfRatingPropelBehavior', array (
  ':delete:pre' => array ('sfRatingPropelBehavior', 'preDelete'),
));

sfPropelBehavior::registerMethods('sfRatingPropelBehavior', array (
  array('sfRatingPropelBehavior', 'countRatings'),
  array('sfRatingPropelBehavior', 'setRating'),
//  array('sfRatingPropelBehavior', 'getMaxRating'),
  array('sfRatingPropelBehavior', 'getRating'),
  array('sfRatingPropelBehavior', 'getRatingDetails'),
  array('sfRatingPropelBehavior', 'getReferenceKey'),
  array('sfRatingPropelBehavior', 'getUserRating'),
  array('sfRatingPropelBehavior', 'hasBeenRated'),
  array('sfRatingPropelBehavior', 'hasBeenRatedByUser'),
  array('sfRatingPropelBehavior', 'clearRatings'),
  array('sfRatingPropelBehavior', 'clearUserRating'),
));                 
