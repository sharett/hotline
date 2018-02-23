# Hotline

Hotline is a phone bank hotline and mass texting tool that uses Twilio.

## Features

Hotlines:
* Direct incoming calls and texts to hotline staff based on day of week, time of day and language ability.
* Send texts and make calls from the browser.
* Support for multiple hotline numbers.

Broadcast:
* Send mass texts, import and remove lists of numbers.  Process unsubscribe requests.

It is not production ready and is very much in progress.

## Principles

* Keep it simple - avoid using external libraries whenever possible.  Add only features that are necessary.

## Requirements

- PHP (7.0 used, earlier versions may work).  Uses mysqli to interface with MySQL.
- [Composer] (https://getcomposer.org) (a PHP package management utility), or manual installation of the [twilio-php] (https://github.com/twilio/twilio-php) library
- MySQL (5.7 used, earlier versions may work)
- a [Twilio] (https://www.twilio.com) account

## Installation & configuration

* Download or git clone this project.

* Run "composer install" in the main directory.  This installs the "twilio-php" library.

* Password protect access to the directory you installed it to.  HTTP basic security should work.

* Optionally, install a database editing utility such as PhpMyAdmin and place it in a directory under the main directory.

* Create a database and credentials to access it (using this utility or directly via the command line using mysql).

* Import the "database_schema.sql" file into the newly created database.

* Copy the "config_sample.php" file to "config.php".  Edit the "config.php" file:
  * The $TWILIO_ACCOUNT_SID and $TWILIO_AUTH_TOKEN can be found from the [Twilio Account Dashboard] (https://www.twilio.com/user/account).
  * Put the database credentials in the $HOTLINE_DB_ settings.
  * For mass texting, set the $BROADCAST_ settings including the phone numbers to use in the '+1NXXNXXXXXX' format ($BROADCAST_CALLER_IDS).
  * For hotlines, set the $HOTLINE_ and $HOTLINES settings including the phone numbers to use in the '+1NXXNXXXXXX' format.

* In your Twilio account, configure each number to make a webhook request to the application.  Use the HTTP basic security you set above (replace username and password and the web address below as appropriate).  For voice, use:
   
   https://username:password@(web address, example: hotline.hotline.org)/twin/incoming-voice.php
  * For messaging (SMS), use:
  
   https://username:password@(web address, example: hotline.hotline.org)/twin/incoming-sms.php

* Optional.  To enable in-browser outbound calling, in your Twilio account create a TwiML app (under Phone numbers, Tools) with a voice request URL of:

   https://username:password@(web address, example: hotline.hotline.org)/call/voice.php
  * Once you've created the TwiML app, copy its SID and set the $TWILIO_TWIML_APP_SID setting in config.php to it.

* Optional.  To enable using Twilio's Notify service to broadcast text from multiple numbers at once:
  * Create a Messenging Service in your Twilio account (under Programmable SMS, Messenging Services).
    * Under the "Configure" menu item:
      * Select the use case "Notifications, 2-Way".
      * Check the "Process Inbound Messages" checkbox.
      * Set the "Request URL" to:
   https://username:password@(web address, example: hotline.hotline.org)/twin/incoming-sms.php
      * Verify that "Sticky Sender" is enabled.
    * Under the "Numbers" menu item:
      * Add each of the numbers you would like to broadcast from.
  * Create a Notify Service in your Twilio account (under Notify, Services).
    * Under the "Configure" menu item:
      * Select the messenging service you created above under the "Properties, Messenging Service SID"
    * Copy the "Service SID" to the $BROADCAST_TWILIO_NOTIFY_SERVICE setting in config.php.
  * Add each of the numbers you are broadcasting from to the $BROADCAST_CALLER_IDS array.

* By default, English and Spanish are set - edit the "languages" table to modify this.  The Twilio code is the language that the voice "alice" will use to speak the text.
  * Each of the prompts may be either the text to be spoken, or a URL to play.  
  * Prompts may also be a valid JSON encoded array, with each key the hotline number in the '+1NXXNXXXXXX' format, and each value the prompt to be played for that hotline.

* The broadcast texting system sends progress marks to the browser as texts are being sent.  It can take some time to send the texts.  You may need to configure the web server and PHP to not buffer these progress marks.
  * For Nginx, add or update "gzip off;" and "proxy_buffering off;" to the /etc/nginx/nginx.conf file.

## Usage

The broadcast interface supports sending, importing and removing numbers.  When importing numbers, each one is sent a welcome message with instructions about how to stop receiving texts.

The hotline interface supports viewing and editing active staff (those signed up to receive calls), viewing call/text logs and language information, sending texts and placing phone calls.  You can also mark texts and voicemails as responded to or not.

## Hotline flow

Voice calls:

* Says: “($HOTLINES['name']) hotline.  Press 1 for English. Para español oprima dos.”
* Wait 15 seconds or for a language to be chosen. On timeout, defaults to English.
* Rings to the caller, and simultaneously calls all available hotline staff, for 30 seconds.
* Any hotline staff who answer hear: 
  * “($HOTLINES['name']) hotline call.  Press 1 to accept.”
  * If they press 1: “Connecting you to the caller.”
  * Otherwise, “Goodbye.” and hangs up.
* If any hotline staff accept the call, they are connected to the caller.  Staff with "answer alerts" enabled will be alerted via a text message.
* If no one answers in time, the caller hears: “No one is available to answer.  Please leave a message.”  Maximum length of message, 5 minutes.
* If a voicemail is left, all on-call staff are alerted via a text message.

Text messages:

* Administrative requests:
  * 'off': Calls and texts to hotline staff are disabled.  Response: “Hotline calls are now disabled.” Staff must send 'on' to enable calls.
  * 'on', 'start','unstop': Calls and texts to hotline staff are enabled.  Response: “Hotline calls are now enabled.”
  * 'stop', 'stopall', 'unsubscribe', 'cancel', 'end' or 'quit': Calls and texts to hotline staff are disabled.  Response: “You have successfully been unsubscribed. You will not receive any more messages from this number. Reply START to resubscribe.” This is an automated response that can't be changed.  Staff must send 'start' to receive any texts in the future.
* Texts:
  * Text is forwarded to all hotline staff on duty, in this format: “Hotline text from (sender's phone number): (original text)”
  * Response from initial text, or after a week of no texts received from the person: “Your message has been received.  Someone will respond shortly.”

## Development guidelines

* New feature development:
  * Branch or fork to a a branch named after the desired change
  * Implement
  * Merge master into the branch if needed to ensure clean merge
  * Code review/ pull request
  * On acceptance merge branch into master

## To do

* Consider a template library to separate the logic from the presentation.
* Consider an authentication method other than HTTP basic.

## License

MIT
