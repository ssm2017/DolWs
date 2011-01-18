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

require_once(DOL_DOCUMENT_ROOT.'/includes/modules/facture/modules_facture.php');
require_once(DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');
require_once(DOL_DOCUMENT_ROOT.'/core/class/discount.class.php');
require_once(DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php');
require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/lib/invoice.lib.php');
require_once(DOL_DOCUMENT_ROOT."/lib/date.lib.php");
if ($conf->projet->enabled)   require_once(DOL_DOCUMENT_ROOT.'/projet/class/project.class.php');
if ($conf->projet->enabled)   require_once(DOL_DOCUMENT_ROOT.'/lib/project.lib.php');

$langs->load('bills');
$langs->load('companies');
$langs->load('products');
$langs->load('main');

class DolWsFacture {
  var $success  = FALSE;
  var $message  = '';
  var $data     = '';

  function createFacture($values) {
    global $conf, $langs, $db, $user;

    $facture = new Facture($db);
    $facture->socid=$values['socid'];

    $db->begin();

    // Replacement invoice
    if ($values['type'] == 1) {
      $datefacture = dol_mktime(12, 0 , 0, $values['remonth'], $values['reday'], $values['reyear']);
      if (empty($datefacture)) {
        $this->success = FALSE;
        $this->message .= 'DolWsFacture::createFacture : '. 'Erreur : '. $langs->trans("ErrorFieldRequired",$langs->trans("Date")). '|';
        $error=1;
      }

      if (!($values['fac_replacement'] > 0)) {
        $this->success = FALSE;
        $this->message .= 'DolWsFacture::createFacture : '. 'Erreur : '. $langs->trans("ErrorFieldRequired",$langs->trans("ReplaceInvoice")). '|';
        $error=1;
      }

      if (!$error) {
        // This is a replacement invoice
        $result=$facture->fetch($values['fac_replacement']);
        $facture->fetch_client();

        $facture->date           = $datefacture;
        $facture->note_public    = trim($values['note_public']);
        $facture->note           = trim($values['note']);
        $facture->ref_client     = $values['ref_client'];
        $facture->modelpdf       = $values['model'];
        $facture->fk_project        = $values['projectid'];
        $facture->cond_reglement_id = $values['cond_reglement_id'];
        $facture->mode_reglement_id = $values['mode_reglement_id'];
        $facture->remise_absolue    = $values['remise_absolue'];
        $facture->remise_percent    = $values['remise_percent'];

        // Proprietes particulieres a facture de remplacement
        $facture->fk_facture_source = $values['fac_replacement'];
        $facture->type              = 1;

        $facid = $facture->createFromCurrent($user);
      }
    }

    // Credit note invoice
    if ($values['type'] == 2) {
      if (! $values['fac_avoir'] > 0) {
        $this->success = FALSE;
        $this->message .= 'DolWsFacture::createFacture : '. 'Erreur : '. $langs->trans("ErrorFieldRequired",$langs->trans("CorrectInvoice")). '|';
        $error=1;
      }

      $datefacture = dol_mktime(12, 0 , 0, $values['remonth'], $values['reday'], $values['reyear']);
      if (empty($datefacture)) {
        $this->success = FALSE;
        $this->message .= 'DolWsFacture::createFacture : '. 'Erreur : '. $langs->trans("ErrorFieldRequired",$langs->trans("Date")). '|';
        $error=1;
      }

      if (! $error) {
        // Si facture avoir
        $datefacture = dol_mktime(12, 0 , 0, $values['remonth'], $values['reday'], $values['reyear']);

        //$result=$facture->fetch($values['fac_avoir']);

        $facture->socid          = $values['socid'];
        $facture->number         = $values['facnumber'];
        $facture->date           = $datefacture;
        $facture->note_public    = trim($values['note_public']);
        $facture->note           = trim($values['note']);
        $facture->ref_client     = $values['ref_client'];
        $facture->modelpdf       = $values['model'];
        $facture->fk_project        = $values['projectid'];
        $facture->cond_reglement_id = 0;
        $facture->mode_reglement_id = $values['mode_reglement_id'];
        $facture->remise_absolue    = $values['remise_absolue'];
        $facture->remise_percent    = $values['remise_percent'];

        // Proprietes particulieres a facture avoir
        $facture->fk_facture_source = $values['fac_avoir'];
        $facture->type              = 2;
        $facid = $facture->create($user);
      }
    }

    // Standard invoice or Deposit invoice created from a predefined invoice
    if (($values['type'] == 0 || $values['type'] == 3) && $values['fac_rec'] > 0) {
      $datefacture = dol_mktime(12, 0 , 0, $values['remonth'], $values['reday'], $values['reyear']);
      if (empty($datefacture)) {
        $this->success = FALSE;
        $this->message .= 'DolWsFacture::createFacture : '. 'Erreur : '. $langs->trans("ErrorFieldRequired",$langs->trans("Date")). '|';
        $error=1;
      }

      if (! $error) {
        $facture->socid       = $values['socid'];
        $facture->type        = $values['type'];
        $facture->number      = $values['facnumber'];
        $facture->date        = $datefacture;
        $facture->note_public = trim($values['note_public']);
        $facture->note        = trim($values['note']);
        $facture->ref_client  = $values['ref_client'];
        $facture->modelpdf    = $values['model'];

        // Source facture
        $facture->fac_rec    = $values['fac_rec'];

        $facid = $facture->create($user);
      }
    }

    // Standard or deposit or proforma invoice
    if (($values['type'] == 0 || $values['type'] == 3 || $values['type'] == 4) && $values['fac_rec'] <= 0) {
      $datefacture = dol_mktime(12, 0 , 0, $values['remonth'], $values['reday'], $values['reyear']);
      if (empty($datefacture)) {
        $this->success = FALSE;
        $this->message .= 'DolWsFacture::createFacture : '. 'Erreur : '. $langs->trans("ErrorFieldRequired",$langs->trans("Date")). '|';
        $error=1;
      }

      if (! $error) {
        // Si facture standard
        $facture->socid          = $values['socid'];
        $facture->type           = $values['type'];
        $facture->number         = $values['facnumber'];
        $facture->date           = $datefacture;
        $facture->note_public    = trim($values['note_public']);
        $facture->note           = trim($values['note']);
        $facture->ref_client     = $values['ref_client'];
        $facture->modelpdf       = $values['model'];
        $facture->fk_project        = $values['projectid'];
        $facture->cond_reglement_id = ($values['type'] == 3?1:$values['cond_reglement_id']);
        $facture->mode_reglement_id = $values['mode_reglement_id'];
        $facture->amount            = $values['amount'];
        $facture->remise_absolue    = $values['remise_absolue'];
        $facture->remise_percent    = $values['remise_percent'];

        // If creation from other modules
        if ($values['origin'] && $values['originid']) {
          // Parse element/subelement (ex: project_task)
          $element = $subelement = $values['origin'];
          if (preg_match('/^([^_]+)_([^_]+)/i',$values['origin'],$regs)) {
            $element = $regs[1];
            $subelement = $regs[2];
          }

          // For compatibility
          if ($element == 'order')    { $element = $subelement = 'commande'; }
          if ($element == 'propal')   { $element = 'comm/propal'; $subelement = 'propal'; }
          if ($element == 'contract') { $element = $subelement = 'contrat'; }

          $facture->origin  = $values['origin'];
          $facture->origin_id = $values['originid'];

          $facid = $facture->create($user);

          if ($facid > 0) {
            require_once(DOL_DOCUMENT_ROOT.'/'.$element.'/class/'.$subelement.'.class.php');
            $classname = ucfirst($subelement);
            $object = new $classname($db);

            if ($object->fetch($values['originid'])) {
              // TODO mutualiser
              $lines = $object->lignes;
              if (empty($lines) && method_exists($object,'fetch_lignes')) $lines = $object->fetch_lignes();
              if (empty($lines) && method_exists($object,'fetch_lines'))  $lines = $object->fetch_lines();

              for ($i = 0 ; $i < sizeof($lines) ; $i++) {
                $desc=($lines[$i]->desc?$lines[$i]->desc:$lines[$i]->libelle);
                $product_type=($lines[$i]->product_type?$lines[$i]->product_type:0);

                // Dates
                // TODO mutualiser
                $date_start=$lines[$i]->date_debut_prevue;
                if ($lines[$i]->date_debut_reel) $date_start=$lines[$i]->date_debut_reel;
                if ($lines[$i]->date_start) $date_start=$lines[$i]->date_start;
                $date_end=$lines[$i]->date_fin_prevue;
                if ($lines[$i]->date_fin_reel) $date_end=$lines[$i]->date_fin_reel;
                if ($lines[$i]->date_end) $date_end=$lines[$i]->date_end;

                $result = $facture->addline(
                  $facid,
                  $desc,
                  $lines[$i]->subprice,
                  $lines[$i]->qty,
                  $lines[$i]->tva_tx,
                  $lines[$i]->localtax1_tx,
                  $lines[$i]->localtax2_tx,
                  $lines[$i]->fk_product,
                  $lines[$i]->remise_percent,
                  $date_start,
                  $date_end,
                  0,
                  $lines[$i]->info_bits,
                  $lines[$i]->fk_remise_except,
                  'HT',
                  0,
                  $product_type
                );

                if ($result < 0) {
                  $error++;
                  break;
                }
              }
            }
            else {
              $error++;
            }
          }
          else {
            $error++;
          }
        }
        else {
          $facid = $facture->create($user);
          // Add predefined lines
          if (count($values['produits'])) {
            foreach($values['produits'] as $produit) {
              $product  = new Product($db);
              $product->fetch($produit['id']);
              $startday = dol_mktime(12, 0 , 0, $produit['date']['start']['month'], $produit['date']['start']['day'], $produit['date']['start']['year']);
              $endday   = dol_mktime(12, 0 , 0, $produit['date']['end']['month'], $produit['date']['end']['day'], $produit['date']['end']['year']);
              $result   = $facture->addline(
                            $facid,
                            $product->description,
                            $product->price,
                            $produit['qty'],
                            $product->tva_tx,
                            $product->localtax1_tx,
                            $product->localtax2_tx,
                            $produit['idprod'],
                            $produit['remise_percent'],
                            $startday, $endday,
                            0,
                            0,
                            '',
                            $product->price_base_type,
                            $product->price_ttc,
                            $product->type
                          );
            }
          }
        }

        if ($values['valide']) {
          $facture->fetch_client();
          $facture->validate($user);
        }
        if ($values['paid']) {
          $facture->set_paid($user);
        }
      }
    }

    // Fin creation facture, on l'affiche
    if ($facid > 0 && !$error) {
      $db->commit();
      $this->success  = TRUE;
      $this->message .= 'Facture créée avec succes : '. $facid. '.|';
      $this->data     = $facid;
    }
    else {
      $db->rollback();
      $this->success  = FALSE;
      $this->message .= 'DolWsFacture::createFacture : Erreur : '. $facture->error. $db->error(). '|';
    }
  }
}
