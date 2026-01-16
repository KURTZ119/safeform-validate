<?php

declare(strict_types=1);

namespace SFORM\FormValidator\Validator;

use SFORM\FormValidator\Validator\Action\AfterAction;

class ValidationException
{
    private static array $messages = []; /** @var array<string, string> */
    private static array $validationResults = []; /** @var array<string, bool> */
    private static array $pendingActions = []; /** @var array<string, mixed> Store actions to execute later */
    private static bool $actionsDeferred = false; /** Flag to defer action execution */

    /**
     * @param callable $callback Callback function
     * @return self
     */
    public static function setMessages(callable $callback): self
    {
        self::$validationResults = Handler::getValidationResults();

        $callback(function (mixed $validationResult): ExceptionType {
            return new ExceptionType($validationResult);
        });

        return new self();
    }

    /** 
     * Execute post-validation actions
     * If actions are deferred, store them for later execution
     */
    public function action(array $actions): AfterAction|self
    {
        if (self::$actionsDeferred) {
            // Store actions instead of executing immediately
            self::$pendingActions = array_merge(self::$pendingActions, $actions);
            return $this;
        }
        
        return AfterAction::execute($actions);
    }

    /**
     * Enable deferred action execution
     * Call this before multiple errorMsg() calls to prevent premature redirects
     * @return void
     */
    public static function deferActions(): void
    {
        self::$actionsDeferred = true;
        self::$pendingActions = [];
    }

    /**
     * Execute all pending actions
     * Call this after all errorMsg() calls are complete
     * @return AfterAction
     */
    public static function executeActions(): AfterAction
    {
        self::$actionsDeferred = false;
        $actions = self::$pendingActions;
        self::$pendingActions = [];
        return AfterAction::execute($actions);
    }

    /**
     * Get pending actions
     * @return array<string, mixed>
     */
    public static function getPendingActions(): array
    {
        return self::$pendingActions;
    }

    /**
     * Check if actions are deferred
     * @return bool
     */
    public static function isActionsDeferred(): bool
    {
        return self::$actionsDeferred;
    }

    /**
     * get all messages
     * @return array<string, string>
     */
    public static function getMessages(): array
    {
        return self::$messages;
    }

    /**
     * clear all messages
     * @return void
     */
    public static function clearMessages(): void
    {
        self::$messages = [];
    }

    /**
     * Reset deferred state
     * @return void
     */
    public static function reset(): void
    {
        self::$actionsDeferred = false;
        self::$pendingActions = [];
    }
}

class ExceptionType
{
    private bool $isValid; /** Validation status */

    public function __construct(mixed $isValid)
    {
        $this->isValid = (bool) $isValid;
    }

    /**
     * set error message if validation failed
     * @param array<string, string> $messages err msg
     * @return self
     */
    public function msg(array $messages): self
    {
        if ($this->isValid === false) {
            foreach ($messages as $key => $message) {
                Handler::addError($key, $message);
            }
        }
        return $this;
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }
}