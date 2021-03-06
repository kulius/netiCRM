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

require_once 'CRM/Utils/String.php';
require_once 'CRM/Utils/Type.php';

require_once 'CRM/Contribute/Import/Field.php';

abstract class CRM_Contribute_Import_Parser {
  CONST MAX_ERRORS = 250, MAX_WARNINGS = 25, VALID = 1, WARNING = 2, ERROR = 3, CONFLICT = 4, STOP = 5, DUPLICATE = 6, MULTIPLE_DUPE = 7, NO_MATCH = 8, SOFT_CREDIT = 9, SOFT_CREDIT_ERROR = 10, PLEDGE_PAYMENT = 11, PLEDGE_PAYMENT_ERROR = 12, PCP = 13, PCP_ERROR = 14;

  /**
   * import contact when import contribution
   */
  CONST CONTACT_NOIDCREATE = 100, CONTACT_AUTOCREATE = 101, CONTACT_DONTCREATE = 102;

  /**
   * various parser modes
   */
  CONST MODE_MAPFIELD = 1, MODE_PREVIEW = 2, MODE_SUMMARY = 4, MODE_IMPORT = 8;

  /**
   * codes for duplicate record handling
   */
  CONST DUPLICATE_SKIP = 1, DUPLICATE_REPLACE = 2, DUPLICATE_UPDATE = 4, DUPLICATE_FILL = 8, DUPLICATE_NOCHECK = 16;

  /**
   * various Contact types
   */
  CONST CONTACT_INDIVIDUAL = 'Individual', CONTACT_HOUSEHOLD = 'Household', CONTACT_ORGANIZATION = 'Organization';

  protected $_fileName;

  /**#@+
   * @access protected
   * @var integer
   */

  /**
   * imported file size
   */
  protected $_fileSize;

  /**
   * seperator being used
   */
  protected $_seperator;

  /**
   * total number of lines in file
   */
  protected $_lineCount;

  /**
   * total number of non empty lines
   */
  protected $_totalCount;

  /**
   * running total number of valid lines
   */
  protected $_validCount;

  /**
   * running total number of invalid rows
   */
  protected $_invalidRowCount;

  /**
   * running total number of valid soft credit rows
   */
  protected $_validSoftCreditRowCount;

  /**
   * running total number of invalid soft credit rows
   */
  protected $_invalidSoftCreditRowCount;

  /**
   * running total number of valid pcp rows
   */
  protected $_validPCPRowCount;

  /**
   * running total number of invalid pcp rows
   */
  protected $_invalidPCPRowCount;

  /**
   * running total number of valid pledge payment rows
   */
  protected $_validPledgePaymentRowCount;

  /**
   * running total number of invalid pledge payment rows
   */
  protected $_invalidPledgePaymentRowCount;

  /**
   * maximum number of invalid rows to store
   */
  protected $_maxErrorCount;

  /**
   * array of error lines, bounded by MAX_ERROR
   */
  protected $_errors;

  /**
   * array of pledge payment error lines, bounded by MAX_ERROR
   */
  protected $_pledgePaymentErrors;

  /**
   * array of pledge payment error lines, bounded by MAX_ERROR
   */
  protected $_softCreditErrors;

  /**
   * array of pledge payment error lines, bounded by MAX_ERROR
   */
  protected $_pcpErrors;

  /**
   * total number of conflict lines
   */
  protected $_conflictCount;

  /**
   * array of conflict lines
   */
  protected $_conflicts;

  /**
   * total number of duplicate (from database) lines
   */
  protected $_duplicateCount;

  /**
   * array of duplicate lines
   */
  protected $_duplicates;

  /**
   * running total number of warnings
   */
  protected $_warningCount;

  /**
   * maximum number of warnings to store
   */
  protected $_maxWarningCount = self::MAX_WARNINGS;

  /**
   * array of warning lines, bounded by MAX_WARNING
   */
  protected $_warnings;

