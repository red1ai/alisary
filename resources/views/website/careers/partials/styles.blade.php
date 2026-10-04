<style>
    .career-page{--teal:#1C463C;--teal-2:#2B6455;--teal-soft:#E4EEEA;--gold:#B8924A;--gold-soft:#F3EAD6;--c-bg:#F7F4EC;--ink:#1B2B26;--muted:#5A6A64;--line:#E2DCCB;--danger:#B3261E;--danger-soft:#FCEBE9;--warn:#7A5200;--warn-soft:#FFF4D6;--r:16px;--shadow:0 1px 2px rgba(28,70,60,.06),0 8px 24px rgba(28,70,60,.07);background:var(--c-bg);color:var(--ink);font-size:17px;line-height:1.85}
    .career-page ul,.career-page ol{list-style:none;margin:0;padding:0}
    .career-page h1,.career-page h2,.career-page h3,.career-page p{margin:0}
    .career-page :focus-visible{outline:3px solid var(--gold);outline-offset:2px;border-radius:6px}
    .career-page .wrap{max-width:1080px;margin:0 auto;padding:0 20px}
    .career-page .draft{background:var(--warn-soft);color:var(--warn);border-bottom:1px solid #E9D49A;text-align:center;padding:8px 16px;font-size:14.5px;line-height:1.6}
    .career-page .hero .draft{display:block;border:1px solid #E9D49A;border-radius:12px;margin-bottom:14px;padding:8px 16px;text-align:start}
    .career-page .hero{background:var(--teal);color:#fff;position:relative;overflow:hidden;padding:130px 0 40px}
    .career-page .hero::before{content:"";position:absolute;inset:0;opacity:.09;background-image:radial-gradient(circle at 20% 20%,#fff 0 2px,transparent 3px),linear-gradient(45deg,transparent 46%,#fff 47% 49%,transparent 50%),linear-gradient(-45deg,transparent 46%,#fff 47% 49%,transparent 50%);background-size:44px 44px}
    .career-page .hero .wrap{position:relative}
    .career-page .eyebrow{display:inline-block;color:#E8D7AE;font-size:15px;font-weight:500;margin-bottom:8px}
    .career-page .eyebrow::before{content:"";display:inline-block;width:22px;height:2px;background:var(--gold);vertical-align:middle;margin-inline-end:10px}
    .career-page .hero h1{font-size:clamp(28px,5.4vw,46px);line-height:1.35;font-weight:700;max-width:22em}
    .career-page .hero .sub{margin-top:10px;color:#D4E2DC;font-size:clamp(16px,2.2vw,19px);max-width:36em}
    .career-page .chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:20px}
    .career-page .chips li{background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.22);border-radius:999px;padding:3px 14px;font-size:14.5px;line-height:1.7}
    .career-page .chips b{font-weight:500;color:#E8D7AE;margin-inline-end:6px}
    .career-page .btn{font:inherit;font-weight:600;font-size:17px;border:0;border-radius:12px;padding:12px 26px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:48px}
    .career-page .btn-gold{background:#8F6A24;color:#fff}
    .career-page .btn-gold:hover{background:#7A5A1D}
    .career-page .hero-cta{margin-top:26px}
    .career-page .share{margin-top:22px;max-width:36em}
    .career-page .share-label{display:block;color:#E8D7AE;font-size:15px;font-weight:500;margin-bottom:8px}
    .career-page .share-list{display:flex;flex-wrap:wrap;gap:8px}
    .career-page .share-btn{font:inherit;font-weight:600;font-size:15.5px;color:#fff;background:rgba(255,255,255,.12);border:1.5px solid rgba(255,255,255,.4);border-radius:12px;padding:8px 18px;min-height:48px;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;cursor:pointer}
    .career-page .share-btn:hover{background:rgba(255,255,255,.22)}
    .career-page .share-btn[hidden]{display:none}
    .career-page .share-status{min-height:1.6em;margin-top:8px;color:#E8F3EE;font-weight:600;font-size:15.5px}
    .career-page .share-manual{margin-top:8px;background:rgba(255,255,255,.12);border-radius:12px;padding:10px 14px}
    .career-page .share-manual[hidden]{display:none}
    .career-page .share-manual label{display:block;font-size:14.5px;color:#E8D7AE}
    .career-page .share-manual input{width:100%;font:inherit;color:var(--ink);background:#fff;border:0;border-radius:8px;padding:8px 10px;min-height:44px}
    .career-page .layout{display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:28px;padding-top:30px;padding-bottom:10px;align-items:start}
    .career-page .layout.single{grid-template-columns:minmax(0,1fr);max-width:760px}
    .career-page .content{display:grid;gap:16px}
    .career-page .block{background:#fff;border:1px solid var(--line);border-radius:var(--r);padding:22px 24px}
    .career-page .block h2{font-size:20px;line-height:1.5;color:var(--teal);font-weight:700;margin-bottom:10px;display:flex;align-items:center;gap:10px}
    .career-page .block h2::before{content:"";width:6px;height:22px;border-radius:3px;background:var(--gold)}
    .career-page .block p{color:#2C3C36;white-space:pre-line}
    .career-page .ticks li{position:relative;padding-inline-start:30px;margin:6px 0}
    .career-page .ticks li::before{content:"";position:absolute;inset-inline-start:0;top:.55em;width:18px;height:18px;border-radius:50%;background:var(--teal-soft) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3E%3Cpath d='M5 10.5l3.2 3.2L15 6.8' fill='none' stroke='%231C463C' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") center/14px no-repeat}
    .career-page .two{display:grid;grid-template-columns:1fr 1fr;gap:20px}
    .career-page .two h3{font-size:15px;color:var(--muted);font-weight:600;margin-bottom:4px}
    .career-page .dots li{position:relative;padding-inline-start:20px;margin:5px 0}
    .career-page .dots li::before{content:"";position:absolute;inset-inline-start:2px;top:.8em;width:7px;height:7px;border-radius:50%;background:var(--gold)}
    .career-page .rows div{display:flex;gap:14px;justify-content:space-between;padding:9px 0;border-bottom:1px dashed var(--line)}
    .career-page .rows div:last-child{border-bottom:0}
    .career-page .rows dt{color:var(--muted)}
    .career-page .rows dd{margin:0;font-weight:600}
    .career-page .block.pay{background:var(--gold-soft);border-color:#E6D5AA}
    .career-page .path{display:flex;flex-wrap:wrap;align-items:center;gap:6px 8px}
    .career-page .path li{display:flex;align-items:center;gap:8px}
    .career-page .path li span{background:var(--teal-soft);color:var(--teal);font-weight:600;border-radius:999px;padding:4px 16px}
    .career-page .path li+li::before{content:"←";color:var(--gold);font-weight:700}
    .career-page .kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px}
    .career-page .kpis li{background:var(--c-bg);border:1px solid var(--line);border-radius:12px;padding:12px 14px;line-height:1.7}
    .career-page .pillars{display:grid;grid-template-columns:repeat(auto-fit,minmax(118px,1fr));gap:10px}
    .career-page .pillar{border:1.5px solid color-mix(in srgb,var(--c) 45%,#fff);border-top:5px solid var(--c);border-radius:14px;padding:12px;background:color-mix(in srgb,var(--c) 7%,#fff)}
    .career-page .pillar b{display:block;color:var(--c);font-size:18px}
    .career-page .steps{counter-reset:s}
    .career-page .steps li{counter-increment:s;position:relative;padding:0 52px 18px 0}
    .career-page .steps li::before{content:counter(s);position:absolute;right:0;top:0;width:36px;height:36px;border-radius:50%;background:var(--teal);color:#fff;display:grid;place-items:center;font-weight:700;font-size:15px}
    .career-page .steps li::after{content:"";position:absolute;right:17px;top:38px;bottom:2px;width:2px;background:var(--line)}
    .career-page .steps li:last-child{padding-bottom:0}
    .career-page .steps li:last-child::after{display:none}
    .career-page .steps b{display:block;line-height:1.6;padding-top:3px}
    .career-page .steps span{color:var(--muted);font-size:15.5px;display:block}
    .career-page .rich-content{line-height:1.9}
    .career-page .aside{position:sticky;top:16px}
    .career-page .aside .card{background:#fff;border:1px solid var(--line);border-radius:var(--r);padding:20px;box-shadow:var(--shadow)}
    .career-page .aside h3{font-size:16px;color:var(--teal);margin-bottom:6px}
    .career-page .aside .rows{margin-bottom:14px;font-size:15.5px}
    .career-page .aside .btn{width:100%}
    .career-page .apply{padding-top:26px;padding-bottom:70px}
    .career-page .form-card{background:#fff;border:1px solid var(--line);border-radius:20px;padding:28px;box-shadow:var(--shadow);max-width:760px;margin:0 auto}
    .career-page .form-card>h2{font-size:24px;color:var(--teal);line-height:1.5}
    .career-page .form-card .lead{color:var(--muted);margin:2px 0 6px}
    .career-page .notice{background:var(--teal-soft);color:var(--teal);border-radius:10px;padding:12px 16px;margin-top:14px;font-size:16px}
    .career-page .success{background:var(--teal-soft);color:var(--teal);border-radius:12px;padding:18px;margin-top:14px;font-weight:600}
    .career-page .application-progress{display:flex;gap:8px;margin:18px 0 22px}
    .career-page .wizard-indicator{flex:1;height:34px;border-radius:999px;background:var(--line);color:var(--muted);display:grid;place-items:center;font-size:14px;font-weight:700}
    .career-page .wizard-indicator.is-active{background:var(--gold-soft);color:var(--teal);border:2px solid var(--gold)}
    .career-page .application-step-head{display:flex;gap:12px;align-items:flex-start;margin-bottom:10px}
    .career-page .application-step-kicker{flex:none;width:34px;height:34px;border-radius:50%;background:var(--teal);color:#fff;display:grid;place-items:center;font-weight:700}
    .career-page .application-step-head h3{font-size:18px;color:var(--teal);font-weight:700}
    .career-page .application-step-head p{color:var(--muted);font-size:15px}
    .career-page .application-field-grid{display:grid;gap:14px}
    .career-page .form-field{display:block;font-weight:600}
    .career-page .form-field input:not([type=checkbox]):not([type=radio]):not([type=file]),.career-page .form-field select,.career-page .form-field textarea{display:block;width:100%;font:inherit;font-weight:400;color:var(--ink);background:#fff;border:1.5px solid #CFC8B5;border-radius:12px;padding:11px 14px;min-height:50px;margin-top:5px}
    .career-page .form-field input:focus,.career-page .form-field select:focus,.career-page .form-field textarea:focus{outline:none;border-color:var(--teal-2);box-shadow:0 0 0 4px rgba(43,100,85,.15)}
    .career-page .form-field b{color:var(--danger)}
    .career-page .form-field small{display:block;color:var(--danger);font-weight:400;font-size:14.5px;margin-top:5px}
    .career-page .choice-grid{display:grid;gap:8px;margin-top:5px}
    .career-page .choice-pill{display:flex;align-items:center;gap:12px;border:1.5px solid #CFC8B5;border-radius:12px;padding:11px 14px;min-height:50px;font-weight:400}
    .career-page .application-upload{display:flex;align-items:center;gap:12px;flex-wrap:wrap;border:2px dashed #CFC8B5;border-radius:12px;padding:14px;background:#FCFBF7;margin-top:5px;font-weight:400}
    .career-page .upload-input{position:absolute;width:1px;height:1px;opacity:0;overflow:hidden}
    .career-page .upload-button{display:inline-flex;align-items:center;min-height:44px;padding:10px 20px;border-radius:10px;background:var(--teal);color:#fff;font-weight:600;cursor:pointer}
    .career-page .upload-input:focus-visible+.upload-button{outline:3px solid var(--teal-soft);outline-offset:2px}
    .career-page .upload-status{flex:1 1 180px;font-weight:600;color:var(--muted);overflow-wrap:anywhere}
    .career-page .upload-status.has-file{color:var(--teal)}
    .career-page .upload-note{flex-basis:100%;font-size:13.5px;color:var(--muted);line-height:1.7}
    .career-page .attachments-ok{color:var(--teal);font-weight:600}
    .career-page .application-actions{display:flex;gap:12px;justify-content:space-between;margin-top:24px;flex-wrap:wrap}
    .career-page .application-nav-button,.career-page .application-submit-button{font:inherit;font-weight:600;border:0;border-radius:12px;padding:12px 26px;min-height:48px;cursor:pointer;display:inline-flex;align-items:center;gap:8px}
    .career-page .application-nav-button{background:#fff;color:var(--teal);border:1.5px solid var(--line)}
    .career-page .application-submit-button{background:var(--teal);color:#fff;margin-inline-start:auto}
    .career-page .btn-ghost{background:transparent;color:#fff;border:1.5px solid rgba(255,255,255,.55)}
    .career-page .btn-ghost:hover{background:rgba(255,255,255,.12)}
    .career-page .hero-cta{display:flex;flex-wrap:wrap;gap:12px}
    .career-page .pillar{opacity:.62;background:#fff;border-color:var(--line)}
    .career-page .pillar.on{opacity:1;background:color-mix(in srgb,var(--c) 7%,#fff);border-color:color-mix(in srgb,var(--c) 45%,#fff)}
    .career-page .pillar small{display:block;color:var(--muted);font-size:13px;line-height:1.7;margin-top:2px}
    .career-page .pillar em{font-style:normal;font-size:12.5px;color:var(--c);font-weight:600}
    .career-page .pillars-note{margin-top:12px}
    .career-page .wizard-indicator{flex-direction:column;gap:2px;height:auto;min-height:34px;padding:6px 8px;border-radius:14px}
    .career-page .wizard-indicator b{font-weight:700}
    .career-page .wizard-indicator em{display:none;font-style:normal;font-size:13px;line-height:1.4;font-weight:600}
    .career-page .wizard-indicator.is-active em{display:block}
    @media(min-width:601px){.career-page .wizard-indicator em{display:block}}
    .career-page fieldset.form-field{border:0;margin:0;padding:0;min-width:0}
    .career-page fieldset.form-field legend{font-weight:600;padding:0;margin-bottom:5px}
    .career-page .form-field .opt{color:var(--muted);font-weight:400;font-size:14px;margin-inline-start:6px}
    .career-page .form-field .hint{display:block;color:var(--muted);font-weight:400;font-size:14.5px;line-height:1.6;margin-top:4px}
    .career-page .choice-pill input{width:20px;height:20px;accent-color:var(--teal);margin:0;flex:none}
    .career-page .choice-pill:has(input:checked){border-color:var(--teal);background:var(--teal-soft);font-weight:600}
    .career-page .choice-grid:has(input[type=radio]){grid-template-columns:repeat(auto-fit,minmax(120px,1fr))}
    .career-page .application-step[hidden],.career-page [hidden]{display:none}
    .career-page .stepper{display:flex;gap:8px;margin:18px 0 22px}
    .career-page .stepper li{flex:1;display:flex;flex-direction:column;gap:6px;font-size:13.5px;color:var(--muted);line-height:1.5}
    .career-page .stepper li::before{content:"";height:6px;border-radius:4px;background:var(--line)}
    .career-page .stepper li.done::before{background:var(--teal-2)}
    .career-page .stepper li.cur::before{background:var(--gold)}
    .career-page .stepper li.cur{color:var(--ink);font-weight:600}
    .career-page .field-error{display:block;color:var(--danger);font-weight:400;font-size:14.5px;line-height:1.6;margin-top:5px}
    .career-page .field-error:empty{display:none}
    .career-page .field-error small{display:inline;color:inherit;margin:0;font-size:inherit}
    .career-page .field-error.is-gate{background:var(--warn-soft);color:var(--warn);border:1px solid #E9D49A;border-radius:10px;padding:9px 14px;font-size:15px}
    .career-page .form-field.bad input:not([type=checkbox]):not([type=radio]),.career-page .form-field.bad select,.career-page .form-field.bad textarea,.career-page .form-field.bad .choice-pill,.career-page .form-field.bad .application-upload{border-color:var(--danger)}
    .career-page .blocked{background:var(--danger-soft);color:var(--danger);border-radius:10px;padding:10px 14px;margin-top:16px;font-size:15px;line-height:1.7}
    .career-page .consent{display:flex;gap:12px;align-items:flex-start;background:var(--c-bg);border-radius:12px;padding:14px;margin-top:10px;font-weight:600}
    .career-page .consent input{width:22px;height:22px;accent-color:var(--teal);flex:none;margin-top:4px}
    .career-page .cnt{float:inline-end;font-size:13px;color:var(--muted);font-weight:400}
    .career-page .success-card{text-align:center;padding:26px 6px}
    .career-page .success-card .tick{width:72px;height:72px;border-radius:50%;background:var(--teal-soft);margin:0 auto 14px;display:grid;place-items:center}
    .career-page .success-card .tick svg{width:38px;height:38px}
    .career-page .success-card h3{font-size:24px;color:var(--teal);line-height:1.5}
    .career-page .success-card p{color:var(--muted);margin-top:8px}
    .career-page .ctabar{position:fixed;inset-inline:0;bottom:0;background:#fff;border-top:1px solid var(--line);padding:10px 16px calc(10px + env(safe-area-inset-bottom));display:none;gap:12px;align-items:center;justify-content:space-between;z-index:20;box-shadow:0 -6px 20px rgba(0,0,0,.08)}
    .career-page .ctabar span{font-weight:600;line-height:1.4;font-size:15px;color:var(--teal)}
    @media(max-width:900px){.career-page .layout{grid-template-columns:1fr}.career-page .aside{display:none}.career-page .ctabar{display:flex}.career-page .apply{padding-bottom:100px}}
    @media(max-width:600px){.career-page{font-size:16.5px}.career-page .wrap{padding:0 16px}.career-page .block{padding:18px}.career-page .form-card{padding:20px 16px;border-radius:16px}.career-page .two{grid-template-columns:1fr}}
    .career-page .org-link{display:inline-flex;align-items:center;gap:12px;color:#fff;text-decoration:none;font-weight:700;margin-bottom:10px}
    .career-page .org-link:hover span{text-decoration:underline}
    .career-page .org-logo{width:56px;height:56px;object-fit:contain;background:#fff;border-radius:12px;padding:6px}
    .career-page .org-logo.lg{width:96px;height:96px;border-radius:16px;padding:8px}
    .career-page .org-list{display:grid;gap:14px;padding:28px 0 56px}
    .career-page .org-card{display:block;background:#fff;border:1px solid var(--line);border-radius:var(--r);box-shadow:var(--shadow);padding:20px;color:inherit;text-decoration:none}
    .career-page .org-card:hover{border-color:var(--gold)}
    .career-page .org-card h2{font-size:20px;color:var(--teal)}
    .career-page .org-card p{color:var(--muted);margin-top:4px}
    .career-page .empty{background:#fff;border:1px dashed var(--line);border-radius:var(--r);padding:28px;text-align:center;color:var(--muted)}
    @media print{.career-page .ctabar,.career-page .aside,.career-page .btn,.career-page .draft{display:none!important}}
</style>
