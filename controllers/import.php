<?php
namespace controllers;

use \bloc\application;

/**
 * Third Coast International Audio Festival Defaults
 */

class Import extends Task
{
  public function CLIproducers()
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
  
  public function CLIfeatures()
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
  
  
  public function CLImapAudiotoFeatures()
  {
    $doc = new \bloc\DOM\Document('data/db');
    $xml  = new \DomXpath($doc);


    $features = $xml->query("//group[@type='published' or @type='unpublished']/token");
    $sql   = new \mysqli('127.0.0.1', 'root', '', 'TCIAF');
    
    foreach ($features as $feature) {
      $id = substr($feature->getAttribute('id'), 2);
      $audio = $sql->query("SELECT CONCAT(id, '/', mp3_file_name) as file FROM audio_files  WHERE feature_id = '{$id}'")->fetch_assoc();
      $media = $doc->createElement("media");
      $media->setAttribute('src', $audio['file']);
      $media->setAttribute('type', 'audio');
      $feature->appendChild($media);
      if (!$doc->validate()) {
        echo "There was a problem regarding:\n";
        print_r($feature);
        exit();
      }
    }
  }
  
  public function CLIremap()
  {
    $doc = new \bloc\DOM\Document('data/db');
    $xml  = new \DomXpath($doc);
    
    $groups = [];
    foreach ($xml->query("//group") as $group) {
      $groups[$group->getAttribute('type')] = $group;
    }
    
    foreach ($xml->query("//token") as $token) {
      $group = $token->getAttribute('type');
      echo "Moving {$token->getAttribute('title')} to {$group} group.\n"; 
      $token->removeAttribute('type'); 
      $groups[$group]->appendChild($token);
    }
    
    if ($doc->validate()) {
      // $doc->save(PATH.'data/db2.xml');
    }
  }
  
  public function CLImapFeaturestoProducers()
  {
    $doc  = new \bloc\DOM\Document('data/db2');
    $xml  = new \DomXpath($doc);
    $sql   = new \mysqli('127.0.0.1', 'root', '', 'TCIAF');
    
    $features = $xml->query("//group[@type='published' or @type='unpublished']/token");

    foreach ($features as $feature) {
      $id       = substr($feature->getAttribute('id'), 2);
      $join     = $sql->query("SELECT * FROM features_producers  WHERE feature_id = '{$id}'")->fetch_assoc();
      $pid      = ':'.$join['producer_id'];
      $producer = $doc->getElementById($pid);
      
      if ($producer) {
        $pointer = $doc->createElement("pointer");
        $pointer->setAttribute('token', $pid);
        $pointer->setAttribute('type', 'producer');
        $feature->appendChild($pointer);
      } 
    }
    
    if ($doc->validate()) {
      $file = 'data/db3.xml';
      echo "New File: {$file}\n";
      // $doc->save(PATH . $file);
      
      $this->CLIcompress($file);
    }
  }
  
  public function CLImapAllFeaturesToProducers()
  {
    $sql   = new \mysqli('127.0.0.1', 'root', '', 'TCIAF');
    $results = $sql->query("SELECT COUNT(feature_id) as c, feature_id FROM features_producers GROUP BY (feature_id) ORDER BY c DESC")->fetch_all(MYSQLI_ASSOC);
    $doc  = new \bloc\DOM\Document('data/db7');
    $xml  = new \DomXpath($doc);
    
    foreach ($results as $result) {
      if ($result['c'] <= 1) continue;
      $feature = $result['feature_id'];
      $producers = $sql->query("SELECT producer_id as id FROM features_producers WHERE feature_id = '{$feature}'")->fetch_all(MYSQLI_ASSOC);
      foreach ($producers as $producer) {

        $p = $doc->getElementById('p:'.$producer['id']);
        $exp = "pointer[@token='s:{$feature}']";

        $pointer = $xml->query($exp, $p);
                echo $pointer->length . "\n";
        if ($p && $pointer->length === 0) {
          
          $pointer = $doc->createElement("pointer");
          $pointer->setAttribute('token', 's:'.$feature);
          $pointer->setAttribute('type', 'producer');
          $p->appendChild($pointer);
          
           echo $producer['id'] . " has no pointer \n";
        }
      }
    }
    
    if ($doc->validate()) {
      $file = 'data/db8.xml';
      echo "New File: {$file}\n";
      $doc->save(PATH . $file);
      
      $this->CLIcompress($file);
    } else {
      print_r(libxml_get_errors());
    } 
  }
  
