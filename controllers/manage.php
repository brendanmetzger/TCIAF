<?php
namespace controllers;

use \bloc\application;
use \bloc\view;
use \bloc\view\renderer;
use \bloc\types\string;
use \bloc\types\xml;
use \bloc\types\dictionary;

use \models\graph;

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
    
    $this->supporters = $this->features = Graph::group('organization')->find("vertex[edge[@type='sponsor' and @vertex='TCIAF']]");
    
    if ($this->authenticated) {
      $this->user = Application::instance()->session('TCIAF')['user'];
      $this->tasks = (new Dictionary(['people', 'features', 'competitions', 'organizations']))->map(function($task) {
        return ['url' => "/explore/{$task}/all", 'name' => $task];
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
    if ($this->authenticated) \bloc\router::redirect($redirect);
    
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
        $user = (new \models\person('p:' . preg_replace('/\W/', '', $username)))->authenticate($password);
        Application::instance()->session('TCIAF', ['user' =>  $user->getAttribute('title')]);
        \bloc\router::redirect($redirect);
      } catch (\InvalidArgumentException $e) {
        $message = sprintf($e->getMessage(), $username);
      }
    } else {
      $message = "This form has expired - it can happen.. try again!";
    }
    
    return $this->GETLogin($redirect, $username, $message);
  }
  
  protected function GETedge($action, $type, $reference, $edge = null)
  {
    return 'not yet';
    /*
      TODO this should all be in a factory, and the 'type' would handle add/removes of whatever.
    */
    // $action will be add|remove
    if ($action == 'remove') {
      $item = Graph::instance()->storage->find("/graph/group/vertex[@id='{$reference}']/edge[@type='{$type}' and @vertex='{$edge}']")->pick(0);
      $item->parentNode->removeChild($item);
    } else if ($action == 'add') {
      $container = Graph::ID($reference);
      $item = Graph::instance()->storage->createElement('edge');
      $item->setAttribute('type', $type);
      $item->setAttribute('vertex', $edge);
      $container->appendChild($item);
    }
    
    print_r($item);
  }
  
  protected function GETcreate($model)
  {
    $view    = new View($this->partials->layout);
    $view->content = sprintf("views/forms/%s.html", $model);
    $this->item = Graph::factory($model);
    return $view->render($this()); 
  }
  
  protected function GETedit($model, $id)
  {
    $view    = new View($this->partials->layout);
    
    if ($model == 'find') {
      $model = Graph::ID($id)->parentNode->getAttribute('type');
    }
    
    $view->content = sprintf("views/forms/%s.html", $model);
    
    $graph = Graph::instance();
    
    $this->item = Graph::factory($model, Graph::ID($id));
    
    $this->edges = $this->item->edge->map(function($point) {
      $vertex = Graph::ID($point['@vertex']);
      return [ 'vertex' => $vertex, 'edge' => $point, 'index' => \bloc\registry::index()];
    });    

    $this->references = $graph->query('graph/group/vertex')->find("[edge[@vertex='{$id}']]")->map(function($item) {
      return $item;
    });
    
    return $view->render($this());
  }
  
  protected function POSTedit($request, $model, $id = null)
  {
    $model = Graph::factory($model, Graph::ID($id));
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