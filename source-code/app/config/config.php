<?php 
require_once APPPATH.'/config/common.config.php'; // Common configuration
require_once APPPATH.'/config/db.config.php'; // Database configuration
require_once APPPATH.'/config/i18n.config.php'; // i18n configuration
require_once APPPATH.'/config/google-services.config.php'; // google services configuration

// ASCII Secure random crypto key
define("CRYPTO_KEY", "def00000696dcbac44167211cb0ae542ac9d5001a06d45c0d487f4309f403bfcc2694f99fa081ebd69096a18237a96010b9b9b8aa8be7a00d222b8ba100d496b293ba488");

// General purpose salt
define("MP_SALT", "ImINZ0B8kD2PmWuU");

// Path to instagram sessions directory
define("SESSIONS_PATH", APPPATH . "/sessions");
// Path to temporary files directory
define("TEMP_PATH", ROOTPATH . "/assets/uploads/temp");

define("UPLOAD_PATH", ROOTPATH . "/assets/uploads");
define("UPLOAD_URL", APPURL . "/assets/uploads");