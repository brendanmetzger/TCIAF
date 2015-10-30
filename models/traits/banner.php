<?php
namespace models\traits;
  

trait banner {
  
  public function getPhoto(\DOMElement $context)
  {
    if ($photo = $this->media['image']->current()) {
      return $photo;
    }
  }
  
  public function getBanner(\DOMElement $context)
  {
    if ($photo = $this->media['image']->current()) {
      return $photo;
    }
  } 
}