<?php
namespace controllers;
use \bloc\view as view;

/**
 * Third Coast International Audio Festival Defaults
 */

class locus
{
  public function __construct()
  {
    view::$webroot = 'visible/';
  }
  
  public function index()
  {
    echo 'hello';
  }
  
  public function login()
  {
    $view = new View('layout.html');
    $view->content = 'forms/credentials.html';
    
    $data = new \stdClass;
    $data->supporters = [
      ['name' => 'The MacArthur Foundation'],
      ['name' => 'The Richard H. Driehaus Foundation'],
      ['name' => 'The Boeing Company'],
      ['name' => 'Individual Donors']
    ];
    
    $data->username = '';
    $data->password = '';
    $data->year = 2015;
    $data->action = "/locus";
    $data->title = 'TCIAF';
    
    $plat = new view\plat($view, $data);
    
    print $view->render();
    
  }
  
  protected function logout()
  {
    echo "no";
  }
}