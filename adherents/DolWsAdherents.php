<?php
/**
 * @package DolWs
 * @copyright Copyright (C) 2011 Wene / ssm2017 Binder (S.Massiaux). All rights reserved.
 * @license   GNU/GPL, http://www.gnu.org/licenses/gpl-2.0.html
 * DolWs is free software. This version may have been modified pursuant to the GNU General Public License,
 * and as distributed it includes or is derivative of works licensed under the GNU General Public License
 * or other free or open source software licenses.
 */
 
global $langs;
require_once(DOL_DOCUMENT_ROOT."/lib/member.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/images.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent_type.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent_options.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/class/cotisation.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/bank/class/account.class.php");

$langs->load("companies");
$langs->load("bills");
$langs->load("members");
$langs->load("users");
$langs->load("errors");

class DolWsAdherents {

  var $success  = FALSE;
  var $message  = '';
  var $data     = array();

  function createAdherent($values) {
    global $conf, $langs, $db;

    $adh = new Adherent($db);
    
    // check if already exists
    $adh->fetch_login($values["member_login"]);
    if ($adh->id) {
      $this->message .= "createAdherent : L'utilisateur existe déjà.|";
      $this->updateAdherent($values);
      return;
    }
    $adho = new AdherentOptions($db);

    $datenaiss='';
    if (isset($values["naissday"]) && $values["naissday"]
      && isset($values["naissmonth"]) && $values["naissmonth"]
      && isset($values["naissyear"]) && $values["naissyear"]) {
      $datenaiss = dol_mktime(12, 0, 0, $values["naissmonth"], $values["naissday"], $values["naissyear"]);
    }

    $datecotisation=time();
    if (isset($values["reyear"]) && isset($values["remonth"]) && isset($values["reday"])) {
      $datecotisation = dol_mktime(0, 0, 0, $values["remonth"], $values["reday"], $values["reyear"]);
    }
    if (isset($values["endyear"]) && isset($values["endmonth"]) && isset($values["endday"])) {
      $datesubend = dol_mktime(0, 0, 0, $values["endmonth"], $values["endday"], $values["endyear"]);
    }

    $adh->cotisation  = $values["cotisation"];

    $adh->civilite_id = $values["civilite_id"];
    $adh->prenom      = $values["prenom"];
    $adh->nom         = $values["nom"];
    $adh->societe     = $values["societe"];
    $adh->adresse     = $values["adresse"];
    $adh->cp          = $values["cp"];
    $adh->ville       = $values["ville"];
    $adh->fk_departement = $values["departement_id"];
    $adh->pays_id     = $values["pays_id"];
    $adh->phone       = $values["phone"];
    $adh->phone_perso = $values["phone_perso"];
    $adh->phone_mobile= $values["phone_mobile"];
    $adh->email       = $values["member_email"];
    $adh->login       = $values["member_login"];
    $adh->pass        = $values["password"];
    $adh->naiss       = $datenaiss;
    $adh->photo       = $values["photo"];
    $adh->typeid      = $values["typeid"];
    $adh->note        = $values["comment"];
    $adh->morphy      = $values["morphy"];
    $adh->user_id     = $values["userid"];
    $adh->fk_soc      = $values["socid"];
    $adh->public      = 0;//$values["public"];
    $adh->statut      = 1;

    foreach($values as $key => $value) {
      if (preg_match("/^options_/",$key)) {
        //escape values from POST, at least with addslashes, to avoid obvious SQL injections
        //(array_options is directly input in the DB in adherent.class.php::update())
        $adh->array_options[$key]=addslashes($value);
      }
    }

    // Check parameters
    if (empty($adh->morphy) ||$adh->morphy == "-1") {
      $this->message .= 'DolWsAdherents::createAdherent : '. $langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Person"))."|";
      $this->success = FALSE;
      return;
    }

    // Test si le login existe deja
    if (empty($adh->login)) {
      $this->message .= 'DolWsAdherents::createAdherent : '. $langs->trans("ErrorFieldRequired",$langs->trans("Login"))."|";
      $this->success = FALSE;
      return;
    }
    else {
      $sql = "SELECT login FROM ".MAIN_DB_PREFIX."adherent WHERE login='".$adh->login."'";
      $result = $db->query($sql);
      if ($result) {
        $num = $db->num_rows($result);
      }
      if ($num) {
        $this->message .= 'DolWsAdherents::createAdherent : '. $langs->trans("ErrorLoginAlreadyExists",$login)."|";
        $this->success = FALSE;
        return;
      }
    }

    if (empty($adh->nom)) {
      $langs->load("errors");
      $this->message .= 'DolWsAdherents::createAdherent : '. $langs->trans("ErrorFieldRequired",$langs->transnoentities("Lastname"))."|";
      $this->success = FALSE;
      return;
    }

    if ($adh->morphy != 'mor' && (!isset($adh->prenom) || $adh->prenom=='')) {
      $langs->load("errors");
      $this->message .= 'DolWsAdherents::createAdherent : '. $langs->trans("ErrorFieldRequired",$langs->transnoentities("Firstname"))."|";
      $this->success = FALSE;
      return;
    }

    if (! ($adh->typeid > 0)) {  // Keep () before !
      $this->message .= 'DolWsAdherents::createAdherent : '. $langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Type"))."|";
      $this->success = FALSE;
      return;
    }

    if ($conf->global->ADHERENT_MAIL_REQUIRED && ! isValidEMail($adh->email)) {
      $langs->load("errors");
      $this->message .= 'DolWsAdherents::createAdherent : '. $langs->trans("ErrorBadEMail",$adh->email)."|";
      $this->message .= 'DolWsAdherents::createAdherent : '. $adh->email."|";
      $this->success = FALSE;
      return;
    }

    if (empty($adh->pass)) {
      $this->message .= 'DolWsAdherents::createAdherent : '. $langs->trans("ErrorFieldRequired",$langs->transnoentities("Password"))."|";
      $this->success = FALSE;
      return;
    }

    //if (isset($adh->public)) $adh->public = 1;

    $db->begin();
    $result = $adh->create($user);

    if ($result > 0) {

      if ($adh->cotisation > 0) {
        $values['adhid'] = $adh->id;
        $this->createCardSubscription($values);
      }
      else {
        $this->message .= 'DolWsAdherents::createAdherent : '. 'Cotisation inferieure a 1.'. "|";
      }

      $db->commit();
      $adh->id;
      $this->message .= 'DolWsAdherents::createAdherent : '. "Adherent ajouté : ". $adh->id. "|";
      $this->data['adherent']['id'] = $adh->id;
      $this->data['adherent']['obj'] = $adh;
    }
    else {
      $db->rollback();
      if ($adh->error) $this->message .= 'DolWsAdherents::createAdherent : '. $adh->error;
      else $this->message .= 'DolWsAdherents::createAdherent : '. $adh->errors[0];
    }
  }

