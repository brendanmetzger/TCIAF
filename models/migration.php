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
  
  public function __construct($document = 'data/tciaf') {
    $this->doc   = new \bloc\DOM\Document($document);
    $this->xpath = new \DOMXpath($this->doc);
  }
  
  public function save($db = 'data/tciaf2.xml')
  {
    $this->doc->save(PATH. $db);
  }
  
  
  
  private function generateIndex() {
    
    
    $nodes = iterator_to_array($this->xpath->query('//group/vertex[@id]'));
    
    usort($nodes, function($a, $b) {
      return $this->xpath->query('edge', $a)->length < $this->xpath->query('edge', $b)->length;
    });

    foreach($nodes as $count => $node) {
      $id   = Graph::ALPHAID($count);
      $slug = $node->getAttribute('id');
      
      $node->setAttribute('key', $slug);

      foreach ($this->xpath->query("//vertex[@id='{$slug}']/@id|//edge[@vertex='{$slug}']/@vertex") as $attr) {
        $attr->nodeValue = $id;
      }
      
      if ($node->hasAttribute('sticky')) {
        echo "STICKY! {$slug} is now {$id}\n";
      }
      
    }
    
  }
  
  protected function getCardinality()
  {
    $size = $this->xpath->query('//group/vertex[@id]')->length;
    $this->doc->documentElement->setAttribute('serial', $size);
    echo $size;
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
  
  public function resetDates()
  {

    foreach ($this->xpath->query('//group/vertex/premier') as $premier) {

      $date = $premier->getAttribute('date');
      echo $date . "\n";
      if (empty($date) || ! is_numeric($date[0])) {
        $premier->parentNode->removeChild($premier);
      } else {
        if (strlen($date) == 4) {
          $date = '01-01-'.$date;
        }

        $date = (new \DateTime($date))->format('Y-m-d');
        $premier->setAttribute('date', $date);
      }
    }
  }
  
  private function compressDates()
  {
    
    foreach ($this->xpath->query('//group/vertex') as $vertex) {
      $vertex->setAttribute('created', \models\graph::alphaid(strtotime($vertex->getAttribute('created'))));
      $vertex->setAttribute('updated', \models\graph::alphaid(strtotime($vertex->getAttribute('updated'))));
    }
    
  }
  
  private function removeRedundantCaptions()
  {
    foreach ($this->xpath->query('//group/vertex/media') as $media) {
      $caption = strtolower(trim($media->nodeValue));
      
      if ($media->hasChildNodes() && ($caption == 'a caption' || strlen($caption) < 3)) {
        $media->removeChild($media->firstChild);
      }
      
    }
  }
  
  public function removeRedundantMarks()
  {
    foreach ($this->xpath->query('//@mark') as $mark) {
      echo $mark->nodeValue . "\n";
      if ($mark->nodeValue == '0') {
        $mark->parentNode->removeAttribute('mark');
      }
    }
  }
  
  public function CLIrenameMediaElements()
  {

    foreach ($this->xpath->query('//group/vertex/media') as $media) {
      $type = $media->getAttribute('type') == 'image' ? 'img' : $media->getAttribute('type');
      $node = new \DOMElement($type);
      $media = $media->parentNode->replaceChild($node, $media);
      if ($media->nodeValue) {
        $node->nodeValue = trim($media->nodeValue);
      }
      $node->setAttribute('src', $media->getAttribute('src'));
      $node->setAttribute('mark', $media->getAttribute('mark'));
      
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
    // set cardinality
    $this->getCardinality();
    // reformat premier dates
    $this->resetDates();
    // compress crud
    $this->compressDates();
    // remove redundant captions
    $this->removeRedundantCaptions();
    // remove redundant marks
    $this->removeRedundantMarks();
    $this->save();
    
  }
}