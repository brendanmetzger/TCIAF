<?php
namespace controllers;
use \bloc\view as view;

/**
 * Explore Represents the user's interest.
 */

class Explore extends Home
{
  public function index()
  {
    $view = new view('views/layout.html');
    $view->content = 'views/home.html';
    
    print $view->render($this());
  }
  
  protected function review($id = null)
  {
    $view = new View($this->partials['layout']);
    $db   = new \mysqli('127.0.0.1', 'root', '', 'TCIAF');
    
    $this->features = $db->query("SELECT * FROM features LIMIT 25")->fetch_all(MYSQLI_ASSOC);
    $view->content = 'views/feature.html';
    // \bloc\application::dump($this->registry);
    // $fragment = $view->dom->createDocumentFragment();
    // $fragment->appendXML("<ul><li>[@origin_country]</li><li>[@premier_locaction]</li><li>[@premier_date]</li><li>[@published]</li><li>[@delta]</li></ul>");
    // \bloc\application::dump(new view($fragment));
    // $view->fieldlist = new view($fragment);
    
    
    print $view->render($this());
  }  
}