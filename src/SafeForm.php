<?php
/**
 * @author Ibnu Yasir
 * @verse 1.0
 */
declare(strict_types=1);

namespace SFORM\FormValidator;

use SFORM\FormValidator\Validator\FormValidator;
use SFORM\FormValidator\Validator\Handler;
use SFORM\FormValidator\Validator\Action\Request;

/**
 * Main SafeForm class - Entry point for form validation
 * class provides a fluent interface for validating form inputs
 *
 */
class SafeForm
{
    private string $fieldName = "";     /** current field name being validated */
    private FormValidator $validator;   /** validator instance */
    private Handler $handler;           /** handler instance */

    public function __construct()
    {
        $this->validator = new FormValidator();
        $this->handler = new Handler();
    }

    /**
     * Set the field name to validate
     *
     * @param string $name Field name from form input
     * @return self Fluent interface
     */
    public function getName(string $name): self
    {
        $this->fieldName = $name;
        $this->validator->setFieldName($name);
        return $this;
    }

    /**
     * Set validation rules for the field
     *
     * @param array<string, mixed> $rules Array of validation rules
     * @return array<string, bool> Validation results
     */
    public function rules(array $rules): array
    {
        return $this->validator->validate($rules);
    }

    /**
     * Set the request method (POST/GET)
     * @param string $method Request method
     * @return void
     */
    public function REQUEST(string $method): void
    {
        Request::setMethod($method);
    }

    /**
     * Get the validator instance
     * @return FormValidator
     */
    public function getValidator(): FormValidator
    {
        return $this->validator;
    }

    /**
     * @return Handler
     */
    public function getHandler(): Handler
    {
        return $this->handler;
    }
}
