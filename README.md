# SafeForm - Secure PHP Form Validation Framework

Just simple PHP native form validation framework with built-in security features including CSRF protection, input sanitization, and more validation rules


## Requirements

- PHP >= 8.1
- ext-mbstring
- paragonie/anti-csrf ^2.4 (optional, for enhanced CSRF protection)

or

```json
    "require": {
        "php": ">=8.1",
        "ext-mbstring": "*",
        "paragonie/anti-csrf": "^2.4"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.5"
    },
    "autoload": {
        "psr-4": {
            "SFORM\\FormValidator\\": "src/"
        },
        "files": [
            "src/helpers.php"
        ]
    },
    "autoload-dev": {
        "psr-4": {
            "SFORM\\FormValidator\\Tests\\": "tests/"
        }
    },
    "minimum-stability": "stable",
    "prefer-stable": true
```

## Installation

### Via Composer

```php
// Still on work
```

### Manual Installation

1. Download the package
2. Include the autoloader:

```php
require_once '/vendor/autoload.php';
# or alternate
require "./autoload.php"
```

## Quick Start

### Basic Form Validation

```php
<?php
require "./autoload.php";

use SFORM\FormValidator\SafeForm;
use function SFORM\FormValidator\is_valid;
use function SFORM\FormValidator\errorMsg;
use function SFORM\FormValidator\xcsrf_token;
use function SFORM\FormValidator\xcsrf_is_valid;
?>
<form method="POST" action="process.php">
    <?php xcsrf_token(); ?>
    <input type="email" name="email" placeholder="Email">
    <input type="text" name="username" placeholder="Username">
    <button type="submit">Submit</button>
</form>

<?php

// ./proccess.php

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    //=======Validate CSRF token=======
    if (!xcsrf_is_valid()) {
        die("CSRF validation failed");
    }

    $form = new SafeForm();
    $form->REQUEST("POST");

    //=======Validate email=======
    $email = $form->getName("email")->rules([
        "required" => true,
        "length" => 100,
        "form_type" => ["email"]
    ]);

    //=======Validate username=======
    $user = $form->getName("username")->rules([
        "required" => true,
        "length" => 50,
        "unicode" => "UTF-8"
    ]);

    //=======Check validation results=======
    if (!is_valid($email)) {
        errorMsg(function ($type) use ($email) {
            $type($email["required"])->msg([
                "email_required" => "Email is required"
            ]);
            $type($email["form_type"])->msg([
                "email_invalid" => "Invalid email format"
            ]);
        })->action([
            "old" => true,
            "redirect" => "/"
        ]);
    }

    if (is_valid($email) && is_valid($user)) {
        //=======Process form data=======
        echo "successfully posted";
    }
}
```

## Security Features

### 1. CSRF Protection

```php
use function SFORM\FormValidator\xcsrf_token;
use function SFORM\FormValidator\xcsrf_is_valid;

xcsrf_token();

if (!xcsrf_is_valid()) {
    print("CSRF validation failed");
}
```

### 2. Input Sanitization

```php
use SFORM\FormValidator\Security\InputSanitizer;

//=======Sanitize string=======
$clean = InputSanitizer::sanitizeString($_POST["name"]);

//=======Sanitize email=======
$email = InputSanitizer::sanitizeEmail($_POST["email"]);

//=======Sanitize URL=======
$url = InputSanitizer::sanitizeUrl($_POST["website"]);

//=======Escape HTML output=======
echo InputSanitizer::escapeHtml($usr_input);

//=======Limit input length=======
$limited = InputSanitizer::limitLength($_POST["comment"], 500);
```

### 3. Secure Redirects

```php
use SFORM\FormValidator\Security\RedirectValidator;

//=======Allowed hosts=======
RedirectValidator::setAllowedHosts(["example.com", "www.example.com"]);

//=======Validate and sanitize redirect URL=======
$thrust_uris = RedirectValidator::sanitizeRedirectUrl($_GET["redirect"], "/");
header("Location: " . $thrust_uris);
```

### 4. Security Logging

```php
use SFORM\FormValidator\Security\Logger;

//=======Log events=======
Logger::logSecurityEvent("Failed login attempt", ["username" => $username]);

//=======CSRF failures=======
Logger::logCsrfFailure(["ip" => $_SERVER["REMOTE_ADDR"]]);

//=======Get recent events=======
$events = Logger::getRecentSecurityEvents(50);
```

