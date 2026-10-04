<?php

namespace App\Http\Controllers;

use App\Models\CareerOpening;
use App\Models\CareerOrganization;
use App\Settings\GeneralSettings;
use App\Support\CareerApplicationRecorder;
use App\Support\CareerForm;
use App\Support\CareerShare;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CareerOpeningController extends Controller
{
    private const MIN_FILL_SECONDS = 3;

    public function show(GeneralSettings $settings, CareerOpening $careerOpening): View
    {
        // Unpublished openings stay invisible to the public; signed-in admins may preview.
        // Published openings without approved content render a "coming soon" page with no form.
        abort_unless($careerOpening->isPublished() || auth()->check(), 404);

        return view('website.careers.show', [
            'settings' => $settings,
            'opening' => $careerOpening->load(['company', 'organization']),
            'meta' => $careerOpening->shareMeta(),
        ]);
    }

    public function legacyRedirect(string $slug): RedirectResponse
    {
        $careerOpening = CareerOpening::where('slug', $slug)->firstOrFail();

        return redirect()->route('jobs.show', $careerOpening, 301);
    }

    public function organization(GeneralSettings $settings, CareerOrganization $careerOrganization): View
    {
        $description = filled($careerOrganization->description)
            ? Str::limit(strip_tags($careerOrganization->description), 200)
            : 'الوظائف المتاحة لدى '.$careerOrganization->name;

        return view('website.careers.organization', [
            'settings' => $settings,
            'organization' => $careerOrganization,
            'openings' => $careerOrganization->publishedOpenings(),
            'meta' => CareerShare::meta(
                $careerOrganization->name.' - الوظائف',
                $description,
                $careerOrganization->shareUrl(),
                $careerOrganization->logoUrl(),
            ),
        ]);
    }

    public function store(Request $request, CareerOpening $careerOpening, GeneralSettings $settings): RedirectResponse
    {
        abort_unless($careerOpening->isAcceptingSubmissions(), 404);

        $validator = Validator::make($request->all(), CareerForm::rules($careerOpening), [
            'email.unique' => 'لقد سبق أن تقدّمت بطلبٍ لهذه الوظيفة بهذا البريد الإلكتروني.',
        ], [
            'full_name' => $careerOpening->coreLabel('full_name', 'الاسم الكامل'),
            'phone' => $careerOpening->coreLabel('phone', 'رقم الهاتف'),
            'email' => $careerOpening->coreLabel('email', 'البريد الإلكتروني'),
            ...CareerForm::fieldAttributes($careerOpening),
        ]);

        $validator->after(function ($validator) use ($careerOpening, $request): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            foreach (CareerForm::eligibilityErrors($careerOpening, (array) $request->input('answers', [])) as $key => $message) {
                $validator->errors()->add($key, $message);
            }
        });

        if ($validator->fails()) {
            throw (new ValidationException($validator))->redirectTo(url()->previous().'#apply-form');
        }

        $renderedAt = $request->integer('form_rendered_at');
        if ($renderedAt > 0 && (time() - $renderedAt) < self::MIN_FILL_SECONDS) {
            // Pretend success so bots do not learn the submission was rejected.
            return back()->withFragment('apply-form')->with('application_success', true);
        }

        $application = CareerApplicationRecorder::record(
            $careerOpening,
            $validator->validated(),
            $request->file('files', []),
            $settings,
        );

        return back()
            ->withFragment('apply-form')
            ->with('application_success', true)
            ->with('application_reference', $application->reference_number)
            ->with('application_files_count', collect($application->files)->flatten()->count());
    }
}
