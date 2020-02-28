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
    $query = 'media[@type="image" and @mark > 2.5]';
    $banner  = $this->context->find($query);

    if ($banner->count() > 0) {
      return new \models\media($banner->pick());
    } elseif ($this->editions->count() > 1) {
      $this->competitions->rewind();
      $banner = $this->competitions->current()['edition']->context->find($query);
      if ($banner->count() > 0) {
        return new \models\media($banner->pick());
      }
      
    }
  }

  public function getSquare(\DOMElement $context)
  {
    $images = $this->media['image'];

    // a square image has special placement; left in a little wiggle room, as a square as haspect ration of 1:1
    while ($images->valid() && ($images->current()->mark > 1.1 || $images->current()->mark < 0.9)) {
      $images->next();
    }

    if ($photo = $images->current()) {
      return $photo;
    }
  }

}
