<?php
namespace controllers;
use \bloc\Application;

/**
 * Third Coast International Audio Festival Defaults
 */

class Task extends \bloc\controller
{
  public function __construct($request)
  {
  }

  public function CLIindex()
  {
    // show a list of methods.
    print_r(get_class_methods($this));
  }
  
  public function CLIcompress($file)
  {
    $text = file_get_contents(PATH . $file);
    $compressed = gzencode($text, 3);
        
    file_put_contents(PATH . substr($file, 0, -4), $compressed, LOCK_EX);
  }
  
  public function CLILogin($xml)
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
    curl_setopt($handle, CURLOPT_COOKIEFILE, "");
    
    $result = curl_exec($handle);
    $info   = curl_getinfo($handle);
    curl_close($handle);
    if ($info['http_code'] == 401) {
      $result = $this->CLILogin($result);
    }
    
    return $result;
  }

}