  /**
   * array of all the fields that could potentially be part
   * of this import process
   * @var array
   */
  protected $_fields;

  /**
   * array of the fields that are actually part of the import process
   * the position in the array also dictates their position in the import
   * file
   * @var array
   */
  protected $_activeFields;

  /**
   * cache the count of active fields
   *
   * @var int
   */
  protected $_activeFieldCount;

  /**
   * maximum number of non-empty/comment lines to process
   *
   * @var int
   */
  protected $_maxLinesToProcess;

  /**
   * cache of preview rows
   *
   * @var array
   */
  protected $_rows;

  /**
   * filename of error data
   *
   * @var string
   */
  protected $_errorFileName;

  /**
   * filename of pledge payment error data
   *
   * @var string
   */
  protected $_pledgePaymentErrorsFileName;

  /**
   * filename of soft credit error data
   *
   * @var string
   */
  protected $_softCreditErrorsFileName;

  /**
   * filename of pcp error data
   *
   * @var string
   */
  protected $_pcpErrorsFileName;

  /**
   * filename of conflict data
   *
   * @var string
   */
  protected $_conflictFileName;

  /**
   * filename of duplicate data
   *
   * @var string
   */
  protected $_duplicateFileName;

  /**
   * whether the file has a column header or not
   *
   * @var boolean
   */
  protected $_haveColumnHeader;

  /**
   * Dedupe group id for contact matching
   *
   * @var integer 
   */
  protected $_dedupeRuleGroupId;

  /**
   * Create contact mode
   *
   * @var integer
   */
  protected $_createContactOption;

  /**
   * contact type
   *
   * @var int
   */

  public $_contactType; function __construct() {
    $this->_maxLinesToProcess = 0;
    $this->_maxErrorCount = self::MAX_ERRORS;
  }

