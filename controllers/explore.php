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
		
    $data = new \stdClass;
    $data->supporters = [
      ['name' => 'The MacArthur Foundation'],
      ['name' => 'The Richard H. Driehaus Foundation'],
      ['name' => 'The Boeing Company'],
      ['name' => 'Individual Donors']
    ];
    
    
    $plat = new view\plat($view, $data);

    
    print $view->render();
  }
}