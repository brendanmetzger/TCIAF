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
    
    public function GETLibrary($sort = 'alpha-numeric', $index = 1, $per = 100)
    {
      $view = new view('views/layout.html');
      $view->content = "views/lists/feature.html";
      $this->search = ['topic' => 'feature', 'path' => 'search/group', 'area' => 'explore/detail'];
      $this->group = 'feature';
      $this->{$sort} = "selected";
      $this->list = Graph::group('feature')
           ->find('vertex')
           ->sort(Graph::sort($sort))
           ->map(function($vertex) {
             return ['item' => Graph::factory($vertex)];
           })
           ->limit($index, $per, $this->setProperty('paginate', ['prefix' => "overview/library/{$sort}"]));

      return $view->render($this());
    }
    
    public function GETtciaf()
    {
      $view = new view('views/layout.html');
      $view->content   = 'views/pages/about.html';
      
      $this->item = Graph::factory(Graph::ID('TCIAF'));
      
      return $view->render($this());
    }
    
    public function GETconference($id = 'tciaf-conference')
    {
      $this->item = Graph::factory(Graph::ID($id));
      $this->banner = 'Conferences';
      $page = (($id === 'tciaf-conference') ? 'overview' : 'edition');
      $view = new view('views/layout.html');
      $view->content = "views/conference/{$page}.html";      
      
      return $view->render($this());
    }
    
    public function GETBroadcasts()
    {
      $view = new view('views/layout.html');
      return $view->render($this());
    }
    
    public function GETcompetition($id = null, $participants = false)
    {
      
      if ($id === null) {
        $this->banner = 'Competitions';
        $this->competitions = [
          ['item' => Graph::factory(Graph::ID('driehaus'))],
          ['item' => Graph::factory(Graph::ID('shortdocs'))],
        ];
        $page = 'overview';
      } else {
        $this->item = Graph::factory(Graph::ID($id));
        if ($participants) {
          $page = 'listing';
        } else {
          $page = 'edition';
        }
      }
      
      $view = new view('views/layout.html');
      $view->content = "views/competition/{$page}.html";      
      
      return $view->render($this());
    }
    

    
    public function GETnothing()
    {
      $view = new view('views/layout.html');

      $view->content = (new \bloc\DOM\Document('<h1>Not Implemented (yet)</h1>', [], \bloc\DOM\Document::TEXT))->documentElement;
      return $view->render($this());
    }
    
  }