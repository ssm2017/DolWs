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

require_once(DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php');
include_once(DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');
include_once(DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php');

$langs->load('companies');
$langs->load('bills');
$langs->load('banks');

class DolWsPaiement {
  var $success  = FALSE;
  var $message  = '';
  var $data     = array();

  function createPaiement($values) {
    global $conf, $langs, $db, $user;

    $datepaye = dol_mktime(12, 0 , 0,
      $values['remonth'],
      $values['reday'],
      $values['reyear']
    );

    $db->begin();

    // Creation de la ligne paiement
    $paiement = new Paiement($db);
    $paiement->datepaye     = $datepaye;
    $paiement->amounts      = $values['amounts'];   // Tableau de montant
    $paiement->paiementid   = $values['paiementid'];
    $paiement->num_paiement = $values['num_paiement'];
    $paiement->note         = $values['comment'];

    $paiement_id = $paiement->create($user);

    if ($paiement_id > 0) {
      if ($conf->banque->enabled) {
        if (count($values['lines'])) {
          foreach ($values['lines'] as $line) {
            $this->addAccountLigne($line, $paiement);
            if (!$this->success) {
              $error++;
            }
          }
        }
        else {
          $this->addAccountLigne($values, $paiement);
          if (!$this->success) {
            $error++;
          }
        }
      }
    }
    else {
      $this->success = FALSE;
      $this->message .= 'DolWsPaiement::createPaiement : '. 'Erreur : '. $paiement->error. '|';
      $error++;
    }

    if ($error == 0) {
      $db->commit();
      $this->success = TRUE;
      $this->message .= 'DolWsPaiement::createPaiement : '. 'Paiement effectué : '. $paiement->id. '|';
      $this->data['paiement']['id']   = $paiement->id;
      $this->data['paiement']['obj']  = $paiement;
    }
    else {
      $db->rollback();
      $this->success = FALSE;
      $this->message .= 'DolWsPaiement::createPaiement : '. 'Erreur : '. $paiement->error. '|';
    }
  }

  function addAccountLigne($values, $paiement) {
    global $conf, $langs, $db, $user;

    // Insertion dans llx_bank
    $acc = new Account($db, $values['accountid']);
    $acc->rowid = $values['accountid'];

    $bank_line_id = $acc->addline(
      $paiement->datepaye,
      $paiement->paiementid,  // Payment mode id or code ("CHQ or VIR for example")
      $values['label'],
      $values['totalpaiement'],
      $paiement->num_paiement,
      '',
      $user,
      $values['chqemetteur'],
      $values['chqbank']
    );

    // Mise a jour fk_bank dans llx_paiement.
    // On connait ainsi le paiement qui a genere l'ecriture bancaire
    if ($bank_line_id > 0) {
      $paiement->update_fk_bank($bank_line_id);
      // Mise a jour liens (pour chaque facture concernees par le paiement)
      foreach ($paiement->amounts as $key => $value) {
        $facid = $key;
        $fac = new Facture($db);
        $fac->fetch($facid);
        $fac->fetch_client();
        $acc->add_url_line(
          $bank_line_id,
          $paiement->id,
          DOL_URL_ROOT.'/compta/paiement/fiche.php?id=',
          '(paiement)',
          'payment'
        );
        $acc->add_url_line(
          $bank_line_id,
          $fac->client->id,
          DOL_URL_ROOT.'/compta/fiche.php?socid=',
          $fac->client->nom,
          'company'
        );
      }
      $this->success = TRUE;
      $this->data['line']['id']       = $bank_line_id;
      $this->data['account']['id']    = $acc->id;
      $this->data['account']['obj']   = $acc;
      $this->data['paiement']['id']   = $paiement->id;
      $this->data['paiement']['obj']  = $paiement;
      $this->data['facture']['id']    = $fac->id;
      $this->data['facture']['obj']   = $fac;
    }
    else {
      $this->success = FALSE;
      $this->message .= 'DolWsPaiement::createPaiement : '. 'Erreur : Entrée sur le compte echec.'. $acc->error. '|';
    }
  }
}
