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

require_once 'CRM/Core/Page.php';

/**
 * Page for displaying list of Payment-Instrument
 */
class CRM_Contribute_Page_DashBoard extends CRM_Core_Page {

  /**
   * Heart of the viewing process. The runner gets all the meta data for
   * the contact and calls the appropriate type of page to view.
   *
   * @return void
   * @access public
   *
   */
  function preProcess() {
    CRM_Utils_System::setTitle(ts('CiviContribute'));

    $status = array('Valid', 'Cancelled');
    $prefixes = array('start', 'month', 'year');
    $startDate = NULL;
    $startToDate = $monthToDate = $yearToDate = array();

    //get contribution dates.
    require_once 'CRM/Contribute/BAO/Contribution.php';
    $dates = CRM_Contribute_BAO_Contribution::getContributionDates();
    foreach (array('now', 'yearDate', 'monthDate') as $date) {
      $$date = $dates[$date];
    }
    $yearNow = $yearDate + 10000;
    foreach ($prefixes as $prefix) {
      $aName = $prefix . 'ToDate';
      $dName = $prefix . 'Date';

      if ($prefix == 'year') {
        $now = $yearNow;
      }

      foreach ($status as $s) {
        ${$aName}[$s] = CRM_Contribute_BAO_Contribution::getTotalAmountAndCount($s, $$dName, $now);
        ${$aName}[$s]['url'] = CRM_Utils_System::url('civicrm/contribute/search',
          "reset=1&force=1&status=1&start={$$dName}&end=$now&test=0"
        );
      }

      $this->assign($aName, $$aName);
    }

    //for contribution tabular View
    $buildTabularView = CRM_Utils_Array::value('showtable', $_GET, FALSE);
    $this->assign('buildTabularView', $buildTabularView);
    if ($buildTabularView) {
      return;
    }

    // Check for admin permission to see if we should include the Manage Contribution Pages action link
    $isAdmin = 0;
    require_once 'CRM/Core/Permission.php';
    if (CRM_Core_Permission::check('administer CiviCRM')) {
      $isAdmin = 1;
    }
    $this->assign('isAdmin', $isAdmin);
  }

  /**
   * This function is the main function that is called when the page loads,
   * it decides the which action has to be taken for the page.
   *
   * return null
   * @access public
   */
  function run() {
    // block contribution
    $this->preProcess();
    $this->getDate();
    $this->processDashBoard();

    // block last contribution
    $controller = new CRM_Core_Controller_Simple('CRM_Contribute_Form_Search',
      ts('Contributions'), NULL
    );
    $controller->setEmbedded(TRUE);
    $controller->set('limit', 10);
    $controller->set('force', 1);
    $controller->set('context', 'dashboard');
    $controller->process();
    $controller->run();

    $chartForm = new CRM_Core_Controller_Simple('CRM_Contribute_Form_ContributionCharts',
      ts('Contributions Charts'), NULL
    );

    $chartForm->setEmbedded(TRUE);
    $chartForm->process();
    $chartForm->run();

    return parent::run();
  }

