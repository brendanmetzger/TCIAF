<?php
namespace controllers;
use \bloc\View;
use \bloc\View\Renderer;
use \bloc\Types\String;
use \bloc\Application;
use \bloc\types\xml;
use \bloc\types\Dictionary;
use \models\Token;

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
    
    $this->supporters = $this->features = \models\Token::storage()->find("/tciaf/group/token[pointer[@type='sponsor' and @token='TCIAF']]");
    // $this->supporters = xml::load(\models\Token::DB)->find();
    
    if ($this->authenticated) {
      $this->user = Application::instance()->session('TCIAF')['user'];
      $this->tasks = (new Dictionary(['people', 'features', 'competitions', 'organizations']))->map(function($task) {
        return ['url' => "/explore/{$task}", 'name' => $task];
      });
      $this->partials->helper = 'views/partials/admin.html';
    }
  }
  
  public function GETindex()
  {
    return (new View($this->partials->layout))->render($this());
  }
  
  public function GETlogin($redirect = '/', $username = null, $message = null)
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
     
    $username = 'p:' . preg_replace('/\s/', '', $request->post(String::rotate('username', $token)));
    $password = $request->post(String::rotate('password', $token));
    $redirect = $request->post(String::rotate('redirect', $token));
    
    if ($key) {
      try {
        $user = (new \models\person($username))->authenticate($password);
        Application::instance()->session('TCIAF', ['user' =>  $user->getAttribute('title')]);
        \bloc\router::redirect($redirect);
      } catch (\InvalidArgumentException $e) {
        $message = $e->getMessage();
      }
    } else {
      $message = "This form has expired - it can happen.. try again!";
    }
    
    return $this->GETLogin($redirect, $username, $message);
  }
  
  protected function GETpointer($action, $type, $reference, $pointer = null)
  {
    return 'not yet';
    /*
      TODO this should all be in a factory, and the 'type' would handle add/removes of whatever.
    */
    // $action will be add|remove
    if ($action == 'remove') {
      $item = \models\Token::storage()->find("/tciaf/group/token[@id='{$reference}']/pointer[@type='{$type}' and @token='{$pointer}']")->pick(0);
      $item->parentNode->removeChild($item);
    } else if ($action == 'add') {
      $container = \models\Token::storage()->getElementById($reference);
      $item = \models\Token::storage()->createElement('pointer');
      $item->setAttribute('type', $type);
      $item->setAttribute('token', $pointer);
      $container->appendChild($item);
    }
    
    print_r($item);
  }
  
  protected function GETcreate($model)
  {
    $view    = new View($this->partials->layout);
    $view->content = sprintf("views/forms/%s.html", $model);
    $this->item = Token::factory($model);
    return $view->render($this()); 
  }
  
  protected function GETedit($model, $id)
  {
    $view    = new View($this->partials->layout);
    $view->content = sprintf("views/forms/%s.html", $model);
    
    $storage = Token::storage();

    // this will be placed into media model.
    $this->s3_url  = $storage->getElementById('k:s3');
    
    $this->item = Token::factory($model, Token::ID($id));
    
    $this->pointers = $this->item->pointer->map(function($point) use($storage) {
      $token = $storage->getElementById($point['@token']);
      return [ 'token' => $token, 'pointer' => $point, 'index' => \bloc\registry::index()];
    });    

    $this->references = $storage->find("/tciaf/group/token[pointer[@token='{$id}']]")->map(function($item) {
      return $item;
    });
    
    return $view->render($this());
  }
  
  protected function POSTedit($request, $model, $id = null)
  {
    $model = Token::factory($model, Token::ID($id));
    if ($instance = $model::create($model, $_POST)) {
      if ($instance->save()) {
        // clear caches
        \models\Search::clear();
        
        if ($id === 'pending') {
          $request->redirect = str_replace('pending', $instance['@id'], $request->redirect);
        }
        
        \bloc\router::redirect($request->redirect);
      } else {

      
      \bloc\application::instance()->log($model->errors);
      }
    } 
    
    
  }
  
  protected function POSTupload($request)
  {
    if (move_uploaded_file($_FILES['upload']['tmp_name'], PATH . 'data/media/' . $_FILES['upload']['name'])) {
      echo "got it";
    } else {
      Application::instance()->getExchange('response')->addHeader("HTTP/1.0 400 Bad Request");
    }
    /*
      TODO post uploaded file somewhere... amazon prolly
    */
  }
}