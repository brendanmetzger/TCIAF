<?php
namespace controllers;
use \bloc\View;
use \bloc\DOM\Document;
use \bloc\types\XML;
use \bloc\types\Dictionary;

/**
 * Search various things
 */

class Search extends Manage
{
  public function GETpeople($subset = null)
  {

    $people = XML::load('data/db6')->find("//group[@type='person']/token")->map(function($person) {
        return ['name' => (string)$person['title'], 'id' => (string)$person['id']];
    });

    $search = new \models\search;
    foreach ($people as $person) {
      $search->addToIndex($person['id'], $person['name']);
    }
    
    return $search->asJSON($subset);
  }

}