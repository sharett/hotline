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

// The hotline name
$HOTLINE_NAME = 'Just Another Hotline';

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
$BROADCAST_CALLER_ID = '+1NXXNXXXXXX';

// Broadcast prompts
$BROADCAST_WELCOME = "Welcome to the ". $HOTLINE_NAME . " alert list. ".
	"To remove yourself from the list, text OFF. To put yourself back on, ".
	"text ON.";
$BROADCAST_GOODBYE = "You will no longer receive ". $HOTLINE_NAME . " alerts.";

// List users authorized to send broadcast texts here.  Leave blank to allow all users.
$BROADCAST_AUTHORIZED_USERS = array();

// **** HOTLINE ****

// Main hotline number
$HOTLINE_CALLER_ID = '+1NXXNXXXXXX';

// Hotline prompts
$HOTLINE_INTRO = $HOTLINE_NAME . ' hotline. ';
$HOTLINE_GOODBYE = 'Goodbye.';
$HOTLINE_STAFF_PROMPT_1 = $HOTLINE_NAME . ' hotline call in '; // language will be added here
$HOTLINE_STAFF_PROMPT_2 = '. Press 1 to accept.';
$HOTLINE_STRAIGHT_TO_VOICEMAIL= 'or press 0 for voicemail';
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
