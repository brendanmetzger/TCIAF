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
    $type = \bloc\request::$data['content-type'];
    $filename  = PATH . 'data/media/' . $image . '.' . $type;

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

    \bloc\Application::instance()->getExchange('response')->addHeader("Content-Type: image/{$type}");
    return $output;
  }

  public function GETmontage($id, $edge)
  {
    $item = \models\Graph::ID($id);

    $images  = [];
    
    foreach ($item->find("edge[@type='{$edge}']") as $edge) {
      foreach (\models\Graph::ID($edge['@vertex'])->find("media[@type='image']") as $item) {
        $images[] = preg_replace('/^(feature-photos\/photos\/[0-9]+\/)(.*)$/i', '$1large/$2', $item['@src']);
        break;
      }
    }
    
    
    $max     = 200;
    $size    = floor(sqrt(count($images)));
    $length  = $size * $max;
    $img_out = imagecreatetruecolor($length, $length * 0.6);
    
    $x_index = 0;
    $y_index = 0;
    foreach ($images as $idx => $src) {
      
      $img_in = imagecreatefromjpeg('http://s3.amazonaws.com/'.$src);
      imagefilter($img_in, IMG_FILTER_GRAYSCALE);
      // imagefilter($img_in, IMG_FILTER_BRIGHTNESS, 50);
      // imagefilter($img_in, IMG_FILTER_EDGEDETECT);
      
      // $img_in = imagerotate($img_in, rand(-5, 5), 0);
      
      $width = imagesx($img_in);
      $height = imagesy($img_in);
      $ratio = $width / $height;
      
      
      // landscape
      if ($ratio > 1) {
        $new_width  = $max;
        $new_height = $max / $ratio;
      } else {
        $new_height = $max;
        $new_width  = $max * $ratio;
      }
      
      imagecopyresized($img_out, $img_in, $x_index * $max, $y_index * $max * 0.6, 0, 0, $new_width, $new_height, $width, $height);
      imagedestroy($img_in);
      $x_index++;
      if ($x_index >= $size) {
          $x_index = 0;
          $y_index++;
          
      }  
      
    }
    
    ob_start();
    // IMG_FILTER_COLORIZE
    // imagefilter($img_out, IMG_FILTER_PIXELATE, 15);
    // imagefilter($img_out, IMG_FILTER_COLORIZE, 110, 50, 50, 100);
    imagejpeg($img_out, null, 100);
    $output = ob_get_clean(); 
    imagedestroy($img_out);
    \bloc\Application::instance()->getExchange('response')->addHeader("Content-Type: image/jpeg");
    return $output;
    
    
    

    foreach ($reviews->documentElement->childNodes as $img) {
      $src = preg_replace('/600x600/','100x100', $img->getAttribute('src'));
        
      $headers = get_headers($src);

      if ((strpos($headers[0], '404') > 0)) {
        continue;
      }

      

      if (strpos($src, 'mzstatic')) {
        imagecopy($montage_image, $current_image, $x_index * 100, $y_index * 100, 0, 0, 100, 100);
      } else {
        imagecopyresized($montage_image, $current_image, $x_index * 100, $y_index * 100, 0, 0, 100, 100, imagesx($current_image), imagesy($current_image));
      }
  
      imagedestroy($current_image);
      $x_index++;
      if ($x_index >= $size) {
          $x_index = 0;
          $y_index++;
      }    
    }

    ob_start();
    imagejpeg($montage_image, null, 100);
    $output = ob_get_clean(); 
    imagedestroy($montage_image);
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
