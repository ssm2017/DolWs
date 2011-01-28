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
  var $data     = array();

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
        $this->message .= 'createContact : '. "Contact ajouté : ". $contact->id. "|";
        $this->data['contact']['id'] = $contact->id;
        $this->data['contact']['obj'] = $contact;
      }
      else {
        $this->success = FALSE;
        $this->message .= 'createContact : '. "Erreur : ". $contact->error. ".|";
      }
    }
  }

  function updateContact($values) {
    global $conf, $langs, $db, $user;

    if (empty($values["name"])) {
      $this->succes = FALSE;
      $this->message .= 'updateContact : '. 'Erreur : '. $langs->trans("ErrorFieldRequired",$langs->transnoentities("Name").' / '.$langs->transnoentities("Label")). '|';
      return;
    }

    $contact = new Contact($db);
    $contact->fetch($values["contactid"]);
    if (!$contact->id) {
      $this->success = FALSE;
      $this->message .= "Le contact n'existe pas : ". $values["contactid"]. "|";
      return;
    }

    $contact->oldcopy       = dol_clone($contact);
    $contact->old_name      = isset($values["old_name"])        ? $values["old_name"] :       $contact->old_name;
    $contact->old_firstname = isset($values["old_firstname"])   ? $values["old_firstname"] :  $contact->old_firstname;
    $contact->socid         = isset($values["socid"])           ? $values["socid"] :          $contact->socid;
    $contact->name          = isset($values["name"])            ? $values["name"] :           $contact->name;
    $contact->firstname     = isset($values["firstname"])       ? $values["firstname"] :      $contact->firstname;
    $contact->civilite_id   = isset($values["civilite_id"])     ? $values["civilite_id"] :    $contact->civilite_id;
    $contact->poste         = isset($values["poste"])           ? $values["poste"] :          $contact->poste;
    $contact->address       = isset($values["address"])         ? $values["address"] :        $contact->address;
    $contact->cp            = isset($values["cp"])              ? $values["cp"] :             $contact->cp;
    $contact->ville         = isset($values["ville"])           ? $values["ville"] :          $contact->ville;
    $contact->fk_departement= isset($values["departement_id"])  ? $values["departement_id"] : $contact->fk_departement;
    $contact->fk_pays       = isset($values["pays_id"])         ? $values["pays_id"] :        $contact->fk_pays;
    $contact->email         = isset($values["email"])           ? $values["email"] :          $contact->email;
    $contact->phone_pro     = isset($values["phone_pro"])       ? $values["phone_pro"] :      $contact->phone_pro;
    $contact->phone_perso   = isset($values["phone_perso"])     ? $values["phone_perso"] :    $contact->phone_perso;
    $contact->phone_mobile  = isset($values["phone_mobile"])    ? $values["phone_mobile"] :   $contact->phone_mobile;
    $contact->fax           = isset($values["fax"])             ? $values["fax"] :            $contact->fax;
    $contact->jabberid      = isset($values["jabberid"])        ? $values["jabberid"] :       $contact->jabberid;
    $contact->priv          = isset($values["priv"])            ? $values["priv"] :           $contact->priv;
    $contact->note          = isset($values["note"])            ? $values["note"] :           $contact->note;

    $result = $contact->update($values["contactid"], $user);

    if ($result > 0) {
      $contact->old_name='';
      $contact->old_firstname='';
      $this->succes = TRUE;
      $this->message .= 'updateContact : '. 'Contact mis à jour : '. $contact->id. '|';
      $this->data['contact']['id'] = $contact->id;
      $this->data['contact']['obj'] = $contact;
    }
    else {
      $this->succes = FALSE;
      $this->message .= 'updateContact : '. 'Erreur : '. $contact->error. '|';
    }
  }
  function getContactId($field, $value, $where = '') {
    global $conf, $langs, $db, $user;

    if (empty($where)) {
      $where = "TRIM(". $field. ")=". $value;
    }

    $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX. "socpeople WHERE $where";

    $result = $db->query($sql);
    if ($result) {
      $rowid  = $db->fetch_object($result);
      if ($rowid) {
        $this->success = TRUE;
        $this->message .= 'getContactId : '. 'Success';
        $this->data['contact']['id'] = $rowid->rowid;
      }
    }
    else {
      $this->success = FALSE;
      $this->message .= 'getContactId : '. 'Erreur : '. $db->error();
    }
  }
}
