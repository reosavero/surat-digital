<?php

/**
 * Security Configuration - Surat Digital
 */

define('CSRF_KEY', 'surat_digital_csrf_token_' . md5(__DIR__));
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 15); // Minutes
