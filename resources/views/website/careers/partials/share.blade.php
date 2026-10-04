@php
    $shareTitle = $shareTitle ?? $opening->title;
    $shareNoun = $shareNoun ?? 'الوظيفة';
    $shareLinks = \App\Support\CareerShare::links($shareTitle, $shareUrl);
@endphp

<div class="share" data-career-share data-share-url="{{ $shareUrl }}" data-share-title="{{ $shareTitle }}" role="group" data-share-noun="{{ $shareNoun }}" aria-label="مشاركة {{ $shareNoun }}: {{ $shareTitle }}">
    <span class="share-label" id="share-label">شارك {{ $shareNoun }}</span>
    <ul class="share-list" aria-labelledby="share-label">
        <li><button type="button" class="share-btn" data-share-native hidden>مشاركة عبر الجهاز</button></li>
        <li><button type="button" class="share-btn" data-share-copy>نسخ الرابط</button></li>
        @foreach ($shareLinks as $link)
            <li>
                <a class="share-btn" href="{{ $link['href'] }}" target="_blank" rel="noopener noreferrer" data-share-site="{{ $link['key'] }}" aria-label="مشاركة {{ $shareNoun }} على {{ $link['label'] }} (يفتح في نافذة جديدة)">{{ $link['label'] }}</a>
            </li>
        @endforeach
    </ul>
    <p class="share-status" data-share-status role="status" aria-live="polite"></p>
    <div class="share-manual" data-share-manual hidden>
        <label for="share-manual-input">انسخ الرابط يدويًا:</label>
        <input id="share-manual-input" type="text" readonly dir="ltr" value="{{ $shareUrl }}">
    </div>
</div>

<script>
    (function () {
        var root = document.querySelector('[data-career-share]');
        if (!root) { return; }

        var url = root.getAttribute('data-share-url');
        var title = root.getAttribute('data-share-title');
        var noun = root.getAttribute('data-share-noun');
        var status = root.querySelector('[data-share-status]');
        var manual = root.querySelector('[data-share-manual]');
        var timer;

        function say(message) {
            status.textContent = message;
            clearTimeout(timer);
            timer = setTimeout(function () { status.textContent = ''; }, 4000);
        }

        function showManual() {
            manual.hidden = false;
            var input = manual.querySelector('input');
            input.focus();
            input.select();
            say('تعذّر النسخ تلقائيًا، انسخ الرابط من الحقل أدناه.');
        }

        function legacyCopy() {
            var area = document.createElement('textarea');
            area.value = url;
            area.setAttribute('readonly', '');
            area.style.position = 'fixed';
            area.style.opacity = '0';
            document.body.appendChild(area);
            area.select();
            var ok = false;
            try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
            document.body.removeChild(area);
            return ok;
        }

        root.querySelector('[data-share-copy]').addEventListener('click', function () {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(url).then(function () {
                    manual.hidden = true;
                    say('تم نسخ رابط ' + noun + '.');
                }, function () {
                    if (legacyCopy()) { say('تم نسخ رابط ' + noun + '.'); } else { showManual(); }
                });
            } else if (legacyCopy()) {
                manual.hidden = true;
                say('تم نسخ رابط ' + noun + '.');
            } else {
                showManual();
            }
        });

        var nativeButton = root.querySelector('[data-share-native]');
        if (navigator.share) {
            nativeButton.hidden = false;
            nativeButton.addEventListener('click', function () {
                navigator.share({ title: title, text: title, url: url }).catch(function (error) {
                    if (error && error.name !== 'AbortError') { say('تعذّرت المشاركة من الجهاز، استخدم أحد الأزرار الأخرى.'); }
                });
            });
        }
    })();
</script>
