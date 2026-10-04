@php
    $key = $field['key'] ?? '';
    $type = $field['type'] ?? 'text';
    $isCore = $type === 'core';
    $coreDefaults = ['full_name' => 'الاسم الكامل', 'phone' => 'رقم الهاتف', 'email' => 'البريد الإلكتروني'];
    $label = $field['label'] ?? ($isCore ? ($coreDefaults[$key] ?? $key) : $key);
    $required = $isCore ? true : (bool) ($field['required'] ?? false);
    $hint = $field['hint'] ?? null;
    $maxLength = filled($field['max_length'] ?? null) ? (int) $field['max_length'] : null;
    $pattern = filled($field['pattern'] ?? null) ? $field['pattern'] : null;
    $multiple = (bool) ($field['multiple'] ?? false);
    $inputmode = $field['inputmode'] ?? null;
    $name = $isCore ? $key : ($type === 'file' ? "files[{$key}]".($multiple ? '[]' : '') : "answers[{$key}]");
    $errorKey = $isCore ? $key : ($type === 'file' ? "files.{$key}" : "answers.{$key}");
    $oldKey = $isCore ? $key : "answers.{$key}";
    $accepted = collect($field['accepted_file_types'] ?? [])->map(fn ($extension) => '.'.$extension)->implode(',');
    $maxKb = (int) ($field['max_file_size_kb'] ?? 5120);
    $oldValues = collect(old($oldKey, []))->filter()->values();
    $inputId = 'f_'.$key;
    $describedBy = trim(($hint ? 'h_'.$key.' ' : '').'e_'.$key);
    $options = collect($field['options'] ?? [])->map(fn ($o) => ['label' => $o['label'] ?? ($o['value'] ?? ''), 'value' => (string) ($o['value'] ?? ($o['label'] ?? ''))]);
    $coreType = ['full_name' => 'text', 'phone' => 'tel', 'email' => 'email'][$key] ?? 'text';
    $coreAutocomplete = ['full_name' => 'name', 'phone' => 'tel', 'email' => 'email'][$key] ?? 'off';
    $counter = in_array($type, ['textarea', 'text'], true) && $maxLength !== null && $type === 'textarea';
@endphp

@if ($type === 'radio' || $type === 'checkbox_list')
    <fieldset class="form-field" data-cf="{{ $key }}" data-type="{{ $type }}" data-required="{{ $required ? 1 : 0 }}" aria-describedby="{{ $describedBy }}">
        <legend>{{ $label }} @if ($required)<b aria-hidden="true">*</b>@else<span class="opt">(اختياري)</span>@endif</legend>
        <span class="choice-grid">
            @foreach ($options as $option)
                <label class="choice-pill">
                    @if ($type === 'radio')
                        <input type="radio" name="{{ $name }}" value="{{ $option['value'] }}" @checked((string) old($oldKey) === $option['value'])>
                    @else
                        <input type="checkbox" name="{{ $name }}[]" value="{{ $option['value'] }}" @checked($oldValues->containsStrict($option['value']))>
                    @endif
                    <span>{{ $option['label'] }}</span>
                </label>
            @endforeach
        </span>
        @if ($hint)<span class="hint" id="h_{{ $key }}">{{ $hint }}</span>@endif
        <span class="field-error" id="e_{{ $key }}" role="alert">@error($errorKey)<small>{{ $message }}</small>@enderror</span>
    </fieldset>
@elseif ($type === 'checkbox')
    <div class="form-field consent-field" data-cf="{{ $key }}" data-type="checkbox" data-required="{{ $required ? 1 : 0 }}">
        <label class="consent">
            <input type="checkbox" name="{{ $name }}" value="1" @checked(old($oldKey)) aria-describedby="{{ $describedBy }}">
            <span>{{ $label }} @if ($required)<b aria-hidden="true">*</b>@endif</span>
        </label>
        <span class="field-error" id="e_{{ $key }}" role="alert">@error($errorKey)<small>{{ $message }}</small>@enderror</span>
    </div>
