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
    $doc = new \bloc\DOM\Document('data/db12');
    $xml  = new \DomXpath($doc);


    $people_group = $xml->query("//group[@type='person']")->item(0);
    

    $sql   = new \mysqli('127.0.0.1', 'root', '', 'TCIAF');
    
    $new_people = $sql->query("SELECT * FROM producers WHERE created_at > '2014-12-02 17:16:09'")->fetch_all(MYSQLI_ASSOC);
    
    
    foreach ($new_people as $person) {

      $id = 'p:'.$person['id'];
      if (! $doc->getElementById($id)) {
        $element = $people_group->appendChild($doc->createElement('vertex'));
        $element->setAttribute('id', $id);
        $element->setAttribute('title', $person['name']);
        $element->setAttribute('created', $person['created_at']);
        $element->setAttribute('updated', $person['updated_at']);
        $abstract = $element->appendChild($doc->createElement('abstract', html_entity_decode(strip_tags(trim($person['bio'])))));
        $abstract->setAttribute('content', 'bio');
        
      }
    }
    
    if ($doc->validate()) {
      $file = 'data/db12.xml';
      echo "New File: {$file}\n";
      $doc->save(PATH . $file);
      
      $this->CLIcompress($file);
    } else {
      print_r(libxml_get_errors());
    } 
    
  }
  
  public function CLImapFeaturestoProducers()
  {
    $doc  = new \bloc\DOM\Document('data/db12');
    $xml  = new \DomXpath($doc);
    $sql   = new \mysqli('127.0.0.1', 'root', '', 'TCIAF');
    
    
    $pairs = $sql->query("SELECT * FROM features_producers")->fetch_all(MYSQLI_ASSOC);


    foreach ($pairs as $pair) {
     
      $fid      = 'f:'. $pair['feature_id'];
      $pid      = 'p:'. $pair['producer_id'];

      if ($pid == 'p:1291') {
        $pid = 'p:MayaGoldbergSafir';
      }
      $producer = $doc->getElementById($pid);

      if ($producer) {
        $edges = $xml->query($producer->getNodePath()."/edge[@vertex = '$fid']");
        if ($edges->length > 0) {
        
          continue;
        }
        
        echo "create edge $pid -> $fid\n";

        $edge = $doc->createElement("edge");
        $edge->setAttribute('vertex', $fid);
        $edge->setAttribute('type', 'producer');
        $producer->appendChild($edge);
      } else {
        echo "no producer for $fid\n";
      }
    }
    
    if ($doc->validate()) {
      $file = 'data/db12.xml';
      echo "New File: {$file}\n";
      $doc->save(PATH . $file);
      
      $this->CLIcompress($file);
    } else {
      print_r(libxml_get_errors());
    }
  }
  
  public function CLIfeatures()
  {

    $sql   = new \mysqli('127.0.0.1', 'root', '', 'TCIAF');
    
    $new_features = $sql->query("SELECT * FROM features WHERE created_at > '2014-12-02 17:16:09'")->fetch_all(MYSQLI_ASSOC);

    foreach ($new_features as $feature) {
      $id = trim('f:' . $feature['id']);
      
      if ($id == 'p:1291') {
        $id = 'p:MayaGoldbergSafir';
      }
      
      try {
        \models\Graph::id($id);
        continue;
      } catch (\Exception $e) {
      
        $data = ['vertex' =>
          [
            '@' => [
              'id'      => $id,
              'title'   => $feature['title'],
              'created' => $feature['created_at'],
              'updated' => $feature['updated_at'],
            ],
            'abstract' => [
              'CDATA' => str_replace(['&#39;', '&ndash;', '&rsquo;', '&nbsp;'], ["'", '–', "'", ' '], trim(strip_tags($feature['description']), "\n\r\t"))
            ],
            'premier' => [
              '@' => [
                'date' => $feature['premier_date'],
              ],
              'CDATA' => $feature['premier_locaction'],
            ],
            'location' => [
              'CDATA' => $feature['origin_country'],
              ]     
          ]
        ];
            
        if ($modeled = \models\feature::create(new \models\feature, $data)) {
          if ($modeled->save()) {
            echo "Added {$id} \n";
          } else {
            print_r($modeled->errors);
          }
        } else {
          echo "something happended!";
          exit();
        }
      }
    }
  }
  
  
  
  
  public function CLImapAudiotoFeatures()
  {
    $doc = new \bloc\DOM\Document('data/db12');
    $xml  = new \DomXpath($doc);


    $features = $xml->query("//group[@type='feature']/vertex[not(media)]");
    $sql   = new \mysqli('127.0.0.1', 'root', '', 'TCIAF');
    
    foreach ($features as $feature) {
      echo $feature->getAttribute('title');
      $id = substr($feature->getAttribute('id'), 2);
      $audio = $sql->query("SELECT CONCAT(id, '/', mp3_file_name) as file FROM audio_files  WHERE feature_id = '{$id}'")->fetch_assoc();
      $media = $doc->createElement("media");
      $media->setAttribute('src', $audio['file']);
      $media->setAttribute('type', 'audio');
      $feature->appendChild($media);
    }
    
    if ($doc->validate()) {
      $file = 'data/db12.xml';
      echo "New File: {$file}\n";
      $doc->save(PATH . $file);
    }
    
  }
  
  public function CLImapImagestoFeatures()
  {
    $doc = new \bloc\DOM\Document('data/db12');
    $xml  = new \DomXpath($doc);


    $features = $xml->query("//group[@type='feature']/vertex");
    $sql   = new \mysqli('127.0.0.1', 'root', '', 'TCIAF');
    
    foreach ($features as $feature) {

      $id = substr($feature->getAttribute('id'), 2);
      
      $images = $sql->query("SELECT CONCAT('feature-photos/photos/', id, '/', photo_file_name) as file, caption FROM feature_photos  WHERE feature_id = '{$id}'")->fetch_all(MYSQLI_ASSOC);
      foreach ($images as $image) {
       
        $media = $doc->createElement("media", $image['caption'] ?: null);
        $media->setAttribute('src', $image['file']);
        $media->setAttribute('type', 'image');
        $feature->appendChild($media);
      }
    }
    
    if ($doc->validate()) {
      $file = 'data/db12.xml';
      echo "New File: {$file}\n";
      $doc->save(PATH . $file);
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
        $exp = "edge[@token='s:{$feature}']";

        $edge = $xml->query($exp, $p);
                echo $edge->length . "\n";
        if ($p && $edge->length === 0) {
          
          $edge = $doc->createElement("edge");
          $edge->setAttribute('token', 's:'.$feature);
          $edge->setAttribute('type', 'producer');
          $p->appendChild($edge);
          
           echo $producer['id'] . " has no edge \n";
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
  
  public function CLIevents()
  {
    $sql   = new \mysqli('127.0.0.1', 'root', '', 'TCIAF');
    
    $events = $sql->query("SELECT * from events")->fetch_all(MYSQLI_ASSOC);
    $doc  = new \bloc\DOM\Document('data/db12');
    $xml  = new \DomXpath($doc);
    
    $event_group = $xml->query("//group[@type='event']")->item(0);
    
    foreach ($events as $event) {
      $vertex = $doc->createElement('vertex');
      
      print_r($event). "\n";
      
      $id = 'e:' . $event['id'];
      
      // location / host
      
      $element = $event_group->appendChild($doc->createElement('vertex'));
      $element->setAttribute('id', $id);
      $element->setAttribute('title', $event['name']);
      $element->setAttribute('created', $event['created_at']);
      $element->setAttribute('updated', $event['updated_at']);
      $abstract_text = str_replace(['&#39;', '&ndash;', '&rsquo;', '&nbsp;'], ["'", '–', "'", ' '], trim(strip_tags($event['short_description']), "\n\r\t"));
            $abstract_text .= "¶HOST: {$event['host']} (this should be a seperate object)";
      $abstract = $element->appendChild($doc->createElement('abstract', $abstract_text));
      $abstract->setAttribute('content', 'desription');
      $location = $element->appendChild($doc->createElement('location', $event['location']));
      

    }
    
    if ($doc->validate()) {
      $file = 'data/db13.xml';
      echo "New File: {$file}\n";
      $doc->save(PATH . $file);
      
      $this->CLIcompress($file);
    } else {
      print_r(libxml_get_errors());
    } 
  }

  
  public function CLIsetWeights()
  {
    $doc  = new \bloc\DOM\Document('data/db9');
    $xml  = new \DomXpath($doc);
    
    $features = $xml->query('//group[@type="feature"]/vertex');
    
    foreach ($features as $feature) {
      
      if ($feature->getAttribute('weight')) {
        echo 'skip ' . $feature->getAttribute('title');
      } else {
        echo "no...";
        $feature->setAttribute('weight', 0);
      }
    }
    
    if ($doc->validate()) {
      $file = 'data/db10.xml';
      echo "New File: {$file}\n";
      $doc->save(PATH . $file);
      
      $this->CLIcompress($file);
    } else {
      print_r(libxml_get_errors());
    }
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
      echo "Create edge to token in {$driehaus->getAttribute('title')}\n\n";

      
      $cid = 'd:'.$key;
      $competition = $doc->createElement('token');
      $competition->setAttribute('id', $cid);
      $competition->setAttribute('title', $key);
      
      $group->appendChild($competition);

      
      $edge = $doc->createElement("edge");
      $edge->setAttribute('rel', $cid);
      $edge->setAttribute('type', 'issue');
      $driehaus->appendChild($edge);
      
      foreach ($awards as $award) {
        $subedge = $doc->createElement('edge', trim($award['award']));
        $subedge->setAttribute('rel', 's:'.$award['feature_id']);
        $subedge->setAttribute('type', 'winner');
        $competition->appendChild($subedge);
      }
    }
    
    if ($doc->validate()) {
      $file = 'data/db4.xml';
      echo "New File: {$file}\n";
      // $doc->save(PATH . $file);
      
      $this->CLIcompress($file);
    }
    
    
  }
  
  public function CLIextras()
  {
    require_once(PATH . 'vendor/markdown/HTML_To_Markdown.php');
    
    $sql   = new \mysqli('127.0.0.1', 'root', '', 'TCIAF');
    
    $extras = $sql->query('SELECT extras.*, extra_audio_files.mp3_file_name, extra_audio_files.description as audio_description FROM extras LEFT JOIN extra_audio_files on (extras.id = extra_audio_files.extra_id);')->fetch_all(MYSQLI_ASSOC);
    
    $grouped_extras = [];
    
    foreach ($extras as $extra) {
      if (! array_key_exists($extra['id'], $grouped_extras)) {
        $grouped_extras[$extra['id']] = [
          'extra' => $extra,
          'media' => [],
        ];
      };
      
      if (isset($extra["mp3_file_name"])) {
        $grouped_extras[$extra['id']]['media'][] = [
            'CDATA'  => trim(strip_tags(str_replace(['&#39;', '&ndash;', '&rsquo;', '&nbsp;', '&quot;'], ["'", '–', "'", ' ', '"'], $extra["audio_description"])), "\n\r\t"),
            '@' => [
              'src' => $extra["mp3_file_name"],
              'type' => 'audio',
              'mark' => 0,
            ]
          ];
      }
    }

    foreach ($grouped_extras as $group) {
      $extra = $group['extra'];
      $fid = 'f:' . $extra['feature_id'];
      $eid = $fid . 'e' . $extra['id'];
      
      
      $markdown = trim((new \HTML_To_Markdown(str_replace(['&#39;', '&ndash;', '&rsquo;', '&nbsp;'], ["'", '–', "'", ' '], $extra['behind_the_scene_text']), ['strip_tags' => true]))->output(), "\n\r\t");
      preg_match('/\**behind\s+the\s+scenes\**\s+.*/im', $markdown, $result, PREG_OFFSET_CAPTURE);
      $interview = false;
      if (empty($result)) {
        if (empty($extra['links_block'])) continue;
        $title       = 'Extra';
        $description = $markdown;
      } else {
        $title       =  $result[0][0];
        $interview = true;
        $description = substr_replace($markdown, '', $result[0][1], strlen($result[0][0]));
      }

      
      
      
      $links_block = trim((new \HTML_To_Markdown(str_replace(['&#39;', '&ndash;', '&rsquo;', '&nbsp;'], ["'", '–', "'", ' '], $extra['links_block']), ['strip_tags' => true]))->output(), "\n\r\t");
      
      $external_vertex = \models\Graph::ID($fid);
      
      // Add this to the feature in $fid
      if (!empty($links_block)) {

        
        $abstract = $external_vertex->insertBefore(\models\Graph::instance()->storage->createElement('abstract'), $external_vertex->getElementsByTagName('abstract')->item(0)->nextSibling);
        $abstract->setAttribute('content', 'extras');
        
        $abstract->nodeValue = str_replace('↩↩' , '¶', preg_replace(["/\r\n/", "/\n{2,}/"], ['↩', '↩↩'], $links_block));
      }
      
      // if there is no description, then we are basically done here.
      if (empty($description)) {
        continue;
      }
      
      echo "{$fid} \n";
        
      $external_vertex->appendChild(\models\Graph::EDGE($eid, 'extra', $interview ? 'interview' : null));
      
      
      $description = preg_replace('/\*{2}\s*(.*)\*{2}/i', '### $1', trim($description));
            
      $data = ['vertex' =>
        [
          '@' => [
            'id'      => $eid,
            'title'   => $title,
            'created' => $extra['created_at'],
            'updated' => $extra['updated_at'],
          ],
          'abstract' => [
            'CDATA'  => str_replace('↩↩' , '¶', preg_replace("/\r\n/", '↩', $description)),
            '@' => [
              'content' => 'description'
            ]
          ],
          'media'    => [],
        ]
      ];
      
        
      
      // if (!empty($group['media'])) {
//         $data['vertex']['media'] = $group['media'];
//       }
//
//       print_r($data);
//
//
//
//       if ($modeled = new \models\feature(null, $data)) {
//         if ($modeled->save()) {
//           echo "Added {$id} \n";
//         } else {
//           print_r($modeled->errors);
//         }
//       } else {
//         echo "something happended!";
//         exit();
//       }
    }
    
    // \models\Graph::instance()->storage->save(PATH . \models\Graph::DB . '.xml');
  }
  
  public function CLIempties()
  {
    foreach (\models\Graph::group('feature')->find('vertex') as $vertex) {
      foreach ($vertex->getElementsByTagName('media') as $abstract) {
        if (empty($abstract->getAttribute('src'))) {
          echo "double node on " . $vertex->getAttribute('title') . "\n";
          $abstract->parentNode->removeChild($abstract);
          continue 2;
        }
      }
    }
    \models\Graph::instance()->storage->save(PATH . \models\Graph::DB . '.xml');
  }
  
  public function CLIdonors()
  {
    $doc = new \bloc\DOM\Document('data/db4');
    $xml = new \DomXpath($doc);
    $sql = new \mysqli('127.0.0.1', 'root', '', 'TCIAF');
    
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
  
  public function CLImoveAbstracts()
  {
    $doc = new \bloc\DOM\Document('data/db15');
    $markdown = new \Parsedown();
    foreach ($doc->getElementsByTagName('abstract') as $abstract) {
      $uri = $abstract->parentNode->getAttribute('id') . '-' .$abstract->getIndex() . '.html';
      $text = $markdown->text(str_replace(['¶', '↩'], ["\n\n", "\n"], $abstract->nodeValue));
      $new_abs = $doc->createElement('abstract');
      $new_abs->setAttribute('src', $uri);
      $new_abs->setAttribute('content', $abstract->getAttribute('content'));
      
      $abstract->parentNode->replaceChild($new_abs, $abstract);
      echo $uri . "\n";
      
      file_put_contents(PATH . 'data/abstracts/'.$uri, $text);
    }
    
    if ($doc->validate()) {
      $file = 'data/db15.xml';
      echo "New File: {$file}\n";
      $doc->save(PATH . $file);
    }    
  }
  
  public function CLIbroadcasts()
  {
    $features = \models\Graph::group('feature')->find('vertex');

    $broadcast = \models\Graph::group('broadcast')->pick('.');
    $article = \models\Graph::group('article')->pick('.');
    


    foreach ($features as $feature) {
      $title = $feature->getAttribute('title');
      $start = strtolower(substr(ltrim($title, "\x00..\x2F"), 0, 5));
      // move to broadcasts
      if ($start === 're:so') {
        echo "Move {$title} to broadcast group\n";
        $broadcast->appendChild($feature);
      }
      
      // move to article
      if ($start === 'extra' || $start === 'behin') {
        // echo "Move {$title} to article group\n";
        $article->appendChild($feature);
      }
    }
    
    echo "There are " . $broadcast->childNodes->length . " broadcasts\n";
    echo "There are " . $article->childNodes->length . " articles\n";
    
    \models\Graph::instance()->storage->save(PATH . \models\Graph::DB . '.xml');
  }
  
  public function CLIshortdocs()
  {
    $doc = new \bloc\DOM\Document('data/db15');
    $sql = new \mysqli('127.0.0.1', 'root', '', 'TCIAF');
    $query = "SELECT CONCAT('f-', features.id) as id, CONCAT('sd-', features.premier_date) as sid from features LEFT JOIN collections_features ON collections_features.feature_id = features.id WHERE collections_features.collection_id = 4;";
    foreach ($sql->query($query)->fetch_all(MYSQLI_ASSOC) as $shortdoc) {
      if ($elem = $doc->getElementById(trim($shortdoc['sid']))) {
        $edge = $elem->appendChild($doc->createElement('edge'));
        $edge->setAttribute('vertex', $shortdoc['id']);
        $edge->setAttribute('type', 'participant');
        echo "Adding to {$shortdoc['sid']}\n";
      } else {
        echo "{$shortdoc['sid']} is unavailable\n";
        exit();
      }
    }
    
    if ($doc->validate()) {
      $file = 'data/db16.xml';
      echo "New File: {$file}\n";
      $doc->save(PATH . $file);
    }
  }
  
  public function CLIlowerdocs()
  {
    foreach (\models\Graph::group('competition')->find('vertex[@id="comp-2"]/edge') as $edge) {
      $exp = "vertex[@id='{$edge->getAttribute('vertex')}']/edge";
      foreach (\models\Graph::group('competition')->find($exp) as $ref) {
        $vertex = \models\Graph::id($ref->getAttribute('vertex'));
        $spectra = $vertex->getElementsByTagName('spectra');
        
        if ($spectra->length > 0) {
          $f = $spectra->item(0)->getAttribute('F');
          if ($f == '50') {
            $spectra->item(0)->setAttribute('F', '0');
          }

        } else {
          $spectra = \models\Graph::instance()->storage->createElement('spectra');
          foreach (['F'=>0,'S'=>50,'M'=>50,'R'=>50,'P'=>50,'T'=>50,'A'=>50] as $key => $value) {
            $spectra->setAttribute($key, $value);
          }
          $vertex->appendChild($spectra);
        }
        
      }
    }
    \models\Graph::instance()->storage->save(PATH .  'data/db17.xml');
  }
  

  public function CLIRenameSpectra()
  {
    $doc  = new \bloc\DOM\Document('data/db10');
    $xml  = new \DomXpath($doc);
    
    foreach ($xml->query("//group/vertex/spectra") as $spectra) {
      parse_str($spectra->nodeValue, $parsed);
      $newspectra = $doc->createElement('spectra');
      foreach ($parsed as $key => $value) {
        $newspectra->setAttribute($key, $value);
      }
      
      $spectra->parentNode->replaceChild($newspectra, $spectra);
      
      $spectra->nodeValue = null;
    }
    
    if ($doc->validate()) {
      $file = 'data/db11.xml';
      echo "New File: {$file}\n";
      $doc->save(PATH . $file);
      
      $this->CLIcompress($file);
    }
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