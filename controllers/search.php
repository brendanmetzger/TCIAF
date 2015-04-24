<?php
namespace controllers;
use \bloc\View;
use \bloc\DOM\Document;
use \bloc\types\Dictionary;
use \models\Token;

/**
 * Search various things
 */

class Search extends Manage
{
  public function GETpeople($subset = null)
  {
    $search = new \models\search("//group[@type='person']/token");
    return $search->asJSON($subset);
  }

}