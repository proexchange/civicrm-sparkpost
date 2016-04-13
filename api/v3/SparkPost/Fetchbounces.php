<?php

/**
 * SparkPost.Get API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_spark_post_Fetchbounces_spec(&$spec) {
  $spec['api_key']['api.required'] = 1;
  $spec['events']['api.required'] = 0;
  $spec['date_filter']['api.required'] = 0;
}

/**
 * SparkPost.Get API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_spark_post_Fetchbounces($params) {
  sparkpost_addOptionValues();
  sparkpost_mailingsCheck();

  //Prepare API call to SparkPost
  if(empty($params['events'])) $params['events']="bounce,delay,policy_rejection,out_of_band,spam_complaint";
  $ch_api ='https://api.sparkpost.com/api/v1/message-events?events='.$params['events'].'&friendly_froms='.sparkpost_getFromAddresses();  
  if(!empty($params['date_filter'])) {
    $lgts = sparkpost_recentFetchSuccess();
    if(!empty($lgts))
      $ch_api .='&from='.gmdate('Y-m-d',strtotime($lgts)).'T'.gmdate('H:i',strtotime($lgts));
  }
  $ch = curl_init($ch_api);
  $headers = array(
    'Accept: application/json',
    'Authorization: ' . $params['api_key']
  );

  //Make API call to SparkPost
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $json = curl_exec($ch);
  curl_close($ch);
  $results = json_decode($json);

  //
  $holder = array();
  if(!empty($results->errors))
    return civicrm_api3_create_error(ts($results->errors['0']->message), array('error_code' => 1, 'field' => 'api_key'));
  else if(empty($results))
    return civicrm_api3_create_error(ts('No results.'), array('error_code' => 2, 'field' => 'api_key'));

  // Get bounces tracked in SparkPost
  foreach($results->results as $row) {
    if(!empty($row->rcpt_meta->{'civi_hash'})) {
      if($row->type == 'spam_complaint')
        $reason = 'rejected as spam';
      else
        $reason = $row->raw_reason;
      
      $temparr = array(
        'type' => $row->rcpt_meta->{'civi_type'},
        'eventqueue' => $row->rcpt_meta->{'civi_queue'},
        'jobid' => $row->rcpt_meta->{'civi_jobid'},
        'hash' => $row->rcpt_meta->{'civi_hash'},
        'reason' => $reason
      ); 
      if(!empty($row->rcpt_meta->{'civi_activityid'})){
        $temparr['activityid'] = $row->rcpt_meta->{'civi_activityid'};
      }
      array_push($holder, $temparr);
    }
  }


  // Mark bounces in CiviCRM
  $bounces = array();
  foreach($holder as $bounce) {
    $hasBounce = CRM_Core_DAO::singleValueQuery("SELECT count(id) as 'COUNT' FROM civicrm_mailing_event_bounce WHERE event_queue_id = ".$bounce['eventqueue']);
    if($hasBounce < 1) {
      sparkpost_addbounce($bounce['jobid'], $bounce['eventqueue'], $bounce['hash'], $bounce['reason']);//add mailing bounce event
      if($bounce['type'] == 'transactional') {
        sparkpost_updateBounceActivity($bounce['activityid'],$bounce['reason']); //update transational email activity with bounced status
      }
      $temparr = array(
        'job_id' => $bounce['jobid'],
        'event_queue_id' => $bounce['eventqueue'],
        'hash' => $bounce['hash'],
        'reason' => $bounce['reason']
      );
      array_push($bounces, $temparr);
    }
  }
  return civicrm_api3_create_success($bounces, $params);
}