## Validation Rules

### Available Rules

- `required` (bool): Field must not be empty
- `length` (int): Maximum character length
- `unicode` (string): Character encoding (UTF-8, ASCII, etc.)
- `form_type` (string|array): Data type validation
  - `email`: Valid email address
  - `url`: Valid URL
  - `ip`: Valid IP address
  - `numeric`: Numeric value
  - `alpha`: Alphabetic characters only
  - `alphanumeric`: Alphanumeric characters only

### Example with Multiple Rules

```php
$validation = $form->getName("email")->rules([
    "required"  => true,
    "length"    => 255,
    "unicode"   => "UTF-8",
    "form_type" => ["email"]
]);
```

## Error Handling

### Display Errors

```php
use function SFORM\FormValidator\show_error;

//=======HTML=======
<?php echo show_error('error_email'); ?>

//=======Custom wrapper=======
<?php echo show_error('error_email', '<span class="error">{error}</span>'); ?>
```

### Multiple Form Validation

```php
use function SFORM\FormValidator\deferActions;
use function SFORM\FormValidator\executeActions;

//=======Defer actions to prevent premature redirects=======
deferActions();

//=======Validate multiple forms=======
if (!is_valid($form1)) {
    errorMsg(function ($type) use ($form1) {
        // ........ message action
        /**
         * Ex:
         * 
         * $type($form1[<key>])->msg(<key> => "msg"
         */
    })->action(["old" => true]);
}

if (!is_valid($form2)) {
    errorMsg(function ($type) use ($form2) {
        // ........ message action
        /**
         * Ex:
         * 
         * $type($form2[<key>])->msg(<key> => "msg"
         */
    })->action(["old" => true]);
}

//======Execute all actions at once======
executeActions()->action(["redirect" => "/"]);
```

## Configuration

### CSRF Token Settings

```php
use SFORM\FormValidator\Form\Xcsrf;

//======maximum tokens to store======
Xcsrf::setMaxTokens(20);

//======Set token lifetime======
Xcsrf::setTokenLifetime(7200); //2 hours
```

### Session Management

```php
use SFORM\FormValidator\Security\SessionManager;

//======with custom config======
SessionManager::init([
    "cookie_lifetime"   => 0,
    "cookie_secure"     => true,
    "cookie_httponly"   => true,
    "cookie_samesite"   => "Strict"
]);

//======Set session value======
SessionManager::set('user_id', 123);

//======Get session value======
$s_id = SessionManager::get('user_id');

//======Flash data (one-time)======
SessionManager::flash("success", "Form submit success");
$msg = SessionManager::getFlash("success");
```

## need to know

1. **Always validate CSRF tokens** on POST requests
2. **Sanitize all user inputs** before processing
3. **Use prepared statements** for database queries (never concatenate SQL)
4. **Validate redirect URLs** to prevent open redirect vulnerabilities
5. **Enable security logging** for audit trails
6. **Set secure session parameters** in production
7. **Limit input lengths** to prevent DoS attacks
8. **Use HTTPS** in production (set `cookie_secure` to true)
9. **Keep logs secure** and monitor them regularly
10. **Escape all output** to prevent XSS attacks

## Security & Features

- **Comprehensive Validation Rules**: Required, length, unicode encoding, email, URL, IP, numeric, alpha, alphanumeric
- **CSRF Protection**: Built-in token generation and validation

- CSRF protection enabled
- Input sanitization applied
- SQL injection prevention (prepared statements)
- XSS prevention (output escaping)
- Open redirect prevention
- Secure session configuration
- Security event logging
- Input length limits
- HTTPS enforcement

- **Input Sanitization**: Automatic sanitization of user inputs
- **Secure Session Management**: Best practices for session handling
- **Security Logging**: Audit trail for security events
- **Open Redirect Prevention**: URL validation for safe redirects
- **Fluent Interface**: Easy-to-use chainable API


## Testing

Run PHPUnit tests:

```bash
composer test
```

Or manually:

```bash
./vendor/bin/phpunit
```

## License

MIT License - see LICENSE file for details

## Contributing

Contributions are welcome! Please submit pull requests or open issues on GitHub.

## Support

For issues and questions:
- GitHub Issues: https://github.com/KURTZ119/issues
- Documentation: https://github.com/KURTZ119/safeform-validate

## Security Vulnerabilities

If you discover a security vulnerability, please email abnyasir.kenka@gmail.com instead of using the issue tracker