  public function CLImapPhotostoFeatures()
  {
    # implement
  }
  
  public function CLImapAwardsToProducer()
  {
    $doc  = new \bloc\DOM\Document('data/db3');
    $xml  = new \DomXpath($doc);
    $sql   = new \mysqli('127.0.0.1', 'root', '', 'TCIAF');
    
    $group = $xml->query("//group[@type='competition']")->item(0);
    $driehaus  = $doc->getElementById('c:1');

    $awards = $sql->query('SELECT competition_awards.feature_id, competition_awards.title as award, competition_editions.title as year_of, competitions.title as competition FROM competition_awards LEFT JOIN competition_editions ON (competition_awards.edition_id = competition_editions.id) LEFT JOIN competitions ON (competition_editions.competition_id = competitions.id)')->fetch_all(MYSQLI_ASSOC);
    $years = [];
    foreach ($awards as $award) {
      if (! array_key_exists($award['year_of'], $years)) {
        $years[$award['year_of']] = [];
      }
      $years[$award['year_of']][] = $award;
    }
    
    foreach ($years as $key => $awards) {
      echo "Create token for $key\n";
      echo "Create pointer to token in {$driehaus->getAttribute('title')}\n\n";

      
      $cid = 'd:'.$key;
      $competition = $doc->createElement('token');
      $competition->setAttribute('id', $cid);
      $competition->setAttribute('title', $key);
      
      $group->appendChild($competition);

      
      $pointer = $doc->createElement("pointer");
      $pointer->setAttribute('rel', $cid);
      $pointer->setAttribute('type', 'issue');
      $driehaus->appendChild($pointer);
      
      foreach ($awards as $award) {
        $subpointer = $doc->createElement('pointer', trim($award['award']));
        $subpointer->setAttribute('rel', 's:'.$award['feature_id']);
        $subpointer->setAttribute('type', 'winner');
        $competition->appendChild($subpointer);
      }
    }
    
    if ($doc->validate()) {
      $file = 'data/db4.xml';
      echo "New File: {$file}\n";
      // $doc->save(PATH . $file);
      
      $this->CLIcompress($file);
    }
    
    
  }
  
  public function CLIdonors()
  {
    $doc  = new \bloc\DOM\Document('data/db4');
    $xml  = new \DomXpath($doc);
    $sql   = new \mysqli('127.0.0.1', 'root', '', 'TCIAF');
    
    $group = $xml->query("//group[@type='organization']")->item(0);
    $driehaus  = $doc->getElementById('c:1');

    foreach ($sql->query('SELECT * from donors')->fetch_all(MYSQLI_ASSOC) as $donor) {
      $token = $doc->createElement('token');
      $token->setAttribute('id', 'o:'.$donor['id']);
      $token->setAttribute('title',  $donor['name']);
      $group->appendChild($token);
    }
    
    if ($doc->validate()) {
      $file = 'data/db5.xml';
      echo "New File: {$file}\n";
      // $doc->save(PATH . $file);
      
      $this->CLIcompress($file);
    }
  }
  
  
  /*
    TODO create a new file called taxonomy. This will be an index of sorts, also where categories and tags are saved.
  */
  public function Categories()
  {
    # implement
  }
  
  public function Tags($value='')
  {
   # implement
  }
  
  public function CLISync()
  {
    for ($i=10; $i < 150; $i++) { 
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