<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJobSubmissionRequest;
use App\Http\Requests\StoreTenderSubmissionRequest;
use App\Mail\JobSubmissionReceived;
use App\Mail\TenderSubmissionReceived;
use App\Models\JobListing;
use App\Models\TenderListing;
use App\Settings\GeneralSettings;
use App\Support\CustomFormFields;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class ListingSubmissionController extends Controller
{
    public function storeJob(StoreJobSubmissionRequest $request, JobListing $jobListing, GeneralSettings $settings): RedirectResponse
    {
        $validated = $request->validated();
        $cvPath = $request->file('cv')?->store("submissions/jobs/{$jobListing->id}/cv", 'public');

        $submission = $jobListing->submissions()->create([
            'full_name' => $validated['full_name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'birthday' => $validated['birthday'],
            'cv_path' => $cvPath,
            'answers' => $validated['answers'] ?? [],
            'files' => CustomFormFields::storeFiles($request, "submissions/jobs/{$jobListing->id}/files"),
        ]);

        $recipients = config('career_notifications.applications_to');

        if (filled($recipients)) {
            Mail::to($recipients)->send(new JobSubmissionReceived($jobListing->loadMissing('company'), $submission));
        }

        return back()->with('status', 'تم استلام طلبكم بنجاح، وسيتواصل معكم الفريق عند الحاجة.');
    }

    public function storeTender(StoreTenderSubmissionRequest $request, TenderListing $tenderListing, GeneralSettings $settings): RedirectResponse
    {
        $validated = $request->validated();

        $submission = $tenderListing->submissions()->create([
            'full_name' => $validated['full_name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'answers' => $validated['answers'] ?? [],
            'files' => CustomFormFields::storeFiles($request, "submissions/tenders/{$tenderListing->id}/files"),
        ]);

        $recipients = $settings->tenderSubmissionRecipientEmails();

        if ($recipients !== []) {
            Mail::to($recipients)->send(new TenderSubmissionReceived($tenderListing->loadMissing('contractor'), $submission));
        }

        return back()->with('status', 'تم استلام طلبكم بنجاح، وسيتواصل معكم الفريق عند الحاجة.');
    }
}
