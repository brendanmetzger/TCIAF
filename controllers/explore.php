<?php
namespace controllers;
use \bloc\view as view;

/**
 * Explore Represents the user's interest.
 */

class explore
{
  public function index()
  {
    $view = new view('visible/layout.html');
		$view->setPage('//body/section', 'visible/home.html');
    print $view->render();
  }
}