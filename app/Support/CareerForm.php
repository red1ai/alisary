<?php

namespace App\Support;

use App\Models\CareerOpening;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class CareerForm
{
    /**
     * Rules for the fixed applicant fields plus the opening's configured fields.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(CareerOpening $opening): array
    {
        return [
            'full_name' => [...['required', 'string', 'max:255'], ...self::extraRules($opening->coreFieldEntries()['full_name'] ?? [])],
            'phone' => [...['required', 'string', 'max:50'], ...self::extraRules($opening->coreFieldEntries()['phone'] ?? [])],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('career_applications', 'email')->where('career_opening_id', $opening->id),
                ...self::extraRules($opening->coreFieldEntries()['email'] ?? []),
            ],
            'website' => ['prohibited'],
            'form_rendered_at' => ['nullable', 'integer'],
            ...self::fieldRules($opening),
        ];
    }

    /**
     * Rules for configured fields: the shared base rules plus career-only options
     * (max_length, pattern, radio choices and multiple files).
     *
     * @return array<string, array<int, mixed>>
     */
    public static function fieldRules(CareerOpening $opening): array
    {
        $fields = $opening->fields();
        $rules = CustomFormFields::validationRules($fields);

        foreach ($fields as $field) {
            $key = $field['key'] ?? null;
            $type = $field['type'] ?? null;

            if (! is_string($key) || ! is_string($type)) {
                continue;
            }

            $required = (bool) ($field['required'] ?? false);

            if ($type === 'radio') {
                $rules["answers.{$key}"] = [
                    $required ? 'required' : 'nullable',
                    'string',
                    Rule::in(collect($field['options'] ?? [])->pluck('value')->filter()->map(fn ($v) => (string) $v)->all()),
                ];

                continue;
            }

            if ($type === 'file' && ($field['multiple'] ?? false)) {
                $rules["files.{$key}"] = [$required ? 'required' : 'nullable', 'array', 'max:10'];
                $rules["files.{$key}.*"] = CustomFormFields::rulesForType('file', $field);

                continue;
            }

            if (in_array($type, ['text', 'textarea', 'phone', 'email'], true)) {
                $rules["answers.{$key}"] = [...($rules["answers.{$key}"] ?? []), ...self::extraRules($field)];
            }
        }

        return $rules;
    }

    /**
     * Human-readable attribute names (the field labels) for validation messages.
     *
     * @return array<string, string>
     */
    public static function fieldAttributes(CareerOpening $opening): array
    {
        $attributes = [];

        foreach ($opening->fields() as $field) {
            $key = $field['key'] ?? null;

            if (! is_string($key) || ! filled($field['label'] ?? null)) {
                continue;
            }

            $label = $field['label'];
            $attributes["answers.{$key}"] = $label;
            $attributes["files.{$key}"] = $label;
            $attributes["files.{$key}.*"] = $label;
        }

        return $attributes;
    }

    /**
     * Career-only text rules: max length and a pattern checked after normalising
     * Arabic-Indic digits (the message is the field's own pattern message).
     *
     * @param  array<string, mixed>  $field
     * @return array<int, mixed>
     */
    private static function extraRules(array $field): array
    {
        $rules = [];

        if (filled($field['max_length'] ?? null)) {
            $rules[] = 'max:'.(int) $field['max_length'];
        }

        if (filled($field['pattern'] ?? null)) {
            $pattern = (string) $field['pattern'];
            $message = filled($field['pattern_message'] ?? null) ? (string) $field['pattern_message'] : 'القيمة غير صحيحة.';

            $rules[] = function (string $attribute, mixed $value, Closure $fail) use ($pattern, $message): void {
                $normalized = strtr((string) $value, [
                    '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
                    '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
                ]);

                if (@preg_match('/'.str_replace('/', '\/', $pattern).'/u', $normalized) !== 1) {
                    $fail($message);
                }
            };
        }

        return $rules;
    }

    /**
     * Evaluate the opening's knockout rules against submitted answers.
     *
     * Each rule: {field, operator: equals|not_equals|in|not_in|min|max|min_age, value, message}.
     * min_age expects a date answer and requires an age in years of at least value.
     * A rule fails (blocks the application) when the answer does not satisfy it.
     *
     * @param  array<string, mixed>  $answers
     * @return array<string, string> error messages keyed by "answers.<field>"
     */
    public static function eligibilityErrors(CareerOpening $opening, array $answers): array
    {
        $errors = [];

        foreach ($opening->eligibility_rules ?? [] as $rule) {
            $field = $rule['field'] ?? null;

            if (! is_string($field) || ! isset($rule['operator'])) {
                continue;
            }

            if (! self::passes($rule, $answers[$field] ?? null)) {
                $errors["answers.{$field}"] ??= $rule['message'] ?? 'عذرًا، لا تنطبق عليك شروط التقديم لهذه الوظيفة.';
            }
        }

        return $errors;
    }

    private static function ageFrom(mixed $date): ?int
    {
        try {
            return filled($date) ? Carbon::parse((string) $date)->age : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    private static function passes(array $rule, mixed $answer): bool
    {
        $expected = $rule['value'] ?? null;
        $values = is_array($expected)
            ? $expected
            : (in_array($rule['operator'], ['in', 'not_in'], true) ? array_map('trim', explode(',', (string) $expected)) : [$expected]);

        return match ($rule['operator']) {
            'equals' => (string) $answer === (string) $expected,
            'not_equals' => (string) $answer !== (string) $expected,
            'in' => in_array((string) $answer, array_map('strval', $values), true),
            'not_in' => ! in_array((string) $answer, array_map('strval', $values), true),
            'min_age' => self::ageFrom($answer) !== null && self::ageFrom($answer) >= (int) $expected,
            'min' => is_numeric($answer) && $answer >= $expected,
            'max' => is_numeric($answer) && $answer <= $expected,
            default => false,
        };
    }
}
