<?php
namespace controllers;
use \bloc\view as view;

/**
 * Explore Represents the user's interest.
 */

class Explore extends Manage
{

  protected function GETreview($id = null)
  {
    $view = new View($this->partials['layout']);
    
    $db   = new \mysqli('127.0.0.1', 'root', '', 'TCIAF');
    $this->features = $db->query("SELECT * FROM features LIMIT 105")->fetch_all(MYSQLI_ASSOC);
    $view->content = 'views/feature.html';

    // $view->fieldlist = (new \bloc\DOM\Document("<ul><li>[@origin_country]</li><li>[@premier_locaction]</li><li>[@premier_date]</li><li>[@published]</li><li>[@delta]</li></ul>", [], \bloc\DOM\Document::TEXT))->documentElement;
    
    return $view->render($this());
  }
  
  protected function GETxml($id = null)
  {
    $view = new View($this->partials['layout']);
    
    $db = simplexml_load_file(PATH.'data/features.xml', '\\bloc\\types\\xml', LIBXML_COMPACT);
    $this->features = $db->xpath('//features/row[position()<=105]');;    
    $view->content = 'views/feature.html';

    // $view->fieldlist = (new \bloc\DOM\Document("<ul><li>[@origin_country]</li><li>[@premier_locaction]</li><li>[@premier_date]</li><li>[@published]</li><li>[@delta]</li></ul>", [], \bloc\DOM\Document::TEXT))->documentElement;
    
    return $view->render($this());
  } 

  
  protected function GETfix($id)
  {
    $view = new View($this->partials['layout']);
    $db   = new \mysqli('127.0.0.1', 'root', '', 'TCIAF');
    $this->s3 = 'http://s3.amazonaws.com/3rdcoast-features/mp3s';
    $this->feature = $db->query("SELECT * FROM features LEFT JOIN audio_files ON (features.id = audio_files.feature_id) WHERE features.id = '{$id}'")->fetch_assoc();
    // \bloc\application::dump($this->feature);
    $view->content = 'views/forms/file.html';
    return $view->render($this());
  } 
}