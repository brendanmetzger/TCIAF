<?php
namespace controllers;
use \bloc\View;
use \bloc\View\Renderer;
use \bloc\Types\String;
use \bloc\Application;
use \bloc\types\xml;
use \bloc\types\Dictionary;

/**
 * Third Coast International Audio Festival Defaults
 */

class Manage extends \bloc\controller
{

  protected $partials;
  
  public function __construct($request)
  {
    $this->partials = new \StdClass();
    $this->partials->layout = 'views/layout.html';

    View::addRenderer('before', Renderer::addPartials($this));
    View::addRenderer('after', Renderer::HTML());
    
    $this->authenticated = (isset($_SESSION) && array_key_exists('user', $_SESSION));

		$this->year = date('Y');
    $this->title = "Third Coast";

    $this->supporters = xml::load(\models\Token::DB)->find("/tciaf/token[pointer[@token='TCIAF' and @type='sponsor']]");
        
    if ($this->authenticated) {
      $this->user = Application::instance()->session('TCIAF')['user'];
      $this->tasks = (new Dictionary(['people', 'features']))->map(function($task) {
        return ['url' => "/explore/{$task}", 'name' => $task];
      });
      $this->partials->helper = 'views/admin.html';
    }
  }
  
  public function GETindex()
  {
    return (new View($this->partials->layout))->render($this());
  }
  
  public function GETlogin($redirect, $username = null, $message = null)
  {
    Application::instance()->getExchange('response')->addHeader("HTTP/1.0 401 Unauthorized");

    $view = new View($this->partials->layout);
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
      try {
        $user = (new \models\person($username))->authenticate($password);
        Application::instance()->session('TCIAF', ['user' =>  $user->getAttribute('id')]);
        \bloc\router::redirect($redirect);
      } catch (\InvalidArgumentException $e) {
        $message = $e->getMessage();
      }
    } else {
      $message = "This form has expired - it can happen.. try again!";
    }
    
    return $this->GETLogin($redirect, $username, $message);
  }
  
  public function POSTadd($request, $model)
  {
    \bloc\application::instance()->log(NS.'models'.NS.$model);
  }
}