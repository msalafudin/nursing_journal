<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;

/**
 * Validation Service
 * 
 * Provides server-side input validation and sanitization
 * to prevent SQL injection, XSS, and other attacks.
 * 
 * Requirements: 10.3
 */
class ValidationService
{
    /**
     * Sanitize string input to prevent XSS attacks
     * 
     * @param string $input
     * @return string
     */
    public static function sanitizeString($input)
    {
        if (!is_string($input)) {
            return $input;
        }

        // Remove null bytes
        $input = str_replace("\0", '', $input);

        // HTML encode special characters
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');

        // Trim whitespace
        $input = trim($input);

        return $input;
    }

    /**
     * Sanitize numeric input
     * 
     * @param mixed $input
     * @return int|null
     */
    public static function sanitizeNumeric($input)
    {
        if ($input === null || $input === '') {
            return null;
        }

        // Remove non-numeric characters except decimal point and minus sign
        $sanitized = preg_replace('/[^0-9.-]/', '', $input);

        // Convert to integer
        return intval($sanitized);
    }

    /**
     * Sanitize array of inputs
     * 
     * @param array $inputs
     * @param array $rules
     * @return array
     */
    public static function sanitizeArray($inputs, $rules = [])
    {
        $sanitized = [];

        foreach ($inputs as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeArray($value, $rules);
            } elseif (is_numeric($value)) {
                $sanitized[$key] = self::sanitizeNumeric($value);
            } else {
                $sanitized[$key] = self::sanitizeString($value);
            }
        }

        return $sanitized;
    }

    /**
     * Validate patient data input
     * 
     * @param array $data
     * @return \Illuminate\Validation\Validator
     */
    public static function validatePatientData($data)
    {
        return Validator::make($data, [
            'date' => 'required|date',
            'shift' => 'required|in:Pagi,Siang,Malam',
            'unit_id' => 'required|integer|exists:units,id',
            'fields' => 'required|array',
            'fields.*' => 'nullable|integer|min:0|max:9999',
        ]);
    }

    /**
     * Validate unit data
     * 
     * @param array $data
     * @return \Illuminate\Validation\Validator
     */
    public static function validateUnit($data)
    {
        return Validator::make($data, [
            'name' => 'required|string|min:2|max:50|regex:/^[a-zA-Z0-9\s]+$/',
        ]);
    }

    /**
     * Validate user data
     * 
     * @param array $data
     * @param int|null $userId
     * @return \Illuminate\Validation\Validator
     */
    public static function validateUser($data, $userId = null)
    {
        $rules = [
            'username' => 'required|string|min:3|max:255|unique:users,username' . ($userId ? ",$userId" : ''),
            'password' => 'required|string|min:8',
            'full_name' => 'required|string|min:2|max:255',
            'unit_id' => 'nullable|integer|exists:units,id',
            'role' => 'required|in:Admin,Nurse',
        ];

        return Validator::make($data, $rules);
    }

    /**
     * Validate login credentials
     * 
     * @param array $data
     * @return \Illuminate\Validation\Validator
     */
    public static function validateLogin($data)
    {
        return Validator::make($data, [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);
    }

    /**
     * Validate date range
     * 
     * @param string $startDate
     * @param string $endDate
     * @param int $maxDays
     * @return array
     */
    public static function validateDateRange($startDate, $endDate, $maxDays = 90)
    {
        $errors = [];

        try {
            $start = \Carbon\Carbon::parse($startDate);
            $end = \Carbon\Carbon::parse($endDate);

            if ($start > $end) {
                $errors[] = 'Start date must be before or equal to end date';
            }

            if ($start->diffInDays($end) > $maxDays) {
                $errors[] = "Date range cannot exceed $maxDays days";
            }
        } catch (\Exception $e) {
            $errors[] = 'Invalid date format';
        }

        return $errors;
    }

    /**
     * Escape SQL-like strings (additional layer of protection)
     * 
     * @param string $input
     * @return string
     */
    public static function escapeSql($input)
    {
        // This is a backup layer - Laravel's query builder already prevents SQL injection
        // but this provides additional protection
        return addslashes($input);
    }

    /**
     * Validate CSRF token
     * 
     * @param string $token
     * @return bool
     */
    public static function validateCsrfToken($token)
    {
        return hash_equals(session('_token'), $token);
    }
}
