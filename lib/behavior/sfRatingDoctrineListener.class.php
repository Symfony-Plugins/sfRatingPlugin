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
 * @author     Carl Vondrick <carlv@carlsoft.net>
 */
class sfRatingDoctrineListener extends Doctrine_Record_Listener
{
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
      if (!class_exists($object_model))
      {
        throw new sfRatingException(sprintf('Unable to load class %s', $object_model));
      }

      $object = self::retrieveByKey($object_model, $object_id);

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

    //TODO: implement isRatable - how can i check if a table has a plugin???
//    $base_class = sprintf('Base%s', $model);
//    return !is_null(sfMixer::getCallable($model.':setRating'));
    return true;
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
      throw new sfRatingException('Class %s does not exist', $object_name);
    }

    $query = Doctrine_Query::create()
      ->from($object_name);

    $field = sfRatingToolkit::getObjectReferenceField($object_name);
    if(!is_null($field))
    {
      $query->addWhere($field . ' = ?', $key);
    }
    else
    {
      $object_table = Doctrine::getTable($object_name);
      $query->addWhere($object_table->getIdentifier() . ' = ?', $key);
    }

    return $query->fetchOne();
  }
}