  abstract function init();
  function run($fileName,
    $seperator = ',',
    &$mapper,
    $skipColumnHeader = FALSE,
    $mode = self::MODE_PREVIEW,
    $contactType = self::CONTACT_INDIVIDUAL,
    $onDuplicate = self::DUPLICATE_SKIP,
    $createContactOption = self::CONTACT_NOIDCREATE,
    $dedupeRuleGroupId = 0
  ) {
    if (!is_array($fileName)) {
      CRM_Core_Error::fatal();
    }
    $fileName = $fileName['name'];
    $this->_contactType = $contactType;
    $this->_createContactOption = $createContactOption;
    $this->_dedupeRuleGroupId = $dedupeRuleGroupId;

    $this->init();

    $this->_haveColumnHeader = $skipColumnHeader;

    $this->_seperator = $seperator;

    $fd = fopen($fileName, "r");
    if (!$fd) {
      return FALSE;
    }

    $this->_lineCount = $this->_warningCount = $this->_validSoftCreditRowCount = $this->_validPledgePaymentRowCount = 0;
    $this->_invalidRowCount = $this->_validCount = $this->_invalidSoftCreditRowCount = $this->_invalidPledgePaymentRowCount = 0;
    $this->_totalCount = $this->_conflictCount = 0;

    $this->_errors = array();
    $this->_warnings = array();
    $this->_conflicts = array();
    $this->_pledgePaymentErrors = array();
    $this->_softCreditErrors = array();
    $this->_pcpErrors = array();

    $this->_fileSize = number_format(filesize($fileName) / 1024.0, 2);

    if ($mode == self::MODE_MAPFIELD) {
      $this->_rows = array();
    }
    else {
      $this->_activeFieldCount = count($this->_activeFields);
    }

    while (!feof($fd)) {
      $this->_lineCount++;

      $values = fgetcsv($fd, 20000, $seperator);
      if (!$values) {
        continue;
      }

      self::encloseScrub($values);

      // skip column header if we're not in mapfield mode
      if ($mode != self::MODE_MAPFIELD && $skipColumnHeader) {
        $skipColumnHeader = FALSE;
        continue;
      }

      /* trim whitespace around the values */

      $empty = TRUE;
      foreach ($values as $k => $v) {
        $values[$k] = trim($v, " \t\r\n");
      }

      if (CRM_Utils_System::isNull($values)) {
        continue;
      }

      $this->_totalCount++;

      if ($mode == self::MODE_MAPFIELD) {
        $returnCode = $this->mapField($values);
      }
      elseif ($mode == self::MODE_PREVIEW) {
        $returnCode = $this->preview($values);
      }
      elseif ($mode == self::MODE_SUMMARY) {
        $returnCode = $this->summary($values);
      }
      elseif ($mode == self::MODE_IMPORT) {
        $returnCode = $this->import($onDuplicate, $values);
      }
      else {
        $returnCode = self::ERROR;
      }

      // note that a line could be valid but still produce a warning
      if ($returnCode == self::VALID) {
        $this->_validCount++;
        if ($mode == self::MODE_MAPFIELD) {
          $this->_rows[] = $values;
          $this->_activeFieldCount = max($this->_activeFieldCount, count($values));
        }
      }

      if ($returnCode == self::SOFT_CREDIT) {
        $this->_validSoftCreditRowCount++;
        $this->_validCount++;
        if ($mode == self::MODE_MAPFIELD) {
          $this->_rows[] = $values;
          $this->_activeFieldCount = max($this->_activeFieldCount, count($values));
        }
      }

      if ($returnCode == self::PLEDGE_PAYMENT) {
        $this->_validPledgePaymentRowCount++;
        $this->_validCount++;
        if ($mode == self::MODE_MAPFIELD) {
          $this->_rows[] = $values;
          $this->_activeFieldCount = max($this->_activeFieldCount, count($values));
        }
      }

      if ($returnCode == self::PCP) {
        $this->_validPCPRowCount++;
        $this->_validCount++;
        if ($mode == self::MODE_MAPFIELD) {
          $this->_rows[] = $values;
          $this->_activeFieldCount = max($this->_activeFieldCount, count($values));
        }
      }

      if ($returnCode == self::WARNING) {
        $this->_warningCount++;
        if ($this->_warningCount < $this->_maxWarningCount) {
          $this->_warningCount[] = $line;
        }
      }

      if ($returnCode == self::ERROR) {
        $this->_invalidRowCount++;
        if ($this->_invalidRowCount < $this->_maxErrorCount) {
          $recordNumber = $this->_lineCount;
          if ($this->_haveColumnHeader) {
            $recordNumber--;
          }
          array_unshift($values, $recordNumber);
          $this->_errors[] = $values;
        }
      }

      if ($returnCode == self::PLEDGE_PAYMENT_ERROR) {
        $this->_invalidPledgePaymentRowCount++;
        if ($this->_invalidPledgePaymentRowCount < $this->_maxErrorCount) {
          $recordNumber = $this->_lineCount;
          if ($this->_haveColumnHeader) {
            $recordNumber--;
          }
          array_unshift($values, $recordNumber);
          $this->_pledgePaymentErrors[] = $values;
        }
      }

      if ($returnCode == self::SOFT_CREDIT_ERROR) {
        $this->_invalidSoftCreditRowCount++;
        if ($this->_invalidSoftCreditRowCount < $this->_maxErrorCount) {
          $recordNumber = $this->_lineCount;
          if ($this->_haveColumnHeader) {
            $recordNumber--;
          }
          array_unshift($values, $recordNumber);
          $this->_softCreditErrors[] = $values;
        }
      }

      if ($returnCode == self::PCP_ERROR) {
        $this->_invalidPCPRowCount++;
        if ($this->_invalidPCPRowCount < $this->_maxErrorCount) {
          $recordNumber = $this->_lineCount;
          if ($this->_haveColumnHeader) {
            $recordNumber--;
          }
          array_unshift($values, $recordNumber);
          $this->_pcpErrors[] = $values;
        }
      }

      if ($returnCode == self::CONFLICT) {
        $this->_conflictCount++;
        $recordNumber = $this->_lineCount;
        if ($this->_haveColumnHeader) {
          $recordNumber--;
        }
        array_unshift($values, $recordNumber);
        $this->_conflicts[] = $values;
      }

      if ($returnCode == self::DUPLICATE) {
        if ($returnCode == self::MULTIPLE_DUPE) {
          /* TODO: multi-dupes should be counted apart from singles
                     * on non-skip action */
        }
        $this->_duplicateCount++;
        $recordNumber = $this->_lineCount;
        if ($this->_haveColumnHeader) {
          $recordNumber--;
        }
        array_unshift($values, $recordNumber);
        $this->_duplicates[] = $values;
        if ($onDuplicate != self::DUPLICATE_SKIP) {
          $this->_validCount++;
        }
      }

      // we give the derived class a way of aborting the process
      // note that the return code could be multiple code or'ed together
      if ($returnCode == self::STOP) {
        break;
      }

      // if we are done processing the maxNumber of lines, break
      if ($this->_maxLinesToProcess > 0 && $this->_validCount >= $this->_maxLinesToProcess) {
        break;
      }
    }

    fclose($fd);

    if ($mode == self::MODE_PREVIEW || $mode == self::MODE_IMPORT) {
      $customHeaders = $mapper;

      $customfields = &CRM_Core_BAO_CustomField::getFields('Contribution');
      foreach ($customHeaders as $key => $value) {
        if ($id = CRM_Core_BAO_CustomField::getKeyID($value)) {
          $customHeaders[$key] = $customfields[$id][0];
        }
      }
      if ($this->_invalidRowCount) {
        // removed view url for invlaid contacts
        $headers = array_merge(array(ts('Line Number'),
            ts('Reason'),
          ),
          $customHeaders
        );
        $this->_errorFileName = self::errorFileName(self::ERROR);
        self::exportCSV($this->_errorFileName, $headers, $this->_errors);
      }

      if ($this->_invalidPledgePaymentRowCount) {
        // removed view url for invlaid contacts
        $headers = array_merge(array(ts('Line Number'),
            ts('Reason'),
          ),
          $customHeaders
        );
        $this->_pledgePaymentErrorsFileName = self::errorFileName(self::PLEDGE_PAYMENT_ERROR);
        self::exportCSV($this->_pledgePaymentErrorsFileName, $headers, $this->_pledgePaymentErrors);
      }

      if ($this->_invalidSoftCreditRowCount) {
        // removed view url for invlaid contacts
        $headers = array_merge(array(ts('Line Number'),
            ts('Reason'),
          ),
          $customHeaders
        );
        $this->_softCreditErrorsFileName = self::errorFileName(self::SOFT_CREDIT_ERROR);
        self::exportCSV($this->_softCreditErrorsFileName, $headers, $this->_softCreditErrors);
      }

      if ($this->_invalidPCPRowCount) {
        // removed view url for invlaid contacts
        $headers = array_merge(array(ts('Line Number'),
            ts('Reason'),
          ),
          $customHeaders
        );
        $this->_pcpErrorsFileName = self::errorFileName(self::PCP_ERROR);
        self::exportCSV($this->_pcpErrorsFileName, $headers, $this->_pcpErrors);
      }

      if ($this->_conflictCount) {
        $headers = array_merge(array(ts('Line Number'),
            ts('Reason'),
          ),
          $customHeaders
        );
        $this->_conflictFileName = self::errorFileName(self::CONFLICT);
        self::exportCSV($this->_conflictFileName, $headers, $this->_conflicts);
      }
      if ($this->_duplicateCount) {
        $headers = array_merge(array(ts('Line Number'),
            ts('View Contribution URL'),
          ),
          $customHeaders
        );

        $this->_duplicateFileName = self::errorFileName(self::DUPLICATE);
        self::exportCSV($this->_duplicateFileName, $headers, $this->_duplicates);
      }
    }
    //echo "$this->_totalCount,$this->_invalidRowCount,$this->_conflictCount,$this->_duplicateCount";
    return $this->fini();
  }

