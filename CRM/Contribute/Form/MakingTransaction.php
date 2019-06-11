<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2012                                |
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
 * @copyright CiviCRM LLC (c) 2004-2012
 * $Id$
 *
 */

require_once 'CRM/Core/Form.php';

/**
 * This class generates form components generic to recurring contributions
 *
 * It delegates the work to lower level subclasses and integrates the changes
 * back in. It also uses a lot of functionality with the CRM API's, so any change
 * made here could potentially affect the API etc. Be careful, be aware, use unit tests.
 *
 */
class CRM_Contribute_Form_MakingTransaction extends CRM_Core_Form {

  /**
   * The recurring contribution id, used when editing the recurring contribution
   *
   * @var int
   */
  protected $_id;
  protected $_online;

  /**
   * the id of the contact associated with this recurring contribution
   *
   * @var int
   * @public
   */
  public $_contactID;

  function preProcess() {
  }

  /**
   * This function sets the default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return None
   */
  function setDefaultValues() {
    
    return $defaults;
  }

  /**
   * Function to actually build the components of the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    $id = $this->get('id');

    $name = $this->getButtonName('submit');
    $submit = $this->addElement('submit', $name, ts('Process now'), array('onclick' => "return confirm('".ts("Are you sure you want to process a transaction of %1?", $id)."')"));
    $this->assign('submit_name', $name);
  }

  /**
   * This function is called after the user submits the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    $recurId = $this->get('id');

    $contributionId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $recurId, 'id', 'contribution_recur_id');
    $paymentClass = CRM_Contribute_BAO_Contribution::getPaymentClass($contributionId);
    if (method_exists($paymentClass, 'doRecurTransact')) {
      $result = $paymentClass::doRecurTransact($recurId);
    }

    $contactId = $this->get('contactId');
    $session = CRM_Core_Session::singleton();
    $url = CRM_Utils_System::url('civicrm/contact/view/contributionrecur',
      'reset=1&id='.$recurId.'&cid=' . $contactId
    );
    $session->replaceUserContext($url);

  }
  //end of function
}
