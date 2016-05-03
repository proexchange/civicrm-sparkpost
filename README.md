# SparkPost / CiviCRM Integration (Bulk and Transactional)  
Integrates SparkPost to CiviCRM, so email can be sent out over the SparkPost service and bounces can be processed in CiviCRM. For bulk(civimail) and transactional emails.  

## What This Extension Does
* Adds metadata to all outgoing bulk(CiviMail) AND transactional email messages for use later in bounce processing 
* Creates an activity for all outgoing transactional email messages
* Adds a scheduled job that uses SparkPost API to fetch bounce events and processes their bounces in CiviCRM
* Adds Bounce type and pattern that helps with marking emails on hold in CiviCRM that SparkPost has added to its suppression list
* Inserts the mailing name in the SMTP message to SparkPost as the Campaign ID, which is useful when looking up results in SparkPost Web UI  
* Processes bulk and transactional email bounces
* Updates activity status for activities created for transactional emails


##Setup Steps
1. Create SparkPost Account
2. Add Sending Domain and verify domain
3. Create API key with appropriate permissions, save this API key
  * “Select All” is easiest way, but you may want to change this for your needs
  * Required: Message Events: Read-only, Send via SMTP, Suppression Lists: Read/Write
4. Edit CiviCRM Outbound Email settings
  * Select mailer: SMTP
  * Smtp server: smtp.sparkpostmail.com
  * Port: (587 OR 2525)
  * Authentication: Yes
  * SMTP Username: SMTP_Injection
  * SMTP Password: [api key from step 3]
5. Install/Enable CiviCRM / SparkPost Extension
6. Edit/Enable SparkPost Fetch Bounces Scheduled Job (at Administer > System Settings > Scheduled Jobs)
  * api_key=[ api key from step 3 -  required]
  * events=[-optional]
  * date_filter=[1 OR 0, defaults to 1 - optional]

##Scheduled Job Parameters Notes  
* events: This is now an optional field. It defaults to 'bounce,delay,policy_rejection,out_of_band,spam_complaint' if not specified. Comma separated list of SparkPost events that should be considered bounces in CiviCRM. You can usually just leave the defaults, but this can be changed to fit your needs. 
* date_filter: If you have this set to 1, this will only query bounce events from SparkPost that have occurred since the scheduled job last ran successfully. This should make things run a little faster because there will be fewer results to parse through from SparkPost.

NOTE: friendly_froms scheduled job parameter has been removed. This value is now filled with the values from Administer > CiviMail > From Email Addresses
