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
  
  
  static public function create($instance, $data)
  {
    if ($instance->context === null) {
      $instance->context = Token::storage()->createElement('token', null);
      Token::storage()->pick('//group[@type="person"]')->appendChild($instance->context);
    }
    
    $data = array_replace_recursive(self::$fixture, $data);
    
        
    foreach ($data['token']['@'] as $key => $value) {
      $instance->context->setAttribute($key, $value);
    }
    
    if (!empty($data['token']['abstract'])) {
      $abstract = $instance->context->getFirst('abstract') ?: $instance->context->appendChild(Token::storage()->createElement('abstract'));
      $abstract->nodeValue = $data['token']['abstract']['CDATA'];
      $abstract->setAttribute('content', $data['token']['abstract']['@']['content']);
    }
    
    
    return Token::storage()->validate() ? $instance : false;
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
    $file = PATH . Token::DB . '.xml';
    return Token::storage()->save($file);
  }
}