  abstract function mapField(&$values);
  abstract function preview(&$values);
  abstract function summary(&$values);
  abstract function import($onDuplicate, &$values);

  abstract function fini();

  /**
   * Given a list of the importable field keys that the user has selected
   * set the active fields array to this list
   *
   * @param array mapped array of values
   *
   * @return void
   * @access public
   */
  function setActiveFields($fieldKeys) {
    $this->_activeFieldCount = count($fieldKeys);
    foreach ($fieldKeys as $key) {
      if (empty($this->_fields[$key])) {
        $this->_activeFields[] = new CRM_Contribute_Import_Field('', ts('- do not import -'));
      }
      else {
        $this->_activeFields[] = clone($this->_fields[$key]);
      }
    }
  }

  function setActiveFieldSoftCredit($elements) {
    for ($i = 0; $i < count($elements); $i++) {
      $this->_activeFields[$i]->_softCreditField = $elements[$i];
    }
  }

  function setActiveFieldPCP($elements) {
    for ($i = 0; $i < count($elements); $i++) {
      $this->_activeFields[$i]->_pcpField = $elements[$i];
    }
  }

  function setActiveFieldValues($elements, &$erroneousField) {
    $maxCount = count($elements) < $this->_activeFieldCount ? count($elements) : $this->_activeFieldCount;
    for ($i = 0; $i < $maxCount; $i++) {
      $this->_activeFields[$i]->setValue($elements[$i]);
    }

    // reset all the values that we did not have an equivalent import element
    for (; $i < $this->_activeFieldCount; $i++) {
      $this->_activeFields[$i]->resetValue();
    }

    // now validate the fields and return false if error
    $valid = self::VALID;
    for ($i = 0; $i < $this->_activeFieldCount; $i++) {
      if (!$this->_activeFields[$i]->validate()) {
        // no need to do any more validation
        $erroneousField = $i;
        $valid = self::ERROR;
        break;
      }
    }
    return $valid;
  }

