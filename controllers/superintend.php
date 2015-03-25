<?php
namespace controllers;
use \bloc\view as View;

/**
 * Third Coast International Audio Festival Defaults
 */

class superintend
{
  use \bloc\registry;

  protected $partials = [
    'layout' => 'views/layout.html',
  ];
  
      
  public function __construct($request, $access)
  {
    $this->registry = new \bloc\model\dictionary;

    View::addRenderer('before', view\renderer::addPartials($this));
    View::addRenderer('after', view\renderer::HTML());

    if (! $access) {
      session_start();
      $this->authenticated = array_key_exists('user', $_SESSION);
      $this->title = "Administrate";
    } 
    
    if ($restricted) {
      $this->partials['helper'] = 'views/admin.html';
    }
  }
  
  public function getPartials()
  {
    return $this->partials;
  }
  
  public function index()
  {
    echo 'hi';
  }
  
  public function login($redirect_url, $post_data)
  {
    $view = new View($this->partials['layout']);
    
    $view->content = 'views/forms/credentials.html';
    
    $data = new \bloc\model\dictionary;
    
    $data->username = array_key_exists('username', $post_data) ? $post_data['username'] : '';
    $data->password = array_key_exists('password', $post_data) ? $post_data['password'] : '';
    
    $users = new \bloc\DOM\Document('data/users', ['validateOnParse' => true]);
    $user = $users->getElementById($data->username);
    if ($user && password_verify($data->password, $user->getAttribute('password'))) {
      $_SESSION['user'] = $data->username;
      header("Location: {$redirect_url}");
      exit();
    }
    
    $data->year = 2015;
    $data->action = $redirect_url;
    $data->title = 'TCIAF';
    $data->password = null;   
    print $view->render($data);
    
  }
  
  protected function logout()
  {
    session_destroy();
    header("Location: /");
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
    
    
    print $view->render($this->registry);

    
  }
}