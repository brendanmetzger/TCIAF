<?php
namespace controllers;
use \bloc\View;
use \bloc\DOM\Document;

/**
 * Explore Represents the user's interest.
 */

class Explore extends Manage
{

  protected function GETreview($id = null)
  {
    $view = new View($this->partials['layout']);
    $db = simplexml_load_string(file_get_contents(PATH.'data/records.xml'), '\\bloc\\types\\xml', LIBXML_COMPACT);
    $this->features = $db->xpath('//records/record[position()>=200 and position()<=300]');

    $view->content = 'views/feature.html';
    $view->fieldlist = (new Document('<ul><li>[$location]</li><li>[$premier:@date]</li><li>[$premier]</li><li>[$@published]</li></ul>', [], Document::TEXT))->documentElement;
    return $view->render($this());
  }
  

  
  protected function GETfix($id)
  {
    $view = new View($this->partials['layout']);
    $db   = new \mysqli('127.0.0.1', 'root', '', 'TCIAF');
    $id = substr($id, 1);
    $this->s3 = 'http://s3.amazonaws.com/3rdcoast-features/mp3s';
    $this->feature = $db->query("SELECT * FROM features LEFT JOIN audio_files ON (features.id = audio_files.feature_id) WHERE features.id = '{$id}'")->fetch_assoc();
    echo '<pre>'.print_r(\bloc\application::log($this->feature), true).'</pre>';
    // \bloc\application::dump($this->feature);
    $view->content = 'views/forms/file.html';
    return $view->render($this());
  } 
}