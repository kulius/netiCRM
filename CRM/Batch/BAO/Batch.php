<?php
/**
 * Batch BAO class.
 */
class CRM_Batch_BAO_Batch extends CRM_Batch_DAO_Batch {

  /**
   * queue name
   */
  const QUEUE_NAME = 'batch_auto';

  /**
   * Batch id to load
   * @var int
   */
  public $_id = NULL;

  /**
   * Cache for the current batch object.
   * @var object
   */
  public $_batch = NULL;

  /**
   * Status of batch
   * @var array
   */
  public static $_batchStatus = array();

  /**
   * Type of batch
   * @var array
   */
  public static $_batchType = array();


  /**
   * Create a new batch.
   *
   * @param array $params
   *
   * @return object
   *   $batch batch object
   */
  public static function create(&$params) {
    $op = 'edit';
    $batchId = CRM_Utils_Array::value('id', $params);
    if (!$batchId) {
      $op = 'create';
      $params['name'] = CRM_Utils_String::titleToVar($params['title']);
    }
    CRM_Utils_Hook::pre($op, 'Batch', $batchId, $params);
    $batch = new CRM_Batch_DAO_Batch();
    if (!empty($params['data'])) {
      if (is_array($params['data'])) {
        $params['data'] = serialize($params);
      }
    }
    $batch->copyValues($params);
    $batch->save();

    CRM_Utils_Hook::post($op, 'Batch', $batch->id, $batch);
    $batch->data = unserialize($batch->data);
    return $batch;
  }

  /**
   * Retrieve the information about the batch.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference ) an assoc array to hold the flattened values.
   *
   * @return array
   *   CRM_Batch_BAO_Batch object on success, null otherwise
   */
  public static function retrieve(&$params, &$defaults) {
    $batch = new CRM_Batch_DAO_Batch();
    $batch->copyValues($params);
    if ($batch->find(TRUE)) {
      CRM_Core_DAO::storeValues($batch, $defaults);
      if (!empty($batch->data)) {
        $batch->data = unserialize($batch->data);
        $defaults['data'] = $batch->data;
      }
      return $batch;
    }
    return NULL;
  }

  /**
   * Function get batch statuses.
   *
   * @return array
   *   array of statuses 
   */
  public static function batchStatus() {
    self::$_batchStatus = CRM_Core_OptionGroup::values('batch_status', FALSE, FALSE, FALSE, NULL, 'name');
    return self::$_batchStatus;
  }

  /**
   * Function get batch types.
   *
   * @return array
   *   array of batches
   */
  public static function batchType() {
    self::$_batchType = CRM_Core_OptionGroup::values('batch_type', FALSE, FALSE, FALSE, NULL, 'name');
    return self::$_batchType;
  }

  /**
   * Run last queuing batching
   *
   * @return string
   *   message that indicate current running status
   */
  public static function runQueue() {
    $type = self::batchType();
    $status = self::batchStatus();
    unset($status['Completed']);
    unset($status['Canceled']);
    $sql = "SELECT id FROM civicrm_batch WHERE type_id = %1 AND status_id IN (".implode(',', $status).") ORDER BY created_date ASC LIMIT 1";
    $dao = CRM_Core_DAO::singleValueQuery($sql, array(
      1 => array($type['auto'], 'Integer'),
    ));

    $message = '';
    if (!empty($dao->id)) {
      // check if running currently or running over 1 hour
      $batch = new CRM_Batch_BAO_Batch($dao->id);
      $running = $batch->dupeCheck();
      if ($running->value) {
        if (CRM_REQUEST_TIME - $running->timestamp > 3600) {
          if ($batch->dupeDelete()) {
            $message = ts('Found batch job number %1 running over 1 hour. We delete this job then start another batch job number %2.', array(1 => $running->value, 2 => $batch->_id));
            CRM_Core_Error::debug_log_message($message);
            // start another process
            $batch->process();
          }
        }
        else {
          $message = ts('We still have running batch job in queue recently.');
        }
      }
      else {
        $batch->process();
        $message = ts('Success processing queuing batch.');
      }
    }
    return $message;
  }

  /**
   * Constructor
   * 
   * @param int
   *   batch id to load whole batch object
   * 
   * @return object
   */
  function __construct($batchId = NULL) {
    self::batchType();
    self::batchStatus();
    if ($batchId) {
      $this->_id = $batchId;
      $params = array('id' => $this->_id);
      $defaults = array();
      $this->_batch = self::retrieve($params, $defaults);
      $this->
    }
  }

