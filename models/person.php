<?php
namespace models;

/**
 * Person
 */

class Person
{
  private $storage;
  
  public function __construct()
  {
    $this->storage = new \bloc\DOM\Document('data/people', ['validateOnParse' => true]);
  }
  
  public function authenticate($username, $password, $role = 1)
  {
    if ($user = $this->storage->getElementById($username)) {
      if (password_verify($password, $user->getAttribute('hash'))) {
        return (int)$user->getAttribute('role') <= 1;
      }
    }
    return false;
  }
}