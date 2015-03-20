<?php
namespace controllers;
use \bloc\view as view;

/**
 * Explore Represents the user's interest.
 */

class explore extends superintend
{
  public function index()
  {
    $view = new view('layout.html');
    $view->content = 'home.html';
		
    $data = new \stdClass;
    $data->year = 2015;
    $data->title = 'Third Coast.';
    $data->supporters = [
      ['name' => 'The MacArthur Foundation'],
      ['name' => 'The Richard H. Driehaus Foundation'],
      ['name' => 'The Boeing Company'],
      ['name' => 'Individual Donors']
    ];
    
    $plat = new view\plat($view, $data);
    print $view->render();
  }
  
  protected function lonely($value='')
  {
    $view = new view('admin.html');
    $view->content = 'forms/file.html';
    print $view->render();
  }
  
}