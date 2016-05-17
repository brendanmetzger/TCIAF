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
    $images = $this->media['image'];

    while ($images->valid() && $images->current()->mark < 2.25) {
      $images->next();
    }

    if ($photo = $images->current()) {
      return $photo;
    }
  }

  public function getSquare(\DOMElement $context)
  {
    $images = $this->media['image'];

    while ($images->valid() && ($images->current()->mark > 1.1 || $images->current()->mark < 0.9)) {
      $images->next();
    }

    if ($photo = $images->current()) {
      return $photo;
    }
  }

}
