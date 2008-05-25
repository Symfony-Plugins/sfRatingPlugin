<?php
/*
 * This file is part of the sfRatingPlugin
 * 
 * @author Nicolas Perriault <nperriault@gmail.com>
 */
sfLoader::loadHelpers('Javascript', 'Tag', 'I18N');
/**
 * Return the HTML code for a unordered list showing rating stars
 * 
 * @param  BaseObject  $object  Propel object instance
 * @param  array       $options        Array of HTML options to apply on the HTML list
 * @throws sfPropelActAsRatableException
 * @return string
 **/
function sf_rater($object, $options = array())
{
  if (is_null($object) or !$object instanceof BaseObject)
  {
    sfLogger::getInstance()->debug('A NULL object cannot be rated');
  }
  
  // Add css resources to the response
  // TODO: handle non http root url
  $css = '/sfRatingPlugin/css/sf_rating';
  sfContext::getInstance()->getResponse()->addStylesheet($css);
  
  $star_width = sfConfig::get('app_rating_star_width', 25);
  try
  {
    $max_rating = sfRatingToolkit::getMaxRating($object);
    $actual_rating = $object->getRating();
    $bar_width = $actual_rating * $star_width;
    
    $options = _parse_attributes($options);
    if (!isset($options['class']))
    {
      $options = array_merge($options, array('class' => 'star-rating'));
    }
    if (!isset($options['style']) or !preg_match('/width:/i', $options['style']))
    {
      $full_bar_width = $max_rating * $star_width;
      $options = array_merge($options, 
                             array('style' => 'width:'.$full_bar_width.'px'));
    }
    
    $object_class = get_class($object);
    $object_id = sfRatingToolkit::getReferenceKey($object);
    $token = sfRatingToolkit::addTokenToSession($object_class, $object_id);
    
    $msg_domid = sprintf('rating_message_%s', $token) ;
    $bar_domid = sprintf('current_rating_%s', $token) ;
    
    $list_content  = '  <li class="current-rating" id="'.$bar_domid.'" style="width:'.$bar_width.'px;">';
    $list_content .= sprintf('Currently rated %s star(s) on %d', 
                             $actual_rating,
                             $max_rating);
    $list_content .= '  </li>';
    
    for ($i=1; $i <= $max_rating; $i++)
    {
      $list_content .= 
        '  <li>'.link_to_remote(sprintf('Rate it %d stars', $i), 
          array('url'      => sprintf('sfRating/rate?token=%s&rating=%d', 
                                      $token, 
                                      $i),
                'update'   => $msg_domid,
                'script'   => true,
                'complete' => visual_effect('appear', $msg_domid).
                              visual_effect('highlight', $msg_domid)), 
          array('class'  => 'r'.$i.'stars',
                'title'  => __(sprintf('Rate it %d stars', $i)))).'</li>';
    }
    
    return content_tag('ul', $list_content, $options).
           content_tag('div', null, array('id' => $msg_domid));
  }
  catch (Exception $e)
  {
//    sfLogger::getInstance()->err('Exception catched from sf_rater helper: '.$e->getMessage());
    echo $e->getMessage();
  }
}
