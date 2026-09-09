<?php


function validateRequired(string $value, string $label): ?string
{
    return trim($value) === '' ? "$label is required." : null;
}

function validateEmailFormat(string $value): ?string
{
    return filter_var($value, FILTER_VALIDATE_EMAIL) ? null : 'Enter a valid email address.';
}

function validateLength(string $value, string $label, int $min = 0, ?int $max = null): ?string
{
    $length = strlen($value);
    if ($length < $min) {
        return "$label must be at least $min characters.";
    }
    if ($max !== null && $length > $max) {
        return "$label must not exceed $max characters.";
    }
    return null;
}

function validatePositiveId(mixed $value, string $label = 'Record'): ?string
{
    $valid = filter_var($value, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);

    return $valid !== false ? null : "$label identifier is invalid.";
}

function validateIsoDate(string $value, string $label = 'Date'): ?string
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return ($date !== false && $date->format('Y-m-d') === $value)
        ? null
        : "$label is invalid.";
}

function validationErrors(array $errors): array
{
    return array_values(array_filter($errors, static fn ($error) => $error !== null && $error !== ''));
}
