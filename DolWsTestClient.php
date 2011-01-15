<?php
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
}

function addAdherent() {
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
  $values["adresse"]        = null;//$content_profile->field_profile_adresse[0]['street'];
  $values["cp"]             = null;//$content_profile->field_profile_adresse[0]['postal_code'];
  $values["ville"]          = null;//$content_profile->field_profile_adresse[0]['city'];
  $values["departement_id"] = null;
  $values["pays_id"]        = null;
  $values["phone"]          = null;
  $values["phone_perso"]    = null;
  $values["phone_mobile"]   = null;
  $values["member_email"]   = $random. '@'. $random. '.com';
  $values["member_login"]   = $random;
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

  $return = $client->addAdherent(serialize($values));
  print "<pre>";
  print_r($return);
  print "</pre>";
}

function addSociete() {
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
  $values["prenom"]                 = '';
  $values["civilite_id"]            = '';

  $values["adresse"]                = '';
  $values["cp"]                     = '';
  $values["ville"]                  = '';
  $values["pays_id"]                = '';
  $values["departement_id"]         = '';
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

  $return = $client->addSociete(serialize($values));
  print "<pre>";
  print_r($return);
  print "</pre>";
}

function addContrat() {
  $random = generatePassword();

  try {
    $client = new SoapClient(URI. '/webservices/DolWs/contrat/server.php?wsdl');
  } catch (SoapFault $fault) {
    echo 'erreur : '.$fault;
  }

  // fill values
  $values = array();

  $values["socid"]                    = 2;
  $values["commercial_suivi_id"]      = 1;
  $values["commercial_signature_id"]  = 1;
  $values["note"]                     = '';
  $values["projectid"]                = 0; // fk_project
  $values["remise_percent"]           = 0;
  $values["ref"]                      = null;

  $return = $client->addContrat(serialize($values));
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
