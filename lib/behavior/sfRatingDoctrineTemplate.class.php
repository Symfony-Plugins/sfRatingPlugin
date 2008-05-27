<?php
/*
 * This file is part of the sfLucenePlugin package
 * (c) 2007 Carl Vondrick <carlv@carlsoft.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Responsible for handling Doctrine's behaviors.
 * @package    sfLucenePlugin
 * @subpackage Behavior
 * @author     Gordon Franke
 */
class sfRatingDoctrineTemplate extends Doctrine_Template
{
  /**
   * setTableDefinition
   *
   * @return void
   */
  public function setTableDefinition()
  {
    $this->addListener(new sfRatingDoctrineListener);
  }

  /**
   * Counts ratings made on given ratable object.
   * 
   * @return int
   */
  public function countRatings()
  {
    $object = $this->getInvoker();

  	return Doctrine_Query::create()
      ->from('sfRating')
      ->addWhere('ratable_id = ?', sfRatingToolkit::getReferenceKey($object))
      ->addWhere('ratable_model = ?', get_class($object))
      ->count();
  }

  /**
   * Clear all ratings for an object
   *
   * @return int affected rows
   **/
  public function clearRatings()
  {
    $object = $this->getInvoker();
    self::setRatingToObject($object, 0);

    return Doctrine_Query::create()
      ->delete()
      ->from('sfRating')
      ->addWhere('ratable_id = ?', sfRatingToolkit::getReferenceKey($object))
      ->addWhere('ratable_model = ?', get_class($object))
      ->execute();
  }

  /**
   * Clear user rating for an object
   *
   * @param  mixed       $user_id  User primary key
   * @return int affected rows
   **/
  public function clearUserRating($user_id)
  {
    $object = $this->getInvoker();
  	if (is_null($user_id) or trim((string)$user_id) === '')
    {
      throw new sfRatingException('Impossible to clear a user rating with no user primary key provided');
    }

    self::setRatingToObject($object, $this->getRating(sfRatingToolkit::getPrecision(), true));

    return Doctrine_Query::create()
      ->delete()
      ->from('sfRating')
      ->addWhere('ratable_id = ?', sfRatingToolkit::getReferenceKey($object))
      ->addWhere('ratable_model = ?', get_class($object))
      ->addWhere('user_id = ?', $user_id)
      ->execute();
  }

  /**
   * Checks if an Object has been rated
   *
   * @return  boolean
   **/
  public function hasBeenRated()
  {
    $object = $this->getInvoker();

  	return false !== Doctrine_Query::create()
  	  ->select('id')
      ->from('sfRating')
      ->addWhere('ratable_id = ?', sfRatingToolkit::getReferenceKey($object))
      ->addWhere('ratable_model = ?', get_class($object))
      ->limit(1)
      ->fetchOne(array(), Doctrine::HYDRATE_ARRAY);
  }

  /**
   * Checks if an Object has been rated by a user
   *
   * @param  mixed       $user_id  Unique reference to a user
   * @return  boolean
   **/
  public function hasBeenRatedByUser($user_id)
  {
    $object = $this->getInvoker();
  	if (is_null($user_id) or trim((string)$user_id) === '')
    {
      throw new sfRatingException(
        'Impossible to check a user rating with no user primary key provided');
    }

  	return false !== Doctrine_Query::create()
  	  ->select('id')
  	  ->from('sfRating')
      ->addWhere('ratable_id = ?', sfRatingToolkit::getReferenceKey($object))
      ->addWhere('ratable_model = ?', get_class($object))
      ->addWhere('user_id = ?', $user_id)
      ->fetchOne(array(), Doctrine::HYDRATE_ARRAY);
  }

