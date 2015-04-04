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
    $this->storage = new \bloc\DOM\Document('data/agents', ['validateOnParse' => true]);
  }
  
  public function authenticate($username, $password, $role = 1)
  {
    if ($user = $this->storage->getElementById($username)) {
      if (password_verify($password, $user->getAttribute('hash'))) {
        return (int)$user->getAttribute('level') <= 1;
      }
    }
    return false;
  }
}