<?php namespace models;


/*
  TODO 
  [ ] generate new database file with sequential alpha-ids
  [ ] generate index file
  [ ] move abstracts
  [ ] apache rewrites and redirects

  manual caveats:
  
  there are some duplicate ids. don't know why.

  change the file permissions of the text after going through the db conversion

  there are some static IDS that must be located. TCIAF, opportunities, and probably anything with 'sticky'
  line 206 on overview.php controller
  line 47 on manage.php
*/
class Migration {
  private $doc, $xpath;
  
  public function __construct() {
    $this->doc   = new \bloc\DOM\Document('data/tciaf');
    $this->xpath = new \DOMXpath($this->doc);
  }
  
  
  private function generateIndex() {
    
    // $index = fopen(PATH . 'data/map.txt', 'w');
    
    $nodes = iterator_to_array($this->xpath->query('//group/vertex[@id]'));
    
    usort($nodes, function($a, $b) {
      return $this->xpath->query('edge', $a)->length < $this->xpath->query('edge', $b)->length;
    });

    foreach($nodes as $count => $node) {
      $id   = Graph::ALPHAID($count);
      $slug = $node->getAttribute('id');
      
      $node->setAttribute('key', $slug);
      // id should be redundant as it is serialized
      // $key = substr(str_pad($slug, 90, " ", STR_PAD_RIGHT), 0, 90);
      // $val = str_pad($id, 8, " ", STR_PAD_LEFT);
      // fwrite($index, "{$key} {$val}\n", 100);

      foreach ($this->xpath->query("//vertex[@id='{$slug}']/@id|//edge[@vertex='{$slug}']/@vertex") as $attr) {
        $attr->nodeValue = $id;
      }
      
      if ($node->hasAttribute('sticky')) {
        echo "STICKY! {$slug} is now {$id}\n";
      }
      
    }
    
  }
  
  private function renameSpectra() {
    foreach ($this->xpath->query('//config/spectra') as $node) {
      $node->setAttribute('id', ':' . $node->getAttribute('id'));
    }
  }
  
  private function copyAbstracts() {
    foreach ($this->xpath->query('//abstract') as $abstract) {
      
      $id      = $abstract->parentNode->getAttribute('id');
      $content = strtolower($abstract->getAttribute('content') ?: 'extras');
      $path    = $abstract->getAttribute('src');
      $dir     = PATH . 'data/text/' . $content;
      if (!file_exists($dir)) {
        mkdir($dir, 0774, true);
      }
      
      if (file_exists(PATH . $path)) {
        if (! copy(PATH . $path, $dir . '/' . $id . '.html')) {
          echo "did not save {$dir}\n";
        }
        $abstract->removeAttribute('src');
        $abstract->setAttribute('content', $content);
      } else {
        echo "There is no file at {$path}... removing attribute\n";
        $abstract->parentNode->removeChild($abstract);
      }
      
      
    }
    
    
  }
  
  protected function joinAbstracts() {
    foreach ($this->xpath->query('//group/vertex') as $vertex) {
      $abstracts  = [];
      
      foreach ($this->xpath->query('abstract', $vertex) as $idx => $abstract) {
        $abstracts[] = $abstract->getAttribute('content');
        $abstract->parentNode->removeChild($abstract);
      }
      
      if (! empty($abstracts)) {
        $vertex->setAttribute('text', implode(' ', $abstracts));
      }
      
    }
  }
  
  
  public function execute() {
    // generate new index with new ids
    $this->generateIndex();
    // rename Spectra so there are no id conflicts
    $this->renameSpectra();
    // move abstracts into new files
    $this->copyAbstracts();
    // rename abstracts into attribute
    $this->joinAbstracts();
    
    $this->doc->save(PATH. 'data/tciaf2.xml');
  }
}