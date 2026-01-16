<?php

declare(strict_types=1);

namespace SFORM\FormValidator;

use SFORM\FormValidator\Validator\Handler;
use SFORM\FormValidator\Validator\ValidationException;
use SFORM\FormValidator\Form\Xcsrf;

/**
 * global helper function for form validation
 */
if (!function_exists(function: "SFORM\Form\Xcsrf\__crsf_token"))
{
    function xcsrf_token()    { Xcsrf::token(); }
    function xcsrf_validate() { Xcsrf::validate(); }
    function xcsrf_is_valid() { return Xcsrf::validate();}
}
if (!function_exists(function: "SFORM\FormValidator\is_error"))
{
    function is_error($e) { return Handler::hasError($e); }
}
if (!function_exists(function: "SFORM\FormValidator\is_valid"))
{
    function is_valid(mixed $validationResult) { return Handler::isValid($validationResult); }
}
if (!function_exists(function: "SFORM\FormValidator\errorMsg"))
{
    function errorMsg(callable $callback): ValidationException { return ValidationException::setMessages($callback); }
}
if (!function_exists(function: "SFORM\FormValidator\deferActions"))
{
    /**
     * Enable deferred action execution for multiple forms
     * Use this before processing multiple errorMsg() calls
     * @return void
     */
    function deferActions(): void { ValidationException::deferActions(); }
}
if (!function_exists(function: "SFORM\FormValidator\executeActions"))
{
    /**
     * Execute all pending actions after processing multiple forms
     * @return \SFORM\FormValidator\Validator\Action\AfterAction
     */
    function executeActions() { return ValidationException::executeActions(); }
}
if (!function_exists(function: "SFORM\FormValidator\show_error"))
{
    /**
     * Display error message with HTML wrapper in a single line
     * @param string $key Error key
     * @param string $wrapper HTML wrapper with {error} placeholder
     * @return string Wrapped error or empty string
     */
    function show_error(string $key, string $wrapper = '<div class="error">{error}</div>'): string 
    { 
        return Handler::showError($key, $wrapper); 
    }
}