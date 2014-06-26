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
class CRM_Mailing_Event_DAO_TrackableURLOpen extends CRM_Core_DAO {

  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'civicrm_mailing_event_trackable_url_open';

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
   * FK to EventQueue
   *
   * @var int unsigned
   */
  public $event_queue_id;

  /**
   * FK to TrackableURL
   *
   * @var int unsigned
   */
  public $trackable_url_id;

  /**
   * When this trackable URL open occurred.
   *
   * @var datetime
   */
  public $time_stamp;

  /**
   * class constructor
   *
   * @access public
   *
   * @return civicrm_mailing_event_trackable_url_open
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
        'event_queue_id' => 'civicrm_mailing_event_queue:id',
        'trackable_url_id' => 'civicrm_mailing_trackable_url:id',
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
        'event_queue_id' => array(
          'name' => 'event_queue_id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => TRUE,
          'FKClassName' => 'CRM_Mailing_Event_DAO_Queue',
        ),
        'trackable_url_id' => array(
          'name' => 'trackable_url_id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => TRUE,
          'FKClassName' => 'CRM_Mailing_DAO_TrackableURL',
        ),
        'time_stamp' => array(
          'name' => 'time_stamp',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Time Stamp'),
          'required' => TRUE,
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
    return self::$_tableName;
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
            self::$_import['mailing_event_trackable_url_open'] = &$fields[$name];
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
            self::$_export['mailing_event_trackable_url_open'] = &$fields[$name];
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

