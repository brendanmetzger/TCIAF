<?php
namespace controllers;
use \bloc\view as view;

/**
 * Explore Represents the user's interest.
 */

class Explore extends Manage
{

  protected function review($id = null)
  {
    $view = new View($this->partials['layout']);
    $db   = new \mysqli('127.0.0.1', 'root', '', 'TCIAF');
    
    $this->features = $db->query("SELECT * FROM features LIMIT 25")->fetch_all(MYSQLI_ASSOC);
    $view->content = 'views/feature.html';

    // $view->fieldlist = (new \bloc\DOM\Document("<ul><li>[@origin_country]</li><li>[@premier_locaction]</li><li>[@premier_date]</li><li>[@published]</li><li>[@delta]</li></ul>", [], \bloc\DOM\Document::TEXT))->documentElement;
    
    
    print $view->render($this());
  }  
}