  function getDate($start_date = NULL, $end_date = NULL){
    $end_date = $this->end_date = $end_date ? $end_date : ($_GET['end_date'] ? $_GET['end_date'] : date('Y-m-d'));
    $start_date = $this->start_date = $start_date ? $start_date : ($_GET['start_date'] ? $_GET['start_date'] : date('Y-m-d', strtotime('-30day')));

    $end_timestamp = strtotime($end_date);
    $start_timestamp = strtotime($start_date);
    if(empty($end_timestamp)){
      CRM_Core_Error::fatal(ts('End date is not valid date format.'));
    }
    if(empty($start_timestamp)){
      CRM_Core_Error::fatal(ts('Start date is not valid date format.'));
    }
    if($start_timestamp > $end_timestamp){
      CRM_Core_Error::fatal(ts('Start date should not be after End date.'));
    }

    list($last_start_date, $last_end_date) = self::getLastDurationTime($start_date, $end_date);
    $this->last_start_date =  $last_start_date;
    $this->last_end_date = $last_end_date;

    $duration_array = array();
    $count_date_stamp = strtotime($this->start_date);
    while($count_date_stamp < strtotime($this->end_date)){
      $duration_array[] = date('Y-m-d', $count_date_stamp);
      $count_date_stamp+=86400;
    }
    $this->duration_array = $duration_array;

    $this->days = ceil(($end_timestamp - $start_timestamp) / 86400);

    $this->params_duration = array(
      1 => array($start_date, 'String'),
      2 => array($end_date, 'String'),
    );
    $this->params_last_duration = array(
      1 => array($last_start_date, 'String'),
      2 => array($last_end_date, 'String'),
    );

    $this->assign('days', $this->days);
    $this->assign('start_date', $this->start_date);
    $this->assign('end_date', $this->end_date);

  }

