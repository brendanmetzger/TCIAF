<?php
namespace controllers;
use \bloc\view as view;

/**
 * Explore Represents the user's interest.
 */

class explore
{
  public function index()
  {
    $view = new view('visible/layout.html');
    $view->content = 'visible/home.html';
		
    
    
    // $plat = new view\plat($view->xpath->query('//body//article')->item(0));

    
    print $view->render();
  }
}