<?php
/*
 * This file is part of the sfPropelActAsRatableBehavior package.
 *
 * (c) 2007 Nicolas Perriault <nperriault@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * This Propel behavior aims at providing rating capabilities on any Propel
 * object
 *
 * @package    plugins
 * @subpackage rating 
 * @author     Nicolas Perriault <nperriault@gmail.com>
 * @author     Fabian Lange
 * @author     Vojtech Rysanek
 */
class sfRatingPropelBehavior
{
  
  /**
   * Counts ratings made on given ratable object.
   * 
   * @param  BaseObject  $object
   * @return int
   */
  public function countRatings(BaseObject $object)
  {
    $c = new Criteria();
    $c->add(sfRatingPeer::RATABLE_ID, sfRatingToolkit::getReferenceKey($object));
    $c->add(sfRatingPeer::RATABLE_MODEL, get_class($object));
    return sfRatingPeer::doCount($c);
  }

  /**
   * Retrieves an existing rating object, or return a new empty one
   *
   * @param  BaseObject  $object
   * @param  mixed       $user_id  Unique user primary key
   * @return sfRating
   * @throws sfPropelActAsRatableException
   **/
  protected static function getOrCreate(BaseObject $object, $user_id = null)
  {
    if ($object->isNew())
    {
      throw new sfRatingException('Unsaved objects are not ratable');
    }
    
    if (is_null($user_id))
    {
      return new sfRating();
    }
    
    $c = new Criteria();
    $c->add(sfRatingPeer::RATABLE_ID, sfRatingToolkit::getReferenceKey($object));
    $c->add(sfRatingPeer::RATABLE_MODEL, get_class($object));
    $c->add(sfRatingPeer::USER_ID, $user_id);
    $user_rating = sfRatingPeer::doSelectOne($c);
    return is_null($user_rating) ? new sfRating() : $user_rating;
  }

  /**
   * Clear all ratings for an object
   *
   * @param  BaseObject  $object
   **/
  public function clearRatings(BaseObject $object)
  {
    $c = new Criteria();
    $c->add(sfRatingPeer::RATABLE_ID, sfRatingToolkit::getReferenceKey($object));
    $c->add(sfRatingPeer::RATABLE_MODEL, get_class($object));
    $ret = sfRatingPeer::doDelete($c);
    self::setRatingToObject($object, 0);
    return $ret;
  }

  /**
   * Clear user rating for an object
   *
   * @param  BaseObject  $object
   * @param  mixed       $user_id  User primary key
   **/
  public function clearUserRating(BaseObject $object, $user_id)
  {
    if (is_null($user_id) or trim((string)$user_id) === '')
    {
      throw new sfRatingException('Impossible to clear a user rating with no user primary key provided');
    }
    
    $c = new Criteria();
    $c->add(sfRatingPeer::RATABLE_ID, sfRatingToolkit::getReferenceKey($object));
    $c->add(sfRatingPeer::RATABLE_MODEL, get_class($object));
    $c->add(sfRatingPeer::USER_ID, $user_id);
    $ret = sfRatingPeer::doDelete($c);
    self::setRatingToObject($object, $this->getRating($object, sfRatingToolkit::getPrecision(), true));
    return $ret;
  }

  /**
   * Checks if an Object has been rated
   *
   * @param  BaseObject  $object
   **/
  public function hasBeenRated(BaseObject $object)
  {
    $c = new Criteria();
    $c->add(sfRatingPeer::RATABLE_ID, sfRatingToolkit::getReferenceKey($object));
    $c->add(sfRatingPeer::RATABLE_MODEL, get_class($object));
    $c->clearSelectColumns();
    $c->addSelectColumn(sfRatingPeer::ID);
    $c->setLimit(1);
    return sfRatingPeer::doSelectRS($c)->getRecordCount() > 0;
  }

  /**
   * Checks if an Object has been rated by a user
   *
   * @param  BaseObject  $object
   * @param  mixed       $user_id  Unique reference to a user
   **/
  public function hasBeenRatedByUser(BaseObject $object, $user_id)
  {
    if (is_null($user_id) or trim((string)$user_id) === '')
    {
      throw new sfRatingException(
        'Impossible to check a user rating with no user primary key provided');
    }
    $c = new Criteria();
    $c->add(sfRatingPeer::RATABLE_ID, sfRatingToolkit::getReferenceKey($object));
    $c->add(sfRatingPeer::RATABLE_MODEL, get_class($object));
    $c->add(sfRatingPeer::USER_ID, $user_id);
    $c->clearSelectColumns();
    $c->addSelectColumn(sfRatingPeer::ID);
    $c->setLimit(1);
    return sfRatingPeer::doSelectRS($c)->getRecordCount() > 0;
  }

