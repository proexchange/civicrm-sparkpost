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
  $spec['friendly_froms']['api.required'] = 1;
  $spec['events']['api.required'] = 1;
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
  $lgts = civiapi_recent_sparkpost();
  $fromtime = gmdate('Y-m-d',strtotime($lgts)).'T'.gmdate('H:i',strtotime($lgts));
  $ch = curl_init('https://api.sparkpost.com/api/v1/message-events?friendly_froms='.$params['friendly_froms'].'&events='.$params['events'].'&from='.$fromtime);
  $headers = array(
    'Accept: application/json',
    'Authorization: ' . $params['api_key']
  );
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $json = curl_exec($ch);
  curl_close($ch);

  $results = json_decode($json);
  $holder = array();

  if(!empty($results->errors))
    return civicrm_api3_create_error(ts($results->errors['0']->message), array('error_code' => 1, 'field' => 'api_key'));
  else if(empty($results))
    return civicrm_api3_create_error(ts('No results.'), array('error_code' => 2, 'field' => 'api_key'));

  // Get bounces tracked in SparkPost
  foreach($results->results as $row) {
    if(!empty($row->rcpt_meta->{'civi-rp'})) {
      // Get hash
      $temp = explode('.', $row->rcpt_meta->{'civi-rp'});
      $hash = substr($temp[3], 0, strpos($temp[3], '@'));

      $queue = sparkpost_queue($hash);

      if($row->type == 'spam_complaint')
        $reason = 'rejected as spam';
      else
        $reason = $row->raw_reason;
      
      $temparr = array(
        'eventqueue' => $queue,
        'return-path' => $row->rcpt_meta->{'civi-rp'},
        'reason' => $reason
      );
      array_push($holder, $temparr);
    }
  }

  // Mark bounces in CiviCRM
  $bounces = array();
  foreach($holder as $bounce) {
    $eqid = $bounce['eventqueue']['id'];
    $hasBounce = CRM_Core_DAO::singleValueQuery("SELECT count(id) as 'COUNT' FROM civicrm_mailing_event_bounce WHERE event_queue_id = $eqid");
    if($hasBounce < 1) {
      sparkpost_addbounce($bounce['eventqueue']['job_id'], $bounce['eventqueue']['id'], $bounce['eventqueue']['hash'], $bounce['reason']);
      $temparr = array(
        'job_id' => $bounce['eventqueue']['job_id'],
        'event_queue_id' => $bounce['eventqueue']['id'],
        'hash' => $bounce['eventqueue']['hash'],
        'reason' => $bounce['reason']
      );
      array_push($bounces, $temparr);
    }
  }

  return civicrm_api3_create_success($bounces, $params);
}