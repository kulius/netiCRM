<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2010                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */

/**
 * form for thank-you / success page - 2nd step of online contribution process
 */
class CRM_Contribute_Form_Payment_ThankYou extends CRM_Contribute_Form_Payment {

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    parent::preProcess();
    if(!empty($this->_values)){
      if(!empty($this->_values['event'])){
        $this->assign('thankyou_text', CRM_Utils_Array::value('thankyou_text', $this->_values['event']));
      }
      else{
        $this->assign('thankyou_text', CRM_Utils_Array::value('thankyou_text', $this->_values));
      }
    }

    $this->assign('trxn_id', CRM_Utils_Array::value('trxn_id', $this->_params));
    if($this->_ids){
      $this->_contrib = $this->get('contrib');
      $this->assign('source', CRM_Utils_Array::value('source', $this->_contrib));
      $this->assign('is_pay_later', CRM_Utils_Array::value('is_pay_later', $this->_contrib));
      $this->assign('amount', CRM_Utils_Array::value('total_amount', $this->_contrib));
      $this->assign('amount_level', CRM_Utils_Array::value('amount_level', $this->_contrib));
    }
    CRM_Utils_System::setTitle(ts("Thank you for your support."));
  }

  /**
   * Function to actually build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {

    $this->freeze();
    // can we blow away the session now to prevent hackery
  }

  /**
   * overwrite action, since we are only showing elements in frozen mode
   * no help display needed
   *
   * @return int
   * @access public
   */
  function getAction() {
    if ($this->_action & CRM_Core_Action::PREVIEW) {
      return CRM_Core_Action::VIEW | CRM_Core_Action::PREVIEW;
    }
    else {
      return CRM_Core_Action::VIEW;
    }
  }
}