  function setActiveFieldLocationTypes($elements) {
    for ($i = 0; $i < count($elements); $i++) {
      $this->_activeFields[$i]->_hasLocationType = $elements[$i];
    }
  }

  function setActiveFieldPhoneTypes($elements) {
    for ($i = 0; $i < count($elements); $i++) {
      $this->_activeFields[$i]->_phoneType = $elements[$i];
    }
  }

  function setActiveFieldWebsiteTypes($elements) {
    for ($i = 0; $i < count($elements); $i++) {
      $this->_activeFields[$i]->_websiteType = $elements[$i];
    }
  }

  /**
   * Function to set IM Service Provider type fields
   *
   * @param array $elements IM service provider type ids
   *
   * @return void
   * @access public
   */
  function setActiveFieldImProviders($elements) {
    for ($i = 0; $i < count($elements); $i++) {
      $this->_activeFields[$i]->_imProvider = $elements[$i];
    }
  }

  /**
   * function to format the field values for input to the api
   *
   * @return array (reference ) associative array of name/value pairs
   * @access public
   */
  function &getActiveFieldParams() {
    $params = array();
    for ($i = 0; $i < $this->_activeFieldCount; $i++) {
      if (isset($this->_activeFields[$i]->_value)) {
        if (isset($this->_activeFields[$i]->_softCreditField)) {
          if (!isset($params[$this->_activeFields[$i]->_name])) {
            $params[$this->_activeFields[$i]->_name] = array();
          }
          $params[$this->_activeFields[$i]->_name][$this->_activeFields[$i]->_softCreditField] = $this->_activeFields[$i]->_value;
        }
        if (isset($this->_activeFields[$i]->_pcpField)) {
          if (!isset($params[$this->_activeFields[$i]->_name])) {
            $params[$this->_activeFields[$i]->_name] = array();
          }
          $params[$this->_activeFields[$i]->_name][$this->_activeFields[$i]->_pcpField] = $this->_activeFields[$i]->_value;
        }

        if (isset($this->_activeFields[$i]->_hasLocationType)) {
          if (!isset($params[$this->_activeFields[$i]->_name])) {
            $params[$this->_activeFields[$i]->_name] = array();
          }

          $value = array(
            $this->_activeFields[$i]->_name =>
            $this->_activeFields[$i]->_value,
            'location_type_id' =>
            $this->_activeFields[$i]->_hasLocationType,
          );

          if (isset($this->_activeFields[$i]->_phoneType)) {
            $value['phone_type_id'] = $this->_activeFields[$i]->_phoneType;
          }

          // get IM service Provider type id
          if (isset($this->_activeFields[$i]->_imProvider)) {
            $value['provider_id'] = $this->_activeFields[$i]->_imProvider;
          }

          $params[$this->_activeFields[$i]->_name][] = $value;
        }
        elseif (isset($this->_activeFields[$i]->_websiteType)) {
          $value = array($this->_activeFields[$i]->_name => $this->_activeFields[$i]->_value,
            'website_type_id' => $this->_activeFields[$i]->_websiteType,
          );

          $params[$this->_activeFields[$i]->_name][] = $value;
        }

        if (!isset($params[$this->_activeFields[$i]->_name])) {
          if (!isset($this->_activeFields[$i]->_softCreditField)) {
            $params[$this->_activeFields[$i]->_name] = $this->_activeFields[$i]->_value;
          }
        }
      }
    }
    return $params;
  }

