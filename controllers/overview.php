<?php
namespace controllers;
use \bloc\View;
use \models\Token;


/**
 * Overview covers 'pages' that have a categorical agenda.
 */

  class Overview extends Manage
  {
    public function GETpolicy()
    {
      $view = new View($this->partials->layout);
      $view->content   = 'views/pages/policy.html';
      return $view->render($this());
    }
    
    public function GETtciaf()
    {
      /*
        TODO show staff
      */
      $view = new View($this->partials->layout);
      $view->content   = 'views/pages/about.html';
      return $view->render($this());
    }
    
    public function GETpeople()
    {
      $view = new View($this->partials->layout);
      $view->content = 'views/pages/staff.html';
      
      
      $this->staff = Token::storage()->find("//group[@type='person']/token[pointer[@type='staff' and @token='TCIAF']]");
      
      return $view->render($this());
      
    }
    
  }