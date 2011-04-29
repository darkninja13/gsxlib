<?php
/**
 * gsxlib/gsxlib.php
 * @author Filipp Lepalaan <filipp@mcare.fi>
 * This program is free software. It comes without any warranty, to
 * the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want
 * To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details.
 */
class GsxLib
{
  private $client;
  private $region;
  private $session_id;
  private $environment;
  
  
  function __construct($account, $username, $password, $environment = 'ws', $region = 'emea', $tz = 'CEST')
  {
    if (!class_exists('SoapClient')) {
      exit('Looks like your PHP lacks SOAP support');
    }
    
    if (!preg_match('/\d+/', $account)) {
      exit('Invalid Sold-To: ' . $account);
    }
    
    $regions = array('am', 'emea', 'apac', 'la');
    
    if (!in_array($region, $regions)) {
      exit('Region must be one of: ' . implode(', ', $regions));
    }
    
    $envirs = array('ws', 'ut', 'it');
    
    if (!in_array($environment, $envirs)) {
      exit('Environment must be one of: ' . implode(', ', $envir));
    }
    
    $wsdl = 'https://gsx'.$environment.'.apple.com/gsx-ws/services/'.$region.'/asp?wsdl';
    $this->client = new SoapClient($wsdl, array('exceptions' => TRUE, 'trace' => 1));
    
    if (!$this->client) {
      exit('Failed to create SOAP client.');
    }
    
    $a = array(
      'AuthenticateRequest' => array(
        'userId'            => $username,
        'password'          => $password,
        'serviceAccountNo'  => $account,
        'languageCode'      => 'en',
        'userTimeZone'      => $tz)
    );
    
    if (@$_SESSION['_gsxlib_session_timeout'] > time()) {
      return $this->session_id = $_SESSION['_gsxlib_session_id'];
    }
    
    try {
      $this->session_id = $this->client->Authenticate($a)->AuthenticateResponse->userSessionId;
    } catch (Exception $e) {
      exit('Authentication with GSX failed');
    }
    
    // there's a session going, put the credentials in the
    if (session_id()) {
      $_SESSION['_gsxlib_session_id'] = $this->session_id;
      $_SESSION['_gsxlib_session_timeout'] = time()+60*30;
    }
    
  }
  
  public function partDetails($partNumber)
  {
    if (!preg_match('/^\w+\-\w+^/', $partNumber)) {
      exit('Invalid part number: ' . $partNumber);
    }
    
  }
  
  /**
   * A shortcut for checking warranty status of device
   */
  public function warrantyStatus($serialNumber)
  {
    if (!preg_match('/^[a-z0-9]{7,18}$/i', $serialNumber)) {
      exit('Invalid serial number: ' . $serialNumber);
    }
    
    $a = array(
      'WarrantyStatusRequest' => array(
        'userSession' => array('userSessionId' => $this->session_id),
        'unitDetail'  => array('serialNumber' => $serialNumber)
    ));
    
    $result = $this->client->WarrantyStatus($a);
    return $result->WarrantyStatusResponse->warrantyDetailInfo;
  
  }
  
  public function request($args)
  {
    $info = $client->WarrantyStatus($a);
    $out[] = $info->WarrantyStatusResponse->warrantyDetailInfo;
  }
  
}


?>