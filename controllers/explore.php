<?php
namespace controllers;

use \bloc\view;
use \bloc\dom\document;
use \bloc\types\dictionary;

use \models\graph;

/**
 * Explore Represents the user's interest.
 */

class Explore extends Manage
{
  public function GETindex()
  {
    $tokens = [];    
    foreach (Graph::group('feature')->find('vertex[@mark < '.time().']') as $feature) {
      $tokens[] = $feature['@id'];
    }
    $this->tokens = implode(' ', $tokens);
    
    return (new view('views/layout.html'))->render($this());
  }
  
  public function GETdetail($id)
  {

    $this->item   = Graph::factory(Graph::ID($id));
    $this->title  = $this->item['@title'] . ", TCIAF";
    $view = new view('views/layout.html');
    $view->content = $v = "views/digests/{$this->item->getView()}.html";
    return $view->render($this());
  }
  
  
  protected function GETcenterpiece($group, $sort = 'year-produced', $index = 1, $per = 25)
  {
    $view = new view('views/layout.html');
    $view->content  = 'views/lists/features.html';
    $this->group    = $group;
    $this->{$sort}  = "selected";
    $this->search   = ['topic' => $group, 'path' => 'search/group', 'area' => 'explore/detail'];
    $this->features = Graph::group($group)->find('vertex')->sort(Graph::sort($sort))->map(function($feature) {
      return [
        'feature'   => new \models\Feature($feature),
        'producers' => Graph::group('person')->find("vertex[edge[@vertex='{$feature['@id']}']]"),
      ];
    })->limit($index, $per, $this->setProperty('paginate', ['prefix' => "explore/{$group}/{$sort}"]));
    
    return $view->render($this());
  }
  
  public function GETfeature($sort = 'newest', $index = 1, $per = 25)
  {
    return $this->GETcenterpiece('feature', $sort, $index, $per);
  }
  
  public function GETbroadcast($sort = 'newest', $index = 1, $per = 25)
  {
    return $this->GETcenterpiece('broadcast', $sort, $index, $per);
  }

  public function GETarticle($sort = 'newest', $index = 1, $per = 25)
  {
    return $this->GETcenterpiece('article', $sort, $index, $per);
  }
  
  

  public function GETperson($type = 'all', $index = 1, $per = 100)
  {
    $view = new view('views/layout.html');
    $view->content = 'views/lists/person.html';
    $this->search  = ['topic' => 'person', 'path' => 'search/group', 'area' => 'explore/detail'];
    $this->people  = Graph::group('person')
                     ->find($type === 'all' ? 'vertex' : "vertex[edge[@type='{$type}']]")
                     ->sort(function($a, $b) {
                       return $a['@title'] > $b['@title'];
                     })
                     ->limit($index, $per, $this->setProperty('paginate', ['prefix' => "explore/person/{$type}"]));



    return $view->render($this());
  }
  
  
  public function GETgroup($group, $type, $index, $per, $query = '')
  {
    $view = new View('views/layout.html');
    $view->content = 'views/lists/collection.html';
    $this->search = ['topic' => $group, 'path' => 'search/group', 'area' => 'explore/detail'];
    $this->collection = Graph::group($group)
                        ->find('vertex'.$query)
                        ->limit($index, $per, $this->setProperty('paginate', ['prefix' => "explore/{$group}/{$type}"]));
    
    return $view->render($this());
  }
  
  public function GETcollection($type = 'all', $index = 1, $per = 100)
  {
    return $this->GETgroup('collection', $type, $index, $per);
  }
  
  
  public function GETcompetition($type = 'all', $index = 1, $per = 100)
  {
    return $this->GETgroup('competition', $type, $index, $per);
  }
  
  public function GETorganization($type = 'all', $index = 1, $per = 100)
  {
    return $this->GETgroup('organization', $type, $index, $per);
  }
  
  public function GETensemble($type = 'all', $index = 1, $per = 100)
  {
    return $this->GETgroup('ensemble',$type, $index, $per);
  }
  
  public function GEThappening($type = 'all', $index = 1, $per = 100)
  {
    return $this->GETgroup('happening',$type, $index, $per);
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