  function getSelectValues() {
    $values = array();
    foreach ($this->_fields as $name => $field) {
      $values[$name] = $field->_title;
    }
    return $values;
  }

  function getSelectTypes() {
    $values = array();
    foreach ($this->_fields as $name => $field) {
      if (isset($field->_hasLocationType)) {
        $values[$name] = $field->_hasLocationType;
      }
    }
    return $values;
  }

  function getHeaderPatterns() {
    $values = array();
    foreach ($this->_fields as $name => $field) {
      if (isset($field->_headerPattern)) {
        $values[$name] = $field->_headerPattern;
      }
    }
    return $values;
  }

  function getDataPatterns() {
    /**
      priority of fields is 'email', 'total amount', 'Each date fields like join_date, start_date', 'phone', 'contribute fields', 'contact fields'
    */
    $values = $contribute_fields = $contact_fields = array();
    $priority_fields = array(
      'email' => '',
      'total_amount' => '',
    );
    $secondary_fields = array(
      'phone' => '',
    );
    foreach ($this->_fields as $name => $field) {
      if(isset($priority_fields[$name])){
        $priority_fields[$name] = $field->_dataPattern;
      }
      elseif(preg_match('/_date$/', $name)){
        $priority_fields[$name] = $field->_dataPattern;
      }
      elseif(isset($secondary_fields[$name])){
        $secondary_fields[$name] = $field->_dataPattern;
      }
      elseif(preg_match('/^'.ts('Contact').'::/', $field->_title)){
        $contact_fields[$name] = $field->_dataPattern;
      }
      else{
        $contribute_fields[$name] = $field->_dataPattern;
      }
    }
    $values = array_merge($priority_fields, $secondary_fields, $contribute_fields, $contact_fields);
    return $values;
  }

