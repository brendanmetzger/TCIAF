<?php
namespace controllers;

use \bloc\application;

/**
 * Third Coast International Audio Festival Defaults
 */

class Task extends \bloc\controller
{
  public function __construct($request)
  {
    if ($request->type == 'CLI') {
      $this->sessionfile = '/tmp/'.$_SERVER['USER'].'-tciaf-login';
      $this->authenticated = file_exists($this->sessionfile);
    }
  }

  public function CLIindex()
  {
    // show a list of methods.
    $reflection_class = new \ReflectionClass($this);
    
    $instance_class_name = get_class($this);
    $parent_class_name = $reflection_class->getParentClass()->name;
    $methods = ['instance' => [], 'parent' => []];
    foreach ($reflection_class->getMethods() as $method) {
      if (substr($method->name, 0, 3) == 'CLI') {
        $name = $method->getDeclaringClass()->name;
        if ($instance_class_name == $name) {
          $methods['instance'][] = substr($method->name, 3) . "\n";
        }
        if ($parent_class_name == $name) {
          $methods['parent'][] = substr($method->name, 3) . "\n";
        }
      }
    }
    
    echo "Available Methods in {$instance_class_name}\n";
    
    
    print_r($methods);
    
  }
  
  public function CLIedgeproducer()
  {
    $doc  = new \bloc\DOM\Document('data/db5');
    $xml  = new \DomXpath($doc);
    
    $edges = $xml->query('//group[@type="feature"]/token/edge');
    
    foreach ($edges as $edge) {
      $token = $doc->getElementById($edge->getAttribute('token'));
      $edge->setAttribute('token', $edge->parentNode->getAttribute('id'));
      $token->appendChild($edge);
    }
    
    if ($doc->validate()) {
      $file = 'data/db6.xml';
      echo "New File: {$file}\n";
      $doc->save(PATH . $file);
      
      $this->CLIcompress($file);
    }
    
    
  }
  
  public function CLIvalid()
  {
    libxml_use_internal_errors(true);
    $doc  = new \bloc\DOM\Document('data/db5');
    if ($doc->validate()) {
      
    } else {
      foreach(libxml_get_errors() as $error) {
        print_r($error);
      }
    }
    
  }
  
  public function CLIcompress($file)
  {
    $text = file_get_contents(PATH . $file);
    $compressed = gzencode($text, 3);
        
    file_put_contents(PATH . substr($file, 0, -4), $compressed, LOCK_EX);
  }
  
  public function CLILogout()
  {
    if (unlink("/tmp/curlCookies.txt")) {
      echo "\nGoodbye!\n";
    }
    
  }
  
  public function CLILoginBak($xml)
  {
    $postdata = [];
    
    $xml = new \SimpleXMLElement($xml);
    $xml->registerXPathNamespace('xmlns', "http://www.w3.org/1999/xhtml");

    echo "\n" .(string)$xml->xpath('//xmlns:legend')[0] . "\n";
    $inputs = $xml->xpath('//xmlns:input');

    foreach ($inputs as $input) {
      
      if ((string)$input['id'] == 'name') {
        echo "\nPlease Enter your username: ";
        $input['value'] = trim(fgets(STDIN));
      }
      
      if ((string)$input['id'] == 'password') {
        echo "\nPlease Enter your password: ";
        $input['value'] = trim(fgets(STDIN));
      }
      
      $postdata[(string)$input['name']] = (string)$input['value'];
    }
    
    $url = 'http://local.thirdcoastfestival.org' . $xml->xpath('//xmlns:form')[0]['action'];

      
    $handle = curl_init();
 
    curl_setopt($handle, CURLOPT_URL, $url);
    curl_setopt($handle, CURLOPT_POST, true);
    curl_setopt($handle, CURLOPT_POSTFIELDS, $postdata);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($handle, CURLOPT_AUTOREFERER,    true);
    curl_setopt($handle, CURLOPT_COOKIEFILE, "/tmp/curlCookies.txt");
    curl_setopt($handle, CURLOPT_COOKIEJAR, "/tmp/curlCookies.txt");
    
    $result = curl_exec($handle);
    $info   = curl_getinfo($handle);
    curl_close($handle);
    if ($info['http_code'] == 401) {
      $result = $this->CLILogin($result);
    }
    
    return $result;
  }
  
  public function CLILogin($value='')
  {
    try {
      echo "\nPlease Enter your username: ";
      $username = trim(fgets(STDIN));
      
      echo "\nPlease Enter your password: ";
      $password = trim(fgets(STDIN));

      $user = (new \models\person('p-' . preg_replace('/\W/', '', $username)))->authenticate($password);
      
      touch($this->sessionfile);
      
      echo "-- Session Created, you may now run restricted commands --";
       
    } catch (\InvalidArgumentException $e) {
      echo sprintf($e->getMessage(), $username);
    }
    
  }
  
  protected function CLIpassword($username = false)
  {
    if (!$username) return "Provide a username as the first argument";
    
    
    echo "\nPlease Enter new password for '{$username}': ";
    $password = trim(fgets(STDIN));

    echo "\nPlease Confirm password: ";
    $confirm = trim(fgets(STDIN));
      
    if ($password !== $confirm) {
      return "\n\nPasswords DO NOT MATCH...";
    }

    $user = new \models\person('p-' . preg_replace('/\W/', '', $username));
    $user->context->setAttribute('hash', $user->getHash($password));
    
    if ($user->save()) {
      return "Saved new password";
    } else {
      print_r($user->errors);
    }
  }
  
