<?php
namespace models;

/**
 * Person
 */

class Person
{
  public $context;
  
  public function __construct($id = null)
  {
    if ($id !== null) {
      if (! $this->context = Token::storage()->getElementById($id)) {
        throw new \InvalidArgumentException("{$id}... Doesn't ring a bell.", 1);
      }
    }
  }
  
  static public function create($instance, $data)
  {
    $token = Token::storage()->createElement('token', null);
    
    foreach ($data['attributes'] as $key => $value) {
      $token->setAttribute($key, $value);
    }
    
    Token::storage()->appendChild($token);
    
    if (!empty($data['abstract'])) {
      $abstract = $token->appendChild($instance->storage->createElement('abstract', $data['abstract']['CDATA']));
      $abstract->setAttribute('content', $data['abstract']['content']);
    }
    
    return $instance->storage->validate() ? $instance : false;
  }
  
  public function authenticate($password)
  {
    if (! password_verify($password, $this->context->getAttribute('hash'))) {
      throw new \InvalidArgumentException("Might I ask you to try once more?", 1);
    }
    return $this->context;
  }
  
  public function authorize()
  {
    /*
      TODO need a system to check for role of staff or contributor.
    */
  }
  
  public function save()
  {
    // $file = PATH . 'data/agents.xml';
    return Token::storage()->save($file);
  }
}