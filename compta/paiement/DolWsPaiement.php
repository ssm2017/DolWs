<?php
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
  var $data     = '';

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
        // Insertion dans llx_bank
        $label = "(CustomerInvoicePayment)";
        $acc = new Account($db, $values['accountid']);

        $bank_line_id = $acc->addline(
          $paiement->datepaye,
          $paiement->paiementid,  // Payment mode id or code ("CHQ or VIR for example")
          $label,
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
              $paiement_id,
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
        }
        else {
          $this->success = FALSE;
          $this->message .= 'DolWsPaiement::createPaiement : '. 'Erreur : Entrée sur le compte echec.'. $paiement->error. '<br/>\n';
          $error++;
        }
      }
    }
    else {
      $this->success = FALSE;
      $this->message .= 'DolWsPaiement::createPaiement b: '. 'Erreur : '. $paiement->error. '<br/>\n';
      $error++;
    }

    if ($error == 0) {
      $db->commit();
      $loc = DOL_URL_ROOT.'/compta/paiement/fiche.php?id='.$paiement_id;
      $this->success = TRUE;
      $this->message .= 'DolWsPaiement::createPaiement : '. 'Paiement effectué : '. $paiement_id. '<br/>\n';
      $this->data = $paiement_id;
    }
    else {
      $db->rollback();
      $this->success = FALSE;
      $this->message .= 'DolWsPaiement::createPaiement c: '. 'Erreur : '. $paiement->error. '<br/>\n';
    }
  }
}
