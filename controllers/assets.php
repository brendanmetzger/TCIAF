<?php
namespace controllers;
use \bloc\view as view;

/**
 * Explore Represents the user's interest.
 */

class Assets extends Manage
{

  public function GETimage($file = null)
  {
    $view = new View('views/images/pidgey.svg');
    $this->brown = '#FFF' ?: '#88746A';
    $this->red = '#FF0000' ?: '#EE3124';
    $this->blue = '#B3DDF2' ?: '#5B9B98';
    return $view->render($this());
  }

  public function GETscale($max, $image)
  {
    // File and new size
    $filename  = PATH . 'data/media/' . $image . '.jpg';

    // Get new sizes
    list($width, $height) = getimagesize($filename);

    $ratio = $width / $height;
    // landscape
    if ($ratio > 1) {
      $new_width  = $max;
      $new_height = $max / $ratio;
    } else {
      $new_height = $max;
      $new_width  = $max * $ratio;
    }
    // Load
    $thumb  = imagecreatetruecolor($new_width, $new_height);
    $source = imagecreatefromjpeg($filename);

    // Resize
    imagecopyresized($thumb, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

    // Output
    ob_start();
    imagejpeg($thumb, NULL, 90);
    $output = ob_get_contents();
    ob_end_clean(); // stop this output buffer

    imagedestroy($thumb);
    imagedestroy($source);

    return $output;
  }

  public function GETbackground($callback = null)
  {
    $out = [
      'url(\'data:image/png;base64,'.base64_encode(file_get_contents(PATH . 'views/images/birds-background-B.png')).'\')',
      'url(\'data:image/png;base64,'.base64_encode(file_get_contents(PATH . 'views/images/birds-background-A.png')).'\')',
    ];

    return sprintf('load_bg_async(%s)', json_encode($out));
  }
}
