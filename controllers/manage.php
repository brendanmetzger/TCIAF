<?php
namespace controllers;
use \bloc\view as View;

/**
 * Third Coast International Audio Festival Defaults
 */

class Manage extends \bloc\controller
{

  protected $partials = [
    'layout' => 'views/layout.html',
  ];
  
  public function __construct($request)
  {
    View::addRenderer('before', view\renderer::addPartials($this));
    View::addRenderer('after', view\renderer::HTML());
    
    $this->authenticated = (isset($_SESSION) && array_key_exists('user', $_SESSION));
		$this->year = date('Y');
    $this->title = "Third Coast";
    $this->supporters = [
      ['name' => 'The MacArthur Foundation'],
      ['name' => 'The Richard H. Driehaus Foundation'],
      ['name' => 'The Boeing Company'],
      ['name' => 'Individual Donors']
    ];
    
    if ($this->authenticated) {
      $this->user = $_SESSION['user'];
      $this->partials['helper'] = 'views/admin.html';
    }
  }
  
  public function index()
  {
    return (new View($this->partials['layout']))->render($this());
  }
  
  public function login($redirect_url, $request)
  {
    $view = new View($this->partials['layout']);    
    $view->content = 'views/forms/credentials.html';
        
    $username = $request->post('username');
    $password = $request->post('password');

      
    if ($user = (new \models\person)->authenticate($username, $password)) {
      session_start();
      $_SESSION['user'] = $user->getAttribute('id');
      \bloc\router::redirect('/');
    }
    
    $this->action   = $redirect_url;
    $this->username = $username;
    $this->password = null;   

    return $view->render($this());
  }
}