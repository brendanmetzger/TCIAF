<?php
namespace controllers;
use \bloc\View;
use \bloc\DOM\Document;
use \bloc\types\xml;

/**
 * Explore Represents the user's interest.
 */

class Explore extends Manage
{

  protected function GETfeatures($index = 0, $per = 10)
  {
    $view = new View($this->partials['layout']);
    $cur = ($index*$per);
    
    $db = xml::load('data/db5');
    $this->features = $db->find("//group[@type='published']/token[position()>={$cur}][position()<={$per}]")->map(function($feature) use($db){
      $feature->name = (string)$db->findOne("//group[@type='person']/token[@id='{$feature->pointer['rel']}']")['title'];
      return $feature;
    });
    
    if (count($this->features) == $per) {
      $this->next = $index+1;
    }
    if ($index > 0) {
      $this->prev = $index-1;
    }
    
    $view->content = 'views/listing/features.html';
    $view->fieldlist = (new Document('<ul><li>[$location]</li><li>[$premier:@date]</li><li>[$premier]</li></ul>', [], Document::TEXT))->documentElement;
    return $view->render($this());
  }
  
  public function GETperson($pid)
  {
    $view = new View($this->partials['layout']);
    
    $db = xml::load('data/db5');
    
    $this->person   = $db->findOne("//group[@type='person']/token[@id='{$pid}']");
    $this->features = $db->xpath("//group[@type='published']/token[pointer[@rel='{$pid}']]");

    $view->content = 'views/forms/person.html';
    
    return $view->render($this());
  }
  
  public function GETpeople()
  {
    $view = new View($this->partials['layout']);
    $view->content = 'views/listing/people.html';
    
    $this->people = xml::load('data/db5')->find("//group[@type='person']/token[position()<100]")->map(function($person) {
      $person['title'] = strtoupper($person['title']);
      return $person;
    });
    
    
    
    return $view->render($this());
  }

  
  protected function GETedit($id)
  {
    $view = new View($this->partials['layout']);
    $view->content = 'views/forms/feature.html';
    $data = xml::load('data/db5');

    $this->s3_bucket = 'http://s3.amazonaws.com/3rdcoast-features/mp3s';
    $this->feature   = $data->findOne("/tciaf/group/token[@id='{$id}']");

    $this->feature->pointer->map(function($point) use($data) {
      return ['rel'     => $data->findOne("//token[@id='{$point['rel']}']"),
              'pointer' => $point,
            ];
    });

    return $view->render($this());
  }
  
  protected function POSTedit($request, $id)
  {
    \bloc\application::instance()->log($_POST);
  }
}