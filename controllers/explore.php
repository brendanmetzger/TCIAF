<?php
namespace controllers;
use \bloc\View;
use \bloc\DOM\Document;
use \bloc\types\Dictionary;
use \models\Token;

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
    
    $this->features = Token::storage()->find("/tciaf/group[@type='published']/token")->map(function($feature) {
      return [
        'feature'   => $feature,
        'producers' => Token::storage()->find("/tciaf/group[@type='person']/token[pointer[@token='{$feature['@id']}']]"),
      ];
    })->limit($index, $per, $this->setProperty('paginate', ['prefix' => 'explore/features']));

    return $view->render($this());
  }

  public function GETpeople($type = 'producer', $index = 1, $per = 100)
  {
    $view = new View($this->partials->layout);
    $view->content = 'views/lists/people.html';
    $this->search = ['topic' => 'people'];
    $this->people = Token::storage()
                    ->find("//group[@type='person']/token[pointer[@type='{$type}']]")
                    ->limit($index, $per, $this->setProperty('paginate', ['prefix' => "explore/people/{$type}"]));



    return $view->render($this());
  }
  

  protected function GETedit($id)
  {
    $view = new View($this->partials->layout);
    

    $storage = Token::storage();

    $this->s3_url  = $storage->getElementById('k:s3');
    
    $this->item = Token::factory(Token::ID($id));

    $view->content = sprintf("views/forms/%s.html", $this->item->name());
        
    
    
    $this->pointers = $this->item->pointer->map(function($point) use($storage) {
      $token = $storage->getElementById($point['@token']);
      return [ 'token' => $token, 'pointer' => $point ];
    });
    
    

    $this->references = $storage->find("/tciaf/group/token[pointer[@token='{$id}']]");
    
    return $view->render($this());
  }

  protected function POSTedit($request, $id = null)
  {
    $model = Token::factory(Token::ID($id));
    
    /*
      TODO proper redirect
    */
    
    /*
      TODO retrieve validation errors and output
    */
    if ($instance = $model::create($model, $_POST)) {
      if ($instance->save()) {
        \bloc\application::instance()->log($instance);
      }
    } else {
      \bloc\application::instance()->log($model->errors);
    }
    
    
  }
}