  function processDashBoard(){

    // refs #22871 add chart data
    $filter_time = array('start_date' => $this->start_date, 'end_date' => $this->end_date);
    $filter_all_year = array('start_date' => date('Y').'-01-01', 'end_date' => date('Y-m-d'));

    $filter_recur = array('contribution_recur_id' => TRUE);
    $filter_not_recur = array('contribution_recur_id' => FALSE);
    $summary_contrib['ContribThisYear']['recur'] = CRM_Report_BAO_Summary::getStaWithCondition(CRM_Report_BAO_Summary::CONTRIBUTION_RECEIVE_DATE,array('interval' => 'MONTH'), array('contribution' => $filter_all_year+$filter_recur));
    $summary_contrib['ContribThisYear']['not_recur'] = CRM_Report_BAO_Summary::getStaWithCondition(CRM_Report_BAO_Summary::CONTRIBUTION_RECEIVE_DATE,array('interval' => 'MONTH'), array('contribution' => $filter_all_year+$filter_not_recur));

    $summary_contrib['LastDurationContrib']['recur'] = CRM_Report_BAO_Summary::getStaWithCondition(CRM_Report_BAO_Summary::CONTRIBUTION_RECEIVE_DATE,array('interval' => 'DAY'), array('contribution' => $filter_time+$filter_recur));
    $summary_contrib['LastDurationContrib']['not_recur'] = CRM_Report_BAO_Summary::getStaWithCondition(CRM_Report_BAO_Summary::CONTRIBUTION_RECEIVE_DATE,array('interval' => 'DAY'), array('contribution' => $filter_time+$filter_not_recur));

    $summary_contrib['LastDurationProvince']['recur'] = CRM_Report_BAO_Summary::getStaWithCondition(CRM_Report_BAO_Summary::PROVINCE, array('contribution' => 1, 'seperate_other' => 1), array('contribution' => $filter_time+$filter_recur));
    $summary_contrib['LastDurationProvince']['not_recur'] = CRM_Report_BAO_Summary::getStaWithCondition(CRM_Report_BAO_Summary::PROVINCE,array('contribution' => 1, 'seperate_other' => 1), array('contribution' => $filter_time+$filter_not_recur));
    if($_GET['debug']){
      dpm($summary_contrib);
    }

    $one_year_label = $year_month_label = array();
    for ($month=1; $month <= 12 ; $month++) {
      $one_year_label[] = $month.'月';
      $year_month = date('Y').'-'.sprintf('%02d',$month);
      $year_month_label[] = $year_month;
    }

    $recur_year_sum = self::getDataForChart($year_month_label, $summary_contrib['ContribThisYear']['recur']);
    $not_recur_year_sum = self::getDataForChart($year_month_label, $summary_contrib['ContribThisYear']['not_recur']);
    for ($i=1; $i < 12; $i++) {
      if($i <= date('m')){
        $recur_year_sum[$i] += $recur_year_sum[$i-1];
        $not_recur_year_sum[$i] += $not_recur_year_sum[$i-1];
      }else{
        unset($recur_year_sum[$i]);
        unset($not_recur_year_sum[$i]);
      }
    }

    $chart = array(
      'id' => 'chart-one-year',
      'selector' => '#chart-one-year',
      'type' => 'Line',
      'labels' => json_encode($one_year_label),
      'series' => json_encode(array($recur_year_sum, $not_recur_year_sum)),
      'seriesUnit' => '$ ',
      'seriesUnitPosition'=> 'prefix',
      'withToolTip' => true,
      'stackLines' => true
    );
    $this->assign('chart_this_year', $chart);

    foreach ($this->duration_array as $date) {
      $recur_index = array_search($date, $summary_contrib['LastDurationContrib']['recur']['label']);
    }
    $recur_duration_sum = self::getDataForChart($this->duration_array, $summary_contrib['LastDurationContrib']['recur']);
    $not_recur_duration_sum = self::getDataForChart($this->duration_array, $summary_contrib['LastDurationContrib']['not_recur']);

    $chart = array(
      'id' => 'chart-duration-sum',
      'selector' => '#chart-duration-sum',
      'type' => 'Line',
      'labels' => json_encode($this->duration_array),
      'series' => json_encode(array($recur_duration_sum, $not_recur_duration_sum)),
      'seriesUnit' => '$ ',
      'seriesUnitPosition'=> 'prefix',
      'withToolTip' => true,
    );
    $this->assign('chart_duration_sum', $chart);

    // $sql = "SELECT t.referrer_type, t.referrer_type FROM civicrm_track t INNER JOIN civicrm_contribution_page cp ON t.entity_id = cp.id WHERE t.page_type = 'civicrm_contribution_page' AND cp.is_active = 1 AND t.visit_date >= %1";
    $sql = "SELECT t.referrer_type referrer_type FROM civicrm_track t INNER JOIN civicrm_contribution_page cp ON t.page_id = cp.id WHERE t.page_type = 'civicrm_contribution_page' AND cp.is_active = 1 AND t.visit_date >= %1 AND t.visit_date < %2 GROUP BY referrer_type";
    $dao = CRM_Core_DAO::executeQuery($sql, $this->params_duration);
    $track_label = array();
    while($dao->fetch()){
      if(!$dao->referrer_type){
        $track_label[] = ts('Others');
      }else{
        if(!in_array($dao->referrer_type, $track_label)){
          $track_label[] = $dao->referrer_type;
        }
      }
    }
    $count_track = count($track_label) ? count($track_label) : 1;
    $duration_track = array_fill(0, count($this->duration_array), array_fill(0, $count_track, 0));

    $sql = "SELECT t.referrer_type, count(t.referrer_type) count, DATE_FORMAT(t.visit_date,'%Y-%m-%d') visit_day FROM civicrm_track t INNER JOIN civicrm_contribution_page cp ON t.page_id = cp.id WHERE t.page_type = 'civicrm_contribution_page' AND cp.is_active = 1 AND t.visit_date >= %1 AND t.visit_date < %2 GROUP BY visit_day, referrer_type";
    $dao = CRM_Core_DAO::executeQuery($sql, $this->params_duration);
    while($dao->fetch()){
      $inx_date = array_search($dao->visit_day, $this->duration_array);
      if(!$dao->referrer_type){
        $track_network = ts('Others');
      }else{
        $track_network = $dao->referrer_type;
      }
      $inx_network = array_search($track_network, $track_label);
      $duration_track[$inx_date][$inx_network] = $dao->count;
    }

    if($_GET['debug']){
      dpm($duration_track);
    }

    $chart = array(
      'id' => 'chart-duration-track',
      'selector' => '#chart-duration-track',
      'type' => 'Bar',
      'labels' => json_encode($this->duration_array),
      'series' => json_encode($duration_track),
      'seriesUnit' => '$ ',
      'seriesUnitPosition'=> 'prefix',
      'withToolTip' => true,
      'stackBars' => true
    );
    $this->assign('chart_duration_track', $chart);

    $duration_province_recur_label = empty($summary_contrib['LastDurationProvince']['recur']) ? array() : $summary_contrib['LastDurationProvince']['recur']['label'];
    $duration_province_not_recur_label = empty($summary_contrib['LastDurationProvince']['not_recur']) ? array() : $summary_contrib['LastDurationProvince']['not_recur']['label'];
    $duration_province_label =  array_unique(array_merge($duration_province_recur_label, $duration_province_not_recur_label));
    $duration_province_recur_sum = self::getDataForChart($duration_province_label, $summary_contrib['LastDurationProvince']['recur']);
    $duration_province_not_recur_sum = self::getDataForChart($duration_province_label, $summary_contrib['LastDurationProvince']['not_recur']);

    $chart = array(
      'id' => 'chart-duration-province-sum',
      'selector' => '#chart-duration-province-sum',
      'type' => 'Bar',
      'labels' => json_encode($duration_province_label),
      'series' => json_encode(array($duration_province_recur_sum, $duration_province_not_recur_sum)),
      'seriesUnit' => '$ ',
      'seriesUnitPosition'=> 'prefix',
      'withToolTip' => true,
      'stackBars' => true
    );
    $this->assign('chart_duration_province_sum', $chart);

    // First contribtion contact in last 30 days
    $sql = "  SELECT COUNT(c.id) ct, ccd.id, SUM(ccd.total_amount) sum FROM civicrm_contact c
      INNER JOIN ( SELECT id, contact_id, total_amount FROM civicrm_contribution WHERE receive_date >= %1 AND receive_date < %2 AND is_test = 0 AND contribution_status_id = 1 GROUP BY contact_id ) ccd ON c.id = ccd.contact_id
      INNER JOIN ( SELECT id, contact_id FROM civicrm_contribution WHERE is_test = 0 AND contribution_status_id = 1 GROUP BY contact_id ) cc_all ON c.id = cc_all.contact_id WHERE ccd.id = cc_all.id;";
    $dao = CRM_Core_DAO::executeQuery($sql, $this->params_duration);
    if($dao->fetch()){
      $duration_count = $dao->ct;
      $duration_sum = $dao->sum;
    }
    $dao = CRM_Core_DAO::executeQuery($sql, $this->params_last_duration);
    if($dao->fetch()){
      $last_duration_count = $dao->ct;
    }

    $this->assign('duration_count', $duration_count);
    if($last_duration_count > 0){
      $duration_count_growth = ( $duratioin_count / $last_duration_count ) -1;
      $this->assign('duration_count_growth', number_format($duration_count_growth * 100, 2));
    }

    $sql = "SELECT * FROM civicrm_contribution cc INNER JOIN civicrm_contact c ON cc.contact_id = c.id WHERE cc.is_test = 0 AND cc.contribution_status_id = 1 AND receive_date >= %1 AND receive_date < %2 ORDER BY cc.total_amount DESC LIMIT 1;";
    $dao = CRM_Core_DAO::executeQuery($sql, $this->params_duration);
    if($dao->fetch()){
      $this->assign('duration_max_amount', $dao->total_amount);
      $this->assign('duration_max_id', $dao->id);
      $this->assign('duration_max_contact_id', $dao->contact_id);
      $this->assign('duration_max_display_name', $dao->display_name);
      $this->assign('duration_max_receive_date', $dao->receive_date);
      // $this->assign('duration_max_receive_date', $dao->receive_date);
    }

    $sql = "SELECT SUM(total_amount) FROM civicrm_contribution cc WHERE cc.is_test = 0 AND cc.contribution_status_id = 1 AND receive_date >= %1 AND receive_date < %2 ;";
    $duration_sum = CRM_Core_DAO::singleValueQuery($sql, $this->params_duration);

    $last_duration_sum = CRM_Core_DAO::singleValueQuery($sql, $this->params_last_duration);

    $this->assign('duration_sum', $duration_sum);
    if($last_duration_sum > 0){
      $duration_sum_growth = ( $duration_sum / $last_duration_sum ) -1;
      $this->assign('duration_sum_growth', number_format($duration_sum_growth * 100, 2));
    }

    // block recur
    $components = CRM_Core_Component::getEnabledComponents();
    $path = get_class($this);
    $summary = CRM_Core_BAO_Cache::getItem('Contribution Chart', $path.'_currentRunningSummary', $components['CiviContribute']->componentID);
    $summaryTime = CRM_Core_BAO_Cache::getItem('Contribution Chart', $path.'_currentRunningSummary_time', $components['CiviContribute']->componentID);
    if(empty($summary) || time() - $summaryTime > 86400 || $_GET['update']) {
      $summary = CRM_Contribute_BAO_ContributionRecur::currentRunningSummary();
      CRM_Core_BAO_Cache::setItem($summary, 'Contribution Chart', $path.'_currentRunningSummary', $components['CiviContribute']->componentID);
      $summaryTime = CRM_REQUEST_TIME;
      CRM_Core_BAO_Cache::setItem($summaryTime, 'Contribution Chart', $path.'_currentRunningSummary_time', $components['CiviContribute']->componentID);
      if ($_GET['update']) {
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contribute', 'reset=1'));
      }
    }
    if(!empty($summary)){
      $this->assign('summaryRecur', $summary);
      $this->assign('summaryTime', date('n/j H:i', $summaryTime));
      $this->assign('frequencyUnit', 'month');
      $chart = CRM_Contribute_BAO_ContributionRecur::chartEstimateMonthly(12);
      $chart['withToolTip'] = true;
      $chart['seriesUnitPosition'] = 'prefix';
      $chart['seriesUnit'] = '$';
      $this->assign('chartRecur', $chart);
    }

    // contribution_page status
    $sql = "SELECT cp.id id, title, goal_amount, SUM(c.total_amount) sum, COUNT(c.id) count FROM civicrm_contribution_page cp INNER JOIN civicrm_contribution c ON cp.id = c.contribution_page_id WHERE c.receive_date >= %1 AND receive_date < %2 AND c.contribution_status_id = 1 AND c.is_test = 0 GROUP BY cp.id ORDER BY count DESC LIMIT 3";
    $dao = CRM_Core_DAO::executeQuery($sql, $this->params_duration);
    $i = 0;
    while($dao->fetch()){
      $cp_status[$i] = self::getContributionPageStatus($dao->id, $this->start_date, $this->end_date);

      $i++;
    }
    $this->assign('contribution_page_status', $cp_status);

    $this->assign('page_col_n', (12 / $dao->N));


    // last 30 days count
    $instrument_option_group_id = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_option_group WHERE name LIKE 'payment_instrument'");

    $sql = "SELECT cc.id id, c.id contact_id, cc.receive_date receive_date, cc.total_amount amount, c.display_name name, ov.label instrument FROM civicrm_contribution cc 
      INNER JOIN civicrm_contact c ON cc.contact_id = c.id
      INNER JOIN civicrm_option_value ov ON cc.payment_instrument_id = ov.value
      WHERE ov.option_group_id = $instrument_option_group_id AND cc.payment_processor_id IS NOT NULL AND cc.contribution_status_id = 1 AND cc.is_test = 0 AND cc.receive_date >= %1 AND cc.receive_date < %2 AND cc.contribution_recur_id IS NULL ORDER BY receive_date DESC LIMIT 5 ";
    $dao = CRM_Core_DAO::executeQuery($sql, $this->params_duration);
    $single_contributions = array();
    while($dao->fetch()){
      $single_contributions[] = array(
        'id' => $dao->id,
        'contact_id' => $dao->contact_id,
        'name' => $dao->name, 
        'date' => date('Y-m-d', strtotime($dao->receive_date)),
        'amount' => $dao->amount,
        'instrument' => $dao->instrument,
      );
    }
    $this->assign('single_contributions', $single_contributions);

    $sql = "SELECT cc.id id, c.id contact_id, cc.receive_date receive_date, cc.total_amount amount, c.display_name name, cr.installments installments FROM civicrm_contribution cc 
      INNER JOIN civicrm_contact c ON cc.contact_id = c.id
      INNER JOIN civicrm_contribution_recur cr ON cr.id = cc.contribution_recur_id
      WHERE cc.payment_processor_id IS NOT NULL AND cc.contribution_status_id = 1 AND cc.is_test = 0 AND cc.receive_date >= %1 AND cc.receive_date < %2 AND cc.contribution_recur_id IS NOT NULL GROUP BY cr.id ORDER BY receive_date DESC LIMIT 5 ";
    $dao = CRM_Core_DAO::executeQuery($sql, $this->params_duration);
    $recur_contributions = array();
    while($dao->fetch()){
      $recur = array(
        'id' => $dao->id,
        'contact_id' => $dao->contact_id,
        'name' => $dao->name, 
        'date' => date('Y-m-d', strtotime($dao->receive_date)),
        'amount' => $dao->amount,
      );
      if($dao->installments){
        $recur['installments'] = $dao->installments;
      }else{
        $recur['installments'] = ts("no limit");
      }
      $recur_contributions[] = $recur;
    }
    $this->assign('recur_contributions', $recur_contributions);

    $params_next_month = array(
      1 => array(date('Y-m-d', strtotime("+1 month")), 'String'),
    );
    $sql = "SELECT c.id contact_id, c.display_name name, cr.amount amount, cr.end_date end_date, cr.id recur_id, c.id contribution_id
      FROM civicrm_contribution_recur cr 
      INNER JOIN civicrm_contact c ON cr.contact_id = c.id 
      WHERE cr.end_date <= %1 AND cr.contribution_status_id = 5 
      GROUP BY cr.id ORDER BY c.id ASC LIMIT 5";
    $dao = CRM_Core_DAO::executeQuery($sql, $params_next_month);
    $due_recur = array();
    while($dao->fetch()){
      $recur = array();
      $recur['contact_id'] = $dao->contact_id;
      $recur['name'] = $dao->name;
      $recur['amount'] = $dao->amount;
      $recur['recur_id'] = $dao->recur_id;
      $recur['end_date'] = date('Y-m-d', strtotime($dao->end_date));
      $due_recur[] = $recur;
    }
    $this->assign('due_recur', $due_recur);

  }

