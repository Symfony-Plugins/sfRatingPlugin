<?php
/**
 * Symfony Propel rating behavior plugin toolkit 
 * 
 * @package plugins
 * @subpackage rating
 * @author Nicolas Perriault
 */
class sfRatingToolkit 
{
  /**
   * Old method to set maximum rating in a class constant
   * This stays here for compability purpose
   * 
   * @param  Object  $object
   * @return int
   */
  protected static function getDefaultMaxRating($object)
  {
    if(defined(get_class($object).'::MAX_RATING'))
    {
      $max_rating = constant(get_class($object).'::MAX_RATING');

    }
    else
    {
      $max_rating = sfConfig::get('app_rating_default_max', 5);
    }
    return (int) $max_rating;
  }

  /**
   * Retrieves maximum rating for given object
   * 
   * @param  Object  $object  Propel object instance
   * @return int
   * @throws sfRatingException
   */
  public static function getMaxRating($object)
  {
    $max_rating = sfConfig::get(sprintf('app_rating_%s_max', get_class($object)));

    if (is_null($max_rating))
    {
      $max_rating = self::getDefaultMaxRating($object);
    }

    $max_rating = (int) $max_rating;

    if ($max_rating < 2 or $max_rating > 10)
    {
      throw new sfRatingException('The max_rating parameter must be an integer greater than 1 and less than 11');
    }
    
    return $max_rating;
  }

  /**
   * Retrieves the id of currently connected user, with sfGuardPlugin detection
   * 
   * @return mixed (int or null if no user id retrieved)
   */
  public static function getUserId()
  {
    // sfGuardPlugin detection and guard user id retrieval
    $session = sfContext::getInstance()->getUser();
    if (class_exists('sfGuardSecurityUser')
        && $session instanceof sfGuardSecurityUser
        && is_callable($session, 'getGuardUser'))
    {
      $guard_user = $session->getGuardUser();
      if (!is_null($guard_user))
      {
        $guard_user_id = $guard_user->getId();
        if (!is_null($guard_user_id))
        {
          return $guard_user_id;
        }
      }
    }
    
    $getter = sfConfig::get('app_rating_user_id_getter');
    if (is_array($getter) && class_exists($getter[0]))
    {
      return call_user_func($getter);
    }
    elseif (is_string($getter) && function_exists($getter))
    {
      return $getter();
    }
    else
    {
      return null;
    }
  }
  
  /**
   * Add a token to available ones in the user session and return generated 
   * token
   * 
   * @author Nicolas Perriault
   * @param  string  $object_model
   * @param  int     $object_id
   * @return string
   */
  public static function addTokenToSession($object_model, $object_id)
  {
    $session = sfContext::getInstance()->getUser();
    $token = self::generateToken($object_model, $object_id);
    $tokens = $session->getAttribute('tokens', array(), 'sf_ratables');
    $tokens = array($token => array($object_model, $object_id)) + $tokens;
    $tokens = array_slice($tokens, 0, sfConfig::get('app_rating_max_tokens', 10));
    $session->setAttribute('tokens', $tokens, 'sf_ratables');

    return $token;
  }
  
  /**
   * Generates token representing a ratable object from its model and its id
   * 
   * @author Nicolas Perriault
   * @param  string  $object_model
   * @param  int     $object_id
   * @return string
   */
  public static function generateToken($object_model, $object_id)
  {
    return md5(sprintf('%s-%s-%s', $object_model, $object_id, sfConfig::get('app_rating_salt', 'r4t4bl3')));
  }

  /**
   * Retrieve ratable object instance from token
   * 
   * @author Nicolas Perriault
   * @param  string  $token
   * @return BaseObject
   */
  public static function retrieveFromToken($token)
  {
    $session = sfContext::getInstance()->getUser();
    $tokens = $session->getAttribute('tokens', array(), 'sf_ratables');
    if (array_key_exists($token, $tokens) && is_array($tokens[$token]) && class_exists($tokens[$token][0]))
    {
      $object_model = $tokens[$token][0];
      $object_id    = $tokens[$token][1];
      $orm_class = 'sfRatingPropelBehavior';
      if(sfConfig::get('sf_orm') == 'doctrine')
      {
      	$orm_class = 'sfRatingDoctrineListener';
      }
      return call_user_func(array($orm_class, 'retrieveRatableObject'), $object_model, $object_id);
    }
    else
    {
      return null;
    }
  }

  /**
   * Retrieves rating_field phpName from configuration
   * 
   * @param  BaseObject  $object
   * @return mixed
   */
  public static function getObjectRatingField($object)
  {
  	$config = sfConfig::get('app_rating_' . get_class($object));
  	return isset($config['rating_field']) ? $config['rating_field']:null;
  }

  /**
   * Retrieves reference_field phpName from configuration
   * 
   * @param  BaseObject  $object
   * @return mixed
   */
  public static function getObjectReferenceField($object_name)
  {
  	if(is_object($object_name))
  	{
      $object_name = get_class($object_name);
  	}

  	$config = sfConfig::get('app_rating_' . $object_name);
    return isset($config['reference_field']) ? $config['reference_field']:null;
  }
  
  /**
   * Retrieves reference key for current ratable object (default returns 
   * primary key)
   * 
   * @param  BaseObject $object
   * @return int
   */
  public static function getReferenceKey($object)
  {
    $reference_field = self::getObjectReferenceField($object);
    if (is_null($reference_field))
    {
      $ret = $object->getPrimaryKey();
      if(is_array($ret))
      {
        $ret = array_shift($ret);
      }

      return $ret;
    }
    
    $getter = 'get'.$reference_field;
    if (method_exists($object, $getter))
    {
      $ret = $object->$getter();
      if (!is_int($ret))
      {
        throw new sfRatingException(
          'A reference field must be typed as integer');
      }

      return $ret;
    }
  }

  /**
   * Retrieves configured float precision for ratings
   * 
   * @param  int  $default_precision
   * @return int
   */
  public static function getPrecision($default_precision = null)
  {
    if (is_null($default_precision))
    {
      $default_precision = sfConfig::get('app_rating_default_precision', 2);
    }

    return sfConfig::get('app_rating_precision', $default_precision);
  }
}
