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

  public function GETindex($token)
  {
    echo "ok";
    flush();
    $groups = Graph::instance()->query('graph/group[@type!="archive"]/')->find('.');
    foreach ($groups as $group) {
      $search = \models\search::FACTORY($group);
      $index = $search->createIndex('group');
    }
  }

  public function GETtry()
  {
    \models\search::BUILD();
  }

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
    $search->key = 'xid';
    $search->tag = 'plain';

    return $search->asJSON('media', $subset, $type);
  }

  public function GETform($type)
  {
    $view = new View('views/forms/partials/search.html');
    $this->search = ['topic' => $type, 'path' => 'search/group'];
    return $view->render($this());
  }
}
