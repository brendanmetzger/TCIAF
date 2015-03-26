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
    $this->storage = new \bloc\DOM\Document('data/users', ['validateOnParse' => true]);
  }
  
  public function authenticate($username, $password)
  {
    if ($user = $this->storage->getElementById($username)) {
      if (password_verify($password, $user->getAttribute('hash'))) {
        return $user;
      }
    }
    return false;
  }
}