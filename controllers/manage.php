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

		$this->year        = date('Y');
    $this->title       = "Third Coast International Audio Festival";
    $this->_controller = $request->controller;
    $this->_action     = $request->action;
    
    $this->supporters = Graph::group('organization')->find("vertex[edge[@type='sponsor' and @vertex='TCIAF']]");
    
    if ($this->authenticated) {

      $this->user = Application::instance()->session('TCIAF')['user'];
      $this->tasks = (new Dictionary(['person', 'feature', 'broadcast', 'article', 'competition', 'organization', 'happening', 'collection']))->map(function($task) {
        return ['name' => $task, 'count' => Graph::group($task)->find('vertex')->count()];
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
        $user = (new \models\person('p-' . preg_replace('/\W/', '', $username)))->authenticate($password);
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
  
  protected function GETedge($model, $type, $id = null)
  {
    $view = new view('views/layout.html');
    $view->content = "views/forms/edge.html";
    
    $this->model  = $model;
    $this->type   = $type; 
    $this->vertex = Graph::ID($id);
    // $this->groups    = Graph::GROUPS($model);
    // $this->types     = Graph::RELATIONSHIPS();
    
    return $view->render($this());
  }
  
  protected function POSTedge($request)
  {
        
    $view = new view('views/layout.html');
    $view->content = "views/forms/partials/edge.html";

    $this->vertex = Graph::factory(Graph::ID($_POST['id']));
    $this->edge   = Graph::EDGE(null, $_POST['keyword'], null);
    
    $this->process = 'keep';
    $this->checked = 'checked';
    
    
    $this->index = time() * -1;
    
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
  
  // Create a new vertex model from scratch
  // output: HTML Form
  protected function GETcreate($model)
  {
    $this->item       = Graph::factory($model);
    $this->action     = "Create New {$model}";
    $this->references = null;
    $this->edges      = null;
    
    
    $view = new view('views/layout.html');    
    $view->content = sprintf("views/forms/%s.html", $this->item->getForm());
    return $view->render($this()); 
  }
  
  // Fetch a vertex and create a model.
  // output: HTML Form
  protected function GETedit($vertex)
  {
    $this->item   = $vertex instanceof \models\model ? $vertex : Graph::factory(Graph::ID($vertex));
    $this->action = "Edit {$this->item->get_model()}:";

    $view = new view('views/layout.html');
    $view->content = sprintf("views/forms/%s.html", $this->item->getForm());
    
    return $view->render($this());
  }
  
  protected function POSTedit($request, $model, $id = null)
  {
    if ($instance = Graph::factory( (Graph::ID($id) ?: $model), $_POST)) {

      if ($instance->save()) {
        // clear caches
        \models\Search::clear();
        \bloc\router::redirect("/manage/edit/{$instance['@id']}");
      } else {
        // echo $instance->context->write(true);
        return $this->GETedit($instance);

      }
    } 
    
    
  }
  
  protected function POSTupload($request)
  {
    $name   = base_convert($_FILES['upload']['size'], 10, 36) . strtolower(preg_replace(['/[^a-zA-Z0-9\-\:\/\_\.]/', '/\.jpeg/'], ['', '.jpg'], $_FILES['upload']['name']));
    $src    = 'data/media/' . $name;
    $mime   = $_FILES['upload']['type'];
    $bucket = 'tciaf-media';
    $type = substr($mime, 0, strpos($mime, '/'));

    if (move_uploaded_file($_FILES['upload']['tmp_name'], PATH . $src)) {
      
      $client = \Aws\S3\S3Client::factory(['profile' => 'TCIAF']);
      
      try {
        $config = [
          'Bucket' => $bucket,
          'Key'    => $type . '/' . $name,
          'ACL'    => 'public-read',
        ];
        
        if ($type === 'image') {
          $config['Body'] =  file_get_contents("http://{$_SERVER['HTTP_HOST']}/assets/scale/800/{$name}");
        } else {
          $config['SourceFile'] = PATH . $src;
        }
        
        $result = $client->putObject($config);
        
        
        if ($type == 'audio' && $result) {
          $transcoder = \Aws\ElasticTranscoder\ElasticTranscoderClient::factory(['profile' => 'TCIAF', 'region' => 'us-east-1']);

          $key = preg_replace('/\.?mp3/i', '', $name) . '.m4a';
          $job = $transcoder->createJob([
            'PipelineId' => '1439307152758-prv5fa',
            'Input' => [
              'Key' => $type . '/' . $name,
            ],
            'Output' => [
              'Key'      => $key,
              'PresetId' => '1439308682558-sehqe8',
            ]
          ]);
            
          $pending = "?/tciaf-audio/{$key}";
        } else {
          $pending = "";
        }
        
        $media = Graph::instance()->storage->createElement('media', 'A caption');
        $media->setAttribute('src',  "/{$bucket}/{$type}/{$name}{$pending}");
        $media->setAttribute('name',  $name);
        $media->setAttribute('type', $type);
      
        $model = new \models\Media($media, (time() * -1));
        
        $view = new view('views/layout.html');
        $view->content = "views/forms/partials/{$type}.html";
        
      
        return $view->render($this($model->slug));
      } catch (\Exception $e) {
        return $this->GETerror("The file was unable to be uploaded to amazon.", 500);
        exit();
      }
      
      
     
    } else {
      return $this->GETerror("The Server has refused this file", 400);
    }
  }
  
  public function POSTcorrelate($request)
  {
    $this->item = Graph::factory(Graph::ID($_POST['vertex']['@']['id']), $_POST);
    
    
    $view = new view('views/layout.html');
    $view->content = 'views/lists/recommendation.html';
    
    return $view->render($this());
  }
  
}