<?php
/**
 * Input validation and sanitization helper
 */
class Validator {
    /**
     * Validates and sanitizes a string field
     */
    public static function validateString($value, $field_name, $min_length = 1, $max_length = 255) {
        if (!isset($value) || !is_string($value)) {
            return ["error" => "$field_name must be a string"];
        }

        $value = trim($value);
        if (empty($value)) {
            return ["error" => "$field_name cannot be empty"];
        }

        if (strlen($value) < $min_length) {
            return ["error" => "$field_name must be at least $min_length characters"];
        }

        if (strlen($value) > $max_length) {
            return ["error" => "$field_name cannot exceed $max_length characters"];
        }

        // Sanitize the string
        return ["value" => htmlspecialchars($value, ENT_QUOTES, 'UTF-8')];
    }

    /**
     * Validates and sanitizes an email field
     */
    /**
     * Validates a phone number in E.164 format
     */
    public static function validatePhoneE164($phone) {
        if (!isset($phone) || !is_string($phone)) {
            return ["error" => "Phone number must be a string"];
        }

        $phone = trim($phone);
        if (empty($phone)) {
            return ["error" => "Phone number cannot be empty"];
        }

        if (!preg_match('/^\+[1-9][0-9]{1,14}$/', $phone)) {
            return ["error" => "Invalid phone number format. Must be in E.164 format (e.g., +18455417975)"];
        }

        return ["value" => $phone];
    }

    public static function validateEmail($email) {
        if (!isset($email) || !is_string($email)) {
            return ["error" => "Email must be a string"];
        }

        $email = trim(strtolower($email));
        if (empty($email)) {
            return ["error" => "Email cannot be empty"];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ["error" => "Invalid email format"];
        }

        if (strlen($email) > 255) {
            return ["error" => "Email cannot exceed 255 characters"];
        }

        return ["value" => $email];
    }

    /**
     * Validates input against allowed fields
     */
    public static function validateAllowedFields($input, $allowed_fields) {
        if (!is_array($input) || !is_array($allowed_fields)) {
            return ["error" => "Invalid input format"];
        }

        $extra_fields = array_diff(array_keys($input), $allowed_fields);
        if (!empty($extra_fields)) {
            return [
                "error" => "Invalid fields in request",
                "invalid_fields" => $extra_fields
            ];
        }

        return ["value" => true];
    }

    /**
     * Validates and sanitizes an array of inputs
     */
    public static function validateInputs($input, $rules) {
        $validated = [];
        $errors = [];

        foreach ($rules as $field => $rule) {
            if (!isset($input[$field]) && !empty($rule['required'])) {
                $errors[$field] = "$field is required";
                continue;
            }

            if (!isset($input[$field])) {
                continue;
            }

            switch ($rule['type']) {
                case 'string':
                    $result = self::validateString(
                        $input[$field],
                        $field,
                        $rule['min_length'] ?? 1,
                        $rule['max_length'] ?? 255
                    );
                    break;
                case 'phone':
                    $result = self::validatePhoneE164($input[$field]);
                    break;
                case 'email':
                    $result = self::validateEmail($input[$field]);
                    break;
                case 'decimal':  // ADD THIS CASE
                    $result = self::validateDecimal(
                        $input[$field],
                        $field,
                        $rule['min'] ?? 0
                    );
                    break;
                case 'integer':
                    $result = self::validateInteger(
                        $input[$field],
                        $field,
                        $rule['min'] ?? 1
                    );
                    break;
                default:
                    $errors[$field] = "Unknown validation type for $field";
                    continue 2;
            }

            if (isset($result['error'])) {
                $errors[$field] = $result['error'];
            } else {
                $validated[$field] = $result['value'];
            }
        }

        if (!empty($errors)) {
            return ["errors" => $errors];
        }

        return ["values" => $validated];
    }

    /**
     * Validates and sanitizes a decimal number for price fields
     * Accepts formats like 9.99, 24.99, etc.
     * 
     * @param mixed $value The value to validate
     * @param string $field_name Name of the field being validated
     * @param float $min Minimum allowed value
     * @return array Validation result with error or sanitized value
     */
    public static function validateDecimal($value, $field_name, $min = 0) {
        if (!isset($value)) {
            return ["error" => "$field_name is required"];
        }

        // Validate format using regex for decimal numbers (9.99, 24.99, etc)
        if (!preg_match('/^\d+(\.\d{1,2})?$/', $value)) {
            return ["error" => "$field_name must be a valid decimal number with up to 2 decimal places"];
        }

        $number = floatval($value);
        if ($number < $min) {
            return ["error" => "$field_name cannot be less than $min"];
        }

        return ["value" => $number];
    }

    /**
     * Validates an integer value
     * 
     * @param mixed $value The value to validate
     * @param string $field_name Name of the field being validated
     * @param int $min Minimum allowed value
     * @return array Validation result with error or sanitized value
     */
    public static function validateInteger($value, $field_name, $min = 1) {
        if (!isset($value)) {
            return ["error" => "$field_name is required"];
        }

        if (!is_numeric($value) || !ctype_digit((string)$value)) {
            return ["error" => "$field_name must be a valid integer"];
        }

        $number = intval($value);
        if ($number < $min) {
            return ["error" => "$field_name cannot be less than $min"];
        }

        return ["value" => $number];
    }
}
?>
