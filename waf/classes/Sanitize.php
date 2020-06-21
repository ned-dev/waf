<?php
/**
 * @package WAF
 */
namespace WAF;

/**
 * Sanitize globals security class
 *
 * PHP version 7.1
 *
 * @package Sanitize
 * @author  Ned Andonov <neoplovdiv@gmail.com>
 */

/**
 * SanitizeGlobals Class
 *
 */
class Sanitize
{
    /**
     * Sanitize Globals
     *
     * Unsets all globals if register_globals is enabled
     * Sanitizes all data before any HTTP request
     *
     * @return void
     */
    public static function globals()
    {
        /**
         * Note: Superglobals cannot be used as variable variables inside functions or class methods.
         * This means we can't do this inside a function or method:
         *
         * $var = '_GET';
         * ${$var}[$key]
         *
         * That is why code below manually sanitizes all protected superglobals
         *
         */

        // Sanitizes $_GET data
        if (!empty($_GET) && is_array($_GET) && count($_GET) > 0) {
            foreach ($_GET as $key => $val) {
                $_GET[self::sanitizeKeys($key)] = self::sanitizeData($val);
            }
        }

        // Sanitizes $_POST data
        if (!empty($_POST) && is_array($_POST) && count($_POST) > 0) {
            foreach ($_POST as $key => $val) {
                $_POST[self::sanitizeKeys($key)] = self::sanitizeData($val);
            }
        }

        // Sanitizes $_REQUEST data
        if (!empty($_REQUEST) && is_array($_REQUEST) && count($_REQUEST) > 0) {
            foreach ($_REQUEST as $key => $val) {
                $_REQUEST[self::sanitizeKeys($key)] = self::sanitizeData($val);
            }
        }

        // Sanitizes $_SESSION data
        if (!empty($_SESSION) && is_array($_SESSION) && count($_SESSION) > 0) {
            foreach ($_SESSION as $key => $val) {
                $_SESSION[self::sanitizeKeys($key)] = self::sanitizeData($val);
            }
        }

        // Sanitizes $_COOKIE data
        if (!empty($_COOKIE) && is_array($_COOKIE) && count($_COOKIE) > 0) {
            foreach ($_COOKIE as $key => $val) {
                $_COOKIE[self::sanitizeKeys($key)] = self::sanitizeData($val);
            }
        }

        // Sanitizes $_SERVER data
        if (!empty($_SERVER) && is_array($_SERVER) && count($_SERVER) > 0) {
            foreach ($_SERVER as $key => $val) {
                $_SERVER[self::sanitizeKeys($key)] = self::sanitizeData($val);
            }
        }

        // Sanitizes $_FILES data
        if (!empty($_FILES) && is_array($_FILES) && count($_FILES) > 0) {
            foreach ($_FILES as $key => $val) {
                $_FILES[self::sanitizeKeys($key)] = self::sanitizeData($val);
            }
        }

        // Sanitizes $_ENV data
        if (!empty($_ENV) && is_array($_ENV) && count($_ENV) > 0) {
            foreach ($_ENV as $key => $val) {
                $_ENV[self::sanitizeKeys($key)] = self::sanitizeData($val);
            }
        }

        // Sanitizes $HTTP_RAW_POST_DATA data
        if (!empty($HTTP_RAW_POST_DATA) && is_array($HTTP_RAW_POST_DATA) && count($HTTP_RAW_POST_DATA) > 0) {
            foreach ($HTTP_RAW_POST_DATA as $key => $val) {
                $HTTP_RAW_POST_DATA[self::sanitizeKeys($key)] = self::sanitizeData($val);
            }
        }

        // Sanitize PHP_SELF
        $_SERVER['PHP_SELF'] = filter_var($_SERVER['PHP_SELF'], FILTER_SANITIZE_STRING);
    }

