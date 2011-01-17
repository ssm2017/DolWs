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
require_once(DOL_DOCUMENT_ROOT."/comm/action/class/actioncomm.class.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/contact.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");

$langs->load("companies");
$langs->load("users");

class DolWsContact {
  var $success  = FALSE;
  var $message  = '';
  var $data     = '';

  function createContact($values) {
    global $conf, $langs, $db, $user;
    $contact = new Contact($db);

    $contact->socid        = $values["socid"];
    $contact->name         = $values["name"];
    $contact->firstname    = $values["firstname"];
    $contact->civilite_id  = $values["civilite_id"];
    $contact->poste        = $values["poste"];
    $contact->address      = $values["address"];
    $contact->cp           = $values["cp"];
    $contact->ville        = $values["ville"];
    $contact->fk_pays      = $values["pays_id"];
    $contact->fk_departement = $values["departement_id"];
    $contact->email        = $values["email"];
    $contact->phone_pro    = $values["phone_pro"];
    $contact->phone_perso  = $values["phone_perso"];
    $contact->phone_mobile = $values["phone_mobile"];
    $contact->fax          = $values["fax"];
    $contact->jabberid     = $values["jabberid"];
    $contact->priv         = $values["priv"];

    $contact->note         = $values["note"];

    if (!$values["name"]) {
      $this->success = FALSE;
      $this->message .= 'createContact : '. 'Erreur : '. $langs->trans("ErrorFieldRequired",$langs->transnoentities("Lastname").' / '.$langs->transnoentities("Label"));
    }

    if ($values["name"]) {
      $id =  $contact->create($user);
      if ($id > 0) {
            $this->success = TRUE;
            $this->message .= 'createContact : '. "Contact ajouté.<br/>\n";
      }
      else {
        $this->success = FALSE;
        $this->message .= 'createContact : '. "Erreur : ". $contact->error. ".<br/>\n";
      }
    }
  }
  function updateContact($values) {
    global $conf, $langs, $db, $user;

    if (empty($values["name"])) {
      $this->succes = FALSE;
      $this->message .= 'updateContact : '. 'Erreur : '. $langs->trans("ErrorFieldRequired",$langs->transnoentities("Name").' / '.$langs->transnoentities("Label")). '<br/>\n';
      return;
    }

    $contact = new Contact($db);
    $contact->fetch($values["contactid"]);

    $contact->oldcopy       = dol_clone($contact);
    $contact->old_name      = $values["old_name"];
    $contact->old_firstname = $values["old_firstname"];
    $contact->socid         = $values["socid"];
    $contact->name          = $values["name"];
    $contact->firstname     = $values["firstname"];
    $contact->civilite_id   = $values["civilite_id"];
    $contact->poste         = $values["poste"];
    $contact->address       = $values["address"];
    $contact->cp            = $values["cp"];
    $contact->ville         = $values["ville"];
    $contact->fk_departement= $values["departement_id"];
    $contact->fk_pays       = $values["pays_id"];
    $contact->email         = $values["email"];
    $contact->phone_pro     = $values["phone_pro"];
    $contact->phone_perso   = $values["phone_perso"];
    $contact->phone_mobile  = $values["phone_mobile"];
    $contact->fax           = $values["fax"];
    $contact->jabberid      = $values["jabberid"];
    $contact->priv          = $values["priv"];
    $contact->note          = $values["note"];

    $result = $contact->update($values["contactid"], $user);

    if ($result > 0) {
      $contact->old_name='';
      $contact->old_firstname='';
      $this->succes = TRUE;
      $this->message .= 'updateContact : '. 'Contact mis à jour.<br/>\n';
    }
    else {
      $this->succes = FALSE;
      $this->message .= 'updateContact : '. 'Erreur : '. $contact->error. '<br/>\n';
    }
  }
  function getContactId($field, $value, $where = '') {
    global $conf, $langs, $db, $user;

    if (empty($where)) {
      $where = $field. "=". $value;
    }

    $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX. "socpeople WHERE $where";

    $result = $db->query($sql);
    if ($result) {
      $rowid  = $db->fetch_object($result)->rowid;
      if ($rowid) {
        $this->success = TRUE;
        $this->message .= 'getContactId : '. 'Success';
        $this->data = $rowid;
      }
    }
    else {
      $this->success = FALSE;
      $this->message .= 'getContactId : '. 'Erreur : '. $db->error();
    }
  }
}
