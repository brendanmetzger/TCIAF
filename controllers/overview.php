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
      $page = (($id === 'tciaf-conference') ? 'overview' : 'edition');
      $view = new view('views/layout.html');
      $view->content = "views/conference/{$page}.html";      
      
      return $view->render($this());
    }
    
    public function GETcompetition($id = null)
    {
      
      if ($id === null) {
        $this->banner = 'Competitions';
        $this->driehaus = Graph::factory(Graph::ID('driehaus'));
        $this->shortdocs = Graph::factory(Graph::ID('shortdocs'));
        $page =  'overview';
      } else {
        $this->item = Graph::factory(Graph::ID($id));
        $page = 'edition';
        
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