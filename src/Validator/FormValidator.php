<?php
/**
 * @author Ibnu Yasir
 * @verse 1.0
 *
 * Core form validator class
 * Handles validation of form fields based on various rules including
 * required, length, unicode encoding, and type validation
 */
declare(strict_types=1);


namespace SFORM\FormValidator\Validator;

use SFORM\FormValidator\Unicode\CharUnicode;

class FormValidator
{
    private $fieldName  = ""; /** field name being validated */
    private $fieldValue = null; /** field value */

    /**
     * set the field name to validate
     * the function is just fetching the name attr from input form html
     * @param string $name
     * @return void
     */
    public function setFieldName($name)
    {
        $this->fieldName = $name;
        $this->fieldValue = $this->getFieldValue($name);
    }

    /**
     * get field value from POST/GET request
     * some handler HTTP Req
     * @param string $name Field name
     * @return mixed Field value or null if not found
     */
    private function getFieldValue($name)
    {
        if (isset($_POST[$name])) {
            return $_POST[$name];
        }

        if (isset($_GET[$name])) {
            return $_GET[$name];
        }

        return null;
    }

    /**
     * validate field based on rules
     *
     * @param array $rules Validation rules
     * @return array Validation results
     *
     * this method implements the Chain of Responsibility Pattern with an Optimistic Validation Strategy
     * each validation rule is executed sequentially with an early-exit mechanism for performance optimization
     *
     * @param array $rules
     *       required     <bool>        : Mandatory field flag
     *       length       <int>         : Maximum character length constraint
     *       unicode      <string>      : Character encoding validation (UTF-8, ASCII, etc)
     *       form_type    <string|array>: Type validation (email, url, numeric, etc)
     *
     * @return <array>
     *       required     <bool>: Required validation status
     *       length       <bool>: Length validation status
     *       unicode      <bool>: Unicode encoding validation status
     *       form_type    <bool>: Type validation status
     *       valid        <bool>: Master flag representing overall validation state
     */
    public function validate($rules)
    {
        /**
         * RESET HANDLER STATE BEFORE EACH VALIDATION
         * this ensures static properties ($is_valid, $errors, $result_validate) are cleared
         * preventing previous validation states from affecting current validation
         */
        Handler::reset();
        
        /**
         * Uses an Optimistic Validation Pattern where all flags start with true
         * cuz this approach is more efficient because we only change state when validation fails
         *
         */
        $results = array(
            "required"  => true,             // Flag: required
            "length"    => true,             // Flag: maximum length val
            "unicode"   => true,             // Flag: char validate char (charset validation)
            "form_type" => true,             // Flag: data type validation (format validation)
            "valid"     => true,             // Master Flag: agregate all req validation
        );
        if (isset($rules["required"]) && $rules["required"])
        {
            if (empty($this->fieldValue) && $this->fieldValue !== '0')
            {
                $results["required"] = false;    // Local State Update: atomic validation as failed
                $results["valid"]    = false;    // Master Flag Update: propagation exceptions to master flag
                //    Handler::setValidationResult() updating static property $is_valid
                //    func as application-wide validation state
                Handler::setValidationResult(false);
            }
        }
        if (isset($rules["length"]) && !empty($this->fieldValue))
        {
            $length = (int) $rules["length"];
            $valueLength = mb_strlen((string) $this->fieldValue);
            if ($valueLength > $length)
            {
                $results["length"] = false;                   // Atomic flag update
                $results["valid"] = false;                    // Master flag propagation
                Handler::setValidationResult(false);   // Global state synchronization
            }
        }
        if (isset($rules["unicode"]) && !empty($this->fieldValue)) {
            /**
             * force cast to string to ensure encoding name is valid
             * ex: 'UTF-8', 'ISO-8859-1', 'ASCII', 'Windows-1252'
             */
            $encoding = (string) $rules["unicode"];
            $isValid = CharUnicode::isValidEncoding((string) $this->fieldValue, $encoding);
            if (!$isValid)
            {
                $results["unicode"] = false;
                $results["valid"]   = false;
                Handler::setValidationResult(false);
            }
        }
        if (isset($rules['form_type']) && !empty($this->fieldValue)) {
            /**
             * input: 'email' -> Output: ['email']
             * input: ['email', 'required'] -> Output: ['email', 'required']
             * (array) "email" -> ["email"]
             * (array) ["email"] -> ["email"] (no change)
             * (array) null -> [] (empty array)
             * (array) false -> [false]
             */
            $types = (array) $rules['form_type'];

            foreach ($types as $type) {
                /**
                 * validateType()   private method for handle specific type checks
                 * email:           FILTER_VALIDATE_EMAIL
                 * url:             FILTER_VALIDATE_URL
                 * ip:              FILTER_VALIDATE_IP
                 * numeric:         is_numeric()
                 * alpha:           ctype_alpha()
                 * alphanumeric:    ctype_alnum()
                 */
                $typeValid = $this->validateType((string) $type, (string) $this->fieldValue);
                if (!$typeValid)
                {
                    $results['form_type'] = false;
                    $results['valid'] = false;
                    Handler::setValidationResult(false);
                    // Early Exit: Stop processing remaining types
                    // if types = ['email', 'url', 'numeric']
                    // & 'email' failed, 'url' & 'numeric'
                    break;
                }
            }
        }
        Handler::setValidationResults($results);
        // Return Value Structure ex:
        // array(
        //     'required' => false,    // Failed
        //     'length' => true,       // Passed
        //     'unicode' => true,      // Passed
        //     'form_type' => false,   // Failed
        //     'valid' => false        // Overall failed
        // )
        return $results;
    }

    /**
     * validate specific type (email, url, etc.)
     *
     * @param string $type Validation type
     * @param string $value Field value
     * @return bool True if valid, false otherwise
     */
    private function validateType($type, $value)
    {
        $type = strtolower($type);

        if ($type === "email") {
            return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
        }
        elseif ($type === "url") {
            return filter_var($value, FILTER_VALIDATE_URL) !== false;
        }
        elseif ($type === "ip") {
            return filter_var($value, FILTER_VALIDATE_IP) !== false;
        }
        elseif ($type === "numeric") {
            return is_numeric($value);
        }
        elseif ($type === "alpha") {
            return ctype_alpha($value);
        }
        elseif ($type === "alphanumeric") {
            return ctype_alnum($value);
        } else {
            return true;
        }
    }

    /**
     * Get current field name
     *
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }
}