  function updateAdherent($values) {
    global $conf, $langs, $db, $user;

    $adh = new Adherent($db);
    if (isset($values['adhid']) && $values['adhid'] > 0) {
      $adh->fetch($values['adhid']);
    }

    //$adh->fetch_login($values["member_login"]);
    if (!$adh->id) {
      $this->message .= 'DolWsAdherents:updateAdherent : '. "L'adherent ". $values['adhid']. " n'existe pas.|";
      if ($values['create']) {
        $this->createAdherent($values);
      }
      return;
    }

    require_once(DOL_DOCUMENT_ROOT."/lib/files.lib.php");

    $datenaiss='';
    if (isset($values["naissday"]) && $values["naissday"]
      && isset($values["naissmonth"]) && $values["naissmonth"]
      && isset($values["naissyear"]) && $values["naissyear"]) {
      $datenaiss=dol_mktime(12, 0, 0, $values["naissmonth"], $values["naissday"], $values["naissyear"]);
    }

    $datecotisation=time();
    if (isset($values["reyear"]) && isset($values["remonth"]) && isset($values["reday"])) {
      $datecotisation = dol_mktime(0, 0, 0, $values["remonth"], $values["reday"], $values["reyear"]);
    }
    if (isset($values["endyear"]) && isset($values["endmonth"]) && isset($values["endday"])) {
      $datesubend = dol_mktime(0, 0, 0, $values["endmonth"], $values["endday"], $values["endyear"]);
    }
    

    // Create new object
    if ($adh->id > 0) {
      $adh->oldcopy=dol_clone($adh);

      // Change values
      $adh->civilite_id = (isset($values["civilite_id"]) && trim($values["civilite_id"]) != $adh->civilite_id) ?trim($values["civilite_id"]) : $adh->civilite_id;
      $adh->prenom      = (isset($values["prenom"]) && trim($values["prenom"]) != $adh->prenom) ?trim($values["prenom"]) : $adh->prenom;
      $adh->nom         = (isset($values["nom"]) && trim($values["nom"]) != $adh->nom) ?trim($values["nom"]) : $adh->nom;
      $adh->login       = (isset($values["login"]) && trim($values["login"]) != $adh->login) ?trim($values["login"]) : $adh->login;
      $adh->pass        = (isset($values["pass"]) && trim($values["pass"]) != $adh->pass) ?trim($values["pass"]) : $adh->pass;

      $adh->societe     = (isset($values["societe"]) && trim($values["societe"]) != $adh->societe) ?trim($values["societe"]) : $adh->societe;
      $adh->adresse     = (isset($values["adresse"]) && trim($values["adresse"]) != $adh->adresse) ?trim($values["adresse"]) : $adh->adresse;
      $adh->cp          = (isset($values["cp"]) && trim($values["cp"]) != $adh->cp) ?trim($values["cp"]) : $adh->cp;
      $adh->ville       = (isset($values["ville"]) && trim($values["ville"]) != $adh->ville) ?trim($values["ville"]) : $adh->ville;

      $adh->fk_departement = (isset($values["departement_id"]) && $values["departement_id"] != $adh->departement_id) ? $values["departement_id"] : $adh->departement_id;
      $adh->pays_id        = (isset($values["pays"]) && $values["pays"] != $adh->pays) ? $values["pays"] : $adh->pays;

      $adh->phone       = (isset($values["phone"]) && trim($values["phone"]) != $adh->phone) ?trim($values["phone"]) : $adh->phone;
      $adh->phone_perso = (isset($values["phone_perso"]) && trim($values["phone_perso"]) != $adh->phone_perso) ?trim($values["phone_perso"]) : $adh->phone_perso;
      $adh->phone_mobile= (isset($values["phone_mobile"]) && trim($values["phone_mobile"]) != $adh->phone_mobile) ?trim($values["phone_mobile"]) : $adh->phone_mobile;
      $adh->email       = (isset($values["email"]) && trim($values["email"]) != $adh->email) ?trim($values["email"]) : $adh->email;
      $adh->naiss       = $datenaiss;

      $adh->typeid      = (isset($values["typeid"]) && $values["typeid"] != $adh->typeid) ? $values["typeid"] : $adh->typeid;
      $adh->note        = (isset($values["comment"]) && trim($values["comment"]) != $adh->comment) ?trim($values["comment"]) : $adh->comment;
      $adh->morphy      = (isset($values["morphy"]) && $values["morphy"] != $adh->morphy) ? $values["morphy"] : $adh->morphy;

      $adh->amount      = (isset($values["amount"]) && $values["amount"] != $adh->amount) ? $values["amount"] : $adh->amount;

      // Get status and public property
      $adh->statut      = (isset($values["statut"]) && $values["statut"] != $adh->statut) ? $values["statut"] : $adh->statut;
      $adh->public      = (isset($values["public"]) && $values["public"] != $adh->public) ? $values["public"] : $adh->public;
      $adh->fk_soc      = (isset($values["socid"]) && $values["socid"] != $adh->socid) ? $values["socid"] : $adh->socid;

      $adh->cotisation  = isset($values["cotisation"]) ? $values["cotisation"] : 0;

      foreach($values as $key => $value) {
        if (preg_match("/^options_/",$key)) {
          //escape values from POST, at least with addslashes, to avoid obvious SQL injections
          //(array_options is directly input in the DB in adherent.class.php::update())
          $adh->array_options[$key]=addslashes($values[$key]);
        }
      }

      // Check if we need to also synchronize user information
      $nosyncuser=0;
      if ($adh->user_id) {  // If linked to a user
        if ($user->id != $adh->user_id && empty($user->rights->user->user->creer)) $nosyncuser=1;   // Disable synchronizing
      }

      // Check if we need to also synchronize password information
      $nosyncuserpass=0;
      if ($adh->user_id) {  // If linked to a user
        if ($user->id != $adh->user_id && empty($user->rights->user->user->password)) $nosyncuserpass=1;  // Disable synchronizing
      }

      $result = $adh->update($user, 0, $nosyncuser, $nosyncuserpass);

      if ($result >= 0 && ! sizeof($adh->errors)) {
        $this->success = TRUE;
        $this->message .= 'DolWsAdherents::updateAdherent : '. "L'adhérent a été mis à jour : ". $adh->id. '|';
        $this->data['adherent']['id'] = $adh->id;
        $this->data['adherent']['obj'] = $adh;
        return;
      }
      else {
        if ($adh->error) {
          $this->success = FALSE;
          $errmsg=$adh->error;
        }
        else {
          foreach($adh->errors as $error) {
            if ($errmsg) $errmsg.='|';
            $this->success = FALSE;
            $errmsg.=$error;
          }
        }
      }
      $this->message .= $errmsg;
    }
  }
  function updateNote($adh, $note) {
    global $user;
    $db->begin();

    $res = $adh->update_note($note, $user);
    if ($res < 0) {
      $this->message .= 'DolWsAdherents::updateAdherent : '. 'Erreur en mettant à jour une note. : '. $adh->error.'|';
      $db->rollback();
    }
    else {
      $db->commit();
      $this->message .= 'DolWsAdherents::updateAdherent : '. 'Note mise à jour.|';
    }
  }
  function createCardSubscription($values) {
    global $conf, $langs, $db;
    $langs->load("banks");

    $adh  = new Adherent($db);
    $adho = new AdherentOptions($db);
    $adht = new AdherentType($db);

    //$adh->id  = $values['adhid'];
    $result   = $adh->fetch($values['adhid']);
    if ($result < 1) {
      $this->success = FALSE;
      $this->message .= 'DolWsAdherents::createCardSubscription : '. 'Erreur : '. $adh->error. '|';
      $this->data['adherent']['id'] = $adh->id;
      $this->data['adherent']['obj'] = $adh;
      return;
    }

    $adht->fetch($adh->typeid);

    // Subscription informations
    $datecotisation = 0;
    $datesubend     = 0;
    if (isset($values["reyear"]) && isset($values["remonth"]) && isset($values["reday"])) {
      $datecotisation = dol_mktime(12, 0, 0, $values["remonth"], $values["reday"], $values["reyear"]);
    }
    if (isset($values["endyear"]) && isset($values["endmonth"]) && isset($values["endday"])) {
      $datesubend = dol_mktime(12, 0, 0, $values["endmonth"], $values["endday"], $values["endyear"]);
    }

    if (!$datecotisation) {
      $this->success = FALSE;
      $this->message .= 'DolWsAdherents::createCardSubscription : '. $langs->trans("BadDateFormat"). '|';
      $this->data['adherent']['datecotisation'] = $datecotisation;
      return;
    }
    // check if date already exists and if yes, adds one year
    $defaultdelay = 1;
    $defaultdelayunit = 'y';
    $result = $db->query("SELECT rowid FROM ". MAIN_DB_PREFIX. "cotisation WHERE dateadh='". $db->idate($datecotisation). "' AND fk_adherent=". $adh->id);
    if ($result) {
      $row = $db->fetch_object($result);
      if ($row) {
        // this date exists so we need to take the highest date and add 1 year
        $result2 = $db->query("SELECT datef FROM ". MAIN_DB_PREFIX. "cotisation WHERE fk_adherent=". $adh->id. " ORDER BY dateadh DESC");
        if ($result2) {
          $row2 = $db->fetch_object($result2);
          if ($row2) {
            $datecotisation = $db->jdate($row2->datef);
          }
        }
        //$datecotisation += 31622400; // 3600*24*365+(3600*24)
        //$datecotisation = dol_time_plus_duree($datecotisation,+1,'Y');
        //$datecotisation = dol_time_plus_duree($datecotisation,+1,'d');
        if ($datesubend) {
          //$datesubend = $datecotisation + 31536000; // (3600*24*365)-(3600*24)
          //$datesubend = dol_time_plus_duree($datecotisation,+1,'Y');
          //$datesubend = dol_time_plus_duree($datesubend,+1,'d');
          $datesubend = dol_time_plus_duree($datecotisation,$defaultdelay,$defaultdelayunit);
        }
      }
    }
    if (!$datesubend) {
      $datesubend = dol_time_plus_duree(dol_time_plus_duree($datecotisation,$defaultdelay,$defaultdelayunit),-1,'d');
    }

    // Payment informations
    $cotisation       = $values["cotisation"]; // Amount of subscription
    $label            = $values["label"];
    $accountid        = $values["accountid"];
    $operation        = $values["operation"]; // Payment mode
    $num_chq          = $values["num_chq"];
    $emetteur_nom     = $values["chqemetteur"];
    $emetteur_banque  = $values["chqbank"];

    if ($adht->cotisation) { // Type adherent soumis a cotisation 
      if (!($values["cotisation"] > 0)) {
        // If field is '' or not a numeric value
        $this->success = FALSE;
        $this->message .= 'DolWsAdherents::createCardSubscription : '. $langs->trans("ErrorFieldRequired",$langs->transnoentities("Amount")). '|';
        return;
      }
      else {
        if ($conf->banque->enabled && $conf->global->ADHERENT_BANK_USE) {
          if ($values["cotisation"]) {
            if (!$values["label"] || !$values["operation"] || !$values["accountid"]) {
              $this->success = FALSE;
              if (!$values["label"])     $this->message .= 'DolWsAdherents::createCardSubscription : '. $langs->trans("ErrorFieldRequired",$langs->transnoentities("Label")). '|';
              if (!$values["operation"]) $this->message .= 'DolWsAdherents::createCardSubscription : '. $langs->trans("ErrorFieldRequired",$langs->transnoentities("PaymentMode")). '|';
              if (!$values["accountid"]) $this->message .= 'DolWsAdherents::createCardSubscription : '. $langs->trans("ErrorFieldRequired",$langs->transnoentities("FinancialAccount")). '|';
              return;
            }
          }
          else {
            $this->success = FALSE;
            if ($values["accountid"])   $this->message .= 'DolWsAdherents::createCardSubscription : '. $langs->trans("ErrorDoNotProvideAccountsIfNullAmount"). '|';
          }
        }
      }
    }

    $db->begin();

    $crowid = $adh->cotisation($datecotisation, $cotisation, $accountid, $operation, $label, $num_chq, $emetteur_nom, $emetteur_banque, $datesubend);

    if ($crowid > 0) {
      $db->commit();
      $this->crowid   = $crowid;
      $this->success  = TRUE;
      $this->message .= 'DolWsAdherents::createCardSubscription : '. 'Cotisation ajoutée avec succes : '. $crowid. '|';
      $this->data['adherent']['id'] = $adh->id;
      $this->data['adherent']['obj'] = $adh;
      $this->data['cotisation']['id'] = $crowid;
      // Envoi mail
      if ($values["sendmail"]) {
        $result = $adh->send_an_email($conf->global->ADHERENT_MAIL_COTIS,$conf->global->ADHERENT_MAIL_COTIS_SUBJECT,array(),array(),array(),"","",0,-1);
        if ($result < 0) $this->message .= 'DolWsAdherents::createCardSubscription : '. $adh->error. '|';
      }
    }
    else {
      $db->rollback();
      $this->success = FALSE;
      $this->message .= 'DolWsAdherents::createCardSubscription : '. 'Erreur : '. $adh->error. ' '. $db->error. '|';
    }
  }
  function getAdherentId($field, $value, $where = '', $options = FALSE) {
    global $conf, $langs, $db, $user;

    if (empty($where)) {
      $where = "TRIM(". $field. ")=". $value;
    }

    if ($options) {
      $sql = "SELECT fk_member FROM ". MAIN_DB_PREFIX. "adherent_options WHERE $where";
    }
    else {
      $sql = "SELECT rowid FROM ". MAIN_DB_PREFIX. "adherent WHERE $where";
    }

    $result = $db->query($sql);
    if ($result) {
      $row = $db->fetch_object($result);
      if ($row) {
        if ($options) {
          $rowid = $row->fk_member;
        }
        else {
          $rowid = $row->rowid;
        }
        $this->success = TRUE;
        $this->message .= 'DolWsAdherents::getAdherentId : '. 'Success : '. $rowid. "|";
        $this->data['adherent']['id'] = $rowid;
      }
      else {
        $this->success = FALSE;
        $this->message .= 'DolWsAdherents::getAdherentId : '. 'error : '. $db->error(). "|";
      }
    }
    else {
      $this->success = FALSE;
      $this->message .= 'DolWsAdherents::getAdherentId : '. 'error : '. $db->error(). "|";
    }
  }
}
