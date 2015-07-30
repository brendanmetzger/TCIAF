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
      return $view->render($this());
    }
    
    public function GETpeople()
    {
      $view = new view('views/layout.html');
      $view->content = 'views/pages/staff.html';
      
      $this->staff = Graph::group('organization')->find("vertex[@id='TCIAF']/edge[@type='staff']")->map(function($staff) {
        $vertex = $staff['@vertex'];
        return ['person' => new \models\Person(Graph::ID($vertex))];
      });
      
      return $view->render($this());
      
    }
    
    public function GETnothing()
    {
      $view = new view('views/layout.html');

      $view->content = (new \bloc\DOM\Document('<h1>Not Implemented (yet)</h1>', [], \bloc\DOM\Document::TEXT))->documentElement;
      return $view->render($this());
    }
    
  }