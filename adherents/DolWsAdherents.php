<?php

class DolWsAdherents {

  var $success  = FALSE;
  var $message  = '';
  var $data     = '';

  function createAdherent($values) {

    global $conf, $langs, $db;
    require_once(DOL_DOCUMENT_ROOT."/lib/member.lib.php");
    require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
    require_once(DOL_DOCUMENT_ROOT."/lib/images.lib.php");
    require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");
    require_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent.class.php");
    require_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent_type.class.php");
    require_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent_options.class.php");
    require_once(DOL_DOCUMENT_ROOT."/adherents/class/cotisation.class.php");
    require_once(DOL_DOCUMENT_ROOT."/compta/bank/class/account.class.php");
    require_once(DOL_DOCUMENT_ROOT."/core/class/html.formcompany.class.php");

    $langs->load("companies");
    $langs->load("bills");
    $langs->load("members");
    $langs->load("users");
    
    $adh = new Adherent($db);
    
    // check if already exists
    $adh->fetch_login($values["member_login"]);
    if ($adh->id) {
      $this->message .= "L'utilisateur existe déjà.<br/>\n";
      $this->updateAdherent($values);
      return;
    }
    $adho = new AdherentOptions($db);

    $datenaiss='';
    if (isset($values["naissday"]) && $values["naissday"]
      && isset($values["naissmonth"]) && $values["naissmonth"]
      && isset($values["naissyear"]) && $values["naissyear"]) {
      $datenaiss=dol_mktime(12, 0, 0, $values["naissmonth"], $values["naissday"], $values["naissyear"]);
    }

    $datecotisation=time();
    if (isset($values["reday"]) && isset($values["remonth"]) && isset($values["reyear"])) {
      $datecotisation=dol_mktime(12, 0 , 0, $values["remonth"], $values["reday"], $values["reyear"]);
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
      $error++;
      $this->message .= $langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Person"))."<br>\n";
    }

    // Test si le login existe deja
    if (empty($adh->login)) {
      $error++;
      $this->message .= $langs->trans("ErrorFieldRequired",$langs->trans("Login"))."<br>\n";
    }
    else {
      $sql = "SELECT login FROM ".MAIN_DB_PREFIX."adherent WHERE login='".$adh->login."'";
      $result = $db->query($sql);
      if ($result) {
        $num = $db->num_rows($result);
      }
      if ($num) {
        $error++;
        $langs->load("errors");
        $this->message .= $langs->trans("ErrorLoginAlreadyExists",$login)."<br>\n";
      }
    }

    if (empty($adh->nom)) {
      $error++;
      $langs->load("errors");
      $this->message .= $langs->trans("ErrorFieldRequired",$langs->transnoentities("Lastname"))."<br>\n";
    }

    if ($adh->morphy != 'mor' && (!isset($adh->prenom) || $adh->prenom=='')) {
      $error++;
      $langs->load("errors");
      $this->message .= $langs->trans("ErrorFieldRequired",$langs->transnoentities("Firstname"))."<br>\n";
    }

    if (! ($adh->typeid > 0)) {  // Keep () before !
      $error++;
      $this->message .= $langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Type"))."<br>\n";
    }

    if ($conf->global->ADHERENT_MAIL_REQUIRED && ! isValidEMail($adh->email)) {
      $error++;
      $langs->load("errors");
      $this->message .= $langs->trans("ErrorBadEMail",$adh->email)."<br>\n";
      $this->message .= $adh->email."<br>\n";
    }

    if (empty($adh->pass)) {
      $error++;
      $this->message .= $langs->trans("ErrorFieldRequired",$langs->transnoentities("Password"))."<br>\n";
    }

    if (isset($adh->public)) $adh->public=1;

    if (! $error) {

      $db->begin();
      $result = $adh->create($user);

      if ($result > 0) {

        if ($adh->cotisation > 0) {

          $crowid = $adh->cotisation($datecotisation, $adh->cotisation);

          // insertion dans la gestion banquaire si configure pour
          if ($conf->global->ADHERENT_BANK_USE) {

            $dateop = time();
            $amount = $adh->cotisation;
            $acct   = new Account($db,$values["accountid"]);
            $insertid = $acct->addline($dateop, $values["operation"], $values["label"], $amount, $values["num_chq"], '', $user);
            if ($insertid == '') {
              $this->message .= "Erreur ajout d'entrée banquaire."."<br>\n";
              dol_print_error($db);
            }
            else {
              // met a jour la table cotisation
              $sql ="UPDATE ".MAIN_DB_PREFIX."cotisation";
              $sql.=" SET fk_bank=$insertid WHERE rowid=$crowid ";
              $result = $db->query($sql);
              if ($result) {
                $this->message .= 'Cotisation ajoutée.'."<br>\n";
              }
              else {
                $this->message .= 'Erreur ajout de cotisation.'."<br>\n";
                dol_print_error($db);
              }
            }
          }
          else {
            $this->message .= 'Cotisation inactive.'."<br>\n";
          }
        }
        else {
          $this->message .= 'Cotisation inferieure a 1.'. "<br>\n";
        }
        
        $db->commit();
        $rowid  = $adh->id;
        $this->message .= 'Adherent ajouté. ID = '. $rowid. "<br>\n";
      }
      else {
        $db->rollback();
        if ($adh->error) $this->message .= $adh->error;
        else $this->message .= $adh->errors[0];
      }
    }
    else {
      $this->success = FALSE;
    }
  }
  function updateAdherent($values) {
    global $conf, $langs, $db, $user;
    require_once(DOL_DOCUMENT_ROOT."/lib/member.lib.php");
    require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
    require_once(DOL_DOCUMENT_ROOT."/lib/images.lib.php");
    require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");
    require_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent.class.php");
    require_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent_type.class.php");
    require_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent_options.class.php");
    require_once(DOL_DOCUMENT_ROOT."/adherents/class/cotisation.class.php");
    require_once(DOL_DOCUMENT_ROOT."/compta/bank/class/account.class.php");
    require_once(DOL_DOCUMENT_ROOT."/core/class/html.formcompany.class.php");

    $adh = new Adherent($db);
    if (isset($values['rowid']) && $values['rowid'] > 0) {
      $adh->fetch($values['rowid']);
    }

    //$adh->fetch_login($values["member_login"]);
    if (!$adh->id) {
      $this->message .= "L'utilisateur n'existe pas.<br/>\n";
      $this->createAdherent($values);
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
    if (isset($values["reday"]) && isset($values["remonth"]) && isset($values["reyear"])) {
      $datecotisation=dol_mktime(12, 0 , 0, $values["remonth"], $values["reday"], $values["reyear"]);
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
        $this->message .= "L'adhérent a été mis à jour.<br/>\n";
        if ($adh->cotisation > 0) {

          $crowid = $adh->cotisation($datecotisation, $adh->cotisation);

          // insertion dans la gestion banquaire si configure pour
          if ($conf->global->ADHERENT_BANK_USE) {

            $dateop = time();
            $amount = $adh->cotisation;
            $acct   = new Account($db,$values["accountid"]);
            $insertid = $acct->addline($dateop, $values["operation"], $values["label"], $amount, $values["num_chq"], '', $user);
            if ($insertid == '') {
              $this->message .= "Erreur ajout d'entrée banquaire."."<br>\n";
              dol_print_error($db);
            }
            else {
              // met a jour la table cotisation
              $sql ="UPDATE ".MAIN_DB_PREFIX."cotisation";
              $sql.=" SET fk_bank=$insertid WHERE rowid=$crowid ";
              $result = $db->query($sql);
              if ($result) {
                $this->message .= 'Cotisation ajoutée.'."<br>\n";
              }
              else {
                $this->message .= 'Erreur ajout de cotisation.'."<br>\n";
                dol_print_error($db);
              }
            }
          }
          else {
            $this->message .= 'Cotisation inactive.'."<br>\n";
          }
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
  function getAdherentId($field, $value, $where) {
    global $conf, $langs, $db, $user;

    if (empty($where)) {
      $where = $field. "=". $value;
    }

    $value = $values['value'];
    $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."adherent WHERE $where";
    $result = $db->query($sql);
    if ($result) {
      $rowid  = $db->fetch_object($result)->rowid;
      if ($rowid) {
        $this->success = TRUE;
        $this->message .= 'Success';
        $this->data = $rowid;
      }
    }
    else {
      $this->success = FALSE;
      $this->message .= 'error : '. $db->error();
    }
  }
}