@else
    <div class="form-field"
        data-cf="{{ $key }}"
        data-type="{{ $isCore ? $coreType : $type }}"
        data-required="{{ $required ? 1 : 0 }}"
        @if ($pattern) data-pattern="{{ $pattern }}" data-pattern-message="{{ $field['pattern_message'] ?? '' }}" @endif
        @if ($type === 'file') data-accept="{{ $accepted }}" data-max-kb="{{ $maxKb }}" @endif>
        <label for="{{ $inputId }}">
            {{ $label }} @if ($required)<b aria-hidden="true">*</b>@else<span class="opt">(اختياري)</span>@endif
            @if ($counter)<span class="cnt" data-cnt="{{ $key }}" aria-hidden="true"></span>@endif
        </label>
        @if ($isCore)
            <input id="{{ $inputId }}" name="{{ $name }}" type="{{ $coreType }}" value="{{ old($oldKey) }}" autocomplete="{{ $coreAutocomplete }}"
                @if ($coreType === 'tel') inputmode="tel" dir="auto" @endif
                @if ($maxLength) maxlength="{{ $maxLength }}" @endif
                aria-describedby="{{ $describedBy }}">
        @elseif ($type === 'textarea')
            <textarea id="{{ $inputId }}" name="{{ $name }}" rows="5" @if ($maxLength) maxlength="{{ $maxLength }}" @endif aria-describedby="{{ $describedBy }}">{{ old($oldKey) }}</textarea>
        @elseif ($type === 'select')
            <select id="{{ $inputId }}" name="{{ $name }}" aria-describedby="{{ $describedBy }}">
                <option value="">اختر…</option>
                @foreach ($options as $option)
                    <option value="{{ $option['value'] }}" @selected((string) old($oldKey) === $option['value'])>{{ $option['label'] }}</option>
                @endforeach
            </select>
        @elseif ($type === 'file')
            <span class="application-upload" data-upload>
                <input type="file" class="upload-input" id="{{ $inputId }}" name="{{ $name }}" @if ($accepted) accept="{{ $accepted }}" @endif @if ($multiple) multiple @endif aria-describedby="{{ $describedBy }} u_{{ $key }}">
                <label for="{{ $inputId }}" class="upload-button">{{ $multiple ? 'اختر الملفات' : 'اختر ملفًا' }}</label>
                <span class="upload-status" id="u_{{ $key }}" data-upload-status data-empty="لم يتم اختيار ملف بعد" aria-live="polite">لم يتم اختيار ملف بعد</span>
                <span class="upload-note">سيُرفع الملف عند إرسال الطلب.@if ($accepted) الصيغ المقبولة: {{ str_replace(',', '، ', $accepted) }}.@endif الحد الأقصى {{ round($maxKb / 1024, 1) }} ميجابايت للملف.</span>
            </span>
        @else
            <input
                id="{{ $inputId }}"
                name="{{ $name }}"
                type="{{ ['email' => 'email', 'number' => 'number', 'date' => 'date', 'phone' => 'tel'][$type] ?? 'text' }}"
                value="{{ old($oldKey) }}"
                @if ($type === 'phone') inputmode="tel" autocomplete="tel" @endif
                @if ($type === 'number') inputmode="decimal" step="any" min="0" @endif
                @if ($type === 'date') min="1930-01-01" max="{{ now()->toDateString() }}" @endif
                @if ($inputmode && ! in_array($type, ['date', 'number', 'phone'], true)) inputmode="{{ $inputmode }}" @endif
                @if ($maxLength) maxlength="{{ $maxLength }}" @endif
                aria-describedby="{{ $describedBy }}"
            >
        @endif
        @if ($hint)<span class="hint" id="h_{{ $key }}">{{ $hint }}</span>@endif
        <span class="field-error" id="e_{{ $key }}" role="alert">@error($errorKey)<small>{{ $message }}</small>@enderror @error($errorKey.'.*')<small>{{ $message }}</small>@enderror</span>
    </div>
@endif
