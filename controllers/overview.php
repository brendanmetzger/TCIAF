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
      
      $this->staff = Graph::group('person')->find("vertex[edge[@type='staff' and @vertex='TCIAF']]");
      
      return $view->render($this());
      
    }
    
  }