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
require_once 'CRM/Core/DAO.php';
require_once 'CRM/Utils/Type.php';
class CRM_Core_DAO_Dashboard extends CRM_Core_DAO {

  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'civicrm_dashboard';

  /**
   * static instance to hold the field values
   *
   * @var array
   * @static
   */
  static $_fields = NULL;

  /**
   * static instance to hold the FK relationships
   *
   * @var string
   * @static
   */
  static $_links = NULL;

  /**
   * static instance to hold the values that can
   * be imported / apu
   *
   * @var array
   * @static
   */
  static $_import = NULL;

  /**
   * static instance to hold the values that can
   * be exported / apu
   *
   * @var array
   * @static
   */
  static $_export = NULL;

  /**
   * static value to see if we should log any modifications to
   * this table in the civicrm_log table
   *
   * @var boolean
   * @static
   */
  static $_log = FALSE;

  /**
   *
   * @var int unsigned
   */
  public $id;

  /**
   * Domain for dashboard
   *
   * @var int unsigned
   */
  public $domain_id;

  /**
   * dashlet title
   *
   * @var string
   */
  public $label;

  /**
   * url in case of external dashlet
   *
   * @var string
   */
  public $url;

  /**
   * dashlet content
   *
   * @var text
   */
  public $content;

  /**
   * Permission for the dashlet
   *
   * @var string
   */
  public $permission;

  /**
   * Permission Operator
   *
   * @var string
   */
  public $permission_operator;

  /**
   * column no for this dashlet
   *
   * @var boolean
   */
  public $column_no;

  /**
   * Is Minimized?
   *
   * @var boolean
   */
  public $is_minimized;

  /**
   * Is Fullscreen?
   *
   * @var boolean
   */
  public $is_fullscreen;

  /**
   * Is this dashlet active?
   *
   * @var boolean
   */
  public $is_active;

  /**
   * Is this dashlet reserved?
   *
   * @var boolean
   */
  public $is_reserved;

  /**
   * Ordering of the dashlets.
   *
   * @var int
   */
  public $weight;

  /**
   * When was content populated
   *
   * @var datetime
   */
  public $created_date;

  /**
   * class constructor
   *
   * @access public
   *
   * @return civicrm_dashboard
   */ function __construct() {
    parent::__construct();
  }

  /**
   * return foreign links
   *
   * @access public
   *
   * @return array
   */
  function &links() {
    if (!(self::$_links)) {
      self::$_links = array(
        'domain_id' => 'civicrm_domain:id',
      );
    }
    return self::$_links;
  }

  /**
   * returns all the column names of this table
   *
   * @access public
   *
   * @return array
   */
  function &fields() {
    if (!(self::$_fields)) {
      self::$_fields = array(
        'id' => array(
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => TRUE,
        ),
        'domain_id' => array(
          'name' => 'domain_id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => TRUE,
          'FKClassName' => 'CRM_Core_DAO_Domain',
        ),
        'label' => array(
          'name' => 'label',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Label'),
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ),
        'url' => array(
          'name' => 'url',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Url'),
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ),
        'content' => array(
          'name' => 'content',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Content'),
        ),
        'permission' => array(
          'name' => 'permission',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Permission'),
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ),
        'permission_operator' => array(
          'name' => 'permission_operator',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Permission Operator'),
          'maxlength' => 3,
          'size' => CRM_Utils_Type::FOUR,
        ),
        'column_no' => array(
          'name' => 'column_no',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Column No'),
        ),
        'is_minimized' => array(
          'name' => 'is_minimized',
          'type' => CRM_Utils_Type::T_BOOLEAN,
        ),
        'is_fullscreen' => array(
          'name' => 'is_fullscreen',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'default' => '',
        ),
        'is_active' => array(
          'name' => 'is_active',
          'type' => CRM_Utils_Type::T_BOOLEAN,
        ),
        'is_reserved' => array(
          'name' => 'is_reserved',
          'type' => CRM_Utils_Type::T_BOOLEAN,
        ),
        'weight' => array(
          'name' => 'weight',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Weight'),
        ),
        'created_date' => array(
          'name' => 'created_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Created Date'),
        ),
      );
    }
    return self::$_fields;
  }

  /**
   * returns the names of this table
   *
   * @access public
   *
   * @return string
   */
  function getTableName() {
    global $dbLocale;
    return self::$_tableName . $dbLocale;
  }

  /**
   * returns if this table needs to be logged
   *
   * @access public
   *
   * @return boolean
   */
  function getLog() {
    return self::$_log;
  }

  /**
   * returns the list of fields that can be imported
   *
   * @access public
   * return array
   */
  function &import($prefix = FALSE) {
    if (!(self::$_import)) {
      self::$_import = array();
      $fields = &self::fields();
      foreach ($fields as $name => $field) {
        if (CRM_Utils_Array::value('import', $field)) {
          if ($prefix) {
            self::$_import['dashboard'] = &$fields[$name];
          }
          else {
            self::$_import[$name] = &$fields[$name];
          }
        }
      }
    }
    return self::$_import;
  }

  /**
   * returns the list of fields that can be exported
   *
   * @access public
   * return array
   */
  function &export($prefix = FALSE) {
    if (!(self::$_export)) {
      self::$_export = array();
      $fields = &self::fields();
      foreach ($fields as $name => $field) {
        if (CRM_Utils_Array::value('export', $field)) {
          if ($prefix) {
            self::$_export['dashboard'] = &$fields[$name];
          }
          else {
            self::$_export[$name] = &$fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
}

