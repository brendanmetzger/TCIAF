<?php
namespace controllers;

use \bloc\view;
use \bloc\dom\document;
use \bloc\types\dictionary;

use \models\Token;

/**
 * Search various things
 */

class Search extends Manage
{
  public function GETperson($subset = null)
  {
    $search = new \models\search("//group[@type='person']/token");
    return $search->asJSON($subset, 'person');
  }
  
  public function GETpublished($subset = null)
  {
    $search = new \models\search("//group[@type='published']/token");
    return $search->asJSON($subset, 'published');
  }
  
  public function GETorganization($subset = null)
  {
    $search = new \models\search("//group[@type='organization']/token");
    return $search->asJSON($subset, 'organization');
  }
  
  public function GETcompetition($subset = null)
  {
    $search = new \models\search("//group[@type='competition']/token");
    return $search->asJSON($subset, 'competition');
  }
  

}