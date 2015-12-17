<?php
namespace controllers;

use \bloc\view;

use \models\graph;


/**
 * Overview covers 'pages' that have a categorical agenda.
 */

  class Overview extends Manage
  {
    public function GETpolicy()
    {
      $view = new view('views/layout.html');
      $view->content   = 'views/pages/policy.html';
      return $view->render($this());
    }

    public function GETLibrary($filter = "all", $sort = 'newest', $index = 1, $per = 25)
    {
      $view = new view('views/layout.html');
      $view->content = "views/lists/feature.html";
      $this->search = ['topic' => 'feature', 'path' => 'search/group', 'area' => 'explore/detail'];

      $this->filter = $filter;
      $this->sort   = $sort;

      $this->{$sort}   = "selected";
      $this->{$filter} = "selected";
      if ($filter == 'shows') {
        $view->blurb = "views/pages/{$filter}.html";
        $query = 'vertex[edge[@vertex="TCIAF"]]';
        $this->title  = "Shows";
      } else if ($filter == 'conference-audio') {
        $query = 'vertex[edge[@type="presenter"]]';
        $this->title  = "Conference Audio";
      } else if ($filter == 'competitions') {
        $query = 'vertex[edge[@type="participant"]]';
        $this->title  = "Competition Entries";
      } else if ($filter == 'awards') {
        $query = 'vertex[edge[@type="award"]]';
        $this->title  = "TCF Award Recipients";
      } else if (substr($filter,0,6) == 'length') {
        $lim = explode('-', substr($filter, 7));
        $lower = $lim[0] * 60;
        $upper = $lim[1] * 60;
        $query = "vertex[media[@type='audio' and @mark > '{$lower}' and @mark < '{$upper}']]";
      } else {
        $query = 'vertex';
        $this->title  = 'Library';
      }

      $this->list = Graph::group('feature')
           ->find($query)
           ->sort(Graph::sort($sort))
           ->map(function($vertex) {
             return ['item' => Graph::FACTORY($vertex)];
           })
           ->limit($index, $per, $this->setProperty('paginate', ['prefix' => "overview/library/{$filter}/{$sort}"]));

      return $view->render($this());
    }

    public function GETpeople($category = 'producer', $filter = 'all', $index = 1, $per = 100, $query = '')
    {
      $view = new view('views/layout.html');
      $view->content = "views/lists/person.html";
      $this->search  = ['topic' => 'people', 'path' => 'search/group', 'area' => 'explore/detail'];
      $alpha = null;

      $query = "edge[@type]";

      if ($category != 'all') {
        $query = "edge[@type='{$category}']";
      }

      if ($filter != 'all') {
        $alpha = substr($filter, 6, 1);
        $query .= "and starts-with(@title, '{$alpha}')";
      }
      $this->alphabet = (new \bloc\types\Dictionary(range('A', 'Z')))->map(function($letter) use($alpha, $category) {
        $map = ['letter' => $letter, 'category' => $category];
        if ($alpha == $letter) {
          $map['selected'] = 'selected';
        }
        return $map;
      });

      $this->{$category} = "selected";
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

      $this->item  = Graph::FACTORY(Graph::ID('TCIAF'));

      return $view->render($this());
    }

    public function GETOpportunities()
    {
      $view = new view('views/layout.html');
      $view->content   = 'views/pages/overview.html';
      $this->item      = Graph::FACTORY(Graph::ID('opportunities'));

      return $view->render($this());
    }

    public function GETconference($id = 'tciaf-conference')
    {
      $this->item = Graph::FACTORY(Graph::ID($id));
      $this->banner = 'Conferences';
      $page = (($id === 'tciaf-conference') ? 'overview' : 'edition');
      $view = new view('views/layout.html');

      $view->content = "views/conference/{$page}.html";

      // if there is an upcoming competition, we want to embed it.
      if (true) {
        // $view->upcoming = 'views/conference/listing.html';
      }


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
        $page = 'overview';
      } else {
        $this->item = Graph::FACTORY(Graph::ID($id));
        if ($participants) {
          $page = 'listing';
        } else {
          $page = 'edition';
        }
      }


      $view->content = "views/competition/{$page}.html";

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
