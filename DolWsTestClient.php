<?php
/**
 * @package DolWs
 * @copyright Copyright (C) 2011 Wene / ssm2017 Binder (S.Massiaux). All rights reserved.
 * @license   GNU/GPL, http://www.gnu.org/licenses/gpl-2.0.html
 * DolWs is free software. This version may have been modified pursuant to the GNU General Public License,
 * and as distributed it includes or is derivative of works licensed under the GNU General Public License
 * or other free or open source software licenses.
 */
 
define('URI', '');
ini_set("soap.wsdl_cache_enabled", "0");
if (isset($_GET['op'])) {
  $func = $_GET['op'];
  $func();
}
else {
  print '<ul>';
  $funcs = get_defined_functions();
  foreach ($funcs['user'] as $func) {
    print '<li><a href="?op='. $func.' ">'. $func.'</a></li>';
  }
  print '</ul>';
}

function listMethods() {
  try {
    $client = new SoapClient(URI. '/webservices/DolWs/adherents/server.php?wsdl');
  } catch (SoapFault $fault) {
    echo 'erreur : '.$fault;
  }
  print "<pre>";
  print_r($client->__getFunctions());
  print "</pre>";
  try {
    $client = new SoapClient(URI. '/webservices/DolWs/societe/server.php?wsdl');
  } catch (SoapFault $fault) {
    echo 'erreur : '.$fault;
  }
  print "<pre>";
  print_r($client->__getFunctions());
  print "</pre>";
  try {
    $client = new SoapClient(URI. '/webservices/DolWs/contrat/server.php?wsdl');
  } catch (SoapFault $fault) {
    echo 'erreur : '.$fault;
  }
  print "<pre>";
  print_r($client->__getFunctions());
  print "</pre>";
}

function createAdherent() {
  $random = generatePassword();

  try {
    $client = new SoapClient(URI. '/webservices/DolWs/adherents/server.php?wsdl');
  } catch (SoapFault $fault) {
    echo 'erreur : '.$fault;
  }

  // fill values
  $values = array();
  $values["typeid"]         = 1;
  $values["civilite_id"]    = 0;
  $values["nom"]            = $random;
  $values["prenom"]         = $random;
  $values["societe"]        = $random;
  $values["adresse"]        = $random;//$content_profile->field_profile_adresse[0]['street'];
  $values["cp"]             = $random;//$content_profile->field_profile_adresse[0]['postal_code'];
  $values["ville"]          = $random;//$content_profile->field_profile_adresse[0]['city'];
  $values["departement_id"] = 30;
  $values["pays_id"]        = 1;
  $values["phone"]          = null;
  $values["phone_perso"]    = null;
  $values["phone_mobile"]   = null;
  $values["member_email"]   = $random. '@'. $random. '.com';
  $values["member_login"]   = $random;
  $values["password"]       = $random;
  $values["photo"]          = null;
  $values["comment"]        = null;
  $values["morphy"]         = 'phy'; // phy ou mor
  $values["cotisation"]     = 10;
  $values["public"]         = null;
  $values["userid"]         = null;
  $values["socid"]          = null;
  // options
  $values['options_dddd']  = $random;
  // payment
  $values['accountid']      = 1;
  $values['operation']      = 'PP';
  $values['label']          = 'Paiement cotisation par site fg';
  $values['num_chq']        = 'rrrrrr';

  $return = $client->createAdherent(serialize($values));
  print "<pre>";
  print_r($return);
  print "</pre>";
}

function updateAdherent() {
  $random = generatePassword();

  try {
    $client = new SoapClient(URI. '/webservices/DolWs/adherents/server.php?wsdl');
  } catch (SoapFault $fault) {
    echo 'erreur : '.$fault;
  }

  // fill values
  $values = array();
  $values['rowid']          = 23;
  $values["typeid"]         = 1;
  $values["civilite_id"]    = 0;
  $values["nom"]            = $random;
  $values["prenom"]         = $random;
  $values["societe"]        = $random;
  $values["adresse"]        = null;//$content_profile->field_profile_adresse[0]['street'];
  $values["cp"]             = null;//$content_profile->field_profile_adresse[0]['postal_code'];
  $values["ville"]          = null;//$content_profile->field_profile_adresse[0]['city'];
  $values["departement_id"] = null;
  $values["pays_id"]        = null;
  $values["phone"]          = null;
  $values["phone_perso"]    = null;
  $values["phone_mobile"]   = null;
  $values["member_email"]   = $random. '@'. $random. '.com';
  $values["member_login"]   = 'aaa';
  $values["password"]       = $random;
  $values["photo"]          = null;
  $values["comment"]        = null;
  $values["morphy"]         = 1;
  $values["cotisation"]     = 10;
  $values["public"]         = null;
  $values["userid"]         = null;
  $values["socid"]          = null;
  // payment
  $values['accountid']      = 1;
  $values['operation']      = 'PP';
  $values['label']          = 'Paiement cotisation par site fg';
  $values['num_chq']        = 'rrrrrr';

  $return = $client->updateAdherent(serialize($values));
  print "<pre>";
  print_r($return);
  print "</pre>";
}