    /**
     * Sanitizes input data
     *
     * It encodes unwanted characters and removes all special tags
     *
     * @param string $str String to sanitize
     *
     * @return string
     */
    public static function sanitizeData($str)
    {
        if (is_array($str)) {
            $new_array = array();
            foreach ($str as $key => $val) {
                $new_array[self::sanitizeKeys($key)] = self::sanitizeData($val);
            }
            return $new_array;
        }

        // Remove control and invisible characters
        $str = self::removeNullCharacters($str);

        // Final and most important sanitization - clear all special and bad characters
        $str = filter_var($str, FILTER_SANITIZE_SPECIAL_CHARS);
        return filter_var($str, FILTER_SANITIZE_STRING);
    }

    /**
     * Sanitizes array keys
     *
     * In order to prevent array keys exploit this method makes
     * sure that keys are only aplha-numeric and contain allowed characters only.
     *
     * @param string $str Key to sanitize
     *
     * @return string
     */
    public static function sanitizeKeys($str)
    {
        if ( ! preg_match('/^[a-z0-9:_\/-]+$/i', $str)) {
            die('Disallowed Key Characters.');
        }

        // Final and most important sanitization
        return filter_var($str, FILTER_SANITIZE_STRING);
    }

    /**
     * Removes control and null characters
     *
     * Prevents sandwiching null(invisible) characters
     *
     * @param string $str         String
     * @param bool   $url_encoded True by default in order to check for URL encoded null characters
     *
     * @return string Safe to use string
     */
    public static function removeNullCharacters($str, $url_encoded = true)
    {
        $non_displayables = array();

        // Every control character except newline, carriage return and horizontal tab
        if ($url_encoded) {

            // Url encoded 00-08, 11, 12, 14, 15
            $non_displayables[] = '/%0[0-8bcef]/';

            // Url encoded 16-31
            $non_displayables[] = '/%1[0-9a-f]/';
        }

        // 00-08, 11, 12, 14-31, 127
        $non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';

        $count = 0;
        do {
            $str = preg_replace($non_displayables, '', $str, -1, $count);
        } while ($count);

        return $str;
    }

    /**
     * Sanitize filename method
     *
     * @param string $string        Filename
     * @param bool   $relative_path True if path is relative
     *
     * @return string Safe to use filename
     */
    public static function sanitizeFilename($string, $relative_path = false)
    {
        $bad = array(
            '../',
            '<!--',
            '-->',
            '<',
            '>',
            '\'',
            '"',
            '&',
            '$',
            '#',
            '{',
            '}',
            '[',
            ']',
            '=',
            ';',
            '?',
            '%20',
            '%22',
            '%3c',        // <
            '%253c',      // <
            '%3e',        // >
            '%0e',        // >
            '%28',        // (
            '%29',        // )
            '%2528',      // (
            '%26',        // &
            '%24',        // $
            '%3f',        // ?
            '%3b',        // ;
            '%3d'         // =
        );

        if ( ! $relative_path) {
            $bad[] = './';
            $bad[] = '/';
        }

        // This prevents sandwiching null characters
        $str = Prepend::removeNullCharacters($string, false);

        // Returns safe to use filename
        $str = stripslashes(str_replace($bad, '', $str));
        return filter_var($str, FILTER_SANITIZE_STRING);
    }

    /**
     * Sanitize email method
     *
     * Prevents email injection, by stripping bad characters. Sample exploit:
     * sender@theirdomain.com%0ABcc:recipient@anotherdomain.com
     *
     * The exploit above tries to BCC another recipient
     *
     * @param string $email The email to sanitize
     *
     * @return string Safe to use email address
     */
    public static function sanitizeEmail($email)
    {
        // Strips injection chars from email headers
        $email = preg_replace('((?:\n|\r|\t|%0A|%0D|%08|%09)+)i', '', $email);

        // Uses PHP5 internal function to sanitize email string
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);

        // Uses PHP5 internal function to remove all bad characters
        $email = filter_var($email, FILTER_SANITIZE_STRING);

        // Makes sure only 1 email address will be returned
        list($username, $domain) = explode('@', $email, 2);

        $username = preg_replace('/[^A-za-z0-9._+-]+/i', '', $username);
        $domain   = preg_replace('/[^A-za-z0-9.-]+/i', '', $domain);

        // Returns safe to use email address
        return $username . '@' . $domain;
    }
}