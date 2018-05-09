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

  public function GETindex()
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

  public function GETcluster($type, $subset = null)
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

  public function GETfull()
  {

    $term = \bloc\request::$data['q'];
    $q = urlencode($term);
    $view = new View('views/layout.html');


    $g = [
      'cx'   => getenv('SEARCH_CX'),
      'key'  => getenv('SEARCH_KEY'),
    ];


    $data = json_decode(file_get_contents("https://www.googleapis.com/customsearch/v1?q={$q}&cx={$g['cx']}&key={$g['key']}"));
    $total = $data->queries->request[0]->totalResults;
    if ($total > 0) {
      $this->message = "Results for <q>{$term}</q>";
      $this->results = (new Dictionary($data->items))->map(function($item) {
        return [
          'title' => str_replace(' & ', ' &amp; ', html_entity_decode(strip_tags($item->htmlTitle, '<b><em><i><strong>'))),
          'copy' => str_replace(' & ', ' &amp; ', html_entity_decode(strip_tags($item->htmlSnippet, '<b><em><i><strong>'))),
          'link' => preg_replace('/^http.*thirdcoastfestival.org/', '', $item->link),
        ];
      });
    } else {
      $this->message = "No results for <q>{$term}</q>";
    }

    $view->content= 'views/pages/search.html';
    return $view->render($this());
  }

  public function GETform($type)
  {
    $view = new View('views/forms/partials/search.html');
    $this->search = ['topic' => $type, 'path' => 'search/cluster'];
    return $view->render($this());
  }
  
}
