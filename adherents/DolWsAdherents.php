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
  var $data     = '';

  function createAdherent($values) {
    global $conf, $langs, $db;

    $adh = new Adherent($db);
    
    // check if already exists
    $adh->fetch_login($values["member_login"]);
    if ($adh->id) {
      $this->message .= "createAdherent : L'utilisateur existe déjà.<br/>\n";
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
      $this->message .= 'createAdherent : '. $langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Person"))."<br>\n";
      $this->success = FALSE;
      return;
    }

    // Test si le login existe deja
    if (empty($adh->login)) {
      $this->message .= 'createAdherent : '. $langs->trans("ErrorFieldRequired",$langs->trans("Login"))."<br>\n";
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
        $this->message .= 'createAdherent : '. $langs->trans("ErrorLoginAlreadyExists",$login)."<br>\n";
        $this->success = FALSE;
        return;
      }
    }

    if (empty($adh->nom)) {
      $langs->load("errors");
      $this->message .= 'createAdherent : '. $langs->trans("ErrorFieldRequired",$langs->transnoentities("Lastname"))."<br>\n";
      $this->success = FALSE;
      return;
    }

    if ($adh->morphy != 'mor' && (!isset($adh->prenom) || $adh->prenom=='')) {
      $langs->load("errors");
      $this->message .= 'createAdherent : '. $langs->trans("ErrorFieldRequired",$langs->transnoentities("Firstname"))."<br>\n";
      $this->success = FALSE;
      return;
    }

    if (! ($adh->typeid > 0)) {  // Keep () before !
      $this->message .= 'createAdherent : '. $langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Type"))."<br>\n";
      $this->success = FALSE;
      return;
    }

    if ($conf->global->ADHERENT_MAIL_REQUIRED && ! isValidEMail($adh->email)) {
      $langs->load("errors");
      $this->message .= 'createAdherent : '. $langs->trans("ErrorBadEMail",$adh->email)."<br>\n";
      $this->message .= 'createAdherent : '. $adh->email."<br>\n";
      $this->success = FALSE;
      return;
    }

    if (empty($adh->pass)) {
      $this->message .= 'createAdherent : '. $langs->trans("ErrorFieldRequired",$langs->transnoentities("Password"))."<br>\n";
      $this->success = FALSE;
      return;
    }

    if (isset($adh->public)) $adh->public = 1;

    $db->begin();
    $result = $adh->create($user);

    if ($result > 0) {

      if ($adh->cotisation > 0) {
        $values['adhid'] = $adh->id;
        $this->createCardSubscription($values);
      }
      else {
        $this->message .= 'createAdherent : '. 'Cotisation inferieure a 1.'. "<br>\n";
      }
      
      $db->commit();
      $rowid  = $adh->id;
      $this->message .= 'createAdherent : '. "Adherent ajouté.<br>\n";
      $this->data = $rowid;
    }
    else {
      $db->rollback();
      if ($adh->error) $this->message .= 'createAdherent : '. $adh->error;
      else $this->message .= 'createAdherent : '. $adh->errors[0];
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
      $this->message .= 'DolWsAdherents:updateAdherent : '. "L'adherent n'existe pas.<br/>\n";
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
        $this->message .= 'DolWsAdherents::updateAdherent : '. "L'adhérent a été mis à jour : ". $adh->id. '<br/>\n';
        if ($adh->cotisation > 0) {
          $values['adhid'] = $adh->id;
          $this->createCardSubscription($values);
        }
        else {
          $this->message .= 'DolWsAdherents::createAdherent : '. 'Cotisation inferieure a 1.'. "<br>\n";
        }
      }
      else {
        if ($adh->error) {
          $errmsg=$adh->error;
        }
        else {
          foreach($adh->errors as $error) {
            if ($errmsg) $errmsg.='<br>';
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
      $this->message .= 'DolWsAdherents::updateAdherent : '. 'Erreur en mettant à jour une note. : '. $adh->error.'<br/>\n';
      $db->rollback();
    }
    else {
      $db->commit();
      $this->message .= 'DolWsAdherents::updateAdherent : '. 'Note mise à jour.<br/>\n';
    }
  }
  function createCardSubscription($values) {
    global $conf, $langs, $db;
    $langs->load("banks");

    $adh = new Adherent($db);
    $adho = new AdherentOptions($db);
    $adht = new AdherentType($db);

    //$adh->id  = $values['adhid'];
    $result   = $adh->fetch($values['adhid']);
    if ($result < 1) {
      $this->success = FALSE;
      $this->message .= 'DolWsAdherents::createCardSubscription : '. 'Erreur : '. $adh->error. '<br/>\n';
      $this->data = $values['adhid'];
      return;
    }

    $adht->fetch($adh->typeid);

    // Subscription informations
    $datecotisation = 0;
    $datesubend     = 0;
    if (isset($values["reyear"]) && isset($values["remonth"]) && isset($values["reday"])) {
      $datecotisation = dol_mktime(0, 0, 0, $values["remonth"], $values["reday"], $values["reyear"]);
    }
    if (isset($values["endyear"]) && isset($values["endmonth"]) && isset($values["endday"])) {
      $datesubend = dol_mktime(0, 0, 0, $values["endmonth"], $values["endday"], $values["endyear"]);
    }

    if (!$datecotisation) {
      $this->success = FALSE;
      $this->message .= 'DolWsAdherents::createCardSubscription : '. $langs->trans("BadDateFormat"). '<br/>\n';
      $this->data = $datecotisation;
      return;
    }
    if (!$datesubend) {
      $datesubend=dol_time_plus_duree(dol_time_plus_duree($datecotisation,$defaultdelay,$defaultdelayunit),-1,'d');
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
        $this->message .= 'DolWsAdherents::createCardSubscription : '. $langs->trans("ErrorFieldRequired",$langs->transnoentities("Amount")). '<br/>\n';
        return;
      }
      else {
        if ($conf->banque->enabled && $conf->global->ADHERENT_BANK_USE) {
          if ($values["cotisation"]) {
            if (!$values["label"] || !$values["operation"] || !$values["accountid"]) {
              $this->success = FALSE;
              if (!$values["label"])     $this->message .= 'DolWsAdherents::createCardSubscription : '. $langs->trans("ErrorFieldRequired",$langs->transnoentities("Label")). '<br/>\n';
              if (!$values["operation"]) $this->message .= 'DolWsAdherents::createCardSubscription : '. $langs->trans("ErrorFieldRequired",$langs->transnoentities("PaymentMode")). '<br/>\n';
              if (!$values["accountid"]) $this->message .= 'DolWsAdherents::createCardSubscription : '. $langs->trans("ErrorFieldRequired",$langs->transnoentities("FinancialAccount")). '<br/>\n';
              return;
            }
          }
          else {
            $this->success = FALSE;
            if ($values["accountid"])   $this->message .= 'DolWsAdherents::createCardSubscription : '. $langs->trans("ErrorDoNotProvideAccountsIfNullAmount"). '<br/>\n';
          }
        }
      }
    }

    $db->begin();

    $crowid = $adh->cotisation($datecotisation, $cotisation, $accountid, $operation, $label, $num_chq, $emetteur_nom, $emetteur_banque, $datesubend);

    if ($crowid > 0) {
      $db->commit();
      $this->success = TRUE;
      $this->message .= 'DolWsAdherents::createCardSubscription : '. 'Cotisation ajoutée avec succes : '. $crowid. '<br/>\n';
      $this->data = $crowid;
      // Envoi mail
      if ($values["sendmail"]) {
        $result = $adh->send_an_email($conf->global->ADHERENT_MAIL_COTIS,$conf->global->ADHERENT_MAIL_COTIS_SUBJECT,array(),array(),array(),"","",0,-1);
        if ($result < 0) $this->message .= 'createCardSubscription : '. $adh->error. '<br/>\n';
      }
    }
    else {
      $db->rollback();
      $this->success = FALSE;
      $this->message .= 'DolWsAdherents::createCardSubscription : '. 'Erreur : '. $adh->error. '<br/>\n';
    }
  }
  function getAdherentId($field, $value, $where = '', $options = FALSE) {
    global $conf, $langs, $db, $user;

    if (empty($where)) {
      $where = $field. "=". $value;
    }

    if ($options) {
      $sql = "SELECT fk_member FROM ".MAIN_DB_PREFIX. "adherent_options WHERE $where";
    }
    else {
      $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX. "adherent WHERE $where";
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
        $this->message .= 'DolWsAdherents::getAdherentId : '. 'Success : '. $rowid. "<br>\n";
        $this->data = $rowid;
      }
      else {
        $this->success = FALSE;
        $this->message .= 'DolWsAdherents::getAdherentId : '. 'error : '. $db->error(). "<br>\n";
      }
    }
    else {
      $this->success = FALSE;
      $this->message .= 'DolWsAdherents::getAdherentId : '. 'error : '. $db->error(). "<br>\n";
    }
  }
}
