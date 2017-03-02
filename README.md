# Hotline

Hotline is a phone bank hotline and mass texting tool that uses Twilio.

## Features

Hotline:
* Direct incoming calls and texts to hotline staff based on day of week, time of day and language ability.
* Send texts and make calls from the browser.

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

* Install a database editing utility such as PhpMyAdmin and place it in the "sanctdb" directory under the main directory.

* Create a database and credentials to access it (using this utility or directly via the command line using mysql).

* Import the "database_schema.sql" file into the newly created database.

* Edit the "config.php" file:
  * The $TWILIO_ACCOUNT_SID and $TWILIO_AUTH_TOKEN can be found from the [Twilio Account Dashboard] (https://www.twilio.com/user/account).
  * Put the database credentials in the $HOTLINE_DB_ settings.
  * For mass texting, set the $BROADCAST_ settings including the phone number to use in the '+1NXXNXXXXXX' format ($BROADCAST_CALLER_ID).
  * For a hotline, set the $HOTLINE_ settings including the phone number to use in the '+1NXXNXXXXXX' format ($HOTLINE_CALLER_ID).

* In your Twilio account, configure each number to make a webhook request to the application.  Use the HTTP basic security you set above (replace username and password and the web address below as appropriate).  For voice, use:
   
   https://username:password@(web address, example: hotline.hotline.org)/twin/incoming-voice.php
  * For messaging (SMS), use:
  
   https://username:password@(web address, example: hotline.hotline.org)/twin/incoming-sms-php

* Optional.  To enable in-browser outbound calling, create a TwiML app (under Phone numbers, Tools) with a voice request URL of:

   https://username:password@(web address, example: hotline.hotline.org)/call/voice.php
  * Once you've created the TwiML app, copy its SID and set the $TWILIO_TWIML_APP_SID setting in config.php to it.

* By default, English and Spanish are set - edit the "languages" table to modify this.  The Twilio code is the language that the voice "alice" will use to speak the text.

## Usage

The broadcast interface supports sending, importing and removing numbers.  When importing numbers, each one is sent a welcome message with instructions about how to stop receiving texts.

The hotline interface supports viewing active staff (those signed up to receive calls), viewing call/text logs and language information, sending texts and placing phone calls.  You can also mark texts and voicemails as responded to or not.  All editing of the volunteers information and times they are accepting calls is done by editing the database directly.

## To do

* Consider a template library to separate the logic from the presentation.
* Consider an authentication method other than HTTP basic.
* Consider an interface to edit hotline staff (contacts) and their available times.

## License

MIT