  function addField($name, $title, $type = CRM_Utils_Type::T_INT, $headerPattern = '//', $dataPattern = '//', $hasLocationType = FALSE) {
    if (empty($name)) {
      $this->_fields['doNotImport'] = new CRM_Contribute_Import_Field($name, $title, $type, $headerPattern, $dataPattern, $hasLocationType);
    }
    else {
      $tempField = CRM_Contact_BAO_Contact::importableFields('All', NULL);
      if (!array_key_exists($name, $tempField)) {
        $this->_fields[$name] = new CRM_Contribute_Import_Field($name, $title, $type, $headerPattern, $dataPattern, $hasLocationType);
      }
      else {
        $this->_fields[$name] = new CRM_Import_Field($name, $title, $type, $headerPattern, $dataPattern, $hasLocationType);
      }
    }
  }

  /**
   * setter function
   *
   * @param int $max
   *
   * @return void
   * @access public
   */
  function setMaxLinesToProcess($max) {
    $this->_maxLinesToProcess = $max;
  }

  /**
   * Store parser values
   *
   * @param CRM_Core_Session $store
   *
   * @return void
   * @access public
   */
  function set($store, $mode = self::MODE_SUMMARY) {
    $store->set('fileSize', $this->_fileSize);
    $store->set('lineCount', $this->_lineCount);
    $store->set('seperator', $this->_seperator);
    $store->set('fields', $this->getSelectValues());
    $store->set('fieldTypes', $this->getSelectTypes());

    $store->set('headerPatterns', $this->getHeaderPatterns());
    $store->set('dataPatterns', $this->getDataPatterns());
    $store->set('columnCount', $this->_activeFieldCount);

    $store->set('totalRowCount', $this->_totalCount);
    $store->set('validRowCount', $this->_validCount);
    $store->set('invalidRowCount', $this->_invalidRowCount);
    $store->set('invalidSoftCreditRowCount', $this->_invalidSoftCreditRowCount);
    $store->set('validSoftCreditRowCount', $this->_validSoftCreditRowCount);
    $store->set('validPCPRowCount', $this->_validPCPRowCount);
    $store->set('invalidPCPRowCount', $this->_invalidPCPRowCount);
    $store->set('invalidPledgePaymentRowCount', $this->_invalidPledgePaymentRowCount);
    $store->set('validPledgePaymentRowCount', $this->_validPledgePaymentRowCount);
    $store->set('conflictRowCount', $this->_conflictCount);

    switch ($this->_contactType) {
      case 'Individual':
        $store->set('contactType', CRM_Contribute_Import_Parser::CONTACT_INDIVIDUAL);
        break;

      case 'Household':
        $store->set('contactType', CRM_Contribute_Import_Parser::CONTACT_HOUSEHOLD);
        break;

      case 'Organization':
        $store->set('contactType', CRM_Contribute_Import_Parser::CONTACT_ORGANIZATION);
    }

    if ($this->_invalidRowCount) {
      $store->set('errorsFileName', $this->_errorFileName);
    }
    if ($this->_conflictCount) {
      $store->set('conflictsFileName', $this->_conflictFileName);
    }
    if (isset($this->_rows) && !empty($this->_rows)) {
      $store->set('dataValues', $this->_rows);
    }

    if ($this->_invalidPledgePaymentRowCount) {
      $store->set('pledgePaymentErrorsFileName', $this->_pledgePaymentErrorsFileName);
    }

    if ($this->_invalidSoftCreditRowCount) {
      $store->set('softCreditErrorsFileName', $this->_softCreditErrorsFileName);
    }

    if ($this->_invalidPCPRowCount) {
      $store->set('pcpErrorsFileName', $this->_pcpErrorsFileName);
    }

    if ($mode == self::MODE_IMPORT) {
      $store->set('duplicateRowCount', $this->_duplicateCount);
      if ($this->_duplicateCount) {
        $store->set('duplicatesFileName', $this->_duplicateFileName);
      }
    }
    //echo "$this->_totalCount,$this->_invalidRowCount,$this->_conflictCount,$this->_duplicateCount";
  }

