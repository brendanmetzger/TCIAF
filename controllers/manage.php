<?php
namespace controllers;

use \bloc\application;
use \bloc\view;
use \bloc\view\renderer as Render;
use \bloc\types\string;
use \bloc\types\dictionary;
use \models\person as Admin;

use \models\graph;

/**
 * Third Coast International Audio Festival Defaults
 */

class Manage extends \bloc\controller
{
  protected $authenticated = false, $request;

  public function __construct($request)
  {
    View::addRenderer('before', Render::PARTIAL());
    View::addRenderer('after',  Render::HTML());
		$this->year        = date('Y');
    $this->title       = "Third Coast International Audio Festival";
    $this->_redirect   = $request->redirect;
    $this->_controller = $request->controller;
    $this->_action     = $request->action;

    $tciaf = Graph::FACTORY(Graph::ID('TCIAF'));

    $this->supporters = $tciaf->supporters;
    $this->staff      = $tciaf->staff;


    if ((isset($_SESSION) && array_key_exists('id', $_SESSION))) {
      $this->authenticated = true;

      Render::PARTIAL('helper', 'views/partials/admin.html');
      $this->user = $_SESSION['user'];
      $this->_login = 'Logout';

      $this->tasks = (new Dictionary(['person', 'feature', 'article', 'competition', 'organization', 'happening', 'collection']))->map(function($task) {
        return ['name' => $task, 'count' => Graph::group($task)->find('vertex')->count()];
      });

    } else {

      $this->_login = "Staff Login";
    }

  }

  public function authenticate()
  {
    return $this->authenticated  ? new \models\person($_SESSION['id']) : null;
  }

  public function GETError($message, $code)
  {
    $this->message = parent::GETerror($message, $code);
    $view = new \bloc\View('views/layout.html');
    $view->content = 'views/layouts/error.html';
    return $view->render($this());
  }

  public function GETindex()
  {
    return (new View('views/layout.html'))->render($this());
  }

