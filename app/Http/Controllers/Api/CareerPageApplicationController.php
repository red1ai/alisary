<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CareerOpening;
use App\Settings\GeneralSettings;
use App\Support\CareerApplicationRecorder;
use App\Support\CareerForm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

/**
 * Receives applications posted by the standalone job pages (resources/job-pages).
 *
 * The pages send flat multipart fields (name, whatsapp, email, nid, cv ...) plus
 * job_id, job_title and tier; this maps them onto the opening's configured form and
 * reuses the same validation, private file storage and notification as the Blade form.
 */
class CareerPageApplicationController extends Controller
{
    /** @var array<string, string> page field name => applicant column */
    private const CORE_FIELDS = ['name' => 'full_name', 'whatsapp' => 'phone', 'email' => 'email'];

    public function __invoke(Request $request, GeneralSettings $settings): JsonResponse
    {
        $opening = CareerOpening::query()->where('slug', (string) $request->input('job_id'))->first();

        abort_unless($opening?->isAcceptingSubmissions(), 404);

        $data = $this->payload($request, $opening);

        $validator = Validator::make($data, CareerForm::rules($opening), [
            'email.unique' => 'لقد سبق أن تقدّمت بطلبٍ لهذه الوظيفة بهذا البريد الإلكتروني.',
        ], [
            'full_name' => $opening->coreLabel('full_name', 'الاسم الكامل'),
            'phone' => $opening->coreLabel('phone', 'رقم الهاتف'),
            'email' => $opening->coreLabel('email', 'البريد الإلكتروني'),
            ...CareerForm::fieldAttributes($opening),
        ]);

        $validator->after(function ($validator) use ($opening, $data): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            foreach (CareerForm::eligibilityErrors($opening, $data['answers']) as $key => $message) {
                $validator->errors()->add($key, $message);
            }

            if ($this->phoneAlreadyApplied($opening, (string) $data['phone'])) {
                $validator->errors()->add('phone', 'لقد سبق أن تقدّمت بطلبٍ لهذه الوظيفة بهذا الرقم.');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $this->pageErrorKeys($validator->errors()->toArray()),
            ], 422);
        }

        $application = CareerApplicationRecorder::record($opening, $validator->validated(), $data['files'], $settings);

        return response()->json(['ref' => $application->reference_number], 201);
    }

    /**
     * Only the applicant fields and the opening's own configured fields are read; anything
     * else the page sends is ignored.
     *
     * @return array{full_name: mixed, phone: mixed, email: mixed, answers: array<string, mixed>, files: array<string, mixed>}
     */
    private function payload(Request $request, CareerOpening $opening): array
    {
        $answers = [];
        $files = [];

        foreach ($opening->fields() as $field) {
            $key = $field['key'] ?? null;

            if (! is_string($key) || array_key_exists($key, self::CORE_FIELDS)) {
                continue;
            }

            if (($field['type'] ?? null) === 'file') {
                $uploaded = $request->file($key);

                if ($uploaded instanceof UploadedFile && ($field['multiple'] ?? false)) {
                    $uploaded = [$uploaded];
                }

                if ($uploaded !== null) {
                    $files[$key] = $uploaded;
                }

                continue;
            }

            if ($request->has($key)) {
                $answers[$key] = $request->input($key);
            }
        }

        return [
            'full_name' => $request->input('name'),
            'phone' => $request->input('whatsapp'),
            'email' => $request->input('email'),
            'answers' => $answers,
            'files' => $files,
        ];
    }

    private function phoneAlreadyApplied(CareerOpening $opening, string $phone): bool
    {
        $needle = $this->phoneDigits($phone);

        return $needle !== '' && $opening->applications()
            ->pluck('phone')
            ->contains(fn (string $existing): bool => $this->phoneDigits($existing) === $needle);
    }

    /**
     * The last eight digits, so "91234567", "+968 91234567" and Arabic-Indic digits compare equal.
     */
    private function phoneDigits(string $phone): string
    {
        $latin = strtr($phone, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        return substr((string) preg_replace('/\D+/', '', $latin), -8);
    }

    /**
     * @param  array<string, array<int, string>>  $errors
     * @return array<string, array<int, string>>
     */
    private function pageErrorKeys(array $errors): array
    {
        $renamed = [];

        foreach ($errors as $key => $messages) {
            $page = array_search($key, self::CORE_FIELDS, true);
            $page = $page !== false ? $page : preg_replace('/^(answers|files)\./', '', $key);
            $renamed[explode('.', (string) $page)[0]] = $messages;
        }

        return $renamed;
    }
}
