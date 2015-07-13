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
    $view = new view('views/layout.html');

    $tokens = [];
    
    foreach (Graph::group('feature')->find('vertex[@mark < '.time().']') as $feature) {
      $tokens[] = $feature['@id'];
    }
    

    $this->tokens = implode(' ', $tokens);
    
    return $view->render($this());
  }
  
  public function GETdetail($id)
  {
    $view = new view('views/layout.html');
    $this->item   = Graph::factory(Graph::ID($id));
    $view->content = "views/digests/{$this->item->get_model()}.html";
    return $view->render($this());
  }
  
  
  protected function GETcenterpiece($group, $sort = 'year-produced', $index = 1, $per = 25)
  {
    $view = new view('views/layout.html');
    $view->content   = 'views/lists/features.html';
    $this->group = $group;
    $this->search = ['topic' => $group];
    $this->features = Graph::group($group)->find('vertex')->sort(Graph::sort($sort))->map(function($feature) {
      return [
        'feature'   => new \models\Feature($feature),
        'producers' => Graph::group('person')->find("vertex[edge[@vertex='{$feature['@id']}']]"),
      ];
    })->limit($index, $per, $this->setProperty('paginate', ['prefix' => "explore/{$group}/{$sort}"]));
    
    return $view->render($this());
  }
  
  protected function GETfeature($sort = 'year-produced', $index = 1, $per = 25)
  {
    return $this->GETcenterpiece('feature', $sort, $index, $per);
  }
  
  protected function GETbroadcast($sort = 'year-produced', $index = 1, $per = 25)
  {
    return $this->GETcenterpiece('broadcast', $sort, $index, $per);
  }

  protected function GETarticle($sort = 'year-produced', $index = 1, $per = 25)
  {
    return $this->GETcenterpiece('article', $sort, $index, $per);
  }
  
  

  public function GETperson($type = 'all', $index = 1, $per = 100)
  {
    $view = new view('views/layout.html');
    $view->content = 'views/lists/person.html';
    $this->search = ['topic' => 'person'];
    $this->people = Graph::group('person')->find($type === 'all' ? 'vertex' : "vertex[edge[@type='{$type}']]")
                    ->sort(function($a, $b) {
                      return $a['@title'] > $b['@title'];
                    })
                    ->limit($index, $per, $this->setProperty('paginate', ['prefix' => "explore/person/{$type}"]));



    return $view->render($this());
  }
  
  
  public function GETcollection($group, $type, $index, $per, $query = '')
  {
    $view = new view('views/layout.html');
    $view->content = 'views/lists/collection.html';
    $this->search = ['topic' => $group];
    $this->collection = Graph::group($group)
                           ->find('vertex'.$query)
                           ->limit($index, $per, $this->setProperty('paginate', ['prefix' => "explore/{$group}/{$type}"]));
    
    return $view->render($this());
  }
  
  
  public function GETcompetition($type = 'all', $index = 1, $per = 100)
  {
    return $this->GETcollection('competition', $type, $index, $per, "[edge[@type='edition']]");
  }
  
  public function GETorganization($type = 'all', $index = 1, $per = 100)
  {
    return $this->GETcollection('organization', $type, $index, $per);
  }
  
  public function GETensemble($type = 'all', $index = 1, $per = 100)
  {
    return $this->GETcollection('ensemble',$type, $index, $per);
  }
  
  public function GEThappening($type = 'all', $index = 1, $per = 100)
  {
    return $this->GETcollection('happening',$type, $index, $per);
  }
  
}