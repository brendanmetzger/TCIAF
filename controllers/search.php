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
  
  public function GETfeature($subset = null)
  {
    $search = new \models\search("//group[@type='feature']/token");
    return $search->asJSON($subset, 'feature');
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