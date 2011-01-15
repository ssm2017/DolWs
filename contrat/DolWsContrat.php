<?php

class DolWsContrat {
  var $success  = FALSE;
  var $message  = '';
  var $data     = '';

  function addContrat($values) {
    global $conf, $langs, $db, $user;
    require_once(DOL_DOCUMENT_ROOT.'/lib/contract.lib.php');
    if ($conf->projet->enabled)  require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
    if ($conf->propal->enabled)  require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
    if ($conf->contrat->enabled) require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
    if ($conf->projet->enabled)  require_once(DOL_DOCUMENT_ROOT."/lib/project.lib.php");

    $langs->load("contracts");
    $langs->load("orders");
    $langs->load("companies");
    $langs->load("bills");
    $langs->load("products");

    $usehm = $conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE;

    // Si ajout champ produit predefini
    if ($values["mode"]=='predefined') {
      $date_start='';
      $date_end='';
      if ($values["date_startmonth"] && $values["date_startday"] && $values["date_startyear"]) {
        $date_start=dol_mktime($values["date_starthour"], $values["date_startmin"], 0, $values["date_startmonth"], $values["date_startday"], $values["date_startyear"]);
      }
      if ($values["date_endmonth"] && $values["date_endday"] && $values["date_endyear"]) {
        $date_end=dol_mktime($values["date_endhour"], $values["date_endmin"], 0, $values["date_endmonth"], $values["date_endday"], $values["date_endyear"]);
      }
    }

    // Si ajout champ produit libre
    if ($values["mode"]=='libre') {
      $date_start_sl='';
      $date_end_sl='';
      if ($values["date_start_slmonth"] && $values["date_start_slday"] && $values["date_start_slyear"]) {
        $date_start_sl=dol_mktime($values["date_start_slhour"], $values["date_start_slmin"], 0, $values["date_start_slmonth"], $values["date_start_slday"], $values["date_start_slyear"]);
      }
      if ($values["date_end_slmonth"] && $values["date_end_slday"] && $values["date_end_slyear"]) {
        $date_end_sl=dol_mktime($values["date_end_slhour"], $values["date_end_slmin"], 0, $values["date_end_slmonth"], $values["date_end_slday"], $values["date_end_slyear"]);
      }
    }

    // Param dates
    $date_contrat='';
    $date_start_update='';
    $date_end_update='';
    $date_start_real_update='';
    $date_end_real_update='';
    if ($values["date_start_updatemonth"] && $values["date_start_updateday"] && $values["date_start_updateyear"]) {
      $date_start_update=dol_mktime($values["date_start_updatehour"], $values["date_start_updatemin"], 0, $values["date_start_updatemonth"], $values["date_start_updateday"], $values["date_start_updateyear"]);
    }
    if ($values["date_end_updatemonth"] && $values["date_end_updateday"] && $values["date_end_updateyear"]) {
      $date_end_update=dol_mktime($values["date_end_updatehour"], $values["date_end_updatemin"], 0, $values["date_end_updatemonth"], $values["date_end_updateday"], $values["date_end_updateyear"]);
    }
    if ($values["date_start_real_updatemonth"] && $values["date_start_real_updateday"] && $values["date_start_real_updateyear"]) {
      $date_start_real_update=dol_mktime($values["date_start_real_updatehour"], $values["date_start_real_updatemin"], 0, $values["date_start_real_updatemonth"], $values["date_start_real_updateday"], $values["date_start_real_updateyear"]);
    }
    if ($values["date_end_real_updatemonth"] && $values["date_end_real_updateday"] && $values["date_end_real_updateyear"]) {
      $date_end_real_update=dol_mktime($values["date_end_real_updatehour"], $values["date_end_real_updatemin"], 0, $values["date_end_real_updatemonth"], $values["date_end_real_updateday"], $values["date_end_real_updateyear"]);
    }
    if ($values["remonth"] && $values["reday"] && $values["reyear"]) {
      $datecontrat = dol_mktime($values["rehour"], $values["remin"], 0, $values["remonth"], $values["reday"], $values["reyear"]);
    }

    $contrat = new Contrat($db);

    $contrat->socid         = $values["socid"];
    $contrat->date_contrat   = $datecontrat;

    $contrat->commercial_suivi_id      = $values["commercial_suivi_id"];
    $contrat->commercial_signature_id  = $values["commercial_signature_id"];

    $contrat->note           = trim($values["note"]);
    $contrat->fk_project     = trim($values["projectid"]);
    $contrat->remise_percent = trim($values["remise_percent"]);
    $contrat->ref            = trim($values["ref"]);

    $user->id = 1;

    $result = $contrat->create($user,$langs,$conf);
    if ($result > 0) {
      $this->success = TRUE;
      $this->message .= 'Le contrat a été créé. ID = '. $result->id. '<br/>\n';
    }
    else {
      $this->message .= $contrat->error. '<br/>\n';
    }
    $db->close();
  }

