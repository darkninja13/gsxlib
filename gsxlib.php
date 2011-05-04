<?php
/**
 * gsxlib/gsxlib.php
 * @package gsxlib
 * @author Filipp Lepalaan <filipp@mcare.fi>
 * http://gsxwsut.apple.com/apidocs/html/WSReference.html?user=asp
 * http://gsxwsut.apple.com/apidocs/html/WSArtifacts.html?user=asp
 * @license
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

  const timeout = 30;
  
  function __construct($account, $username, $password, $environment = '', $region = 'emea', $tz = 'CEST')
  {
    if (!class_exists('SoapClient')) {
      exit('Looks like your PHP lacks SOAP support');
    }
    
    if (!preg_match('/\d+/', $account)) {
      exit('Invalid Sold-To: ' . $account);
    }
    
    $regions = array('am', 'emea', 'apac', 'la');
    
    if (!in_array($region, $regions)) {
      exit('Region '.$region.' should be one of: ' . implode(', ', $regions));
    }
    
    $envirs = array('ut', 'it');
    
    if (!empty($environment)) {
      if (!in_array($environment, $envirs)) {
        exit('Environment '.$environment. ' should be one of: ' . implode(', ', $envirs));
      }
    }
    
    $wsdl = 'https://gsxws'.$environment.'.apple.com/gsx-ws/services/'.$region.'/asp?wsdl';
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
        'userTimeZone'      => $tz,
      )
    );
    
    if (@$_SESSION['_gsxlib_session_timeout'] > time()) {
      return $this->session_id = $_SESSION['_gsxlib_session_id'];
    }
    
    try {
      $this->session_id = $this->client
        ->Authenticate($a)
        ->AuthenticateResponse
        ->userSessionId;
    }
    catch (SoapFault $e) {
      exit('Authentication with GSX failed. Does this account have access to '.$environment.' ?');
    }
    
    // there's a session going, put the credentials in there
    if (session_id()) {
      $_SESSION['_gsxlib_session_id'] = $this->session_id;
      $_SESSION['_gsxlib_session_timeout'] = time()+(60*self::timeout);
    }
    
  }
  
  /**
   * Get current GSX status of repair
   * @param mixed $dispatchId
   */
  public function repairStatus($dispatchId)
  {
    $toCheck = array();
    
    if (!is_array($dispatchId)) {
      $dispatchId = array($dispatchId);
    }
    
    foreach ($dispatchId as $id) {
      if (self::looksLike($id, 'dispatchId')) {
        $toCheck[] = $id;
      }
    }
    
    if (empty($toCheck)) {
      exit('No valid dispatch IDs given');
    }
    
    $req = array('RepairStatus' => array(
      'repairConfirmationNumbers' => $toCheck
    ));
    
    return $this->request($req)->repairStatus;
    
  }
  
  public function bulkReturnProforma()
  {
    
  }
  
  public function repairLookup($query)
  {
    $fields = array(
      'repairConfirmationNumber'  => '',
      'customerEmailAddress'      => '',
      'customerFirstName'         => '',
      'customerLastName'          => '',
      'fromDate'                  => '',
      'toDate'                    => '',
      'incompleteRepair'          => 'N',
      'pendingShipment'           => 'N',
      'purchaseOrderNumber'       => '',
      'repairNumber'              => '',
      'repairStatus'              => '',
      'repairType'                => 'CA',
      'serialNumber'              => '',
      'shipToCode'                => '',
      'soldToReferenceNumber'     => '',
      'technicianFirstName'       => '',
      'technicianLastName'        => '',
      'unreceivedModules'         => 'N',
    );
    
    if (!is_array($query)) {
      if (self::looksLike($query, 'dispatchId')) {
        $query = array('repairConfirmationNumber' => $query);
      } else {
        exit('Invalid query for repair lookup: ' . $query);
      } 
    }
    
    foreach ($fields as $k => $v) {
      if (array_key_exists($k, $query)) {
        $fields[$k] = $query[$k];
      }
    }
    
    $req = array('RepairLookup' => array('lookupRequestData' => $fields));
    return $this->request($req)->lookupResponseData;
    
  }
  
  /**
   * List parts pending for return
   * Default to Open Carry-Ins
   * @param mixed $repairData
   * @return mixed
   */
  public function partsPendingReturn($repairData = null)
  {
    $fields = array(
      'repairType'                => 'CA',    // default to Carry In repairs
      'repairStatus'              => 'Open',  // and current ones
      'purchaseOrderNumber'       => '',
      'sroNumber'                 => '',
      'repairConfirmationNumber'  => '',
      'serialNumber'              => '',
      'shipToCode'                => '',
      'customerFirstName'         => '',
      'customerLastName'          => '',
      'customerEmailAddress'      => '',
      'createdFromDate'           => '',
      'createdToDate'             => '',
    );
    
    if (!is_array($repairData)) {
      $repairData = array();
    }
    
    if (!empty($repairData)) {
      foreach ($fields as $k => $v) {
        if (array_key_exists($k, $repairData)) {
          $fields[$k] = $repairData[$f];
        }
      }
    }
    
    $req = array('PartsPendingReturn' => array('repairData' => $fields));
    
    return $this->request($req)->partsPendingResponse;
    
  }
  
  public function compTiaCodes()
  {
    $result = $this->request(array('ComptiaCodeLookup' => array()));
    return $result->comptiaInfo;
  }
  
  /**
   * Return details for given dispatch ID
   * @param string $dispatchId
   * @return object lookupResponseData
   */
  public function repairDetails($dispatchId)
  {
    $dispatchId = trim($dispatchId);
    
    if (!self::looksLike($dispatchId, 'dispatchId')) {
      exit('Invalid dispatch ID: ' . $dispatchId);
    }
    
    $req = array('RepairDetails' => array('dispatchId' => $dispatchId));
    return $this->request($req)->lookupResponseData;
  
  }
  
  public function returnLabel($returnOrder, $partNumber)
  {
    if (!self::looksLike($returnOrder, 'returnOrder')) {
      exit('Invalid order number: ' . $returnOrder);
    }
    
    if (!self::looksLike($partNumber, 'partNumber')) {
      exit('Invalid part number: ' . $partNumber);
    }
    
    $req = array('ReturnLabel' => array(
      'returnOrderNumber' => $returnOrder,
      'partNumber' => $partNumber
    ));
    
    return $this->request($req)->returnLabelData;
    
  }
  
  /**
   * a shortcut for looking up part information
   * @param mixed $string
   * @return [bool|string]
   */
  public function partsLookup($string = null)
  {
    $string = trim($string);
    $what = self::looksLike($string);
    
    if (!$what) {
      exit('Invalid search term for part lookup: ' . $string);
    }
    
    $req = array('PartsLookup' => array(
      'lookupRequestData' => array($what => $string)
    ));
    
    return $this->request($req)->parts;
  
  }
  
  /**
   * A shortcut for checking warranty status of device
   */
  public function warrantyStatus($serialNumber)
  {
    $serialNumber = trim($serialNumber);
    
    if (!self::looksLike($serialNumber, 'serialNumber')) {
      exit('Invalid serial number: ' . $serialNumber);
    }
    
    $a = array('WarrantyStatus' => array(
      'unitDetail'  => array('serialNumber' => $serialNumber)
    ));
    
    return $this->request($a)->warrantyDetailInfo;
  
  }
  
  /**
   * return the GSX user session ID
   * I still keep the property private since it should not be modified
   * outside the constructor
   * @return string GSX session ID
   */
  public function getSessionId()
  {
    return $this->session_id;
  }
  
  private function request($req)
  {
    $result = false;
    list($r, $p) = each($req);
    $p['userSession'] = array('userSessionId' => $this->session_id);
    $request = array($r.'Request' => $p);
    
    try {
      $result = $this->client->$r($request);
      $resp = "{$r}Response";
      return $result->$resp;
    }
    catch (SoapFault $e) {
      trigger_error($e->getMessage());
    }
    
    return $result;
    
  }
  
  /**
   * Try to "categorise" a string
   * About identifying serial numbers - before 2010, Apple had a logical
   *  serial number format, with structure, that you could id quite reliably.
   *  unfortunately, it's no longer the case
   * @param string $string
   */
  static function looksLike($string, $what = null)
  {
    $result = false;
    
    $rex = array(
      'partNumber'    => '/^([a-z]{1,2})?\d{3}\-\d{4}$/i',
      'serialNumber'  => '/^[a-z0-9]{11,12}$/i',
      'eeeCode'       => '/^[a-z0-9]{3,4}$/i',
      'returnOrder'   => '/^7\d{9}$/',
      'repairNumber'  => '/^\d{12}$/',
      'dispatchId'    => '/^G\d{9}$/i',
    );
    
    foreach ($rex as $k => $v) {
      if (preg_match($v, $string)) {
        $result = $k;
      }
    }
    
    return ($what) ? ($result == $what) : $result;
  
  }
  
}


?>