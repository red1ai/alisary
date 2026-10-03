<script>
    (function () {
        var form = document.querySelector('[data-career-form]');
        if (!form) { return; }

        var steps = [].slice.call(form.querySelectorAll('[data-career-step]'));
        var items = [].slice.call(document.querySelectorAll('[data-stepper-item]'));
        var prev = form.querySelector('[data-career-prev]');
        var next = form.querySelector('[data-career-next]');
        var submit = form.querySelector('[data-career-submit]');
        var blocked = form.querySelector('[data-career-blocked]');
        var gates = [];
        try { gates = JSON.parse(form.getAttribute('data-gates') || '[]'); } catch (e) { gates = []; }
        var current = 0;

        function digits(text) {
            return String(text).replace(/[٠-٩]/g, function (c) { return c.charCodeAt(0) - 1632; })
                .replace(/[۰-۹]/g, function (c) { return c.charCodeAt(0) - 1776; });
        }

        function wrapper(key) { return form.querySelector('[data-cf="' + key + '"]'); }
        function errorBox(w) { return w.querySelector('.field-error'); }

        function setError(w, message, kind) {
            var box = errorBox(w);
            box.textContent = message || '';
            box.classList.toggle('is-gate', kind === 'gate' && !!message);
            w.classList.toggle('bad', !!message);
            [].forEach.call(w.querySelectorAll('input,select,textarea'), function (el) {
                if (message) { el.setAttribute('aria-invalid', 'true'); } else { el.removeAttribute('aria-invalid'); }
            });
        }

        function value(w) {
            var type = w.getAttribute('data-type');
            var inputs = w.querySelectorAll('input,select,textarea');
            if (type === 'radio') { var picked = w.querySelector('input:checked'); return picked ? picked.value : ''; }
            if (type === 'checkbox_list') { return [].slice.call(w.querySelectorAll('input:checked')).map(function (x) { return x.value; }); }
            if (type === 'checkbox') { return inputs[0].checked; }
            if (type === 'file') { return inputs[0].files; }
            return (inputs[0].value || '').trim();
        }

        function isEmpty(w, v) {
            var type = w.getAttribute('data-type');
            if (type === 'file') { return !v || !v.length; }
            if (type === 'checkbox_list') { return v.length === 0; }
            return !v;
        }

        function age(text) {
            var born = new Date(text), now = new Date();
            if (isNaN(born)) { return null; }
            var years = now.getFullYear() - born.getFullYear();
            var month = now.getMonth() - born.getMonth();
            if (month < 0 || (month === 0 && now.getDate() < born.getDate())) { years--; }
            return years;
        }

        function validateField(w) {
            var type = w.getAttribute('data-type');
            var required = w.getAttribute('data-required') === '1';
            var v = value(w);
            var message = '';

            if (isEmpty(w, v)) {
                if (required) {
                    message = type === 'radio' || type === 'select' ? 'اختر إجابة.'
                        : type === 'file' ? 'أرفق الملف المطلوب.'
                        : type === 'checkbox' ? 'يلزم الإقرار لإرسال الطلب.'
                        : type === 'checkbox_list' ? 'اختر خيارًا واحدًا على الأقل.'
                        : 'هذا الحقل مطلوب.';
                }
            } else if (type === 'file') {
                var accept = (w.getAttribute('data-accept') || '').split(',').map(function (x) { return x.trim().toLowerCase(); }).filter(Boolean);
                var maxBytes = (parseInt(w.getAttribute('data-max-kb'), 10) || 5120) * 1024;
                for (var i = 0; i < v.length; i++) {
                    var name = v[i].name.toLowerCase();
                    var ext = name.slice(name.lastIndexOf('.'));
                    if (accept.length && accept.indexOf(ext) === -1) { message = 'صيغة الملف غير مقبولة (' + accept.join('، ') + ').'; break; }
                    if (v[i].size > maxBytes) { message = 'حجم الملف أكبر من ' + Math.round(maxBytes / 1048576 * 10) / 10 + ' ميجابايت.'; break; }
                }
            } else if (typeof v === 'string') {
                var pattern = w.getAttribute('data-pattern');
                if (pattern && !new RegExp(pattern).test(digits(v))) {
                    message = w.getAttribute('data-pattern-message') || 'القيمة غير صحيحة.';
                } else if (type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) {
                    message = 'صيغة البريد غير صحيحة.';
                } else if (type === 'number' && (isNaN(+v) || +v < 0)) {
                    message = 'أدخل رقمًا صحيحًا.';
                }
            }

            setError(w, message, 'error');
            return !message;
        }

        function gateFails(rule) {
            var w = wrapper(rule.field);
            if (!w) { return false; }
            var v = value(w);
            if (isEmpty(w, v) || typeof v === 'object') { return false; }
            var expected = rule.value;
            var list = Array.isArray(expected) ? expected : String(expected).split(',').map(function (x) { return x.trim(); });
            switch (rule.operator) {
                case 'equals': return String(v) !== String(expected);
                case 'not_equals': return String(v) === String(expected);
                case 'in': return list.indexOf(String(v)) === -1;
                case 'not_in': return list.indexOf(String(v)) !== -1;
                case 'min': return !(!isNaN(+v) && +v >= +expected);
                case 'max': return !(!isNaN(+v) && +v <= +expected);
                case 'min_age': var a = age(v); return !(a !== null && a >= +expected);
                default: return false;
            }
        }

        function refreshGates() {
            var messages = [];
            gates.forEach(function (rule) {
                var w = wrapper(rule.field);
                if (!w) { return; }
                var failed = gateFails(rule);
                var message = rule.message || 'عذرًا، لا تنطبق عليك شروط التقديم لهذه الوظيفة.';
                var box = errorBox(w);
                if (failed) {
                    setError(w, message, 'gate');
                    messages.push(message);
                } else if (box.classList.contains('is-gate')) {
                    setError(w, '', 'error');
                }
            });
            if (messages.length) {
                blocked.hidden = false;
                blocked.textContent = 'لا يمكن إرسال الطلب حاليًّا: ' + messages[0] + ' إن كان اختيارك خاطئًا فعدِّله.';
            } else {
                blocked.hidden = true;
            }
            return messages.length;
        }

        function validateStep(index) {
            var firstBad = null;
            [].forEach.call(steps[index].querySelectorAll('[data-cf]'), function (w) {
                if (!validateField(w) && !firstBad) { firstBad = w; }
            });
            if (refreshGates() > 0 && !firstBad) {
                [].forEach.call(steps[index].querySelectorAll('[data-cf]'), function (w) {
                    if (!firstBad && errorBox(w).classList.contains('is-gate')) { firstBad = w; }
                });
            }
            if (firstBad) {
                var control = firstBad.querySelector('input,select,textarea');
                if (control) { control.focus({ preventScroll: true }); firstBad.scrollIntoView({ block: 'center', behavior: 'smooth' }); }
            }
            return !firstBad;
        }

        function show(index, scroll) {
            current = index;
            steps.forEach(function (step, i) { step.hidden = i !== index; });
            items.forEach(function (item, i) { item.className = i < index ? 'done' : (i === index ? 'cur' : ''); });
            prev.hidden = index === 0;
            next.hidden = index === steps.length - 1;
            submit.hidden = index !== steps.length - 1;
            if (scroll) { document.getElementById('apply-form').scrollIntoView({ behavior: 'smooth', block: 'start' }); }
        }

        prev.addEventListener('click', function () { show(Math.max(0, current - 1), true); });
        next.addEventListener('click', function () {
            if (!validateStep(current)) { return; }
            show(Math.min(steps.length - 1, current + 1), true);
        });

        form.addEventListener('submit', function (event) {
            for (var i = 0; i < steps.length; i++) {
                show(i, false);
                if (!validateStep(i)) { event.preventDefault(); return; }
            }
            show(steps.length - 1, false);
            submit.disabled = true;
            submit.textContent = 'جارٍ الإرسال…';
        });

        form.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' && event.target.tagName !== 'TEXTAREA' && event.target.type !== 'submit') {
                event.preventDefault();
                if (!next.hidden) { next.click(); } else { submit.click(); }
            }
        });

        form.addEventListener('input', function (event) {
            var w = event.target.closest && event.target.closest('[data-cf]');
            if (w) {
                if (!errorBox(w).classList.contains('is-gate')) { setError(w, '', 'error'); }
                var counter = w.querySelector('[data-cnt]');
                if (counter && event.target.maxLength > 0) { counter.textContent = event.target.value.length + ' / ' + event.target.maxLength; }
            }
            refreshGates();
        });

        form.addEventListener('change', function (event) {
            var w = event.target.closest && event.target.closest('[data-cf]');
            if (w) { validateField(w); }
            refreshGates();
        });

        [].forEach.call(form.querySelectorAll('[data-cnt]'), function (counter) {
            var field = counter.closest('[data-cf]').querySelector('textarea');
            if (field) { counter.textContent = field.value.length + ' / ' + field.maxLength; }
        });

        var firstWithServerError = -1;
        steps.forEach(function (step, i) {
            if (firstWithServerError === -1 && step.querySelector('.field-error small')) { firstWithServerError = i; }
        });

        show(firstWithServerError === -1 ? 0 : firstWithServerError, false);
        refreshGates();
    })();
</script>
