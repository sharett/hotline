<?php
/**
* @file
* Base configuration file
*
* This file must be included first.  
* 
*/

// *** DEFINE GLOBALS ***

// The name of the website
$WEBSITE_NAME = 'Hotline admin';

// Filesystem path to the document root
$HTML_BASE = '/home/hotline/html/';

// Filesystem path to the 'lib' directory
$LIB_BASE = $HTML_BASE . 'lib/';

// Web path to the Twilio interface directory
$TWILIO_INTERFACE_WEBROOT = 'https://username:password@hotline.hotline.org/twin/';

// Twilio account ID
$TWILIO_ACCOUNT_SID = '';

// Twilio authorization token
$TWILIO_AUTH_TOKEN = '';

// Twilio cost per text
$TWILIO_COST_PER_TEXT = 0.0075;

// Optional twilio TwiML app for placing in-browser calls
$TWILIO_TWIML_APP_SID = '';

// **** BROADCAST ****

// Broadcast number
$BROADCAST_CALLER_IDS = array('+1NXXNXXXXXX');

// Optional Twilio Notify Service SID
$BROADCAST_TWILIO_NOTIFY_SERVICE = '';

// Broadcast prompts
$BROADCAST_WELCOME = "Welcome to the alert list. ".
	"To remove yourself from the list, text OFF.";
$BROADCAST_GOODBYE = "You will no longer receive alerts. To put yourself back on, ".
	"text ON.";

// If set, callers will hear this message in each language and then it will hang up.  The format is an array, 
// with each key the language code, and the value the text to read in that language.  "es-MX" is Spanish, 
// "en-US" is English.  Don't set this and $BROADCAST_SEND_TO_HOTLINE.
// Example: 'en-US' => "Goodbye"
$BROADCAST_VOICE_MESSAGES = array(

);

// If set, callers to the broadcast numbers will be redirected to this hotline.
// Don't set this and $BROADCAST_VOICE_MESSAGES
$BROADCAST_SEND_TO_HOTLINE = '';

// When a broadcast text is sent that is limited to certain tags, this text will be added to the database 
// records of the text.
$BROADCAST_LIMITED_TO_TAGS_TEXT = "LIMITED TO TAGS";

// List users authorized to send broadcast texts here.  Leave blank to allow all users.
$BROADCAST_AUTHORIZED_USERS = array();

// If nonzero, outputs a progress mark every X texts sent
$BROADCAST_PROGRESS_MARK_EVERY = 3;

// **** HOTLINE ****

// Each hotline is an element of this array.  The array key is the hotline number, and the values are prompts.
$HOTLINES = array(
	'+1NXXNXXXXXX' => 
		array('name' => 'Just another hotline',
		      'intro' => 'Just another hotline',
		      'voicemail' => 'or press 0 for voicemail',
		      'staff_prompt_1' => 'Just another hotline call in ', // language will be added here
		      'staff_prompt_2' => '. Press 1 to accept.',
              'text_error' => 'Unable to forward your text.  Please call in.',
              'text_response' => 'Your message has been received.  Someone will respond shortly.'),
);

// Hotline prompts
$HOTLINE_GOODBYE = 'Goodbye.';
$HOTLINE_CONNECTING_TO_CALLER = 'Connecting you to the caller.';
$HOTLINE_CALLER_HUNG_UP = 'The caller hung up or someone else took the call.  Goodbye.';

// List users authorized to update the staff data here.  Leave blank to allow all users.
$HOTLINE_AUTHORIZED_USERS = array();

// **** DATABASE ****

// Database setup
$HOTLINE_DB_DATABASE = '';
$HOTLINE_DB_USERNAME = '';
$HOTLINE_DB_PASSWORD = '';
$HOTLINE_DB_HOSTNAME = 'localhost';

// Time zone
date_default_timezone_set('America/New_York');

// include the database and other common functions
require_once 'lib/lib_db.php';
