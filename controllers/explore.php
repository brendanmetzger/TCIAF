<?php
namespace controllers;
use \bloc\View;
use \bloc\DOM\Document;
use \bloc\types\Map;

/**
 * Explore Represents the user's interest.
 */

class Explore extends Manage
{

  protected function GETreview($index = 0, $per = 10)
  {
    $view = new View($this->partials['layout']);
    $cur = ($index*$per);
    
    $db = simplexml_load_string(gzdecode(file_get_contents(PATH.'data/db5')), '\\bloc\\types\\xml', LIBXML_COMPACT);
    $this->features = $db->xpath("/tciaf/group[@type='published']/token[position()>={$cur}][position()<={$per}]");
    
    if (count($this->features) == $per) {
      $this->next = $index+1;
    }
    if ($index > 0) {
      $this->prev = $index-1;
    }
    
    $view->content = 'views/listing/features.html';
    // $view->fieldlist = (new Document('<ul><li>[$location]</li><li>[$premier:@date]</li><li>[$premier]</li><li>[$@published]</li></ul>', [], Document::TEXT))->documentElement;
    return $view->render($this());
  }
  

  
  protected function GETedit($id)
  {
    $view = new View($this->partials['layout']);
    $db = simplexml_load_string(file_get_contents(PATH.'data/db5.xml'), '\\bloc\\types\\xml', LIBXML_COMPACT);

    $this->s3       = 'http://s3.amazonaws.com/3rdcoast-features/mp3s';
    $this->feature  = $db->xpath("/tciaf/group/token[@id='{$id}']")[0];

    foreach ($this->feature->pointer as $point) {
      $points[] = ['rel' => $db->xpath("/tciaf/group/token[@id='{$point['rel']}']")[0], 'pointer' => $point];
    }

    $this->pointers = $points;

    \bloc\application::instance()->log($this->pointers);

    $view->content = 'views/forms/feature.html';
    return $view->render($this());
  }
  
  protected function POSTedit($request, $id)
  {
    \bloc\application::instance()->log($_POST);
  }
}