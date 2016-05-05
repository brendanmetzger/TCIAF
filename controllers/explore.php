<?php
namespace controllers;

use \bloc\view;
use \bloc\dom\document;
use \bloc\types\dictionary;

use \models\graph;

/**
 * Explore
 */

class Explore extends Manage
{

  /*
   * Index Function
   */
  public function GETindex($group = null, $filter = 'all', $sort = 'alpha-numeric', $index = 1, $per = 100, $query = '')
  {
    $view = new view('views/layout.html');
    if ($group !== null) {
      $view->content = "views/lists/{$group}.html";
      $this->search  = ['topic' => $group, 'path' => 'search/group', 'area' => 'explore/detail'];
      $this->group   = $group;
      $alpha = null;
      if (strtolower(substr($filter, 0, 5)) == 'alpha') {
        $alpha = substr($filter, 6, 1);
        $query = "[starts-with(@title, '{$alpha}')]";
      }
      $this->alphabet = (new Dictionary(range('A', 'Z')))->map(function($letter) use($alpha) {
        $map = ['letter' => $letter];
        if ($alpha == $letter) {
          $map['selected'] = 'selected';
        }
        return $map;
      });

      $this->{$sort} = "selected";
      $this->list = Graph::group($group)
           ->find('vertex'.$query)
           ->sort(Graph::sort($sort))
           ->map(function($vertex) {
             return ['item' => Graph::FACTORY($vertex)];
           })
           ->limit($index, $per, $this->setProperty('paginate', ['prefix' => "explore/index/{$group}/{$filter}/{$sort}"]));
    } else {
      // the homepage is a collection.
      $this->collection = new \models\collection(Graph::GROUP('collection')->pick("vertex[@sticky='homepage']"));
      $this->search     = ['topic' => 'feature', 'path' => 'search/group', 'area' => 'explore/detail'];
    }

    return $view->render($this());
  }

  public function GETdetail($id)
  {

    $this->item = Graph::FACTORY(Graph::ID($id));


    $this->title  = strip_tags($this->item['title']);
    $view = new view('views/layout.html');

    $view->content = "views/digests/{$this->item->template('digest')}.html";

    return $view->render($this());
  }

  public function GETbehindTheScenes($sort = 'date', $index = 1, $per = 25)
  {
    $view = new view('views/layout.html');
    $view->content = 'views/lists/article.html';
    $this->{$sort} = "selected";
    $this->search  = ['topic' => 'article', 'path' => 'search/group', 'area' => 'explore/detail'];
    $this->list    = Graph::group('article')
         ->find("vertex[starts-with(translate(@title, 'BEH*', 'beh'), 'beh')]")
         ->sort(Graph::sort($sort))
         ->map(function($item) {
           return ['item' => new \models\Article($item)];
         })
         ->limit($index, $per, $this->setProperty('paginate', ['prefix' => "explore/behindthescenes/{$sort}"]));

    return $view->render($this());
  }

  public function GETmedia($type = 'image', $index = 1, $per = 25)
  {
    $view = new View('views/layout.html');
    $view->content = 'views/lists/media.html';
    $this->search  = ['topic' => 'image', 'path' => 'search/media', 'area' => 'explore/resource'];
    $this->media   = Graph::instance()->query('/graph/group/vertex/')
                     ->find("media[@type='{$type}']")
                     ->map(function($item) {
                        return new \models\Media($item);
                      })
                      ->limit($index, $per, $this->setProperty('paginate', ['prefix' => "explore/media/{$type}"]));
    return $view->render($this());
  }

  public function GETresource($type, $context, $index = 0)
  {
    $view = new view('views/layout.html');
    $view->content = 'views/forms/partials/image.html';
    $media = new \models\Media(Graph::ID($context)->getElementsByTagName('media')->item($index));

    return $view->render($this($media->slug));
  }
}
