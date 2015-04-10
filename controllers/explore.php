<?php
namespace controllers;
use \bloc\View;
use \bloc\DOM\Document;
use \bloc\types\xml;
use \bloc\types\Dictionary;

/**
 * Explore Represents the user's interest.
 */

class Explore extends Manage
{

  protected function GETfeatures($index = 0, $per = 10)
  {
    $view = new View($this->partials['layout']);
    
    $db = xml::load('data/db5');
    
    $this->features = $db->find("//group[@type='published']/token")->map(function($feature) use($db){
      
      $feature->name = (string)$db->findOne("//group[@type='person']/token[@id='{$feature->pointer['rel']}']")['title'];
      return $feature;
      
    })->limit($index, $per, $this->setProperty('paginate', ['prefix' => 'explore/features']));
    
    
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
  
  public function GETpeople($type = 'producer', $index =0, $per = 100)
  {
    $view = new View($this->partials['layout']);
    $view->content = 'views/listing/people.html';

    $this->people = xml::load('data/db5')->find("id(//group[@type='published']//pointer[@type='{$type}']/@rel)")->map(function($person) {
        
        $person['title'] = strtoupper($person['title']);
        return $person;
        
    })->limit($index, $per, $this->setProperty('paginate', ['prefix' => "explore/people/{$type}"]));
    
    
    return $view->render($this());
  }

  
  protected function GETedit($id)
  {
    $view = new View($this->partials['layout']);
    $view->content = 'views/forms/feature.html';
    $data = xml::load('data/db5');

    $this->s3_url  = $data->findOne('/tciaf/config/key[@id="k:s3"]');
    $this->feature = $data->findOne("/tciaf/group/token[@id='{$id}']");
    
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