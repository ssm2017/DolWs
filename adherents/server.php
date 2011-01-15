<?php

require_once("../../../master.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");
require_once(NUSOAP_PATH.'/nusoap.php');    // Include SOAP

dol_syslog("Call Dolibarr webservices interfaces");

// Enable and test if module web services is enabled
if (empty($conf->global->MAIN_MODULE_WEBSERVICES)) {
  $langs->load("admin");
  dol_syslog("Call Dolibarr webservices interfaces with module webservices disabled");
  print $langs->trans("WarningModuleNotActive",'WebServices').'.<br><br>';
  print $langs->trans("ToActivateModule");
  exit;
}

$user->fetch(1);

// Create the soap Object
$server = new soap_server();
$server->soap_defencoding='UTF-8';
$ns='DolWsAdherents';
$server->configureWSDL('WebServicesDolibarr',$ns);
$server->wsdl->schemaTargetNamespace=$ns;

// register methods
$server->register('createAdherent',
  array('data'=>'xsd:string'),
  array('success'=>'xsd:int', 'message'=>'xsd:string', 'data'=>'xsd:string'),
  $ns
);
$server->register('updateAdherent',
  array('data'=>'xsd:string'),
  array('success'=>'xsd:int', 'message'=>'xsd:string', 'data'=>'xsd:string'),
  $ns
);
$server->register('getAdherentId',
  array('field'=>'xsd:string', 'value'=>'xsd:string', 'where'=>'xsd:string'),
  array('success'=>'xsd:int', 'message'=>'xsd:string', 'data'=>'xsd:string'),
  $ns
);

// Return the results.
$server->service($HTTP_RAW_POST_DATA);

function createAdherent($data='') {
  require_once("DolWsAdherents.php");
  $values = unserialize($data);
  $adherents = new DolWsAdherents();
  $adherents->createAdherent($values);
  return array(
    'success' => $adherents->success,
    'message' => $adherents->message,
    'data'    => $adherents->data,
  );
}

function updateAdherent($data='') {
  require_once("DolWsAdherents.php");
  $values = unserialize($data);
  $adherents = new DolWsAdherents();
  $adherents->updateAdherent($values);
  return array(
    'success' => $adherents->success,
    'message' => $adherents->message,
    'data'    => $adherents->data,
  );
}

function getAdherentId($field, $value, $where) {
  require_once("DolWsAdherents.php");
  $adherents = new DolWsAdherents();
  $adherents->getAdherentId($field, $value, $where);
  return array(
    'success' => $adherents->success,
    'message' => $adherents->message,
    'data'    => $adherents->data,
  );
}

$db->close();
