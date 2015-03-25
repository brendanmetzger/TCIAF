<?php
namespace controllers;
use \bloc\view as view;

/**
 * Third Coast International Audio Festival Defaults
 */

class superintend
{
  use \bloc\registry;
  
  public function __construct($request, $access)
  {
    $this->registry = new \bloc\model\dictionary;

    if (! $access) {
      session_start();
      $this->authenticated = array_key_exists('user', $_SESSION);
      $this->layout = '/views/admin.html';
    } else {
      $this->layout = '/views/layout.html';
    }
  }
  
  public function index()
  {
    echo 'hi';
  }
  
  public function login($redirect_url, $post_data)
  {
    $view = new View($this->layout);
    $view->content = 'views/forms/credentials.html';
    
    $data = new \bloc\model\dictionary;
    
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
        
    print $view->render($data);
    
  }
  
  protected function logout()
  {
    session_destroy();
    header("Location: /");
  }
}