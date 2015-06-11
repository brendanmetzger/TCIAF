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

    View::addRenderer('before', Renderer::addPartials($this));
    View::addRenderer('after', Renderer::HTML());
        
    $this->authenticated = (isset($_SESSION) && array_key_exists('user', $_SESSION));

		$this->year = date('Y');
    $this->title = "Third Coast";
    
    $this->supporters = Graph::group('organization')->find("vertex[edge[@type='sponsor' and @vertex='TCIAF']]");
    
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

    $view = new view('views/layout.html');
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
  
  protected function GETedge($model)
  {
    $view = new view('views/layout.html');
    $view->content = 'views/forms/edge.html';

    $this->model         = $model;
    $this->groups        = Graph::GROUPS($model);
    $this->relationships = Graph::RELATIONSHIPS();
    
    return $view->render($this());
  }
  
  protected function POSTedge($request)
  {
        
    $view = new view('views/layout.html');
    $view->content = 'views/forms/partials/edge.html';

    $this->vertex = Graph::ID($_POST['id']);
    $this->edge   = Graph::EDGE(null, $_POST['type'], $_POST['caption']);
    
    $this->process = 'add';
    $this->checked = 'checked';
    $this->index = $this->vertex['edge']->count() * -1;
    
    return $view->render($this());
  }
  
  protected function GETMedia($vertex, $type, $index = null)
  {
    $view = new view('views/layout.html');
    
    $this->media = \models\Media::COLLECT(Graph::ID($vertex)['media'], $type);
    $index -= 1;
    
    if ($index >= 0) {
      $view->content = 'views/forms/partials/media.html';
      foreach ($this->media[$index] as $key => $value) {
        $this->{$key} = $value;
      }
    } else {
      $view->content = 'views/forms/media.html';
    }
    return $view->render($this());
  }
  
  protected function GETcreate($model)
  {
    $view    = new view('views/layout.html');
    $view->content = sprintf("views/forms/%s.html", $model);
    $this->item = Graph::factory($model);
    $this->action = "Create New {$model}";
    $this->references = null;
    $this->edges = null;
    return $view->render($this()); 
  }
  
  protected function GETedit($model, $id)
  {
    $view    = new view('views/layout.html');
    
    $vertex = Graph::ID($id);
    
    if ($model == 'find') {
      $model = $vertex->parentNode->getAttribute('type');
    }
    
    $view->content = sprintf("views/forms/%s.html", $model);
        
    $this->action = "Edit {$model}";
    $this->item = Graph::factory($model, $vertex);
    
    $this->groups        = Graph::GROUPS($model);
    $this->relationships = Graph::RELATIONSHIPS();
    
    $this->edges = $this->item->edge->map(function($edge) {
      $vertex = Graph::ID($edge['@vertex']);
      return [ 'vertex' => $vertex, 'edge' => $edge, 'index' => $edge->getIndex()];
    });

    $this->references = Graph::instance()->query('graph/group/vertex')->find("/edge[@vertex='{$id}']")->map(function($edge) {
      return ['vertex' => $edge->parentNode, 'edge' => $edge, 'index' => $edge->getIndex(), 'process' => 'remove'];
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
        if (isset($_POST['edge'])) {
          $instance->setReferencedEdges($_POST['edge']);
        }
        
        if (strpos(strtolower($id), 'pending') === 0) {
          $request->redirect = preg_replace('/pending.*/', $instance['@id'] , $request->redirect);
        }
        
        \bloc\router::redirect($request->redirect);
      } else {

      // echo $model->context->write(true);
      \bloc\application::instance()->log($model->errors);
      }
    } 
    
    
  }
  
  protected function POSTupload($request)
  {
    $name   = $_FILES['upload']['name'];
    $src    = 'data/media/' . $name;
    $mime   = $_FILES['upload']['type'];
    $bucket = 'tciaf-media';
    $type = substr($mime, 0, strpos($mime, '/'));

    if (move_uploaded_file($_FILES['upload']['tmp_name'], PATH . $src)) {
      $view = new view('views/layout.html');
      $view->content = 'views/forms/partials/media.html';
      $client = \Aws\S3\S3Client::factory(['profile' => 'TCIAF']);
      $result = $client->putObject(array(
          'Bucket'     => $bucket,
          'Key'        => $type . '/' . $name,
          'SourceFile' => PATH . $src,
          'ACL'        => 'public-read',
      ));
      
      
      $media = Graph::instance()->storage->createElement('media', 'A caption');
      $media->setAttribute('src',  "/{$bucket}/{$type}/{$name}");
      $media->setAttribute('name',  $name);
      $media->setAttribute('type', $type);
      
      $model = new \models\Media($media, (time() * -1));
      
      foreach ($model as $key => $value) {
        $this->{$key} = $value;
      }
      
      return $view->render($this());
    } else {
      Application::instance()->getExchange('response')->addHeader("HTTP/1.0 400 Bad Request");
    }
  }
  
  
}