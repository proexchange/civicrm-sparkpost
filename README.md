# CiviCRM / SparkPost Integration
Integrates SparkPost to CiviCRM, so email can be sent out over the SparkPost service and bounce can be processed in CiviCRM

## What This Extentsion Does
* Adds a tag to all outgoing civimail messages, the civi-generated return-path is added a a sparkpost tag, because like most smtp services, sparkpost strips out the return-path header for its own.
* Adds a schdeuled job that uses sparkpost api to fetch bounce events and processes their bounces in civi. I use the hash included in the sparkpost tag
* Adds Bounce type and Pattern that helps with marking emails on hold in civi that sparkpost has added to it's suppression list

##Setup Steps
1.  Create SparkPost Account
2.	Add Sending Domain and verify domain
3.	Create API key with appropriate permissions, save this api key
  * “Select All” is easiest way, but you may want to change this for your needs
  * Required: Message Events: Read-only, Send via SMTP, Suppression Lists: Read/Write
4.	Edit CiviCRM Outbound Email settings
  * Select mailer: SMTP
  * Smtp server: smtp.sparkpostemail.com
  * Port: 587
  * Authentication: Yes
  * SMTP Username: SMTP_Injection
  * SMTP Password: [api key from step 3]
5.	Install/Enable CiviCRM/SparkPost Extension
6.	Edit/Enable Scheduled Job
  * api_key=[ api key from step 3]
  * friendly_froms=[user@example.com]
  * events=[leave deafults, remove “- required”]   

##Scheduled Job Parameters  
* friendly_from: comma separated lists, you should enter the any email addresses that will be used to send email from. 
Administer > CiviMail > From Email Addresses
* events: comma separated list of sparkpost events that should be considered bounces in civicrm. You can usually just leave the defaults, but this can be changed to fit your needs. 