  static function getLastDurationTime($start_date, $end_date) {
    $last_end_date = $start_date;
    $duration_stamp = strtotime($end_date) - strtotime($start_date);
    $last_start_date = date('Y-m-d', strtotime($start_date) - $duration_stamp);
    return array($last_start_date, $last_end_date);
  }

  static function getContributionPageStatus($pid, $start_date = NULL, $end_date = NULL) {
    // contribution_page status
    $contribution_page_status = array();

    $and_clause = array(' c.contribution_status_id = 1 AND c.is_test = 0 AND cp.id = %1 ');
    if($start_date){
      $and_clause[] = ' c.receive_date >= %2 ';
    }
    if($end_date){
      $and_clause[] = ' c.receive_date < %3 ';
    }
    $and = implode(' AND ', $and_clause);
    $sql = "SELECT cp.id id, title, goal_amount, SUM(c.total_amount) sum, COUNT(c.id) count FROM civicrm_contribution_page cp INNER JOIN civicrm_contribution c ON cp.id = c.contribution_page_id WHERE $and GROUP BY cp.id";
    $params = array(
      1 => array((int)$pid, 'Integer'),
      2 => array($start_date , 'String'),
      3 => array($end_date , 'String'),
    );
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    $dao->fetch();

    if(!empty($start_date)){
      if(empty($end_date)){
        $end_date = date('Y-m-d');
      }
      list($last_start_date, $last_end_date) = self::getLastDurationTime($start_date, $end_date);

      $sql = "SELECT COUNT(id) FROM civicrm_contribution c WHERE contribution_page_id = %1 AND receive_date >= %2 AND receive_date < %3 AND contribution_status_id = 1 AND c.is_test = 0 ";
      $params = array(
        1 => array((int)$pid, 'Integer'),
        2 => array($last_start_date , 'String'),
        3 => array($last_end_date , 'String'),
      );
      $last_duration_count = CRM_Core_DAO::singleValueQuery($sql, $params);

    }

    $sql = "SELECT COUNT(id) count, SUM(total_amount) total_amount FROM civicrm_contribution c WHERE contribution_page_id = %1 AND contribution_status_id = 1 AND c.is_test = 0 ";
    $dao_page = CRM_Core_DAO::executeQuery($sql, $params);
    if($dao_page->fetch()){
      $total_count = $dao_page->count;
      $total_amount = $dao_page->total_amount;
    }

    $source = self::getSourceByPageID($pid);

    $duration_count = $dao->count;
    $goal = $dao->goal_amount;

    $contribution_page_status = array(
      'title' => $dao->title,
      'duration_count' => $duration_count,
      'goal' => $goal,
      'total_count' => $total_count,
      'total_amount' => $total_amount,
      'source' => $source,
    );

    if(!empty($goal)){
      $contribution_page_status['process'] = ($total_amount / $goal) * 100;
    }

    if($last_duration_count > 0){
      $duration_count_growth = ( $duration_count / $last_duration_count ) -1;
      $contribution_page_status['duration_count_growth'] = number_format($duration_count_growth * 100,2 );
    }

    return $contribution_page_status;

  }

