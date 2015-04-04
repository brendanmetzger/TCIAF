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
  
  public function GETlogin($redirect, $username = null, $message = null)
  {
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
        'username' => \bloc\types\string::rotate('username', $token),
        'password' => \bloc\types\string::rotate('password', $token),
        'redirect' => \bloc\types\string::rotate('redirect', $token),
      ]
      
    ]);
    
    
    return $view->render($this());
  }
  
  public function POSTLogin($request, $key)
  {
    
    $token = date('zG') + 1 + strlen(getenv('HTTP_USER_AGENT'));
    $key = ($key === base_convert((ip2long($_SERVER['REMOTE_ADDR']) + ip2long($_SERVER['SERVER_ADDR'])), 10, date('G')+11));
        
    $username = $request->post(\bloc\types\string::rotate('username', $token));
    $password = $request->post(\bloc\types\string::rotate('password', $token));
    $redirect = $request->post(\bloc\types\string::rotate('redirect', $token));
    
    if ($key && $user = (new \models\person)->authenticate($username, $password)) {
      \bloc\Application::session('TCIAF', ['user' =>  $user->getAttribute('id')]);
      \bloc\router::redirect($redirect);
    } 
    
    return $this->GETLogin($redirect, $username, "Hmm, better try again.");
  }
  
  public function CLItask($file)
  {
    $text = file_get_contents(PATH . $file);
    $compressed = gzencode($text, 3);
        
    file_put_contents(PATH . substr($file, 0, -4), $compressed, LOCK_EX);
  }
}