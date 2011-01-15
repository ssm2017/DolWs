<?php

class DolWsSociete {
  var $success  = FALSE;
  var $message  = '';
  var $data     = '';

  function addSociete($values) {
    global $conf, $langs, $db;
    require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
    require_once(DOL_DOCUMENT_ROOT."/core/class/html.formadmin.class.php");
    require_once(DOL_DOCUMENT_ROOT."/core/class/html.formcompany.class.php");
    require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
    require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
    if ($conf->adherent->enabled) require_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent.class.php");

    $langs->load("companies");
    $langs->load("commercial");
    $langs->load("bills");
    $langs->load("banks");
    $langs->load("users");

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

    $soc->tva_assuj             = $values["assujtva_value"];

    // Local Taxes
    $soc->localtax1_assuj   = $values["localtax1assuj_value"];
    $soc->localtax2_assuj   = $values["localtax2assuj_value"];

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

    if (! $error) {

      $db->begin();

      if (empty($soc->client))      $soc->code_client = '';
      if (empty($soc->fournisseur)) $soc->code_fournisseur = '';

      $result = $soc->create($user);
      if ($result >= 0) {
        if ($soc->particulier) {
          dol_syslog("This thirdparty is a personal people",LOG_DEBUG);
          $contact=new Contact($db);

          $contact->civilite_id = $soc->civilite_id;
          $contact->name=$soc->nom_particulier;
          $contact->firstname=$soc->prenom;
          $contact->address=$soc->address;
          $contact->cp=$soc->cp;
          $contact->ville=$soc->ville;
          $contact->fk_pays=$soc->fk_pays;
          $contact->socid=$soc->id;         // fk_soc
          $contact->status=1;
          $contact->email=$soc->email;
          $contact->priv=0;

          $result = $contact->create($user);
        }
      }
      else {
        $this->success = FALSE;
        $this->message .= "La société n'a pas été crée car : ". $soc->error. "<br>\n";
        return;
      }

      if ($result >= 0) {
        $db->commit();
        $this->message .= 'La société a été créée. ID = '. $result->id. "<br>\n";
        return;
      }
      else {
        $db->rollback();
        $langs->load("errors");
        $this->message .= $langs->trans($soc->error). "<br>\n";
      }
    }
  }
}
