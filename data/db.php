<?php
namespace data;

/**
 * Impromptu Databases
 */

class db extends \DOMDocument
{
  function __construct($file)
  {
    parent::__construct();
    $this->validateOnParse = true;
    $this->load(PATH."data/{$file}.xml");
  }
}