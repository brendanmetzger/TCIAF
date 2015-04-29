<?php
namespace controllers;
use \bloc\View;


/**
 * Overview covers 'pages' that have a pretty broad and specific agenda.
 */

  class Overview extends Manage
  {
    protected function GETpolicy($index = 1, $per = 25)
    {
      $view = new View($this->partials->layout);
      $view->content   = 'views/pages/policy.html';
      return $view->render($this());
    }
  }