  public function GETlogin($redirect = '/', $status = "default", $username = null)
  {
    if ($this->authenticated) \bloc\router::redirect('/manage/logout');
    Application::instance()->getExchange('response')->addHeader("HTTP/1.0 401 Unauthorized");

    $messages = [
      'default' => 'Login',
      'expired' => 'The form has expired.. try one again',
      'invalid' => "Username/password mismatch."
    ];

    $view = new view('views/layout.html');
    $view->content = 'views/forms/credentials.html';

    $token = date('zG') + 1 + strlen(getenv('HTTP_USER_AGENT'));

    $key = ip2long(getenv('REMOTE_ADDR')) + ip2long(getenv('SERVER_ADDR'));
    $this->input = new \bloc\types\Dictionary([
      'token'    => base_convert($key, 10, date('G')+11),
      'message'  => $messages[$status],
      'username' => base64_decode($username),
      'password' => null,
      'action'   => $redirect,
      'redirect' =>  base64_decode($redirect),
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
    $key  = ($key === base_convert((ip2long($_SERVER['REMOTE_ADDR']) + ip2long($_SERVER['SERVER_ADDR'])), 10, date('G')+11));
    $type = 'expired';
    $username = $request->post(String::rotate('username', $token));
    $password = $request->post(String::rotate('password', $token));
    $redirect = $request->post(String::rotate('redirect', $token));

    if ($key) {
      try {
        $id = 'p-' . preg_replace('/\W/', '', $username);
        $user = (new \models\person($id))->authenticate($password);
        Application::instance()->session('TCIAF', ['id' => $id, 'user' =>  $user->getAttribute('title')]);
        \bloc\router::redirect($redirect ?: '/');
      } catch (\InvalidArgumentException $e) {
        $type = 'invalid';
      }
    }
    $retry = sprintf('/manage/login/%s/%s/%s', base64_encode($redirect), $type, base64_encode($username));
    \bloc\router::redirect($retry);
  }

  protected function GETedge(Admin $user, $model, $type, $id = null)
  {
    $view = new view('views/layout.html');
    $view->content = "views/forms/edge.html";
    $this->model  = $model;
    $this->type   = $type;
    $this->vertex = Graph::ID($id);
    return $view->render($this());
  }

  protected function POSTedge(Admin $user, $request)
  {
    $view = new view('views/layout.html');
    $view->content = "views/forms/partials/edge.html";

    $this->vertex = Graph::FACTORY(Graph::ID($_POST['id']));
    $this->edge   = Graph::EDGE(null, $_POST['keyword'], null);
    $this->process = 'keep';
    $this->checked = 'checked';
    $this->index = time() * -1;

    return $view->render($this());
  }


  // Create a new vertex model from scratch
  // output: HTML Form
  protected function GETcreate(Admin $user, $model)
  {
    $this->item       = Graph::FACTORY($model);
    $this->action     = "Create New {$model}";
    $this->references = null;
    $this->edges      = null;

    $view = new view('views/layout.html');
    $view->content = sprintf("views/forms/%s.html", $this->item->template('form'));
    return $view->render($this());
  }

  protected function GETgroup(Admin $user, $to_group = null, $from_group = null, $vertex = null)
  {
    $view = new view('views/layout.html');

    if ($vertex) {
      $vertex = Graph::ID($vertex);
      $dom = Graph::instance()->storage;
      $context = $dom->pick("/graph/group[@type='{$to_group}']");
      if ($to_group === 'archive') {
        $vertex->setAttribute('mark', $from_group);
        $vertex->setAttribute('updated', 'expunged');
      } else {
        $vertex->removeAttribute('mark');
        $vertex->setAttribute('updated', (new \DateTime())->format('Y-m-d H:i:s'));
      }
      $context->insertBefore($vertex, $context->firstChild);
      $filepath = PATH . Graph::DB . '.xml';

      if ($dom->validate() && is_writable($filepath)) {
        $dom->save($filepath);
        \models\Search::CLEAR();
      }
    }

    $this->archive = Graph::group('archive')->find('vertex');
    $view->content = 'views/lists/archive.html';
    return $view->render($this());
  }

  // Fetch a vertex and create a model.
  // output: HTML Form
  protected function GETedit(Admin $user, $vertex)
  {
    $this->item   = $vertex instanceof \models\model ? $vertex : Graph::FACTORY(Graph::ID($vertex));
    $this->action = "Edit {$this->item->get_model()}:";
    $view = new view('views/layout.html');
    $view->content = sprintf("views/forms/%s.html", $this->item->template('form'));
    $view->upload = sprintf("views/forms/fieldset/media/%s.html", $this->item->template('upload'));
    return $view->render($this());
  }

  protected function POSTedit(Admin $user, $request, $model, $id = null)
  {
    if ($instance = Graph::FACTORY( (Graph::ID($id) ?: $model), $_POST)) {
      if ($instance->save()) {
        // clear and rebuild caches w/o slowing down response
        \models\search::CLEAR();
        get_headers('http://'.$_SERVER['HTTP_HOST'].'/search/index');

        // Check about slugs; make ID fields represent actual content instead of random strings.
        // $instance->slugify();

        \bloc\router::redirect("/manage/edit/{$instance['@id']}");
      } else {
        return $this->GETedit($instance);
      }
    }
  }

  protected function POSTupload(Admin $user, $request)
  {
    $upload = $_FILES['upload'];
    $size   = base_convert($upload['size'], 10, 36);
    $base   = preg_replace(['/\.[a-z34]{3,4}$/i', '/[^A-z0-9\-:\/_]/'], '', $upload['name']);
    $mime   = explode('/', $upload['type']);
    $type   = $mime[0];
    $name   = strtolower("{$size}_{$base}.{$mime[1]}");
    $bucket = 'tciaf-media';
    $source = PATH . "data/media/{$name}";


    if (move_uploaded_file($upload['tmp_name'], $source)) {
      $client = \Aws\S3\S3Client::factory(['profile' => 'TCIAF']);
      try {
        $filename = "{$type}/{$name}";
        $config   = [
          'Bucket' => $bucket,
          'Key'    => $filename,
          'ACL'    => 'public-read',
        ];

        if ($type === 'image') {
          $path = preg_match('/\.jpe?g$/i', $name) ? "http://{$_SERVER['HTTP_HOST']}/assets/scale/800/{$name}" : $source;
          $config['Body'] =  file_get_contents($path);
        } else {
          $config['SourceFile'] = $source;
        }

        $result = $client->putObject($config);

        if ($type == 'audio' && $result) {
          $transcoder = \Aws\ElasticTranscoder\ElasticTranscoderClient::factory(['profile' => 'TCIAF', 'region' => 'us-east-1']);
          $key = preg_replace('/\.?mp3/i', '', $name) . '.m4a';
          $job = $transcoder->createJob([
            'PipelineId' => '1439307152758-prv5fa',
            'Input'  => ['Key' => $filename],
            'Output' => ['Key' => $key, 'PresetId' => '1439308682558-sehqe8']
          ]);

          $pending = "?/tciaf-audio/{$key}";
          $mark = 0;
        } else {
          $dims = getimagesize($source);
          $mark = round($dims[0] / $dims[1], 1);
          $pending = "";
        }

        $media = Graph::instance()->storage->createElement('media', 'A caption');
        $media->setAttribute('src',  "/{$bucket}/{$type}/{$name}{$pending}");
        $media->setAttribute('name',  $name);
        $media->setAttribute('type', $type);
        $media->setAttribute('mark', $mark);

        $model = new \models\Media($media, (time() * -1));

        $view = new view('views/layout.html');
        $view->content = "views/forms/partials/{$type}.html";

        return $view->render($this($model->slug));
      } catch (\Exception $e) {
        return $this->GETerror("The file was unable to be uploaded to amazon.\n\n{$e->getMessage()}", 500);
        exit();
      }
    } else {
      return $this->GETerror("The Server has refused this file", 400);
    }
  }

  public function POSTcorrelate($request)
  {
    $this->item = Graph::FACTORY(Graph::ID($_POST['vertex']['@']['id']), $_POST);
    $view = new view('views/layout.html');
    $view->content = 'views/lists/recommendation.html';
    return $view->render($this());
  }
}
