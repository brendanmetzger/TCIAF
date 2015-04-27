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
    $this->item    = $storage->getElementById($id);
    
    $type = $this->item->parentNode->getAttribute('type');
    
    $view->content = "views/forms/{$type}.html";
    
    parse_str($this->item->getFirst('spectrum')->nodeValue, $spectra);

    $this->spectra = $storage->find('/tciaf/config/spectra')->map(function($item) use($spectra) {
      return ['item' => $item, 'title' => $item->nodeValue, 'value' => $spectra[$item['@id']]];
    });
    

    $this->pointers = $this->item['pointer']->map(function($point) use($storage) {
      $token = $storage->getElementById($point['@token']);
      return [ 'token' => $token, 'pointer' => $point ];
    });
    
    

    $this->references = $storage->find("/tciaf/group/token[pointer[@token='{$id}']]");
    
    return $view->render($this());
  }

  protected function POSTedit($request, $id = null)
  {
    $model = Token::factory(Token::ID($id));
    $instance = $model::create(new $model($id), $_POST);

    /*
      TODO proper redirect
    */
    
    /*
      TODO retrieve validation errors and output
    */
    if ($instance->save()) {
      \bloc\application::instance()->log($instance);
    }
    
    
  }
}