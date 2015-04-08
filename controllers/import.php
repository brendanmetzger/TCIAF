<?php
namespace controllers;
use \bloc\Application;

/**
 * Third Coast International Audio Festival Defaults
 */

class Import extends Task
{
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
  
  public function CLISync()
  {
    for ($i=10; $i < 50; $i++) { 
      $handle = curl_init();
    
      $url = 'http://local.thirdcoastfestival.org/explore/fix/:' . $i;

      curl_setopt($handle, CURLOPT_URL, $url);
      curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($handle, CURLOPT_COOKIEFILE, "/tmp/curlCookies.txt");
      curl_setopt($handle, CURLOPT_COOKIEJAR, "/tmp/curlCookies.txt");
    
      $result = curl_exec($handle);
      $info   = curl_getinfo($handle);
    
      curl_close($handle);
    
      if ($info['http_code'] == 401) {
        $result = $this->CLILogin($result);
      }
        
      if ($xml = simplexml_load_string(html_entity_decode($result, ENT_QUOTES, "utf-8"))) {
        $xml->registerXPathNamespace('xmlns', "http://www.w3.org/1999/xhtml");
        foreach ($xml->xpath('//xmlns:form')[0]->input as $input) {
          echo $input['name'] . "\n";
        }
        
      } else {
        echo $result;
      }
      echo "\n\n\n";
    }
  }
}