  function addLigne($values) {
    global $conf, $langs, $db, $user;
    require_once(DOL_DOCUMENT_ROOT.'/lib/contract.lib.php');
    if ($conf->projet->enabled)  require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
    if ($conf->propal->enabled)  require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
    if ($conf->contrat->enabled) require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
    if ($conf->projet->enabled)  require_once(DOL_DOCUMENT_ROOT."/lib/project.lib.php");

    $langs->load("contracts");
    $langs->load("orders");
    $langs->load("companies");
    $langs->load("bills");
    $langs->load("products");

    $usehm = $conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE;

    if ($values["pqty"] && (($values["pu"] != '' && $values["desc"]) || $values["p_idprod"])) {
      $contrat = new Contrat($db);
      $ret = $contrat->fetch($values["contrat_id"]);
      if ($ret < 0) {
        dol_print_error($db, $commande->error);
        $this->success = FALSE;
        $this->message .= 'error'. $commande->error;
        return;
      }
      $ret = $contrat->fetch_client();

      $date_start = '';
      $date_end   = '';
      // Si ajout champ produit libre
      if ($values['mode'] == 'libre') {
        if ($values["date_start_slmonth"] && $values["date_start_slday"] && $values["date_start_slyear"]) {
          $date_start = dol_mktime($values["date_start_slhour"], $values["date_start_slmin"], 0, $values["date_start_slmonth"], $values["date_start_slday"], $values["date_start_slyear"]);
        }
        if ($values["date_end_slmonth"] && $values["date_end_slday"] && $values["date_end_slyear"]) {
          $date_end = dol_mktime($values["date_end_slhour"], $values["date_end_slmin"], 0, $values["date_end_slmonth"], $values["date_end_slday"], $values["date_end_slyear"]);
        }
      }
      // Si ajout champ produit predefini
      if ($values['mode'] == 'predefined') {
        if ($values["date_startmonth"] && $values["date_startday"] && $values["date_startyear"]) {
          $date_start = dol_mktime($values["date_starthour"], $values["date_startmin"], 0, $values["date_startmonth"], $values["date_startday"], $values["date_startyear"]);
        }
        if ($values["date_endmonth"] && $values["date_endday"] && $values["date_endyear"]) {
          $date_end = dol_mktime($values["date_endhour"], $values["date_endmin"], 0, $values["date_endmonth"], $values["date_endday"], $values["date_endyear"]);
        }
      }

      // Ecrase $pu par celui du produit
      // Ecrase $desc par celui du produit
      // Ecrase $txtva par celui du produit
      // Ecrase $base_price_type par celui du produit
      if ($values['p_idprod']) {
        $prod = new Product($db, $values['p_idprod']);
        $prod->fetch($values['p_idprod']);

        $tva_tx = get_default_tva($mysoc,$contrat->client,$prod->id);
        $tva_npr = get_default_npr($mysoc,$contrat->client,$prod->id);

        // On defini prix unitaire
        if ($conf->global->PRODUIT_MULTIPRICES && $contrat->client->price_level) {
          $pu_ht = $prod->multiprices[$contrat->client->price_level];
          $pu_ttc = $prod->multiprices_ttc[$contrat->client->price_level];
          $price_base_type = $prod->multiprices_base_type[$contrat->client->price_level];
        }
        else {
          $pu_ht = $prod->price;
          $pu_ttc = $prod->price_ttc;
          $price_base_type = $prod->price_base_type;
        }

        // On reevalue prix selon taux tva car taux tva transaction peut etre different
        // de ceux du produit par defaut (par exemple si pays different entre vendeur et acheteur).
        if ($tva_tx != $prod->tva_tx) {
          if ($price_base_type != 'HT') {
            $pu_ht = price2num($pu_ttc / (1 + ($tva_tx/100)), 'MU');
          }
          else {
            $pu_ttc = price2num($pu_ht * (1 + ($tva_tx/100)), 'MU');
          }
        }

        $desc = $prod->description;
        $desc.= $prod->description && $values['desc'] ? "\n" : "";
        $desc.= $values['desc'];
      }
      else {
        $pu_ht            = $values['pu'];
        $price_base_type  = 'HT';
        $tva_tx           = str_replace('*','',$values['tva_tx']);
        $tva_npr          = preg_match('/\*/',$values['tva_tx'])?1:0;
        $desc             = $values['desc'];
      }

      $localtax1_tx = get_localtax($tva_tx,1,$contrat->client);
      $localtax2_tx = get_localtax($tva_tx,2,$contrat->client);

      $info_bits = 0;
      if ($tva_npr) $info_bits |= 0x01;

      if (!isset($values['premise'])) $values['premise'] = 0;

      // Insert line
      $result = $contrat->addline(
                  $desc,
                  $pu_ht,
                  $values["pqty"],
                  $tva_tx,
                  $localtax1_tx,
                  $localtax2_tx,
                  $values["p_idprod"],
                  $values["premise"],
                  $date_start,
                  $date_end,
                  $price_base_type,
                  $pu_ttc,
                  $info_bits
                  );

      if ($result > 0) {
        $this->success = TRUE;
        $this->message .= 'Line added. ID = '.$result->id.'<br/>\n';
      }
      else {
        $this->success = FALSE;
        $this->message .= 'Error : '.$contrat->error.'<br/>\n';
      }
      $db->close();
    }
    else {
      $this->message .= "Prix et quantité requis.".'<br/>\n';
    }
  }
}
