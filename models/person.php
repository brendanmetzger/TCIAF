<?php
namespace models;

/**
 * Person
 */

class Person
{
  public $context = null;
  
  static public $fixture = [
    'token' => [
      '@' => [
        'id'      => null,
        'title'    => '',
        'created' => '',
        'age' => '',
      ],
      'abstract' => [
        'CDATA'   => '',
        '@' => [
          'content' => 'bio'
        ]
      ]
    ]
  ];
  
  public function __construct($id = null)
  {
    if ($id !== null) {
      if (! $this->context = Token::storage()->getElementById($id)) {
        throw new \InvalidArgumentException("{$id}... Doesn't ring a bell.", 1);
      }
    }
    self::$fixture['token']['@']['age'] = (new \DateTime())->format('Y-m-d H:i:s');
  }
  
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
      $abstract = $instance->context->appendChild(Token::storage()->createElement('abstract', $data['token']['abstract']['CDATA']));
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