function getAdherentId() {
  try {
    $client = new SoapClient(URI. '/webservices/DolWs/adherents/server.php?wsdl');
  } catch (SoapFault $fault) {
    echo 'erreur : '.$fault;
  }

  $field = 'login';
  $value = "'aaa'";
  $options = FALSE;
  $field = 'uuid';$value = "'wxcv'";$options = TRUE;
  $where = '';

  $return = $client->getAdherentId($field, $value, $where, $options);
  print "<pre>";
  print_r($return);
  print "</pre>";
}

function createSociete() {
  $random = generatePassword();

  try {
    $client = new SoapClient(URI. '/webservices/DolWs/societe/server.php?wsdl');
  } catch (SoapFault $fault) {
    echo 'erreur : '.$fault;
  }

  // fill values
  $values = array();

  $values["private"]                = 1; // particulier
  $values["nom"]                    = $random; // nom_particulier * obligatoire
  $values["prenom"]                 = $random;
  $values["civilite_id"]            = 'MR'; // MME MR MLE MTRE

  $values["adresse"]                = $random;
  $values["cp"]                     = $random;
  $values["ville"]                  = $random;
  $values["pays_id"]                = 1;
  $values["departement_id"]         = 30;
  $values["tel"]                    = '';
  $values["fax"]                    = '';
  $values["email"]                  = $random. '@'. $random. '.com'; // * obligatoire
  $values["url"]                    = '';
  $values["idprof1"]                = ''; // siren
  $values["idprof2"]                = ''; // siret
  $values["idprof3"]                = ''; // ape
  $values["idprof4"]                = ''; // idprof4
  $values["prefix_comm"]            = '';
  $values["code_client"]            = '';
  $values["code_fournisseur"]       = '';
  $values["capital"]                = '';
  $values["gencod"]                 = '';

  $values["assujtva_value"]         = '';

  // Local Taxes
  $values["localtax1assuj_value"]   = '';
  $values["localtax2assuj_value"]   = '';

  $values["tva_intra"]              = '';

  $values["forme_juridique_code"]   = '';
  $values["effectif_id"]            = '';
  $values["typent_id"]              = '';
  $values["client"]                 = 1;
  $values["fournisseur"]            = 0;
  $values["fournisseur_categorie"]  = '';

  $values["commercial_id"]          = '';
  $values["default_lang"]           = '';

  $return = $client->createSociete(serialize($values));
  print "<pre>";
  print_r($return);
  print "</pre>";
}

function createContrat() {
  $random = generatePassword();

  try {
    $client = new SoapClient(URI. '/webservices/DolWs/contrat/server.php?wsdl');
  } catch (SoapFault $fault) {
    echo 'erreur : '.$fault;
  }

  // fill values
  $values = array();

  $values["socid"]                    = 32;
  $values["commercial_suivi_id"]      = 1;
  $values["commercial_signature_id"]  = 1;
  $values["note"]                     = '';
  $values["projectid"]                = 0; // fk_project
  $values["remise_percent"]           = 0;
  $values["ref"]                      = null;
  $values['mode']                     = 'libre'; // predefined / libre

  $return = $client->createContrat(serialize($values));
  print "<pre>";
  print_r($return);
  print "</pre>";
}

function addLigne() {
  $random = generatePassword();

  try {
    $client = new SoapClient(URI. '/webservices/DolWs/contrat/server.php?wsdl');
  } catch (SoapFault $fault) {
    echo 'erreur : '.$fault;
  }

  // fill values
  $values = array();

  $values['mode']       = 'libre';
  $values['pu']         = 1;
  $values['desc']       = 'essai';
  $values['contrat_id'] = 12;
  $values['pqty']       = 1;

  $return = $client->addLigne(serialize($values));
  print "<pre>";
  print_r($return);
  print "</pre>";
}

function generatePassword($length=5, $strength=0) {
  $vowels = 'aeuy';
  $consonants = 'bdghjmnpqrstvz';
  if ($strength & 1) {
    $consonants .= 'BDGHJLMNPQRSTVWXZ';
  }
  if ($strength & 2) {
    $vowels .= "AEUY";
  }
  if ($strength & 4) {
    $consonants .= '23456789';
  }
  /*if ($strength & 8) {
    $consonants .= '@#$%';
  }*/
 
  $password = '';
  $alt = time() % 2;
  for ($i = 0; $i < $length; $i++) {
    if ($alt == 1) {
      $password .= $consonants[(rand() % strlen($consonants))];
      $alt = 0;
    } else {
      $password .= $vowels[(rand() % strlen($vowels))];
      $alt = 1;
    }
  }
  return $password;
}
