<?php
/**
 * Rating components
 * 
 */
class sfRatingComponents extends sfComponents 
{

  /**
   * Gets object rating details and end it to according view
   * 
   */
  public function executeRatingDetails()
  {
    if ($this->object)
    {
      $details = $this->object->getRatingDetails(true);
      $total_ratings = array_sum($details);
      $full_details = array();
      foreach ($details as $rating => $nb_ratings)
      {
        if ($total_ratings > 0)
        {
          $percent = $nb_ratings / $total_ratings * 100;
        } else $percent = 0;
        $full_details[$rating] = array('count'   => $nb_ratings,
                                       'percent' => $percent);
      }
      $this->rating_details = $full_details;
      $this->object_type = get_class($this->object);
    }
  }

}
