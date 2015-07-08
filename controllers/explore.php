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
    \bloc\application::instance()->log($this->item->title);
    return $view->render($this());
  }
  
  
  protected function GETcenterpiece($group, $sort = 'year-produced', $index = 1, $per = 25)
  {
    $view = new view('views/layout.html');
    $view->content   = 'views/lists/features.html';
    
    $this->search = ['topic' => $group];
    $this->features = Graph::group($group)->find('vertex')->sort(Graph::sort($sort))->map(function($feature) {
      return [
        'feature'   => new \models\Feature($feature),
        'producers' => Graph::group('person')->find("vertex[edge[@vertex='{$feature['@id']}']]"),
      ];
    })->limit($index, $per, $this->setProperty('paginate', ['prefix' => "explore/features/{$sort}"]));
    
    return $view->render($this());
  }
  
  protected function GETfeatures($sort = 'year-produced', $index = 1, $per = 25)
  {
    return $this->GETcenterpiece('feature', $sort, $index, $per);
  }
  
  protected function GETbroadcasts($sort = 'year-produced', $index = 1, $per = 25)
  {
    return $this->GETcenterpiece('broadcast', $sort, $index, $per);
  }

  protected function GETarticles($sort = 'year-produced', $index = 1, $per = 25)
  {
    return $this->GETcenterpiece('article', $sort, $index, $per);
  }
  
  

  public function GETpeople($type = 'all', $index = 1, $per = 100)
  {
    $view = new view('views/layout.html');
    $view->content = 'views/lists/people.html';
    $this->search = ['topic' => 'person'];
    $this->people = Graph::group('person')->find($type === 'all' ? 'vertex' : "vertex[edge[@type='{$type}']]")
                    ->sort(function($a, $b) {
                      return $a['@title'] > $b['@title'];
                    })
                    ->limit($index, $per, $this->setProperty('paginate', ['prefix' => "explore/people/{$type}"]));



    return $view->render($this());
  }
  
  
  public function GETcollection($group, $type, $index, $per, $query = '')
  {
    $view = new view('views/layout.html');
    $view->content = 'views/lists/collection.html';
    $this->search = ['topic' => $group];
    $this->collection = Graph::group($group)
                           ->find('vertex'.$query)
                           ->limit($index, $per, $this->setProperty('paginate', ['prefix' => "explore/{$group}s/{$type}"]));
    
    return $view->render($this());
  }
  
  
  public function GETcompetitions($type = 'all', $index = 1, $per = 100)
  {
    return $this->GETcollection('competition', $type, $index, $per, "[edge[@type='issue']]");
  }
  
  public function GETorganizations($type = 'all', $index = 1, $per = 100)
  {
    return $this->GETcollection('organization', $type, $index, $per);
  }
  
  public function GETevents($type = 'all', $index = 1, $per = 100)
  {
    return $this->GETcollection('event',$type, $index, $per);
  }
  
  public function GETconferences($type = 'all', $index = 1, $per = 100)
  {
    return $this->GETcollection('conferences',$type, $index, $per);
  }
  
  public function GETfestivals($type = 'all', $index = 1, $per = 100)
  {
    return $this->GETcollection('festivals',$type, $index, $per);
  }
}