  private static function getDataForChart($label_array, $summary_array, $type='sum') {
    $return_array = array();
    foreach ($label_array as $label) {
      $recur_index = array_search($label, $summary_array['label']);
      if((!empty($recur_index) || $recur_index === 0 ) && !empty($summary_array[$type][$recur_index])){
        $return_array[] = floatval($summary_array[$type][$recur_index]);
      }else{
        $return_array[] = 0;
      }
    }

    return $return_array;
  }

  private static function getSourceByPageID($page_id) {
    $sql = "SELECT COUNT(session_key) sum FROM civicrm_track WHERE page_type = 'civicrm_contribution_page' AND page_id = %1";
    $params = array(
      1 => array($page_id, 'Integer'),
    );
    $sum = CRM_Core_DAO::singleValueQuery($sql, $params);
    $sql = "SELECT COUNT(session_key) count, referrer_type FROM civicrm_track WHERE page_type = 'civicrm_contribution_page' AND page_id = %1 GROUP BY referrer_type ORDER BY count";
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    $return_array = array();
    $i = 0;
    $other = 0;
    while($dao->fetch()){
      if(count($return_array) < 4 && !empty($dao->referrer_type)){
        $return_array[$i] = array(
          'type' => $dao->referrer_type,
          'count' => number_format(($dao->count / $sum) * 100 , 2),
        );
      }else{
        $other += $dao->count;
      }
    }
    $return_array[4] = array(
      'type' => 'other',
      'count' => number_format(($other / $sum) * 100, 2),
    );

    /* for test */
    if ($_GET['test']) {
      $return_array = array(
        array(
          'type' => '電子報',
          'count' => rand(0,10000) / 100,
        ),
        array(
          'type' => 'FB',
          'count' => rand(0,10000) / 100,
        ),
        array(
          'type' => 'Twitter',
          'count' => rand(0,10000) / 100,
        ),
        array(
          'type' => '搜尋',
          'count' => rand(0,10000) / 100,
        ),
        array(
          'type' => '其他',
          'count' => rand(0,10000) / 100,
        ),
      );
    }
    /* for test */

    return $return_array;
  }
}

