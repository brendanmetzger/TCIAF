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
  protected function GETfeatures($index = 1, $per = 25)
  {
    $view = new View($this->partials->layout);
    $view->content   = 'views/lists/features.html';
    $view->fieldlist = (new Document('<ul><li>[$feature:location]</li><li>[$feature:premier:@date]</li><li>[$feature:premier]</li></ul>', [], Document::TEXT))->documentElement;
    
    $this->search = ['topic' => 'published'];
    $this->features = Token::storage()->find("/tciaf/group[@type='published']/token")->map(function($feature) {
      return [
        'feature'   => new \models\Published($feature),
        'producers' => Token::storage()->find("/tciaf/group[@type='person']/token[pointer[@token='{$feature['@id']}']]"),
      ];
    })->limit($index, $per, $this->setProperty('paginate', ['prefix' => 'explore/features']));

    return $view->render($this());
  }

  public function GETpeople($type = 'all', $index = 1, $per = 100)
  {
    $view = new View($this->partials->layout);
    $view->content = 'views/lists/people.html';
    $this->search = ['topic' => 'person'];
    $predicate = $type === 'all' ? '' : "[pointer[@type='{$type}']]";
    $this->people = Token::storage()
                    ->find("//group[@type='person']/token{$predicate}")
                    ->sort(function($a, $b) {
                      return $a['@title'] > $b['@title'];
                    })
                    ->limit($index, $per, $this->setProperty('paginate', ['prefix' => "explore/people/{$type}"]));



    return $view->render($this());
  }
  
  public function GETcompetitions($index = 1, $per = 100)
  {
    $view = new View($this->partials->layout);
    $view->content = 'views/lists/competitions.html';
    $this->search = ['topic' => 'competition'];
    $this->competitions = Token::storage()
                    ->find("//group[@type='competition']/token[pointer[@type='issue']]")
                    ->limit($index, $per, $this->setProperty('paginate', ['prefix' => "explore/competitions"]));

    return $view->render($this());
  }
  
  
  public function GETorganizations($index = 1, $per = 100)
  {
    $view = new View($this->partials->layout);
    $view->content = 'views/lists/organizations.html';
    $this->search = ['topic' => 'organization'];
    $this->organizations = Token::storage()
                    ->find("//group[@type='organization']/token")
                    ->limit($index, $per, $this->setProperty('paginate', ['prefix' => "explore/organizations"]));



    return $view->render($this());
  }
  


}