  /**
   * Retrieves the object rating
   *
   * @param  BaseObject  $object
   * @param  int         $precision   Result float precision
   * @return float
   **/
  public function getRating(BaseObject $object, $precision=2, $docount=false)
  {
    if ($docount === false && !is_null(sfRatingToolkit::getObjectRatingField($object)))
    {
      return round(self::getRatingToObject($object), sfRatingToolkit::getPrecision());
    }
    
    $c = new Criteria();
    $c->add(sfRatingPeer::RATABLE_ID, sfRatingToolkit::getReferenceKey($object));
    $c->add(sfRatingPeer::RATABLE_MODEL, get_class($object));
    $c->clearSelectColumns();
    $c->addAsColumn('nb_ratings', 'COUNT('.sfRatingPeer::ID.')');
    $c->addAsColumn('total', 'SUM('.sfRatingPeer::RATING.')');
    $c->addGroupByColumn(sfRatingPeer::RATABLE_MODEL);
    $rs = sfRatingPeer::doSelectRS($c);
    $rs->setFetchmode(ResultSet::FETCHMODE_ASSOC);
    while ($rs->next())
    {
      $nb_ratings = $rs->getInt('nb_ratings');
      $total      = $rs->getInt('total');
      if (!$nb_ratings or $nb_ratings === 0)
      {
        return NULL; // Object has not been rated yet
      }
      return round($total / $nb_ratings, self::getPrecision($precision));
    }
  }
  
  /**
   * Gets the object rating details
   *
   * @author Fabian Lange
   * @author Nicolas Perriault
   * @param  BaseObject  $object
   * @param  boolean     $include_all  Shall we include all available ratings?
   * @return associative array containing (rating => count)
   **/
  public function getRatingDetails(BaseObject $object, $include_all = false)
  {
    $c = new Criteria();
    $c->add(sfRatingPeer::RATABLE_ID, sfRatingToolkit::getReferenceKey($object));
    $c->add(sfRatingPeer::RATABLE_MODEL, get_class($object));
    $c->clearSelectColumns();
    $c->addAsColumn('nb_ratings', 'COUNT('.sfRatingPeer::ID.')');
    $c->addAsColumn('rating', sfRatingPeer::RATING);
    $c->addGroupByColumn(sfRatingPeer::RATING);
    $rs = sfRatingPeer::doSelectRS($c);
    $rs->setFetchmode(ResultSet::FETCHMODE_ASSOC);
    $details = array();
    while ($rs->next())
    {
      $details = $details + array ($rs->getInt('rating') => (int)$rs->getString('nb_ratings'));
    }
    if ($include_all === true)
    {
      for ($i=1; $i<=sfRatingToolkit::getMaxRating($object); $i++)
      {
        if (!array_key_exists($i, $details))
        {
          $details[$i] = 0;
        }
      }
    }
    ksort($details);
    return $details;
  }
  
  /**
   * Gets the object rating for given user pk
   *
   * @param  BaseObject  $object
   * @param  mixed       $user_id  User primary key
   * @return int or false
   **/
  public function getUserRating(BaseObject $object, $user_id)
  {
    if (is_null($user_id) or trim((string)$user_id) === '')
    {
      throw new sfRatingException(
        'Impossible to get a user rating with no user primary key provided');
    }
    
    $c = new Criteria();
    $c->add(sfRatingPeer::RATABLE_ID, sfRatingToolkit::getReferenceKey($object));
    $c->add(sfRatingPeer::RATABLE_MODEL, get_class($object));
    $c->add(sfRatingPeer::USER_ID, $user_id);
    $rating_object = sfRatingPeer::doSelectOne($c);
    if (!is_null($rating_object))
    {
      return $rating_object->getRating();
    }
  }
  
  /**
   * Retrieves ratable object instance from class name and key
   * 
   * @param  string  $class_name
   * @param  int     $key
   * @return BaseObject
   */
  public static function retrieveByKey($object_name, $key)
  {
    if (!class_exists($object_name))
    {
      throw new sfRatingException('Class %s does not exist', 
                                              $object_name);
    }
    $object = new $object_name;
    $peer = $object->getPeer();
    $field = sfRatingToolkit::getObjectReferenceField($object);
    if (is_null($field))
    {
      return call_user_func(array($peer, 'retrieveByPK'), $key);
    }
    else
    {
      $column = call_user_func(array($peer, 'translateFieldName'),
                               $field, 
                               BasePeer::TYPE_PHPNAME, 
                               BasePeer::TYPE_COLNAME);
      $c = new Criteria();
      $c->add($column, $key);
      return call_user_func(array($peer, 'doSelectOne'), $c);
    }
  }

