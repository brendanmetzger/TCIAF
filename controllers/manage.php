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
  
  public function GETindex()
  {
    return (new View($this->partials['layout']))->render($this());
  }
  
  public function GETlogin($redirect, $username = null)
  {
    $view = new View($this->partials['layout']);    
    $view->content = 'views/forms/credentials.html';
    
    $data = new \bloc\types\Dictionary(['username' => $username, 'password' => null, 'redirect' => $redirect]);
    return $view->render($data);
  }
  
  public function POSTLogin($request)
  {
    $username = $request->post('username');
    $password = $request->post('password');
    $redirect = $request->post('redirect');
    
    if ($user = (new \models\person)->authenticate($username, $password)) {
      \bloc\Application::session('TCIAF', ['user' =>  $user->getAttribute('id')]);
      \bloc\router::redirect($redirect);
    } else {
      $this->GETLogin($request, $redirect, $username);
    }
  }
}