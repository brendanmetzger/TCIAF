<?php
namespace controllers;

use \bloc\view;
use \bloc\dom\document;
use \bloc\types\dictionary;

use \models\Graph;

/**
 * Search various things
 */

class Search extends Manage
{
  public function GETgroup($type, $subset = null)
  {
    $list   = Graph::group($type)->find('vertex');
    $search = new \models\search($list);
    return $search->asJSON($subset, $type);
  }
}