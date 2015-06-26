<?php
namespace models;

/**
 * Person
 */

class Person extends Model
{  
  public $form = 'vertex';
  
  static public $fixture = [
    'vertex' => [
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
  
  public function setIdAttribute(\DOMElement $context, $value)
  {
    if (empty($value)) {
      $value = 'pending';
    } else if (strtolower($value) === 'pending') {
      $value = 'p:' . preg_replace('/[^a-z0-9]/i', '', static::$fixture['vertex']['@']['title']);
    }
    
    
    if (empty($value)) {
      $this->errors[] = "Name Invalid, either doesn't exist, or is not unique enough.";
      throw new \RuntimeException($message, 1);
    }

    $context->setAttribute('id', $value);
    
  }
}