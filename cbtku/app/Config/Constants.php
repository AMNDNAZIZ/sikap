<?php

/*
 | --------------------------------------------------------------------
 | App Namespace
 | --------------------------------------------------------------------
 |
 | This defines the default Namespace that is used throughout
 | CodeIgniter to refer to the Application directory. Change
 | this constant to change the namespace that all application
 | classes should use.
 |
 | NOTE: changing this will require manually modifying the
 | existing namespaces of App\* namespaced-classes.
 */
defined('APP_NAMESPACE') || define('APP_NAMESPACE', 'App');

/*
 | --------------------------------------------------------------------------
 | Composer Path
 | --------------------------------------------------------------------------
 |
 | The path that Composer's autoload file is expected to live. By default,
 | the vendor folder is in the Root directory, but you can customize that here.
 */
defined('COMPOSER_PATH') || define('COMPOSER_PATH', ROOTPATH . 'vendor/autoload.php');

/*
 |--------------------------------------------------------------------------
 | Timing Constants
 |--------------------------------------------------------------------------
 |
 | Provide simple ways to work with the myriad of PHP functions that
 | require information to be in seconds.
 */
defined('SECOND') || define('SECOND', 1);
defined('MINUTE') || define('MINUTE', 60);
defined('HOUR')   || define('HOUR', 3600);
defined('DAY')    || define('DAY', 86400);
defined('WEEK')   || define('WEEK', 604800);
defined('MONTH')  || define('MONTH', 2_592_000);
defined('YEAR')   || define('YEAR', 31_536_000);
defined('DECADE') || define('DECADE', 315_360_000);

/*
 | --------------------------------------------------------------------------
 | Exit Status Codes
 | --------------------------------------------------------------------------
 |
 | Used to indicate the conditions under which the script is exit()ing.
 | While there is no universal standard for error codes, there are some
 | broad conventions.  Three such conventions are mentioned below, for
 | those who wish to make use of them.  The CodeIgniter defaults were
 | chosen for the least overlap with these conventions, while still
 | leaving room for others to be defined in future versions and user
 | applications.
 |
 | The three main conventions used for determining exit status codes
 | are as follows:
 |
 |    Standard C/C++ Library (stdlibc):
 |       http://www.gnu.org/software/libc/manual/html_node/Exit-Status.html
 |       (This link also contains other GNU-specific conventions)
 |    BSD sysexits.h:
 |       http://www.gsp.com/cgi-bin/man.cgi?section=3&topic=sysexits
 |    Bash scripting:
 |       http://tldp.org/LDP/abs/html/exitcodes.html
 |
 */
defined('EXIT_SUCCESS')        || define('EXIT_SUCCESS', 0);        // no errors
defined('EXIT_ERROR')          || define('EXIT_ERROR', 1);          // generic error
defined('EXIT_CONFIG')         || define('EXIT_CONFIG', 3);         // configuration error
defined('EXIT_UNKNOWN_FILE')   || define('EXIT_UNKNOWN_FILE', 4);   // file not found
defined('EXIT_UNKNOWN_CLASS')  || define('EXIT_UNKNOWN_CLASS', 5);  // unknown class
defined('EXIT_UNKNOWN_METHOD') || define('EXIT_UNKNOWN_METHOD', 6); // unknown class member
defined('EXIT_USER_INPUT')     || define('EXIT_USER_INPUT', 7);     // invalid user input
defined('EXIT_DATABASE')       || define('EXIT_DATABASE', 8);       // database error
defined('EXIT__AUTO_MIN')      || define('EXIT__AUTO_MIN', 9);      // lowest automatically-assigned error code
defined('EXIT__AUTO_MAX')      || define('EXIT__AUTO_MAX', 125);    // highest automatically-assigned error code
defined('APP_VERSION') || define('APP_VERSION', '2.1.2');
defined('LAST_UPDATE') || define('LAST_UPDATE', '2026-08-30 14:00');

/*
 |--------------------------------------------------------------------------
 | Developer Instance Flag
 |--------------------------------------------------------------------------
 |
 | Set ke true HANYA di mesin developer/localhost.
 | Mengontrol fitur khusus developer seperti Generate Patch ZIP.
 | Di server client, biarkan false atau jangan tambahkan di .env sama sekali.
 |
 | Cara aktifkan di localhost: tambahkan baris ini di .env:
 |   IS_DEVELOPER = true
 |
 */
if (!defined('IS_DEVELOPER')) {
    // CI4 meload .env ke DotEnv, bukan ke getenv()/\$_ENV standar.
    // Baca nilai langsung dari .env agar tersedia sejak Constants.php diload.
    $isDev = false;

    // Coba via env() jika CI4 sudah bootstrap (misal saat request normal)
    if (function_exists('env')) {
        $isDev = filter_var(env('IS_DEVELOPER', false), FILTER_VALIDATE_BOOLEAN);
    }

    // Fallback: parse .env manual jika env() belum tersedia
    if (!$isDev) {
        $envFile = defined('ROOTPATH') ? ROOTPATH . '.env' : dirname(__DIR__, 2) . '/.env';
        if (is_file($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#') continue;
                if (strpos($line, 'IS_DEVELOPER') === 0) {
                    [$key, $val] = array_map('trim', explode('=', $line, 2));
                    if ($key === 'IS_DEVELOPER') {
                        $isDev = filter_var($val, FILTER_VALIDATE_BOOLEAN);
                    }
                }
            }
        }
    }

    define('IS_DEVELOPER', $isDev);
}
