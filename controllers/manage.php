<?php
namespace controllers;
use \bloc\view as View;

/**
 * Third Coast International Audio Festival Defaults
 */

class Manage extends \bloc\controller
{

  protected $partials = [
    'layout' => 'views/layout.html',
  ];
  
  public function __construct($request)
  {
    View::addRenderer('before', view\renderer::addPartials($this));
    View::addRenderer('after', view\renderer::HTML());
    
    $this->authenticated = (isset($_SESSION) && array_key_exists('user', $_SESSION));

		$this->year = date('Y');
    $this->title = "Third Coast";
    $this->supporters = [
      ['name' => 'The MacArthur Foundation'],
      ['name' => 'The Richard H. Driehaus Foundation'],
      ['name' => 'The Boeing Company'],
      ['name' => 'Individual Donors']
    ];
    
    if ($this->authenticated) {
      $this->user = $_SESSION['user'];
      $this->partials['helper'] = 'views/admin.html';
    }
  }
  
  public function GETindex()
  {
    return (new View($this->partials['layout']))->render($this());
  }
  
  public function GETlogin($redirect, $username = null, $message = null)
  {
    $view = new View($this->partials['layout']);
    $view->content = 'views/forms/credentials.html';

    $token = date('zG') + 1 + strlen(getenv('HTTP_USER_AGENT'));
    $key = ip2long(getenv('REMOTE_ADDR')) + ip2long(getenv('SERVER_ADDR'));
    $this->input = new \bloc\types\Dictionary([
      'token'    => base_convert($key, 10, date('G')+11),
      'message'  => $message ?: 'Login',
      'username' => $username,
      'password' => null, 
      'redirect' => $redirect,
      'tokens'   => [
        'username' => \bloc\types\string::rotate('username', $token),
        'password' => \bloc\types\string::rotate('password', $token),
        'redirect' => \bloc\types\string::rotate('redirect', $token),
      ]
      
    ]);
    
    
    return $view->render($this());
  }
  
  public function POSTLogin($request, $key)
  {
    $token = date('zG') + 1 + strlen(getenv('HTTP_USER_AGENT'));
    $key = ($key === base_convert((ip2long($_SERVER['REMOTE_ADDR']) + ip2long($_SERVER['SERVER_ADDR'])), 10, date('G')+11));
        
    $username = $request->post(\bloc\types\string::rotate('username', $token));
    $password = $request->post(\bloc\types\string::rotate('password', $token));
    $redirect = $request->post(\bloc\types\string::rotate('redirect', $token));

    if ($key) {
      if ($user = (new \models\person)->authenticate($username, $password)) {
        echo '<pre>'.print_r(\bloc\application::log($user), true).'</pre>';
        \bloc\Application::session('TCIAF', ['user' =>  $user->getAttribute('id')]);
        \bloc\router::redirect($redirect);
      } 
    } else {
      $message = "This form has expired - it happens sometimes.. try again!";
    }
    
    return $this->GETLogin($redirect, $username, $message ?: "Hmm, better try again.");
  }
  
  
  
  
  public function CLIcompress($file)
  {
    $text = file_get_contents(PATH . $file);
    $compressed = gzencode($text, 3);
        
    file_put_contents(PATH . substr($file, 0, -4), $compressed, LOCK_EX);
  }
  
