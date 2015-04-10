<?php
namespace controllers;
use \bloc\View;
use \bloc\View\Renderer;
use \bloc\Types\String;
use \bloc\Application;
use \bloc\types\xml;

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
    View::addRenderer('before', Renderer::addPartials($this));
    View::addRenderer('after', Renderer::HTML());
    
    $this->authenticated = (isset($_SESSION) && array_key_exists('user', $_SESSION));
    
		$this->year = date('Y');
    $this->title = "Third Coast";

    $this->supporters = xml::load('data/db5')->find("//group[@type='organization']/token[@id='TCIAF']/pointer[@type='sponsor']")->map(function($supporter) {
      return xml::load('data/db5')->findOne("//group[@type='organization']/token[@id='{$supporter['rel']}']");
    });
    
    if ($this->authenticated) {
      $this->user = Application::instance()->session('TCIAF')['user'];
      $this->partials['helper'] = 'views/admin.html';
    }
  }
  
  public function GETindex()
  {
    return (new View($this->partials['layout']))->render($this());
  }
  
  public function GETlogin($redirect, $username = null, $message = null)
  {
    View::addRenderer('preflight', function ($view) {
      header("HTTP/1.0 401 Unauthorized");
    });

    $view = new View($this->partials['layout']);
    $view->content = 'views/forms/credentials.html';

    $token = date('zG') + 1 + strlen(getenv('HTTP_USER_AGENT'));
    $key = ip2long(getenv('REMOTE_ADDR')) + ip2long(getenv('SERVER_ADDR'));
    $this->input = new \bloc\types\Dictionary([
      'token'    => base_convert($key, 10, date('G')+11),
      'message'  => $message ?: 'Login',
      'username' => $username,
      'password' => null, 
      'redirect' => $redirect,
      'tokens'   => [
        'username' => String::rotate('username', $token),
        'password' => String::rotate('password', $token),
        'redirect' => String::rotate('redirect', $token),
      ]
    ]);
      
    return $view->render($this());
  }
  
  public function POSTLogin($request, $key)
  {
    $token = date('zG') + 1 + strlen(getenv('HTTP_USER_AGENT'));
    $key = ($key === base_convert((ip2long($_SERVER['REMOTE_ADDR']) + ip2long($_SERVER['SERVER_ADDR'])), 10, date('G')+11));
     
    $username = $request->post(String::rotate('username', $token));
    $password = $request->post(String::rotate('password', $token));
    $redirect = $request->post(String::rotate('redirect', $token));
    
    if ($key) {
      if ($user = (new \models\person)->authenticate($username, $password)) {
        Application::instance()->session('TCIAF', ['user' =>  $user->getAttribute('id')]);
        \bloc\router::redirect($redirect);
      } else {
        $message = "Your credentials weren't quite right.";
      }
    } else {
      $message = "This form has expired - it can happen.. try again!";
    }
    
    return $this->GETLogin($redirect, $username, $message);
  }
}