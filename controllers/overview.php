<?php
namespace controllers;

use \bloc\view;
use \models\graph;

function alphabet($alpha, $category)
{
  return (new \bloc\types\Dictionary(range('a', 'z')))->map(function($letter) use($alpha, $category) {
    $map = ['letter' => $letter, 'category' => $category];
    if ($alpha == $letter) {
      $map['selected'] = 'selected';
    }
    return $map;
  });

}

/**
 * Overview covers 'pages' that have a categorical agenda.
 */

  class Overview extends Manage
  {
    public function GETpolicy()
    {
      $view = new view('views/layout.html');
      $view->content = 'views/pages/policy.html';
      return $view->render($this());
    }

    public function GETLibrary($filter = "all", $sort = 'newest', $group = null, $index = 1, $per = 25)
    {
      $view = new view('views/layout.html');
      $view->content = "views/lists/feature.html";
      $this->search = ['topic' => 'feature', 'path' => 'search/group', 'area' => 'explore/detail'];

      $this->filter = $filter;
      $this->sort   = $sort;
      $this->group  = $group;
      $this->title  = 'Library';
      $this->{$sort}   = "selected";
      $this->{$filter} = "selected";

      $query = "edge";

      if ($filter == 'shows') {
        $view->blurb = "views/pages/{$filter}.html";
        $query = 'edge[@vertex="TCIAF"]';
        $this->title  = "Shows";
      } else if ($filter == 'conference-audio') {
        $query = 'edge[@type="presenter"]';
        $this->title  = "Conference Audio";
      } else if ($filter == 'shortdocs') {
        $query = 'edge[@type="participant"]';
        $this->title  = "ShortDocs";
      } else if ($filter == 'awards') {
        $query = 'edge[@type="award"]';
        $this->title  = "TCF Award Recipients";
      }

      if ($sort == 'alpha-numeric') {
        // show the picker
        $alpha = strtolower(substr($group, 6, 1));
        $query .= " and starts-with(@id, '{$alpha}')";
        $this->alphabet = alphabet($alpha, $filter);
        $view->picker = "views/partials/alpha-numeric.html";
      }

      if ($sort == 'duration') {

        $lim = explode('-', substr($group ?: 'length:0-100', 7));

        $l = $lim[0] * 60;
        $u = $lim[1] * 60;
        $len = 'length'.$lim[0].$lim[1];

        $this->{$len} = "selected";
        $query .= " and media[@type='audio' and @mark>'{$l}' and @mark<'{$u}']";
        $view->picker = "views/partials/duration.html";
      }

      if ($sort == 'date') {
        // show the picker
        $year = $group ?: 2016;
        $query .= " and premier[starts-with(@date, '{$year}')]";
        // $this->alphabet = alphabet($alpha, $filter);
        $view->picker = "views/partials/date.html";
      }



      $this->features = Graph::group('feature')
           ->find("vertex[{$query}]")
           ->sort(Graph::sort($sort))
           ->map(function($vertex) {
             return ['item' => Graph::FACTORY($vertex)];
           })
           ->limit($index, $per, $this->setProperty('paginate', ['prefix' => "overview/library/{$filter}/{$sort}/{$group}"]));

      return $view->render($this());
    }

    public function GETpeople($category = 'producers', $filter = 'any', $index = 1, $per = 100, $query = '')
    {
      $view = new view('views/layout.html');
      $view->content = "views/lists/person.html";
      $this->search  = ['topic' => 'people', 'path' => 'search/group', 'area' => 'explore/detail'];
      $this->title = ucfirst($category).'s, Third Coast International Audio Festival';
      $alpha = null;

      $query = "edge[@type]";

      if ($category != 'all') {
        $trimmed = substr($category, 0, -1);
        $query = "edge[@type='{$trimmed}']";
      }

      if ($filter != 'any') {
        $alpha = substr($filter, 6, 1);
        $query .= "and starts-with(@title, '{$alpha}')";
      }

      $this->alphabet = alphabet($alpha, $category);

      $this->{$category} = "selected";
      $this->{$filter} = 'selected';
      $this->filter = $filter;
      $this->category = $category;

      $this->list = Graph::group('person')
           ->find("vertex[{$query}]")
           ->sort(Graph::sort('alpha-numeric'))
           ->map(function($vertex) {
             return ['item' => new \models\person($vertex)];
           })
           ->limit($index, $per, $this->setProperty('paginate', ['prefix' => "overview/people/{$category}/{$filter}"]));

      return $view->render($this());

    }

    public function GETtciaf()
    {
      $view = new view('views/layout.html');
      $view->content = 'views/pages/about.html';
      $this->item  = new \models\Organization('TCIAF');
      return $view->render($this());
    }

    public function GETOpportunities()
    {
      $view = new view('views/layout.html');
      $view->content   = 'views/pages/overview.html';
      $this->item      = Graph::FACTORY(Graph::ID('opportunities'));
      return $view->render($this());
    }

    public function GETconference($id = null)
    {
      $this->banner = 'Conferences';
      $this->item   = Graph::FACTORY(Graph::ID($id ?: 'tciaf-conference'));

      $template = $id === null ? 'overview' : $this->item->_template;

      $view = new View('views/layout.html');
      $view->content = "views/conference/{$template}.html";

      return $view->render($this());
    }

    public function GETcompetition($id = null, $participants = false)
    {
      $view = new view('views/layout.html');
      if ($id === null) {
        $this->banner = 'Competitions';
        $this->competitions = [
          ['item' => Graph::FACTORY(Graph::ID('driehaus'))],
          ['item' => Graph::FACTORY(Graph::ID('shortdocs'))],
        ];
        $page = 'competition/overview';
      } else {
        $this->item = Graph::FACTORY(Graph::ID($id));
        if ($participants) {
          $page = 'competition/listing';
        } else {
          $page = $this->item->template['digest'];
        }
      }
      $view->content = "views/{$page}.html";
      return $view->render($this());
    }

    public function GETplaylists($sort = 'newest', $index = 1, $per = 25)
    {
      $view = new view('views/layout.html');
      $view->content = "views/lists/collection.html";
      $this->search = ['topic' => 'playlist', 'path' => 'search/group', 'area' => 'overview/playlists'];
      $this->group = 'collection';
      $this->{$sort} = "selected";
      $this->list = Graph::group('collection')
           ->find('vertex')
           ->sort(Graph::sort($sort))
           ->map(function($vertex) {
             return ['item' => Graph::FACTORY($vertex)];
           })
           ->limit($index, $per, $this->setProperty('paginate', ['prefix' => "overview/playlists/{$sort}"]));
      return $view->render($this());
    }


    public function GETevents()
    {
      $view = new view('views/layout.html');
      $view->content = 'views/lists/event.html';

      // Gather upcoming events
      $this->upcoming = \models\happening::EVENTS();

      // Gather Past Events
      $this->past = \models\happening::EVENTS('past');

      return $view->render($this());
    }

    public function GETsubscribe()
    {
      $view = new view('views/layout.html');
      $view->content = 'views/pages/subscribe.html';
      return $view->render($this());
    }

  }
