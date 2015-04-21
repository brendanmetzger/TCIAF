<?php
namespace controllers;
use \bloc\View;
use \bloc\DOM\Document;
use \bloc\types\XML;
use \bloc\types\Dictionary;
use \models\Token;

/**
 * Explore Represents the user's interest.
 */

class Explore extends Manage
{
  protected function GETfeatures($index = 0, $per = 25)
  {
    $view = new View($this->partials->layout);
    $view->content   = 'views/listing/features.html';
    $view->fieldlist = (new Document('<ul><li>[$feature:location]</li><li>[$feature:premier:@date]</li><li>[$feature:premier]</li></ul>', [], Document::TEXT))->documentElement;

    $db = XML::load(Token::DB);

    $this->features = $db->find("/tciaf/group[@type='published']/token")->map(function($feature) use($db) {
      return [
        'feature'   => $feature,
        'producers' => $db->find("/tciaf/group[@type='person']/token[pointer[@token='{$feature['id']}']]"),
      ];
    })->limit($index, $per, $this->setProperty('paginate', ['prefix' => 'explore/features']));

    return $view->render($this());
  }

  public function GETpeople($type = 'producer', $index = 0, $per = 100)
  {
    $view = new View($this->partials->layout);
    $view->content = 'views/listing/people.html';

    $this->people = XML::load(Token::DB)
                    ->find("//group[@type='person']/token[pointer[@type='{$type}']]")
                    // ->map(function($person) {
                    //   return $person;
                    // })
                    ->limit($index, $per, $this->setProperty('paginate', ['prefix' => "explore/people/{$type}"]));


    return $view->render($this());
  }
  

  protected function GETedit($type, $id)
  {
    $view = new View($this->partials->layout);
    $view->content = "views/forms/{$type}.html";

    $data = XML::load(Token::DB);

    $this->s3_url = $data->findOne('/tciaf/config/key[@id="k:s3"]');
    $this->item   = $data->findOne("/tciaf/group/token[@id='{$id}']");

    $this->item->pointer->map(function($point) use($data) {
      return ['token'     => $data->findOne("//token[@id='{$point['token']}']"),
              'pointer' => $point,
            ];
    });
    
    $this->raw = $this->item->asXML();

    return $view->render($this());
  }

  protected function POSTedit($request, $type, $id)
  {
    echo "<pre>";
    print_r($_POST);
  }
}