  public function CLIimportproducers()
  {
    $xml = simplexml_load_file(PATH.'data/producers.xml');
    $admin = [186, 260, 1222];
    foreach ($xml->producers->row as $person) {
      $data = [
        'attributes' => [
          'id' => ':' . (int)$person->id,
          'level' => in_array((int)$person->id, $admin) ? 1 : 4,
          'name' => (string)$person->name,
          'created' => (string)$person->created_at,
          'updated' => (string)$person->updated_at,
        ],
        'abstract' => [
          'content' => 'bio',
          'CDATA' => htmlentities(trim(str_replace(['&nbsp;', "\n\n"], [' ', '¶'], html_entity_decode(strip_tags((string)$person->bio), ENT_XML1, 'UTF-8'))), ENT_COMPAT|ENT_XML1, 'UTF-8', false)
        ]
      ];
      if ($data['attributes']['level'] < 4) {
        $id = explode(' ', $data['attributes']['name'])[0];
        // $data['attributes']['id'] = $id;
        $data['attributes']['hash'] = password_hash($id, PASSWORD_DEFAULT);
      }
      
      if ($modeled = \models\person::create(new \models\person, $data)) {
        print_r($modeled->save());
      } else {
        echo "something happended!";
        exit();
      }      
    }
  }
  
  public function CLIimportfeatures()
  {
    /*
    
	<features>
	<row>
		<id>1</id>
		<title>Road Scholar </title>
		<description>&lt;p&gt;
	You might recognize Andrei Codrescu&amp;#39;s voice from his insightful commentaries on NPR, but Codrescu has also brought his unique perspective on American culture to the silver screen, via the movie &lt;i&gt;Road Scholar&lt;/i&gt;.&lt;/p&gt;
&lt;p&gt;
	Released in 1992, the film tells the story of his cross-country road trip in a 1968 cherry-red Cadillac. In this audio excerpt from the film, Codrescu shares his impressions of America through a lyrical blend of scrutiny, criticism, and respect.&lt;/p&gt;</description>
		<created_at>2009-10-09 20:37:51</created_at>
		<updated_at>2011-04-28 20:47:35</updated_at>
		<origin_country>USA</origin_country>
		<premier_locaction>PBS</premier_locaction>
		<premier_date>2001</premier_date>
		<published>1</published>
		<permalink>NULL</permalink>
		<delta>0</delta>
	</row>
    
<records>
  <config/>
  <record id="f" date="today" title="s" created="1" updated="2">
    <abstract>This is something</abstract>
    <premier date="0" location="no"/>
    <location geo="0,0">USA</location>
    <media id="something" type="image">A Photo</media>
  </record>
</records>
    
    */
    
    $word_chars = array(
      "\xe2\x80\x98" => "'", // left single quote
      "\xe2\x80\x99" => "'", // right single quote
      "\xe2\x80\x9c" => '"', // left double quote
      "\xe2\x80\x9d" => '"', // right double quote
      "\xe2\x80\x94" => '-', // em dash
      "\xe2\x80\xa6" => '..'  // elipses
    );
    
    
    
    $xml = simplexml_load_file(PATH.'data/features.xml');
    foreach ($xml->features->row as $feature) {
      $created =  strtotime((string)$feature->created_at);
      $updated =  strtotime((string)$feature->updated_at);
      $data = [
        'attributes' => [
          'id' => ':' . (int)$feature->id,
          'title' => trim((string)$feature->title),
          'created' => (string)$feature->created_at,
          'age' => round((($updated - $created) / 60 / 60 / 24 / 365), 2),
          'published' => (int)$feature->published,
        ],
        'abstract' => [
          'CDATA' =>  str_replace(array_keys($word_chars), array_values($word_chars), trim(htmlentities(preg_replace(['/&nbsp;/', "/\n+\s*/"], [' ', '¶'], html_entity_decode(strip_tags(trim((string)$feature->description)), ENT_XML1, 'UTF-8')), ENT_COMPAT|ENT_XML1, 'UTF-8', false), '¶')) 
        ],
        'premier' => [
          'date' => (string)$feature->premier_date,
          'CDATA' => htmlentities((string)$feature->premier_locaction, ENT_COMPAT|ENT_XML1, 'UTF-8', false),
        ],
        'location' => [
          'CDATA' => (string)$feature->origin_country,
        ]        
      ];
      
      if ($modeled = \models\feature::create(new \models\feature, $data)) {
        print_r($modeled->save());
      } else {
        echo "something happended!";
        exit();
      }
    }
  }
}