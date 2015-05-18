<?php
namespace controllers;

use \bloc\view;
use \bloc\dom\document;
use \bloc\types\dictionary;

use \models\token;

/**
 * Explore Represents the user's interest.
 */

class Explore extends Manage
{
  protected function GETfeatures($type = 'all', $index = 1, $per = 25)
  {
    $view = new View($this->partials->layout);
    $view->content   = 'views/lists/features.html';
    $view->fieldlist = (new Document('<ul><li>[$feature:location]</li><li>[$feature:premier:@date]</li><li>[$feature:premier]</li></ul>', [], Document::TEXT))->documentElement;
    
    $this->search = ['topic' => 'published'];
    $this->features = Token::storage()->find("/tciaf/group[@type='published']/vertex")->map(function($feature) {
      return [
        'feature'   => new \models\Published($feature),
        'producers' => Token::storage()->find("/tciaf/group[@type='person']/vertex[edge[@token='{$feature['@id']}']]"),
      ];
    })->limit($index, $per, $this->setProperty('paginate', ['prefix' => "explore/features/{$type}"]));

    return $view->render($this());
  }

  public function GETpeople($type = 'all', $index = 1, $per = 100)
  {
    $view = new View($this->partials->layout);
    $view->content = 'views/lists/people.html';
    $this->search = ['topic' => 'person'];
    $predicate = $type === 'all' ? '' : "[edge[@type='{$type}']]";
    $this->people = Token::storage()
                    ->find("/tciaf/group[@type='person']/vertex{$predicate}")
                    ->sort(function($a, $b) {
                      return $a['@title'] > $b['@title'];
                    })
                    ->limit($index, $per, $this->setProperty('paginate', ['prefix' => "explore/people/{$type}"]));



    return $view->render($this());
  }
  
  public function GETcompetitions($type = 'all', $index = 1, $per = 100)
  {
    $view = new View($this->partials->layout);
    $view->content = 'views/lists/competitions.html';
    $this->search = ['topic' => 'competition'];
    $this->competitions = Token::storage()
                    ->find("//group[@type='competition']/vertex[edge[@type='issue']]")
                    ->limit($index, $per, $this->setProperty('paginate', ['prefix' => "explore/competitions/{$type}"]));

    return $view->render($this());
  }
  
  
  public function GETorganizations($type = 'all', $index = 1, $per = 100)
  {
    $view = new View($this->partials->layout);
    $view->content = 'views/lists/organizations.html';
    $this->search = ['topic' => 'organization'];
    $this->organizations = Token::storage()
                    ->find("//group[@type='organization']/vertex")
                    ->limit($index, $per, $this->setProperty('paginate', ['prefix' => "explore/organizations/{$type}"]));
    
    return $view->render($this());
  }
}