  /**
   * Rates the Object
   *
   * @param  BaseObject  $object
   * @param  int         $rating
   * @param  mixed       $user_id  Optionnal unique reference to user
   * @throws sfRatingException
   **/
  public function setRating(BaseObject $object, $rating, $user_id = null)
  {
    if (is_float($rating) && floor($rating) != $rating)
    {
      throw new sfRatingException(
        sprintf('You cannot rate an object with a float (you provided "%s")', 
                $rating));
    }
    
    $rating = (int)$rating;
    
    if ($rating > sfRatingToolkit::getMaxRating($object))
    {
      throw new sfRatingException(
        sprintf('Maximum rating is %d', sfRatingToolkit::getMaxRating($object)));
    }
    
    if ($rating < 1)
    {
      throw new sfRatingException('Minimum rating is 1');
    }
    
    $rating_object = self::getOrCreate($object, $user_id);
    $rating_object->setRatableModel(get_class($object));
    $rating_object->setRatableId(sfRatingToolkit::getReferenceKey($object));
    $rating_object->setUserId($user_id);
    $rating_object->setRating($rating);
    $ret = $rating_object->save();
    self::setRatingToObject($object, $this->getRating($object, sfRatingToolkit::getPrecision(), true));
    return $ret;
  }
  
  /**
   * Deletes all rating for a ratable object (delete cascade emulation)
   * 
   * @param  BaseObject  $object
   */
  public function preDelete(BaseObject $object)
  {
    try
    {
      $c = new Criteria();
      $c->add(sfRatingPeer::RATABLE_ID, sfRatingToolkit::getReferenceKey($object));
      sfRatingPeer::doDelete($c);
    }
    catch (Exception $e)
    {
      throw new sfRatingException(
        'Unable to delete ratable object related ratings records');
    }
  }
  
  /*
   * Contributed by Vojtech Rysanek
   */

  /**
   * Sets cached rating
   * 
   * @param  BaseObject  $object
   * @param  float       $value
   */
  protected static function setRatingToObject(BaseObject $object, $value)
  {
    $field = sfRatingToolkit::getObjectRatingField($object);
    if (!is_null($field)) 
    {
      $setter = 'set'.$field;
      if (method_exists($object, $setter))
      {
        $ret = $object->$setter($value);
        return $object->save();
      }
    }
  } 
  
  /**
   * Return cached rating from object
   * 
   * @param  BaseObject  $object
   * @return float
   */
  protected static function getRatingToObject(BaseObject $object)
  {
    $field = sfRatingToolkit::getObjectRatingField($object);
    if (!is_null($field)) 
    {
      $getter = 'get'.$field; 
      if (method_exists($object, $getter))
      {
        return $object->$getter();
      }
    }
    return null;
  }

  /**
   * Retrieve a ratable object
   * 
   * @param  string  $object_model
   * @param  int     $object_id
   */
  public static function retrieveRatableObject($object_model, $object_id)
  {
    try
    {
      $peer = sprintf('%sPeer', $object_model);

      if (!class_exists($peer))
      {
        throw new sfRatingException(sprintf('Unable to load class %s', $peer));
      }

      $object = call_user_func(array($peer, 'retrieveByPk'), $object_id);

      if (is_null($object))
      { 
        throw new sfRatingException(sprintf('Unable to retrieve %s with primary key %s', $object_model, $object_id));
      }

      if (!self::isRatable($object))
      {
        throw new sfRatingException(sprintf('Class %s does not have the ratable behavior', $object_model));
      }

      return $object;
    }
    catch (Exception $e)
    {
      return sfContext::getInstance()->getLogger()->log($e->getMessage());
    }
  }  

  /**
   * Returns true if the passed model name is ratable
   * 
   * @author     Xavier Lacot
   * @param      string  $object_name
   * @return     boolean
   */
  public static function isRatable($model)
  {
    if (is_object($model))
    {
      $model = get_class($model);
    }

    if (!is_string($model))
    {
      throw new sfRatingException('The param passed to the metod isRatable must be a string.');
    }

    if (!class_exists($model))
    {
      throw new sfRatingException(sprintf('Unknown class %s', $model));
    }

     $base_class = sprintf('Base%s', $model);
    return !is_null(sfMixer::getCallable($base_class.':setRating'));
  }
}