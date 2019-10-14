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

function calendar($start, $category, $query)
{
  $x = new \DOMXpath(Graph::instance()->storage);
  return iterator_to_array((new \bloc\types\Dictionary(range($start, 2000)))->map(function($current) use($category, $x, $query) {
    $q = sprintf("/graph/group[@type='feature']/vertex[{$query}]", $current);
    $g = $x->query($q)->length;
    return ['year' => $current, 'category' => $category, 'count' => $g];
  }));
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

    public function GETLibrary($filter = "all", $sort = 'newest', $group = 'any', $index = 1, $per = 25)
    {
      $view = new view('views/layout.html');
      $view->content = "views/lists/feature.html";
      $this->search = ['topic' => 'feature', 'path' => 'search/cluster', 'area' => 'explore/detail'];

      $this->filter = $filter;
      $this->sort   = $sort;
      $this->group  = $group;
      $this->title  = 'Library';
      $this->{$sort}   = "selected";
      $this->{$filter} = "selected";
      $this->{$group}  = "selected";

      $queries = [
        'shows' => 'edge[@vertex="A"]',
        'conference-audio' => 'edge[@type="session"]',
        'shortdocs' => 'edge[@type="participant"]',
        'awards' => 'edge[@type="award"]',
      ];

      $query = $filter == 'all' || $filter == 'stories' ? 'premier[@date!=""]' : $query = $queries[$filter];

      try {
          $this->blurb = Graph::FACTORY(Graph::group('article')->pick("vertex[@sticky='{$filter}']"));
      } catch (\Exception $e) {

      }


      if ($filter == 'shows') {
        $this->title  = "Shows";
      } else if ($filter == 'conference-audio') {
        $this->title  = "Conference Audio";
      } else if ($filter == 'shortdocs') {
        $this->title  = "ShortDocs";
      } else if ($filter == 'awards') {
        $this->title  = "TCF Award Recipients";
      } else if ($filter == "stories") {
        // combine all queries and negate, whatevers left is a story.
        $query = implode(' and ', array_map(function($item) {
          return "not($item)";
        }, array_values($queries)));
        $this->title = 'Stories';
      }

      if ($sort == 'alpha-numeric') {
        // show the picker
        $alpha = strtolower(substr($group, 6, 1));
        $query .= " and starts-with(@key, '{$alpha}')";
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
        $now = (int)date('Y', time());
        
        $query .= " and premier[starts-with(@date, '%s')]";
        $years = calendar($now, $filter, $query);

        if ($group == 'any') {

          foreach ($years as $key => $current) {
            if ($current['count'] > 0 ) {
              $years[$key]['selected'] = 'selected';
              $year = $current['year'];
             break; 
            }
          }
          
        } else {
          
          foreach ($years as $key => $current) {
            if ($current['year'] == $group) {
              $years[$key]['selected'] = 'selected';
              break;
            }
          }
          $year = $group;
        }
        
        $this->years = $years;
        

        $query = sprintf($query, $year);

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
      $this->search  = ['topic' => 'people', 'path' => 'search/cluster', 'area' => 'explore/detail'];
      $this->title = ucfirst($category).', Third Coast International Audio Festival';
      $alpha = null;

      $query = "edge[@type]";

      if ($category != 'all') {
        $trimmed = substr($category, 0, -1);
        $query = "edge[@type='{$trimmed}']";
      }

      if ($filter != 'any') {
        $alpha = strtolower(substr($filter, 6, 1));
        $query .= "and starts-with(@key, '{$alpha}')";
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

      $this->item  = new \models\Organization('A');
      
      if (true) {
        \bloc\application::instance()->log($this->item->content->extras);
        // $view->presenters =  "views/partials/presenters.html";
      }
      
      return $view->render($this());
    }

    public function GETOpportunities()
    {
      $view = new view('views/layout.html');
      $view->content   = 'views/pages/overview.html';
      $this->item      = Graph::FACTORY(Graph::ID('BlN'));
      return $view->render($this());
    }

    public function GETconference($id = null, $schedule = false)
    {
      $this->banner = 'Conferences';
      $node = $id ? Graph::group('happening')->find("vertex[@key='{$id}']")->pick(0) : GRAPH::ID('CJ');
      $this->item   = Graph::FACTORY($node);
      $sections = [];

      $template = $id === null ? 'overview' : ( $schedule ? 'schedule' : $this->item->_template);
      
      $view = new View('views/layout.html');
      $view->content = "views/conference/{$template}.html";

      // Sessions, Presenters and Sponsers only show up when available
      
      if ($this->item->sessions->count() > 0) {
        // $view->schedule = 'views/conference/schedule.html';
      }
      
      if ($this->item->presenters->count() > 0) {
        $this->pkey = 'presenters';
        $sections[] = ['id' => 'presenters', 'title' => 'Featured People'];
        $view->presenters =  "views/partials/presenters.html";
      }

      if ($this->item->sponsors) {
        $view->sponsors =  "views/partials/sponsors.html";
      }
      $this->sections = $sections;
      return $view->render($this());
    }
    
    public function GETresidency($id = null) {
      $node = $id ? Graph::group('happening')->find("vertex[@key='{$id}']")->pick(0) : GRAPH::ID('BpW');
      
      $this->item   = Graph::FACTORY($node);

      $template = $id === null ? 'overview' : $this->item->_template;
      
      $view = new View('views/layout.html');
      $view->content = "views/residency/{$template}.html";
      
      if ($this->item->sponsors) {
        $view->sponsors =  "views/partials/sponsors.html";
      }
      
      return $view->render($this());
    }

    public function GETcompetition($id = null, $participants = false)
    {
      $view = new view('views/layout.html');
      $group = Graph::group('competition');
      
      if ($id === null) {
        $this->banner = 'Competitions';
        

        $this->competitions = [
          ['item' => new \models\competition($group->pick('vertex[@sticky="driehaus"]'))],
          ['item' => new \models\competition($group->pick('vertex[@sticky="shortdocs"]'))],
        ];

        $view->content = "views/competition/overview.html";
      } else {
        
        $this->item = Graph::Factory($group->find("vertex[@key='{$id}']")->pick(0));

        if ($participants) {
          $page = 'competition/listing';
        } else {
          $page = $this->item->template['digest'];
        }

        $view->content = "views/{$page}.html";

        if ($this->item->judges) {
          $view->judges = 'views/partials/judges.html';
        }

        if ($this->item->sponsors) {
          $view->sponsors =  "views/partials/sponsors.html";
        }
      }

      return $view->render($this());
    }

    public function GETplaylists($sort = 'newest', $index = 1, $per = 25)
    {
      $view = new view('views/layout.html');
      $view->content = "views/lists/collection.html";
      $this->search = ['topic' => 'playlist', 'path' => 'search/cluster', 'area' => 'overview/playlists'];
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
    
    
    public function GETplayer()
    {
      $view = new view('views/layout.html');
      $view->content = 'views/pages/test.html';
      return $view->render($this());
    }
    
  }