  public function CLIaws()
  {
    $client = \Aws\S3\S3Client::factory(['profile' => 'TCIAF']);
    $result = $client->listObjects([
        'Bucket' => '3rdcoast-features',
        'MaxKeys' => 2,
        'Marker' => 'mp3s/1000/We_Believe_We_Are_Invincible.mp3',
    ]);
    print($result);
    // foreach ($result['Buckets'] as $bucket) {
    //   print_r($bucket);
    //     // Each Bucket value will contain a Name and CreationDate
    //     echo "{$bucket['Name']} - {$bucket['CreationDate']}\n";
    // }
  }
  
  static public function pearson($id = null)
  {
    
    $spectrum  = \models\Graph::group('feature')->find('vertex/spectra');
    $list      = [];
    
    $count = 7;
    
    foreach ($spectrum as $spectra) {
      $item = new \stdClass;
      $item->sum    = 0;
      $item->sumsq  = 0;
      $item->values = [];
      $item->best   = [];
      $item->id     = $spectra->parentNode['@id'];
      
      $skip = 0;
      foreach ($spectra->attributes as $attr) {
        if ($skip++ < 1) continue; // do not factor recommendation into correlation
        $value = (int)$attr->nodeValue;
        $item->sum += $value;
        $item->sumsq += pow($value, 2);
        $item->values[] = $value;
      }

      $item->pow = $item->sumsq - pow($item->sum, 2) / $count;
      
      if ($item->pow == 0) continue;
      $list[$item->id] = $item;
    }
    
    
    if ($id !== null) {
      
      if (!array_key_exists($id, $list)) {
        throw new \RuntimeException("No recommendations will be available", 25);
        
      }
      $A = $list[$id];
      
      foreach ($list as $bid => $B) {
        if ($id == $bid) continue;
      
        $sum_p = array_sum(array_map(function($a, $b) {
          return $a * $b;
        }, $A->values, $B->values));

        $r = ($sum_p - (($A->sum * $B->sum) / $count ) ) / sqrt( $A->pow  * $B->pow );
        if ($r == 1 || $r == -1) continue;
        if ($r > 0.5 || $r < -0.5) {
          $A->best[$bid] = $r;
        }
      }
      return $A;
    } else {
      $finished = [];

      foreach ($list as $aid => $A) {

        foreach ($list as $bid => $B) {
          if ($aid == $bid) continue;
          
          
          $sum_p = array_sum(array_map(function($a, $b) {
            return $a * $b;
          }, $A->values, $B->values));

          $r = ($sum_p - (($A->sum * $B->sum) / $count ) ) / sqrt( $A->pow  * $B->pow );
          

          if ($r == 1 || $r == -1) continue;
          if ($r > 0.5 || $r < -0.5) {
            $list[$aid]->best[$bid] = $r;
            $list[$bid]->best[$aid] = $r;
          }
        }
        $finished[] = array_shift($list);
      }
    
      return $finished;
    }
    

  }
  
  public function CLItranscode($size = 5)
  {
    
    $pipeline = [
      'new' => '1439307152758-prv5fa',
      'old' => '1439307760286-8f5hu5',
    ];

    $mp4_preset_id = '1439308682558-sehqe8';
    
    $client = \Aws\ElasticTranscoder\ElasticTranscoderClient::factory(['profile' => 'TCIAF', 'region' => 'us-east-1']);
    
    $tracks = \models\Graph::group('broadcast')->find('vertex/media[@type="audio"]');
    
    foreach ($tracks as $track) {
      $path = $track->getAttribute('src');
      $parts = explode('/', $path);
      if ($parts[0] == '3rdcoast-features') {
        $path = substr($path, strlen('3rdcoast-features/'));
        $new_path = $parts[2] . '_' . preg_replace('/\.?mp3/i', '', array_pop($parts)) . '.m4a';
        // set new attribute
        $track->setAttribute('src', 'tciaf-audio/'. $new_path);
        echo $path . ' - into - ' . $new_path . "\n";
        
        $result = $client->createJob([
          'PipelineId' => $pipeline['old'],
          'Input' => [
            'Key' => $path,
          ],
          'Output' => [
            'Key'      => $new_path,
            'PresetId' => $mp4_preset_id,
          ]
        ]);
        
        echo "new job created: " . $result['Job']['Id'] . "\n\n";  
        
        if ($size-- < 0) {
          break;
        }
      }
    }
    
    \models\Graph::instance()->storage->save(PATH . \models\Graph::DB . '.xml');
  }

  private function CLIcorrelate($id = null)
  {
    return self::pearson($id);
  }
  
  public function CLImarkMedia($per = 25)
  {
    $unmarked = \models\Graph::group('feature')->find('vertex/media[@type="image" and @mark=0]');
    if ($unmarked->count() < 1) {
      echo "None left\n";
    }
    
    foreach ($unmarked as $image) {
      if ($per-- < 0) {
        echo "Quitting - run again if you must....\n";
        break;
      }
      $src = preg_replace('/^(feature-photos\/photos\/[0-9]+\/)(.*)$/i', '$1small/$2', $image['@src']);
      $url = "http://s3.amazonaws.com/{$src}";
      
      $size = getimagesize($url);
      $ratio = round($size[0] / $size[1], 1);
      $image->setAttribute('mark', $ratio);
      
      echo $image->write() . "\n";
    }
    \models\Graph::instance()->storage->save(PATH . \models\Graph::DB . '.xml');
  }

}