  /**
   * Retrieves the object rating
   *
   * @param  int         $precision   Result float precision
   * @return float
   **/
  public function getRating($precision=2, $docount=false)
  {
    $object = $this->getInvoker();
    if ($docount === false && !is_null(sfRatingToolkit::getObjectRatingField($object)))
    {
      return round(sfRatingToolkit::getRatingToObject($object), sfRatingToolkit::getPrecision());
    }

    $rating = Doctrine_Query::create()
      ->select('COUNT(id) as nb_ratings, SUM(rating) as total')
      ->from('sfRating')
      ->addWhere('ratable_id = ?', sfRatingToolkit::getReferenceKey($object))
      ->addWhere('ratable_model = ?', get_class($object))
      ->groupby('ratable_model')
      ->fetchOne(array(), Doctrine::HYDRATE_ARRAY);

    if(!$rating)
    {
      return NULL; // Object has not been rated yet
    }

    return round($rating['total'] / $rating['nb_ratings'], sfRatingToolkit::getPrecision($precision));
  }

  /**
   * Gets the object rating details
   *
   * @param  boolean     $include_all  Shall we include all available ratings?
   * @return associative array containing (rating => count)
   **/
  public function getRatingDetails($include_all = false)
  {
    $object = $this->getInvoker();
  	$ratings = Doctrine_Query::create()
      ->select('COUNT(id) as nb_ratings, rating')
      ->from('sfRating')
      ->addWhere('ratable_id = ?', sfRatingToolkit::getReferenceKey($object))
      ->addWhere('ratable_model = ?', get_class($object))
      ->groupby('rating')
      ->execute(array(), Doctrine::HYDRATE_ARRAY);

    $details = array();
    foreach($ratings as $rating)
    {
      $details = $details + array ($rating['rating'] => (int)$rating['nb_ratings']);
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
   * @param  mixed       $user_id  User primary key
   * @return int or false
   **/
  public function getUserRating($user_id)
  {
    $object = $this->getInvoker();
  	if (is_null($user_id) or trim((string)$user_id) === '')
    {
      throw new sfRatingException(
        'Impossible to get a user rating with no user primary key provided');
    }

  	$rating_object = Doctrine_Query::create()
  	  ->from('sfRating')
      ->addWhere('ratable_id = ?', sfRatingToolkit::getReferenceKey($object))
      ->addWhere('ratable_model = ?', get_class($object))
      ->addWhere('user_id = ?', $user_id)
      ->fetchOne(array(), Doctrine::HYDRATE_ARRAY);

    return $rating_object ? $rating_object['rating']:false;
  }  

  /**
   * Rates the Object
   *
   * @param  int         $rating
   * @param  mixed       $user_id  Optionnal unique reference to user
   * @throws sfRatingException
   **/
  public function setRating($rating, $user_id = null)
  {
  	$object = $this->getInvoker();
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
    $rating_object->save();
    self::setRatingToObject($object, $this->getRating(sfRatingToolkit::getPrecision(), true));

    return $rating_object;
  }

  /**
   * Retrieves an existing rating object, or return a new empty one
   *
   * @param  BaseObject  $object
   * @param  mixed       $user_id  Unique user primary key
   * @return sfRating
   * @throws sfRatingException
   **/
  protected static function getOrCreate($object, $user_id = null)
  {
    if (!$object->exists())
    {
      throw new sfRatingException('Unsaved objects are not ratable');
    }

    if (is_null($user_id))
    {
      return new sfRating();
    }
    
    $user_rating = Doctrine_Query::create()
      ->from('sfRating')
      ->addWhere('ratable_id = ?', sfRatingToolkit::getReferenceKey($object))
      ->addWhere('ratable_model = ?', get_class($object))
      ->addWhere('user_id = ?', $user_id)
      ->fetchOne();

    return $user_rating ? $user_rating:new sfRating();
  }

  /**
   * Sets cached rating
   * 
   * @param  BaseObject  $object
   * @param  float       $value
   */
  protected static function setRatingToObject($object, $value)
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
  protected static function getRatingToObject($object)
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
}