  /**
   * Create and start a batch process 
   * 
   * @param array
   *   information that batch process needed.
   * 
   * @return object
   *   batch object that just insert into db
   */
  public function start($arguments) {
    // check if we have running job currently
    $runningStatus = self::$_batchStatus['Running'];
    $runningBatch = CRM_Core_DAO::getFieldValue('CRM_Batch_DAO_Batch', $runningStatus, 'id', 'status_id', TRUE);
    if ($runningBatch) {
      $statusId = self::$_batchStatus['Pending'];
    }
    else {
      $statusId = $runningStatus;
    }
    $session = CRM_Core_Session::singleton();
    $currentContact = $session->get('userID');
    $params = array(
      'name' => 'batch-'.date('YmdHis').'.'.mt_rand(1,100),
      'label' => $arguments['label'],
      'description' => $arguments['description'],
      'created_id' => $currentContact,
      'created_date' => date('Y-m-d H:i:s'),
      'modified_id' => 'null',
      'modified_date' => 'null',
      'status_id' => $statusId,
      'type_id' => self::$_batchType['auto'],
      'data' => $arguments,
    );
    $batch = self::create($params);
    $this->_batch = $batch;
    $this->_id = $batch->id;

    // after saved start logic, trigger logic to handling before start warehousing
    // do not use start callback to process rows. use process instead.
    if (isset($this->_batch->data['start_callback'])) {
      if (!empty($this->_batch['start_callback_args'])) {
        call_user_func_array($this->_batch['start_callback'], $this->_batch['start_callback_args']);
      }
      else {
        call_user_func($this->_batch['start_callback']);
      }
    }
    return $this->_batch;
  }

  /**
   * Process part of batch each run
   * 
   * @param int   $batchId
   *   an id of batch process to load 
   * 
   * @return null
   */
  public function process($force = FALSE) {
    // start processing, insert record in db to prevent duplicate running
    $this->dupeInsert();

    // real processing logic 
    if (isset($this->_batch->data['process_callback'])) {
      // TODO - still need a way to calculate processed rows
      if (!empty($this->_batch['process_callback_args'])) {
        call_user_func_array($this->_batch['process_callback'], $this->_batch['process_callback_args']);
      }
      else {
        call_user_func($this->_batch['process_callback']);
      }
    }

    // end processing
    $this->dupeDelete();
  }

  /**
   * Finish batch
   * 
   * @param int   $batchId
   *   an id of batch process to load 
   * 
   * @return null
   */
  public function finish() {
    // before finish, trigger logic to handling ending of batch
    if (isset($this->_batch->data['finish_callback'])) {
      if (!empty($this->_batch->data['finish_callback_args']) && is_array($this->_batch->data['finish_callback_args'])) {
        call_user_func_array($this->_batch->data['finish_callback'], $this->_batch->data['finish_callback_args']);
      }
      else {
        call_user_func($this->_batch->data['finish_callback']);
      }
    }
    // after finish, don't forget to delete job.
    $this->dupeDelete();
  }


  /**
   * Duplicate running process check
   * 
   * @return object|bool
   */
  protected function dupeCheck() {
    $dao = new CRM_Core_DAO_Sequence();
    $dao->name = self::QUEUE_NAME;
    if ($dao->find(TRUE)) {
      return $dao;
    }
    return FALSE;
  }

  /**
   * Duplicate running object insert
   * 
   * @return object
   */
  protected function dupeInsert() {
    $dao = new CRM_Core_DAO_Sequence();
    $dao->name = self::QUEUE_NAME;
    if ($dao->find(TRUE)) {
      $dao->timestamp = time();
      $dao->value = $this->_id;
      $dao->save();
    }
    else {
      $dao->timestamp = time();
      $dao->value = $this->_id;
      $dao->save();
    }
    return $dao;
  }

  /**
   * Duplicate running object delete
   * 
   * @return bool
   */
  protected function dupeDelete() {
    $dao = new CRM_Core_DAO_Sequence();
    $dao->name = self::QUEUE_NAME;
    if ($dao->find(TRUE)) {
      return $dao->delete();
    }
    return FALSE;
  }
}