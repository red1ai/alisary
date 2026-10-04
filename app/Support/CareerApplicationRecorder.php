<?php

namespace App\Support;

use App\Mail\CareerApplicationReceived;
use App\Models\CareerApplication;
use App\Models\CareerOpening;
use App\Settings\GeneralSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CareerApplicationRecorder
{
    /**
     * Persist a validated application: private files on the local disk, the record,
     * and a queued internal notification to the configured recipients.
     *
     * @param  array<string, mixed>  $validated
     * @param  array<string, mixed>  $uploaded
     */
    public static function record(CareerOpening $opening, array $validated, array $uploaded, GeneralSettings $settings): CareerApplication
    {
        $directory = "career-applications/{$opening->id}/".Str::uuid();

        $files = [];
        foreach ($uploaded as $key => $file) {
            if (! isset($validated['files'][$key])) {
                continue;
            }

            if ($file instanceof UploadedFile) {
                $files[$key] = $file->store($directory, 'local');
            } elseif (is_array($file)) {
                $files[$key] = collect($file)
                    ->filter(fn ($item): bool => $item instanceof UploadedFile)
                    ->map(fn (UploadedFile $item): string => $item->store($directory, 'local'))
                    ->values()
                    ->all();
            }
        }

        $application = CareerApplication::create([
            'reference_number' => 'CA-'.now()->format('ymd').'-'.Str::upper(Str::random(5)),
            'career_opening_id' => $opening->id,
            'full_name' => $validated['full_name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'answers' => $validated['answers'] ?? [],
            'files' => $files,
        ]);

        $emails = $settings->jobSubmissionRecipientEmails();
        if (! empty($emails)) {
            Mail::to($emails)->queue(new CareerApplicationReceived($application));
        }

        return $application;
    }
}
