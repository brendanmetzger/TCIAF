<?php
namespace models\traits;
  

trait navigation {
  
  public function getPages(\DOMElement $context)
  {
    return $context->find("edge[@type='page' or @type='article']")->map(function($edge) {
      return ['article' => new \models\Article($edge['@vertex'])];
    });  
  }
  
  public function getPlaylists(\DOMElement $context)
  {
    return $context->find("edge[@type='playlist']")->map(function($edge) {
      return ['playlist' => new \models\Collection($edge['@vertex'])];
    });  
  }
}