<?php
/**
 * @package DolWs
 * @copyright Copyright (C) 2011 Wene / ssm2017 Binder (S.Massiaux). All rights reserved.
 * @license   GNU/GPL, http://www.gnu.org/licenses/gpl-2.0.html
 * DolWs is free software. This version may have been modified pursuant to the GNU General Public License,
 * and as distributed it includes or is derivative of works licensed under the GNU General Public License
 * or other free or open source software licenses.
 */
 
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
  array('success'=>'xsd:boolean', 'message'=>'xsd:string', 'data'=>'xsd:string'),
  $ns
);
$server->register('updateAdherent',
  array('data'=>'xsd:string'),
  array('success'=>'xsd:boolean', 'message'=>'xsd:string', 'data'=>'xsd:string'),
  $ns
);
$server->register('getAdherentId',
  array('field'=>'xsd:string', 'value'=>'xsd:string', 'where'=>'xsd:string', 'options'=>'xsd:boolean'),
  array('success'=>'xsd:boolean', 'message'=>'xsd:string', 'data'=>'xsd:string'),
  $ns
);

// Return the results.
$server->service($HTTP_RAW_POST_DATA);

function createAdherent($data) {
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

function updateAdherent($data) {
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

function getAdherentId($field, $value, $where, $options) {
  require_once("DolWsAdherents.php");
  $adherents = new DolWsAdherents();
  $adherents->getAdherentId($field, $value, $where, $options);
  return array(
    'success' => $adherents->success,
    'message' => $adherents->message,
    'data'    => $adherents->data,
  );
}

$db->close();
