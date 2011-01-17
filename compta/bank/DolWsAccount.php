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
require_once(DOL_DOCUMENT_ROOT."/lib/bank.lib.php");
require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/sociales/class/chargesociales.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/paiement/class/paiement.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/tva/class/tva.class.php");
require_once(DOL_DOCUMENT_ROOT."/fourn/class/paiementfourn.class.php");

$langs->load("bills");

class DolWsAccount {
  var $success  = FALSE;
  var $message  = '';
  var $data     = '';

  function addLigne($values) {
    global $conf, $langs, $db, $user;

    if (price2num($values["credit"]) > 0) {
      $amount = price2num($values["credit"]);
    }
    else {
      $amount = - price2num($values["debit"]);
    }

    $dateop     = dol_mktime(12,0,0,$values["opmonth"],$values["opday"],$values["opyear"]);
    $operation  = $values["operation"];
    $num_chq    = $values["num_chq"];
    $label      = $values["label"];
    $cat1       = $values["cat1"];

    if (!$dateop)    $mesg = $langs->trans("ErrorFieldRequired",$langs->trans("Date"));
    if (!$operation) $mesg = $langs->trans("ErrorFieldRequired",$langs->trans("Type"));
    if (!$amount)    $mesg = $langs->trans("ErrorFieldRequired",$langs->trans("Amount"));

    if (!$mesg) {
      $acct   = new Account($db);
      $result = $acct->fetch($values['accountid']);
      
      $insertid = $acct->addline($dateop, $operation, $label, $amount, $num_chq, $cat1, $user, '', '');
      if ($insertid > 0) {
        if ($values['url_line_id']) {
          $inserturlid = $acct->add_url_line($insertid, $values['url_line_id'], $values['url_line_url'], $values['url_line_label'], $values['url_line_type']);
          if ($insertid < 1) {
            $this->success  = FALSE;
            $this->message .= 'DolWsAccount::addLigne : Erreur : '. $acct->error;
           }
        }
        $this->success  = TRUE;
        $this->message .= 'DolWsAccount::addLigne : Entrée ajoutée avec succes.';
        $this->data     = $insertid;
      }
      else {
        $this->success  = FALSE;
        $this->message .= 'DolWsAccount::addLigne : Erreur : '. $acct->error. '<br/>\n';
      }
    }
    else {
      $this->success  = FALSE;
      $this->message .= 'DolWsAccount::addLigne : Erreur : '. $mesg. '<br/>\n';
    }
  }
}
