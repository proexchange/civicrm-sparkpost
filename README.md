# civicrm-sparkpost
Integrates SparkPost to CiviCRM, so email can be sent out over the SparkPost service and bounce can be processed in CiviCRM

A SparkPost tag is added to all outgoing civimail emails. A scheduled job is created that will then use SparkPost API to fetch bounce events and process those events in civi. 

Setup Steps: 
1) Create SparkPost account and setup SMTP in CiviCRM, save your API key. 
2) Install this extension and enable. 
3) Update schduled job "SparkPost Fetch Bounces" with your API key and sending email addresses, enable scheduled job.
