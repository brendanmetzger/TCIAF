<?php
namespace models;

/**
 * Person
 */

class Person extends Model
{
  const NAME = 'person';
  
  static public $fixture = [
    'token' => [
      'abstract' => [
        '@' => [
          'content' => 'bio'
        ]
      ]
    ]
  ];
  
  
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
}