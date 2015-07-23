<?php
namespace controllers;

use \bloc\view;
use \bloc\dom\document;
use \bloc\types\dictionary;

use \models\Graph;

/**
 * Search various things
 */

class Search extends Manage
{
  public function GETgroup($type, $subset = null)
  {
    $list   = Graph::group($type)->find('vertex');
    $search = new \models\search($list);

    return $search->asJSON('group', $subset, $type);
  }
  
  public function GETmedia($type, $subset = null)
  {
    $list = Graph::instance()->query('/graph/group/vertex/')->find("media[@type='{$type}']")->map(function($item) {
      return new \models\Media($item);
    });
    $search = new \models\search($list);
    $search->key = 'context';
    $search->tag = 'caption';
    
    return $search->asJSON('media', $subset, $type);
  }
  
  public function GETform($type)
  {
    $view = new View('views/forms/partials/search.html');
    $this->search = ['topic' => $type, 'path' => 'search/group'];
    return $view->render($this());
  }
}