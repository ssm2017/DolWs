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
$ns='DolWsContrat';
$server->configureWSDL('WebServicesDolibarr',$ns);
$server->wsdl->schemaTargetNamespace=$ns;

// register methods
$server->register('createContrat',
  array('data'=>'xsd:string'),
  array('success'=>'xsd:boolean', 'message'=>'xsd:string', 'data'=>'xsd:string'),
  $ns
);

$server->register('addLigne',
  array('data'=>'xsd:string'),
  array('success'=>'xsd:boolean', 'message'=>'xsd:string', 'data'=>'xsd:string'),
  $ns
);

// Return the results.
$server->service($HTTP_RAW_POST_DATA);

function createContrat($data) {
  require_once("DolWsContrat.php");
  $values = unserialize($data);
  $contrat = new DolWsContrat();
  $contrat->createContrat($values);
  return array(
    'success' => $contrat->success,
    'message' => $contrat->message,
    'data'    => $contrat->data,
  );
}

function addLigne($data) {
  require_once("DolWsContrat.php");
  $values = unserialize($data);
  $contrat = new DolWsContrat();
  $contrat->addLigne($values);
  return array(
    'success' => $contrat->success,
    'message' => $contrat->message,
    'data'    => $contrat->data,
  );
}

$db->close();
