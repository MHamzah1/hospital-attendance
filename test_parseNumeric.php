<?php

// Test the parseNumeric function
function parseNumeric($value)
{
    // If already numeric (from Excel reading), return as integer
    if (is_numeric($value)) {
        return (int) $value;
    }

    // Convert to string for processing
    $value = (string) $value;
    
    // Trim whitespace
    $value = trim($value);
    
    if (empty($value)) {
        return 0;
    }

    // Remove all whitespace
    $value = preg_replace('/\s+/', '', $value);

    // Check if value contains only digits (already an integer)
    if (ctype_digit($value)) {
        return (int) $value;
    }

    // Handle negative numbers
    $isNegative = strpos($value, '-') === 0;
    if ($isNegative) {
        $value = ltrim($value, '-');
    }

    // Find last occurrence of comma and period
    $lastComma = strrpos($value, ',');
    $lastPeriod = strrpos($value, '.');

    // Remove all separators and convert to integer
    if ($lastComma === false && $lastPeriod === false) {
        // No separators - just digits and possibly a sign
        $result = (int) $value;
    } elseif ($lastComma === false) {
        // Only period - remove it and convert
        $value = str_replace('.', '', $value);
        $result = (int) $value;
    } elseif ($lastPeriod === false) {
        // Only comma - remove it and convert
        $value = str_replace(',', '', $value);
        $result = (int) $value;
    } else {
        // Both exist - use the rightmost as decimal indicator if it's in last 3 positions
        if ($lastComma > $lastPeriod) {
            // Comma is rightmost
            if ($lastComma > strlen($value) - 4) {
                // Treat as decimal separator (1-3 digits after)
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
                $result = (int) round((float) $value);
            } else {
                // Treat as thousand separator
                $value = str_replace(['.', ','], '', $value);
                $result = (int) $value;
            }
        } else {
            // Period is rightmost - standard format with decimals
            if ($lastPeriod > strlen($value) - 4) {
                // Treat as decimal separator
                $value = str_replace(',', '', $value);
                $result = (int) round((float) $value);
            } else {
                // Treat as thousand separator
                $value = str_replace([',', '.'], '', $value);
                $result = (int) $value;
            }
        }
    }

    return $isNegative ? -$result : $result;
}

// Test cases
$testCases = [
    '5' => 5,                          // Direct number
    '5000000' => 5000000,              // Large number
    '5.000.000' => 5000000,            // Indonesian format (thousands)
    '5,000,000' => 5000000,            // International format (thousands)
    '5.000,50' => 5000,                // Indonesian format with decimal
    '5,000.50' => 5000,                // International format with decimal
    5000000 => 5000000,                // Already numeric (float from Excel)
    5.0 => 5,                          // Float from Excel
    '-5.000.000' => -5000000,          // Negative with separators
    '5000' => 5000,                    // Simple number with no separators
];

echo "Testing parseNumeric function:\n";
echo str_repeat('=', 60) . "\n";

$allPass = true;
foreach ($testCases as $input => $expected) {
    $result = parseNumeric($input);
    $pass = $result === $expected;
    $allPass = $allPass && $pass;
    $status = $pass ? '✓ PASS' : '✗ FAIL';
    echo sprintf("%s | Input: %-15s | Expected: %-10d | Got: %-10d\n", 
        $status, 
        var_export($input, true), 
        $expected, 
        $result
    );
}

echo str_repeat('=', 60) . "\n";
echo $allPass ? "All tests passed!\n" : "Some tests failed!\n";

?>
