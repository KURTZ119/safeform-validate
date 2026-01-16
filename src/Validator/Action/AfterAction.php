<?php

declare(strict_types=1);

namespace SFORM\FormValidator\Validator\Action;

use SFORM\FormValidator\Security\RedirectValidator;
use SFORM\FormValidator\Security\InputSanitizer;

class AfterAction
{
    public static function execute($actions)
    {
        if (!is_array($actions))
        {
            $actions = array();
        }

        if (array_key_exists("old", $actions) && $actions["old"] === TRUE)
        {
            self::preserveOldInput();
        }

        if (array_key_exists("redirect", $actions))
        {
            self::redirect(strval($actions["redirect"]));
        }

        return new self();
    }

    /**
     * Chainable action method for fluent interface
     * Allows: AfterAction::execute()->action([...])
     */
    public function action(array $actions): self
    {
        return self::execute($actions);
    }

    private static function preserveOldInput()
    {
        if (session_status() === PHP_SESSION_NONE)
        {
            session_start();
        }
        $input_data = array();
        if (isset($_POST) && is_array($_POST))
        {
            // Sanitize input data before storing
            $input_data = InputSanitizer::sanitizeArray($_POST);
        }
        elseif (isset($_GET) && is_array($_GET))
        {
            // Sanitize input data before storing
            $input_data = InputSanitizer::sanitizeArray($_GET);
        }

        $_SESSION["old_input"] = $input_data;
    }

    public static function old($key, $default = "")
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION["old_input"]) && is_array($_SESSION["old_input"]))
        {
            if (array_key_exists($key, $_SESSION["old_input"]))
            {
                $value = $_SESSION["old_input"][$key];
                unset($_SESSION["old_input"][$key]);
                // Escape output to prevent XSS
                return InputSanitizer::escapeHtml(strval($value));
            }
        }

        return InputSanitizer::escapeHtml(strval($default));
    }

    /**
     * Redirect to a URL with security validation
     * @param string $path URL to redirect to
     * @return never
     */
    private static function redirect(string $path): never
    {
        // Validate redirect URL to prevent open redirect vulnerability
        $safePath = RedirectValidator::sanitizeRedirectUrl($path, '/');
        
        if (headers_sent())
        {
            // Escape for JavaScript context
            $jsPath = addslashes($safePath);
            echo '<script type="text/javascript">';
            echo 'window.location.href="' . $jsPath . '";';
            echo '</script>';
            echo '<noscript>';
            // Escape for HTML context
            echo '<meta http-equiv="refresh" content="0;url=' . htmlspecialchars($safePath, ENT_QUOTES, 'UTF-8') . '" />';
            echo '</noscript>';
        }
        else {
            header("Location: " . $safePath);
        }
        exit();
    }

    /**
     * Redirect back to previous page with security validation
     * @return<nope|never>
     */
    public static function back(): never
    {
        $referer = "/";
        if (isset($_SERVER["HTTP_REFERER"]) && strlen($_SERVER["HTTP_REFERER"]) > 0)
        {
            $referer = RedirectValidator::sanitizeRedirectUrl($_SERVER["HTTP_REFERER"], '/');
        }

        self::redirect($referer);
    }

    public static function clearOldInput(): void
    {
        if (session_status() === PHP_SESSION_NONE)
        {
            session_start();
        }

        if (isset($_SESSION["old_input"]))
        {
            unset($_SESSION["old_input"]);
        }
    }
}