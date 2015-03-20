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
  
  public function login($redirect_url, $post_data)
  {
    $view = new View('layout.html');
    $view->content = 'forms/credentials.html';
    
    $data = new \stdClass;
    
    $data->username = array_key_exists('username', $post_data) ? $post_data['username'] : '';
    $data->password = array_key_exists('password', $post_data) ? $post_data['password'] : '';
    
    $users = new \data\db('users');
    $user = $users->getElementById($data->username);
    if ($user && password_verify($data->password, $user->getAttribute('password'))) {
      $_SESSION['user'] = $data->username;
      header("Location: {$redirect_url}");
      exit();
    }
    
    $data->year = 2015;
    $data->action = $redirect_url;
    $data->title = 'TCIAF';
    
    $plat = new view\plat($view, $data);
    
    print $view->render();
    
  }
  
  protected function logout()
  {
    session_destroy();
    header("Location: /");
  }
}