<?php
namespace models;

/**
 * Person
 */

class Person
{
  public $storage;
  
  public function __construct()
  {
    $this->storage = new \bloc\DOM\Document('data/agents', ['validateOnParse' => true]);    
  }
  
  static public function create($instance, $data)
  {
    $agent = $instance->storage->createElement('agent', null);
    foreach ($data['attributes'] as $key => $value) {
      $agent->setAttribute($key, $value);
    }
    
    $instance->storage->documentElement->appendChild($agent);
    
    if (!empty($data['abstract'])) {
      $abstract = $agent->appendChild($instance->storage->createElement('abstract', $data['abstract']['CDATA']));
      $abstract->setAttribute('content', $data['abstract']['content']);
    }
    
    return $instance->storage->validate() ? $instance : false;
  }
  
  public function authenticate($username, $password, $role = 1)
  {
    if ($user = $this->storage->getElementById($username)) {
      if (password_verify($password, $user->getAttribute('hash'))) {
        return (int)$user->getAttribute('level') <= 1 ? $user : false;
      }
    }
    return false;
  }
  
  public function save()
  {
    $file = PATH . 'data/agents.xml';
    return $this->storage->save($file);
  }
}