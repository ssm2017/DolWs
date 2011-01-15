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

// Create the soap Object
$server = new soap_server();
$server->soap_defencoding='UTF-8';
$ns='DolWsContrat';
$server->configureWSDL('WebServicesDolibarr',$ns);
$server->wsdl->schemaTargetNamespace=$ns;

// register methods
$server->register('addContrat',
  array('data'=>'xsd:string'),
  array('success'=>'xsd:int', 'message'=>'xsd:string', 'data'=>'xsd:string'),
  $ns
);

$server->register('addLigne',
  array('data'=>'xsd:string'),
  array('success'=>'xsd:int', 'message'=>'xsd:string', 'data'=>'xsd:string'),
  $ns
);

// Return the results.
$server->service($HTTP_RAW_POST_DATA);

function addContrat($data='') {
  require_once("DolWsContrat.php");
  $values = unserialize($data);
  $societe = new DolWsContrat();
  $societe->addContrat($values);
  return array(
    'success' => TRUE,
    'message' => $societe->message,
    'data'    => $societe->data,
  );
}

function addLigne($data='') {
  require_once("DolWsContrat.php");
  $values = unserialize($data);
  $societe = new DolWsContrat();
  $societe->addLigne($values);
  return array(
    'success' => TRUE,
    'message' => $societe->message,
    'data'    => $societe->data,
  );
}
