<?php
namespace controllers;

use \bloc\application;
use \bloc\view;
use \bloc\view\renderer;
use \bloc\types\string;
use \bloc\types\dictionary;

use \models\graph;

/**
 * Third Coast International Audio Festival Defaults
 */

class Manage extends \bloc\controller
{
  protected $partials,
            $request;

  public function __construct($request)
  {
    $this->partials = new \StdClass();

    View::addRenderer('before', Renderer::addPartials($this));
    View::addRenderer('after',  Renderer::HTML());

    $this->authenticated = (isset($_SESSION) && array_key_exists('user', $_SESSION));

		$this->year        = date('Y');
    $this->title       = "Third Coast International Audio Festival";
    $this->_redirect   = $request->redirect;
    $this->_controller = $request->controller;
    $this->_action     = $request->action;

    $tciaf = Graph::FACTORY(Graph::ID('TCIAF'));

    $this->supporters = $tciaf->supporters;
    $this->staff      = $tciaf->staff;




    if ($this->authenticated) {
      $this->_login = 'Logout';
      $this->user = Application::instance()->session('TCIAF')['user'];
      $this->tasks = (new Dictionary(['person', 'feature', 'article', 'competition', 'organization', 'happening', 'collection']))->map(function($task) {
        return ['name' => $task, 'count' => Graph::group($task)->find('vertex')->count()];
      });
      $this->partials->helper = 'views/partials/admin.html';
    } else {
      $this->_login = "Staff Login";
    }
  }

  public function GETindex()
  {
    return (new View($this->partials->layout))->render($this());
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
        $type = 'invalid';
      }
    } else {
      $type = 'expired';
    }
    $retry = sprintf('/manage/login/%s/%s/%s', base64_encode($redirect), $type, base64_encode($username));

    \bloc\router::redirect($retry);
  }

  protected function GETedge($model, $type, $id = null)
  {
    $view = new view('views/layout.html');
    $view->content = "views/forms/edge.html";
    $this->model  = $model;
    $this->type   = $type;
    $this->vertex = Graph::ID($id);
    return $view->render($this());
  }

  protected function POSTedge($request)
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
  protected function GETcreate($model)
  {
    $this->item       = Graph::FACTORY($model);
    $this->action     = "Create New {$model}";
    $this->references = null;
    $this->edges      = null;

    $view = new view('views/layout.html');
    $view->content = sprintf("views/forms/%s.html", $this->item->template('form'));
    return $view->render($this());
  }

  protected function GETgroup($to_group = null, $from_group = null, $vertex = null)
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
  protected function GETedit($vertex)
  {
    $this->item   = $vertex instanceof \models\model ? $vertex : Graph::FACTORY(Graph::ID($vertex));
    $this->action = "Edit {$this->item->get_model()}:";
    $view = new view('views/layout.html');
    $view->content = sprintf("views/forms/%s.html", $this->item->template('form'));
    $view->upload = sprintf("views/forms/fieldset/media/%s.html", $this->item->template('upload'));
    return $view->render($this());
  }

  protected function POSTedit($request, $model, $id = null)
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
        // echo $instance->context->write(true);
        return $this->GETedit($instance);

      }
    }
  }

  protected function POSTupload($request)
  {
    $name   = base_convert($_FILES['upload']['size'], 10, 36) . '_' . strtolower(preg_replace(['/\.[a-z34]{3,4}$/i', '/[^a-zA-Z0-9\-\:\/\_]/'], ['', ''], $_FILES['upload']['name']));

    $mime   = $_FILES['upload']['type'];
    $bucket = 'tciaf-media';

    $slash = strpos($mime, '/');
    $type = substr($mime, 0, $slash);
    $name = $name . '.' . substr($mime, $slash + 1);
    $src    = 'data/media/' . $name;

    if (move_uploaded_file($_FILES['upload']['tmp_name'], PATH . $src)) {

      $client = \Aws\S3\S3Client::factory(['profile' => 'TCIAF']);

      try {
        $config = [
          'Bucket' => $bucket,
          'Key'    => $type . '/' . $name,
          'ACL'    => 'public-read',
        ];
        if ($type === 'image') {
          if (substr($name, -3) === 'jpg') {
            $config['Body'] =  file_get_contents("http://{$_SERVER['HTTP_HOST']}/assets/scale/800/{$name}");
          } else {
            $config['Body'] =  file_get_contents(PATH . $src);
          }

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
          $mark = 0;
        } else {
          $size = getimagesize(PATH . $src);
          $mark = round($size[0] / $size[1], 1);
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
