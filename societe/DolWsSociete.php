<?php
/**
 * @package DolWs
 * @copyright Copyright (C) 2011 Wene / ssm2017 Binder (S.Massiaux). All rights reserved.
 * @license   GNU/GPL, http://www.gnu.org/licenses/gpl-2.0.html
 * DolWs is free software. This version may have been modified pursuant to the GNU General Public License,
 * and as distributed it includes or is derivative of works licensed under the GNU General Public License
 * or other free or open source software licenses.
 */
 
global $conf, $langs;
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
if ($conf->adherent->enabled) require_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent.class.php");

$langs->load("companies");
$langs->load("commercial");
$langs->load("bills");
$langs->load("banks");
$langs->load("users");

class DolWsSociete {
  var $success  = FALSE;
  var $message  = '';
  var $data     = '';

  function createSociete($values) {
    global $conf, $langs, $db, $user;

    // Initialization Company Object
    $soc = new Societe($db);

    if ($values["getcustomercode"]) {
      // We defined value code_client
      $values["code_client"]="Acompleter";
    }

    if ($values["getsuppliercode"]) {
      // We defined value code_fournisseur
      $values["code_fournisseur"]="Acompleter";
    }

    require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");
    $error = 0;

    if ($values["private"] == 1) {
      $soc->particulier         = $values["private"];
      $soc->nom                 = empty($conf->global->MAIN_FIRSTNAME_NAME_POSITION)?trim($values["prenom"].' '.$values["nom"]):trim($values["nom"].' '.$values["prenom"]);
      $soc->nom_particulier     = $values["nom"];
      $soc->prenom              = $values["prenom"];
      $soc->civilite_id         = $values["civilite_id"];
    }
    else {
      $soc->nom                 = $values["nom"];
    }
    $soc->address               = $values["adresse"];
    $soc->adresse               = $values["adresse"]; // TODO obsolete
    $soc->cp                    = $values["cp"];
    $soc->ville                 = $values["ville"];
    $soc->pays_id               = $values["pays_id"];
    $soc->departement_id        = $values["departement_id"];
    $soc->tel                   = $values["tel"];
    $soc->fax                   = $values["fax"];
    $soc->email                 = trim($values["email"]);
    $soc->url                   = $values["url"];
    $soc->siren                 = $values["idprof1"];
    $soc->siret                 = $values["idprof2"];
    $soc->ape                   = $values["idprof3"];
    $soc->idprof4               = $values["idprof4"];
    $soc->prefix_comm           = $values["prefix_comm"];
    $soc->code_client           = $values["code_client"];
    $soc->code_fournisseur      = $values["code_fournisseur"];
    $soc->capital               = $values["capital"];
    $soc->gencod                = $values["gencod"];
    $soc->note                  = $values['note'];

    $soc->tva_assuj             = $values["assujtva_value"];

    // Local Taxes
    $soc->localtax1_assuj       = $values["localtax1assuj_value"];
    $soc->localtax2_assuj       = $values["localtax2assuj_value"];

    $soc->tva_intra             = $values["tva_intra"];

    $soc->forme_juridique_code  = $values["forme_juridique_code"];
    $soc->effectif_id           = $values["effectif_id"];
    if ($values["private"] == 1) {
      $soc->typent_id           = 8; // TODO predict another method if the field "special" change of rowid
    }
    else {
      $soc->typent_id           = $values["typent_id"];
    }
    $soc->client                = $values["client"];
    $soc->fournisseur           = $values["fournisseur"];
    $soc->fournisseur_categorie = $values["fournisseur_categorie"];

    $soc->commercial_id         = $values["commercial_id"];
    $soc->default_lang          = $values["default_lang"];

    $db->begin();

    if (empty($soc->client))      $soc->code_client = '';
    if (empty($soc->fournisseur)) $soc->code_fournisseur = '';

    $result = $soc->create($user);
    if ($result >= 0) {
      if (!empty($soc->note)) {
        $sql = "UPDATE ".MAIN_DB_PREFIX."societe SET note='".addslashes($soc->note)."' WHERE rowid=".$soc->id;
        $db->query($sql);
      }
      if ($soc->particulier) {
        dol_syslog("This thirdparty is a personal people",LOG_DEBUG);
        $contact=new Contact($db);

        $contact->civilite_id = $soc->civilite_id;
        $contact->name        = $soc->nom_particulier;
        $contact->firstname   = $soc->prenom;
        $contact->address     = $soc->address;
        $contact->cp          = $soc->cp;
        $contact->ville       = $soc->ville;
        $contact->fk_pays     = $soc->fk_pays;
        $contact->socid       = $soc->id;         // fk_soc
        $contact->status      = 1;
        $contact->email       = $soc->email;
        $contact->priv        = 0;
        $contact->note        = $soc->note;

        $result = $contact->create($user);
      }
    }
    else {
      $this->success  = FALSE;
      $this->message .= print_r($result, true). 'DolWsSociete::createSociete : '. "La société n'a pas été crée car : ". join(',', $soc->errors). ' nom = '. $soc->nom. ' / '. $values['nom']. "|";
      return;
    }

    if ($result >= 0) {
      $db->commit();
      $this->success  = TRUE;
      $this->message .= 'DolWsSociete::createSociete : '. 'La société a été créée : '. $soc->id. '|';
      $this->data     = $soc->id;
      return;
    }
    else {
      $db->rollback();
      $langs->load("errors");
      $this->succes   = FALSE;
      $this->message .= 'DolWsSociete::createSociete : '. 'Erreur : '. $langs->trans($soc->error). "|";
    }
  }
  function updateSociete($values) {
    global $conf, $langs, $db, $user;

    // Initialization Company Object
    $soc = new Societe($db);
    $soc->fetch($values['socid']);

    if ($values["getcustomercode"]) {
      // We defined value code_client
      $values["code_client"]="Acompleter";
    }

    if ($values["getsuppliercode"]) {
      // We defined value code_fournisseur
      $values["code_fournisseur"]="Acompleter";
    }

    require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");
    $error = 0;

    $soc->nom     = isset($values['nom']) ? trim($values['nom']) : $soc->nom;
    $soc->prenom  = isset($values['prenom']) ? trim($values['prenom']) : $soc->prenom;
    if ($values["private"] == 1) {
      $soc->particulier         = $values["private"];
      $soc->nom                 = empty($conf->global->MAIN_FIRSTNAME_NAME_POSITION) ? $soc->prenom.' '. $soc->nom : $soc->nom.' '. $soc->prenom;
      $soc->nom_particulier     = $soc->nom;
      $soc->prenom              = $soc->prenom;
      $soc->civilite_id         = isset($values['civilite_id']) ? $values['civilite_id'] : $soc->civilite_id;
    }
    $soc->address               = isset($values['adresse']) ? $values['adresse'] : $soc->adresse;
    $soc->adresse               = isset($values['adresse']) ? $values['adresse'] : $soc->adresse; // TODO obsolete
    $soc->cp                    = isset($values['cp']) ? $values['cp'] : $soc->cp;
    $soc->ville                 = isset($values['ville']) ? $values['ville'] : $soc->ville;
    $soc->pays_id               = isset($values['pays_id']) ? $values['pays_id'] : $soc->pays_id;
    $soc->departement_id        = isset($values['departement_id']) ? $values['departement_id'] : $soc->departement_id;
    $soc->tel                   = isset($values['tel']) ? $values['tel'] : $soc->tel;
    $soc->fax                   = isset($values['fax']) ? $values['fax'] : $soc->fax;
    $soc->email                 = isset($values['email']) ? trim($values['email']) : $soc->email;
    $soc->url                   = isset($values['url']) ? $values['url'] : $soc->url;
    $soc->siren                 = isset($values['idprof1']) ? $values['idprof1'] : $soc->idprof1;
    $soc->siret                 = isset($values['idprof2']) ? $values['idprof2'] : $soc->idprof2;
    $soc->ape                   = isset($values['idprof3']) ? $values['idprof3'] : $soc->idprof3;
    $soc->idprof4               = isset($values['idprof4']) ? $values['idprof4'] : $soc->idprof4;
    $soc->prefix_comm           = isset($values['prefix_comm']) ? $values['prefix_comm'] : $soc->prefix_comm;
    $soc->code_client           = isset($values['code_client']) ? $values['code_client'] : $soc->code_client;
    $soc->code_fournisseur      = isset($values['code_fournisseur']) ? $values['code_fournisseur'] : $soc->code_fournisseur;
    $soc->capital               = isset($values['capital']) ? $values['capital'] : $soc->capital;
    $soc->gencod                = isset($values['gencod']) ? $values['gencod'] : $soc->gencod;
    $soc->note                  = isset($values['note']) ? $values['note'] : $soc->note;

    $soc->tva_assuj             = isset($values['assujtva_value']) ? $values['assujtva_value'] : $soc->assujtva_value;

    // Local Taxes
    $soc->localtax1_assuj       = isset($values['localtax1assuj_value']) ? $values['localtax1assuj_value'] : $soc->localtax1assuj_value;
    $soc->localtax2_assuj       = isset($values['localtax2assuj_value']) ? $values['localtax2assuj_value'] : $soc->localtax2assuj_value;

    $soc->tva_intra             = isset($values['tva_intra']) ? $values['tva_intra'] : $soc->tva_intra;

    $soc->forme_juridique_code  = isset($values['forme_juridique_code']) ? $values['forme_juridique_code'] : $soc->forme_juridique_code;
    $soc->effectif_id           = isset($values['effectif_id']) ? $values['effectif_id'] : $soc->effectif_id;
    if ($values["private"] == 1) {
      $soc->typent_id           = 8; // TODO predict another method if the field "special" change of rowid
    }
    else {
      $soc->typent_id           = isset($values['typent_id']) ? $values['typent_id'] : $soc->typent_id;
    }
    $soc->client                = isset($values['client']) ? $values['client'] : $soc->client;
    $soc->fournisseur           = isset($values['fournisseur']) ? $values['fournisseur'] : $soc->fournisseur;
    $soc->fournisseur_categorie = isset($values['fournisseur_categorie']) ? $values['fournisseur_categorie'] : $soc->fournisseur_categorie;

    $soc->commercial_id         = isset($values['commercial_id']) ? $values['commercial_id'] : $soc->commercial_id;
    $soc->default_lang          = isset($values['default_lang']) ? $values['default_lang'] : $soc->default_lang;

    $db->begin();

    $oldsoc = new Societe($db);
    $result = $oldsoc->fetch($values['socid']);

    // To not set code if third party is not concerned. But if it had values, we keep them.
    if (empty($soc->client) && empty($oldsoc->code_client))          $soc->code_client = '';
    if (empty($soc->fournisseur)&& empty($oldsoc->code_fournisseur)) $soc->code_fournisseur = '';
    //var_dump($soc);exit;

    $result = $soc->update($values['socid'], $user, 1, $oldsoc->codeclient_modifiable(), $oldsoc->codefournisseur_modifiable());

    if ($result >= 0) {
      if (!empty($soc->note)) {
        $sql = "UPDATE ".MAIN_DB_PREFIX."societe SET note='".addslashes($soc->note)."' WHERE rowid=".$soc->id;
        $db->query($sql);
      }
    }
    else {
      $this->success  = FALSE;
      $this->message .= print_r($result, true). 'DolWsSociete::updateSociete : '. "La société n'a pas été mise à jour car : ". join(',', $soc->errors). ' nom = '. $soc->nom. ' / '. $values['nom']. "|";
      return;
    }

    if ($result >= 0) {
      $db->commit();
      $this->success  = TRUE;
      $this->message .= 'DolWsSociete::updateSociete : '. 'La société a été mise à jour : '. $values['socid']. '|';
      $this->data     = $soc->id;
      return;
    }
    else {
      $db->rollback();
      $langs->load("errors");
      $this->succes   = FALSE;
      $this->message .= 'DolWsSociete::updateSociete : '. 'Erreur : '. $langs->trans($soc->error). "|";
    }
  }

  function getSocieteId($field, $value, $where = '') {
    global $conf, $langs, $db, $user;

    if (empty($where)) {
      $where = $field. "=". $value;
    }

    $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX. "societe WHERE $where";

    $result = $db->query($sql);
    if ($result) {
      $rowid  = $db->fetch_object($result)->rowid;
      if ($rowid) {
        $this->success  = TRUE;
        $this->message .= 'DolWsSociete::getSocieteId : '. 'Success : '. $rowid. "|";
        $this->data     = $rowid;
      }
      else {
        $this->success = FALSE;
        $this->message .= 'DolWsSociete::getSocieteId : '. 'Erreur : '. $db->error(). "|";
      }
    }
    else {
      $this->success = FALSE;
      $this->message .= 'DolWsSociete::getSocieteId : '. 'Erreur : '. $db->error(). "|";
    }
  }
}
