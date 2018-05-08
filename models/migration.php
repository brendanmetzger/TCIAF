<?php namespace models;


/*
  TODO 
  [ ] generate new database file with sequential alpha-ids
  [ ] generate index file
  [ ] move abstracts
  [ ] apache rewrites and redirects

  manual caveats:

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
    
    $index_doc = new \bloc\DOM\Document('<i/>', [], 2);
    $nodes     = iterator_to_array($this->xpath->query('//group/vertex[@id]'));
    
    usort($nodes, function($a, $b) {
      return $this->xpath->query('edge', $a)->length < $this->xpath->query('edge', $b)->length;
    });

    foreach($nodes as $count => $node) {
      $id   = Graph::ALPHAID($count);
      $slug = $node->getAttribute('id');
      
      $key  = $index_doc->documentElement->appendChild(new \DOMElement($id));
      $key->setAttribute('k', $slug);
      
      foreach ($this->xpath->query("//vertex[@id='{$slug}']/@id|//edge[@vertex='{$slug}']/@vertex") as $attr) {
        $attr->nodeValue = $id;
      }
      
      echo "{$id} now represents {$slug}\n";
    }
    
    $index_doc->save(PATH . 'data/index.xml');
  }
  
  private function renameSpectra() {
    foreach ($this->xpath->query('//config/spectra') as $node) {
      $node->setAttribute('id', ':' . $node->getAttribute('id'));
    }
  }
  
  protected function abstracts() {
    foreach ($this->xpath->query('//abstract') as $abstract) {
      
      $id      = $abstract->parentNode->getAttribute('id');
      $content = strtolower($abstract->getAttribute('content') ?: 'extras');
      $path    = $abstract->getAttribute('src');

      if (! copy(PATH . $path, PATH . 'data/abstracts/' . $content . '/' . $id . '.html')) {
        echo "did not save {$path}\n";
      }
      
      $abstract->removeAttribute('src');
      $abstract->setAttribute('content', $content);
    }
    
    
  }
  
  protected function moveAbstracts() {
    foreach ($this->xpath->query('//group/vertex') as $vertex) {
      $abstracts  = [];
      
      foreach ($this->xpath->query('abstract', $vertex) as $idx => $abstract) {
        $abstracts[] = $abstract->getAttribute('content');
        $abstract->parentNode->removeChild($abstract);
      }
      
      $vertex->setAttribute('abstract', implode(' ', $abstracts));
    }
  }
  
  
  public function execute() {
    // generateIndex
    $this->generateIndex();
    // renameSpectra
    $this->doc->save(PATH. 'data/tciaf2.xml');
  }
}