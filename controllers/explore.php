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
  
  public function GETfeature($id)
  {
    $view = new view('views/layout.html');
    $view->content = 'views/digests/feature.html';
    $this->feature = new \models\Feature($id);
    
    return $view->render($this());
  }
  
  public function GETPerson($id)
  {
    $view = new view('views/layout.html');
    $view->content = 'views/digests/person.html';
    $this->person = new \models\Person($id);
    return $view->render($this());
  }
  
  public function GETCompetition($id)
  {
    $view = new view('views/layout.html');
    $view->content = 'views/digests/competition.html';
    $this->competition = new \models\Competition($id);
    return $view->render($this());
  }
  
  
  
  protected function GETfeatures($sort = 'year-produced', $index = 1, $per = 25)
  {
    $sorters = [
      'newest' => function($a, $b) {
        return strtotime($a->getAttribute('created')) < strtotime($b->getAttribute('created'));
      },
      'updated' => function($a, $b) {
        return strtotime($a->getAttribute('updated')) < strtotime($b->getAttribute('updated'));
      },
      'title' => function($a, $b) {
        return ucfirst(ltrim($a->getAttribute('title'), "\x00..\x2F")) > ucfirst(ltrim($b->getAttribute('title'), "\x00..\x2F"));
      },
      'year-produced' => function($a, $b) {
        return strtotime($a->getFirst('premier')->getAttribute('date')) < strtotime($b->getFirst('premier')->getAttribute('date'));
      }
    ];
    
    $view = new view('views/layout.html');
    $view->content   = 'views/lists/features.html';
    
    $this->search = ['topic' => 'feature'];
    $this->features = $f = Graph::group('feature')->find('vertex')->sort($sorters[$sort])->map(function($feature) {
      return [
        'feature'   => new \models\Feature($feature),
        'producers' => Graph::group('person')->find("vertex[edge[@vertex='{$feature['@id']}']]"),
      ];
    })->limit($index, $per, $this->setProperty('paginate', ['prefix' => "explore/features/{$sort}"]));

    
    return $view->render($this());
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
  
  public function GETcompetitions($type = 'all', $index = 1, $per = 100)
  {
    $view = new view('views/layout.html');
    $view->content = 'views/lists/competitions.html';
    $this->search = ['topic' => 'competition'];
    $this->competitions = Graph::group('competition')
                         ->find("vertex[edge[@type='issue']]")
                         ->limit($index, $per, $this->setProperty('paginate', ['prefix' => "explore/competitions/{$type}"]));

    return $view->render($this());
  }
  
  
  public function GETorganizations($type = 'all', $index = 1, $per = 100)
  {
    $view = new view('views/layout.html');
    $view->content = 'views/lists/organizations.html';
    $this->search = ['topic' => 'organization'];
    $this->organizations = Graph::group('organization')
                           ->find('vertex')
                           ->limit($index, $per, $this->setProperty('paginate', ['prefix' => "explore/organizations/{$type}"]));
    
    return $view->render($this());
  }
}