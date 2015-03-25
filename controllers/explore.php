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
    $view = new view('views/layout.html');
    $view->content = 'views/home.html';
		
    $data = new \bloc\model\Dictionary([
      'year' => 2015,
      'title' => 'Third Coast',
      'supporters' => [
        ['name' => 'The MacArthur Foundation'],
        ['name' => 'The Richard H. Driehaus Foundation'],
        ['name' => 'The Boeing Company'],
        ['name' => 'Individual Donors']
      ]
    ]);

    print $view->render($data);
  }
  
  protected function lonely($value = '')
  {
    \bloc\application::dump($_SESSION);
    $view = new view('views/admin.html');
    $view->content = 'views/forms/file.html';
    print $view->render();
  }
  
}