  /**
   * Export data to a CSV file
   *
   * @param string $filename
   * @param array $header
   * @param data $data
   *
   * @return void
   * @access public
   */
  static function exportCSV($fileName, $header, $data) {
    CRM_Core_Report_Excel::writeExcelFile($fileName, $header, $data, $download = FALSE);
  }

  /**
   * Remove single-quote enclosures from a value array (row)
   *
   * @param array $values
   * @param string $enclosure
   *
   * @return void
   * @static
   * @access public
   */
  static function encloseScrub(&$values, $enclosure = "'") {
    if (empty($values)) {
      return;
    }

    foreach ($values as $k => $v) {
      $values[$k] = preg_replace("/^$enclosure(.*) $enclosure$/", '$1', $v);
    }
  }

  function errorFileName($type) {
    $fileName = NULL;
    if (empty($type)) {
      return $fileName;
    }

    $config = CRM_Core_Config::singleton();
    $fileName = "sqlImport";

    switch ($type) {
      case CRM_Contribute_Import_Parser::ERROR:
      case CRM_Contribute_Import_Parser::NO_MATCH:
      case CRM_Contribute_Import_Parser::CONFLICT:
      case CRM_Contribute_Import_Parser::DUPLICATE:
        //here constants get collides.
        require_once 'CRM/Import/Parser.php';
        if ($type == CRM_Contribute_Import_Parser::ERROR) {
          $type = CRM_Import_Parser::ERROR;
        }
        elseif ($type == CRM_Contribute_Import_Parser::NO_MATCH) {
          $type = CRM_Import_Parser::NO_MATCH;
        }
        elseif ($type == CRM_Contribute_Import_Parser::CONFLICT) {
          $type = CRM_Import_Parser::CONFLICT;
        }
        else {
          $type = CRM_Import_Parser::DUPLICATE;
        }
        $fileName = CRM_Import_Parser::errorFileName($type);
        break;

      case CRM_Contribute_Import_Parser::SOFT_CREDIT_ERROR:
        $fileName .= '.softCreditErrors.xlsx';
        break;

      case CRM_Contribute_Import_Parser::PLEDGE_PAYMENT_ERROR:
        $fileName .= '.pledgePaymentErrors.xlsx';
        break;

      case CRM_Contribute_Import_Parser::PCP_ERROR:
        $fileName .= '.pcpErrors.xlsx';
        break;
    }

    return $fileName;
  }

  function saveFileName($type) {
    $fileName = NULL;
    if (empty($type)) {
      return $fileName;
    }

    switch ($type) {
      case CRM_Contribute_Import_Parser::ERROR:
      case CRM_Contribute_Import_Parser::NO_MATCH:
      case CRM_Contribute_Import_Parser::CONFLICT:
      case CRM_Contribute_Import_Parser::DUPLICATE:
        //here constants get collides.
        require_once 'CRM/Import/Parser.php';
        if ($type == CRM_Contribute_Import_Parser::ERROR) {
          $type = CRM_Import_Parser::ERROR;
        }
        elseif ($type == CRM_Contribute_Import_Parser::NO_MATCH) {
          $type = CRM_Import_Parser::NO_MATCH;
        }
        elseif ($type == CRM_Contribute_Import_Parser::CONFLICT) {
          $type = CRM_Import_Parser::CONFLICT;
        }
        else {
          $type = CRM_Import_Parser::DUPLICATE;
        }
        $fileName = CRM_Import_Parser::saveFileName($type);
        break;

      case CRM_Contribute_Import_Parser::SOFT_CREDIT_ERROR:
        $fileName = 'Import_Soft_Credit_Errors.xlsx';
        break;

      case CRM_Contribute_Import_Parser::PLEDGE_PAYMENT_ERROR:
        $fileName = 'Import_Pledge_Payment_Errors.xlsx';
        break;

      case CRM_Contribute_Import_Parser::PCP_ERROR:
        $fileName = 'Import_PCP_Errors.xlsx';
        break;
    }

    return $fileName;
  }
}

