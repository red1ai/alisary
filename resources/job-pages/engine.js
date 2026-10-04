/* محرّك عرض صفحة الوظيفة: يقرأ window.CONFIG ويبني الصفحة والاستمارة */
(function(){
var C = window.CONFIG, B = C.blocks || {}, F = C.form || {};
var app = document.getElementById('app');
var STORE = 'jp:' + C.id, DONE = 'jp:done:' + C.id;
var t0 = Date.now();

function esc(s){return String(s==null?'':s).replace(/[&<>"']/g,function(m){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]})}
function on(k){return B[k] && B[k].on}
function title(k){var d=BLOCKS.filter(function(b){return b.k===k})[0];return esc((B[k]&&B[k].title)||(d&&d.t)||'')}
function ls(a){return Array.isArray(a)?a:String(a||'').split('\n').map(function(x){return x.trim()}).filter(Boolean)}
function safe(fn){try{return fn()}catch(e){return null}}

/* ---------------- أقسام المحتوى ---------------- */
function block(k){
  if(!on(k)) return '';
  var b=B[k], h='<section class="block'+(k==='pay'?' pay':'')+'" id="b-'+k+'"><h2>'+title(k)+'</h2>', body='';
  if(k==='about'||k==='pay'){ body = ls(b.text).map(function(p){return '<p>'+esc(p)+'</p>'}).join('') }
  else if(k==='tasks'){ body='<ul class="ticks">'+ls(b.items).map(function(x){return '<li>'+esc(x)+'</li>'}).join('')+'</ul>' }
  else if(k==='who'){
    var must=ls(b.must), pref=ls(b.prefer);
    body='<div class="'+(pref.length?'two':'')+'"><div>'+(pref.length?'<h3>لا بدّ منه</h3>':'')+'<ul class="dots">'+must.map(function(x){return '<li>'+esc(x)+'</li>'}).join('')+'</ul></div>'
      +(pref.length?'<div><h3>يُقدَّم من عنده</h3><ul class="dots">'+pref.map(function(x){return '<li>'+esc(x)+'</li>'}).join('')+'</ul></div>':'')+'</div>';
  }
  else if(k==='schedule'){ body='<dl class="rows">'+(b.items||[]).map(function(r){return '<div><dt>'+esc(r.k)+'</dt><dd>'+esc(r.v)+'</dd></div>'}).join('')+'</dl>' }
  else if(k==='branches'){
    var rows=b.rows||[], mx=Math.max.apply(null,rows.map(function(r){return +r.seats||0}).concat([1]));
    body='<ul class="seatbar">'+rows.map(function(r){return '<li><span>'+esc(r.name)+'</span><i style="--w:'+Math.round((+r.seats||0)/mx*100)+'%"></i><b>'+esc(r.seats)+'</b></li>'}).join('')+'</ul>';
  }
  else if(k==='growth'){ body='<ul class="path">'+ls(b.items).map(function(x){return '<li><span>'+esc(x)+'</span></li>'}).join('')+'</ul>' }
  else if(k==='kpis'){ body='<ul class="kpis">'+ls(b.items).map(function(x){return '<li>'+esc(x)+'</li>'}).join('')+'</ul>' }
  else if(k==='values'){
    var emph=b.emph||[];
    body='<div class="pillars">'+PILLARS.map(function(p){var e=emph.indexOf(p.id)>-1;return '<div class="pillar'+(e?' on':'')+'" style="--c:'+p.c+'"><b>'+p.n+'</b>'+(e?'<em>محور أساسي لهذه الوظيفة</em>':'')+'<small>'+p.s.join(' · ')+'</small></div>'}).join('')+'</div>'
      +(b.note?'<p style="margin-top:12px">'+esc(b.note)+'</p>':'');
  }
  else if(k==='process'){ body='<ol class="steps">'+(b.items||[]).map(function(r){return '<li><b>'+esc(r.k)+'</b>'+(r.v?'<span>'+esc(r.v)+'</span>':'')+'</li>'}).join('')+'</ol>' }
  return h+body+'</section>';
}

/* أقسام نصية حرّة بعد الأقسام الثابتة: C.extra = [{title, text}] */
function extraBlocks(){return (C.extra||[]).map(function(x,i){return '<section class="block" id="x-'+i+'"><h2>'+esc(x.title)+'</h2>'+ls(x.text).map(function(p){return '<p>'+esc(p)+'</p>'}).join('')+'</section>'}).join('')}

function chips(){return (C.chips||[]).length?'<ul class="chips">'+C.chips.map(function(c){return '<li><b>'+esc(c.k)+'</b>'+esc(c.v)+'</li>'}).join('')+'</ul>':''}

function view(){
  var order=BLOCKS.map(function(b){return b.k}), html=order.map(block).join('')+extraBlocks(), closed=!!C.applyClosed;
  var single=C.tier==='light';
  var aside='';
  if(!single){
    aside='<aside class="aside"><div class="card"><h3>ملخّص سريع</h3><dl class="rows">'
      +(C.chips||[]).map(function(c){return '<div><dt>'+esc(c.k)+'</dt><dd>'+esc(c.v)+'</dd></div>'}).join('')
      +(C.deadline?'<div><dt>آخر موعد</dt><dd>'+esc(C.deadline)+'</dd></div>':'')
      +'</dl>'+(closed?'':'<a class="btn btn-main" href="#apply">قدّم الآن</a>')+'</div></aside>';
  }
  return (C.draft?'<div class="draft">نسخة تجريبية — راجِع النصوص والشروط قبل النشر (يُزال هذا الشريط بإيقاف خيار «مسودة»)</div>':'')
   +'<header class="hero tier-'+esc(C.tier)+'"><div class="wrap"><div class="eyebrow">'+esc(C.unit||'')+'</div><h1>'+esc(C.title)+'</h1>'
   +(C.subtitle?'<p class="sub">'+esc(C.subtitle)+'</p>':'')+chips()
   +'<div class="hero-cta">'+(closed?'':'<a class="btn btn-gold" href="#apply">قدّم الآن</a>')+(html?'<a class="btn btn-ghost" href="#details">التفاصيل</a>':'')+'</div></div></header>'
   +(html?'<div class="wrap layout'+(single?' single':'')+'" id="details"><div class="content">'+html+'</div>'+aside+'</div>':'')
   +'<section class="wrap apply" id="apply">'+(closed?'<div class="form-card"><h2>التقديم</h2><p class="lead">'+esc(C.applyClosed)+'</p></div>':formHTML())+'</section>'
   +'<footer class="foot"><div class="wrap"><b>'+esc(C.org||'مجموعة العيسري')+'</b>'+(C.tagline?' — «'+esc(C.tagline)+'»':'')+'</div></footer>'
   +(closed?'':'<div class="ctabar" id="ctabar"><span>'+esc(C.title)+'</span><a class="btn btn-main" href="#apply">قدّم الآن</a></div>');
}

/* ---------------- الاستمارة ---------------- */
function fdef(f){
  var d=Object.assign({},FIELDS[f.k]||{});
  ['label','hint','options','max','min','unit','type'].forEach(function(p){if(f[p]!=null)d[p]=f[p]});
  if(d.fromBranches){var rows=(B.branches&&B.branches.rows)||[];d.options=rows.map(function(r){return r.name})}
  return d;
}
function fieldHTML(f){
  var d=fdef(f), k=f.k, id='f_'+k, req=f.req, lab=esc(d.label)+(req?'<span class="req" aria-hidden="true">*</span>':'<span class="opt">(اختياري)</span>');
  var hint=d.hint?'<div class="hint" id="h_'+k+'">'+esc(d.hint)+'</div>':'', aria=d.hint?' aria-describedby="h_'+k+' e_'+k+'"':' aria-describedby="e_'+k+'"';
  var ctl='';
  if(d.type==='radio'){
    ctl='<fieldset'+aria+'><legend>'+lab+'</legend><div class="choices'+(d.options.length<=3&&d.options.join('').length<30?' row':'')+'">'
      +d.options.map(function(o){return '<label class="choice"><input type="radio" name="'+k+'" value="'+esc(o)+'"><span>'+esc(o)+'</span></label>'}).join('')+'</div>'+hint+'</fieldset>';
  } else {
    var head='<label for="'+id+'">'+lab+(d.type==='textarea'?'<span class="cnt" data-cnt="'+k+'"></span>':'')+'</label>';
    if(d.type==='select') ctl=head+'<select class="input" id="'+id+'" name="'+k+'"'+aria+'><option value="">اختر…</option>'+d.options.map(function(o){return '<option>'+esc(o)+'</option>'}).join('')+'</select>'+hint;
    else if(d.type==='textarea') ctl=head+'<textarea class="input" id="'+id+'" name="'+k+'" maxlength="'+(d.max||1000)+'"'+aria+'></textarea>'+hint;
    else if(d.type==='file') ctl=head+'<div class="file"><input type="file" id="'+id+'" name="'+k+'" accept="'+esc(d.accept||'')+'"'+(d.multiple?' multiple':'')+aria+'></div>'+hint;
    else if(d.type==='number') ctl=head+'<div class="unit"><input class="input" id="'+id+'" name="'+k+'" type="number" inputmode="decimal" min="'+(d.min||0)+'" step="any"'+aria+'><span>'+esc(d.unit||'')+'</span></div>'+hint;
    else if(d.type==='date') ctl=head+'<input class="input" id="'+id+'" name="'+k+'" type="date" min="1930-01-01" max="'+new Date().toISOString().slice(0,10)+'"'+aria+'>'+hint;
    else ctl=head+'<input class="input" id="'+id+'" name="'+k+'" type="'+(d.type||'text')+'"'+(d.inputmode?' inputmode="'+d.inputmode+'"':'')+(d.auto?' autocomplete="'+d.auto+'"':'')+aria+'>'+hint;
  }
  var rv='';
  if(d.reveal){
    var r=d.reveal;
    rv='<div class="reveal" hidden data-src="'+k+'" data-when=\''+esc(JSON.stringify(r.when||[]))+'\' data-not=\''+esc(JSON.stringify(r.not||[]))+'\'><label for="f_'+r.k+'">'+esc(r.label)+'</label><input class="input" id="f_'+r.k+'" name="'+r.k+'" type="text"></div>';
  }
  return '<div class="field" data-k="'+k+'" data-req="'+(req?1:0)+'">'+ctl+rv+'<div class="gate" hidden></div><div class="err" id="e_'+k+'" role="alert"></div></div>';
}
function formHTML(){
  var steps=F.steps||[], multi=steps.length>1;
  var st=multi?'<ol class="stepper" aria-label="مراحل الاستمارة">'+steps.map(function(s,i){return '<li data-i="'+i+'"><span>'+esc(s.title||('الخطوة '+(i+1)))+'</span>'+(s.desc?'<small>'+esc(s.desc)+'</small>':'')+'</li>'}).join('')+'</ol>':'';
  var body=steps.map(function(s,i){
    return '<div class="step" data-step="'+i+'"'+(i?' hidden':'')+'>'+(!multi&&s.title?'':'')+(s.fields||[]).map(function(f){return f.h?'<div class="grp">'+esc(f.h)+'</div>':fieldHTML(f)}).join('')+'</div>';
  }).join('');
  var consent='<label class="consent" data-k="consent"><input type="checkbox" name="consent" id="f_consent"><span>'+esc(F.consent||'أُقرّ بصحة ما أدخلتُ من بيانات، وأوافق على استخدامها لأغراض التوظيف فقط.')+'</span></label><div class="err" id="e_consent" role="alert"></div>';
  return '<div class="form-card"><h2>'+esc(F.title||'استمارة التقدّم')+'</h2>'+(F.lead?'<p class="lead">'+esc(F.lead)+'</p>':'')
   +'<div id="dupe" class="notice" hidden></div>'+st
   +'<form id="jf" novalidate><div class="hp" aria-hidden="true"><label>الموقع<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>'+body+consent
   +'<div class="blocked" id="blocked" hidden aria-live="polite"></div>'
   +'<div class="actions"><button type="button" class="btn btn-line" id="back" hidden>السابق</button><button type="button" class="btn btn-main" id="next">'+(multi?'التالي':'إرسال الطلب')+'</button></div></form>'
   +'<div class="success" id="ok" hidden><div class="tick"><svg viewBox="0 0 24 24" fill="none" stroke="#1C463C" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></div><h3>وصلَنا طلبُك</h3><p>'+esc(F.success||'سيصلك الردّ خلال أسبوعين، قُبلتَ أو لم تُقبَل.')+'</p><p id="refline"></p><div class="demo" id="demo" hidden>وضع التجربة: لم يُرسَل شيء لأن عنوان الاستلام لم يُضبط بعد.</div></div></div>';
}

/* ---------------- السلوك ---------------- */
function init(){
  var form=document.getElementById('jf'); if(!form) return;
  var steps=[].slice.call(form.querySelectorAll('.step')), cur=0, multi=steps.length>1;
  var fields={}; (F.steps||[]).forEach(function(s){(s.fields||[]).forEach(function(f){if(f.k)fields[f.k]=f})});
  var btnN=document.getElementById('next'), btnB=document.getElementById('back'), blocked=document.getElementById('blocked');

  function val(k){var el=form.elements[k]; if(!el) return ''; if(el.type==='file') return el.files; if(el.type==='checkbox') return el.checked; return (el.value||'').trim()}
  function age(s){if(!s) return null; var d=new Date(s), n=new Date(); if(isNaN(d)) return null; var a=n.getFullYear()-d.getFullYear(); var m=n.getMonth()-d.getMonth(); if(m<0||(m===0&&n.getDate()<d.getDate())) a--; return a}
  function wrap(k){return form.querySelector('.field[data-k="'+k+'"]')}
  function setErr(k,msg){var w=wrap(k); if(!w) return; w.classList.toggle('bad',!!msg); var e=w.querySelector('.err'); e.textContent=msg||''}
  function hiddenField(k){var w=wrap(k); return !w || !!w.closest('[hidden]')}

  function gateFail(k){
    var g=fields[k]&&fields[k].gate; if(!g) return '';
    var v=val(k); if(!v) return '';
    if(g.eq && v!==g.eq) return g.msg||'لا تنطبق عليك شروط هذه الوظيفة.';
    if(g.minAge){var a=age(v); if(a!==null && a<g.minAge) return g.msg||('يُشترط ألّا يقلّ العمر عن '+g.minAge+' سنة.')}
    return '';
  }
  function refreshGates(){
    var msgs=[];
    Object.keys(fields).forEach(function(k){
      if(!fields[k].gate) return;
      var m=gateFail(k), w=wrap(k), g=w.querySelector('.gate');
      g.hidden=!m; g.textContent=m||''; if(m) msgs.push(m);
    });
    if(msgs.length){blocked.hidden=false;blocked.textContent='لا يمكن إرسال الطلب حاليًّا: '+msgs[0]+' إن كان اختيارك خاطئًا فعدِّله.'}
    else blocked.hidden=true;
    return msgs.length;
  }
  function refreshReveals(){
    [].forEach.call(form.querySelectorAll('.reveal'),function(r){
      var v=val(r.getAttribute('data-src')), when=JSON.parse(r.getAttribute('data-when')), not=JSON.parse(r.getAttribute('data-not'));
      var show = when.length ? when.indexOf(v)>-1 : (v && not.indexOf(v)===-1);
      r.hidden=!show;
    });
  }
  function validateField(k){
    if(hiddenField(k)) {setErr(k,'');return true}
    var f=fields[k], d=fdef(f), v=val(k), msg='';
    var empty = d.type==='file' ? (!v||!v.length) : !v;
    if(f.req && empty) msg = d.type==='radio'||d.type==='select' ? 'اختر إجابة.' : d.type==='file' ? 'أرفق الملف المطلوب.' : 'هذا الحقل مطلوب.';
    else if(!empty){
      if(d.type==='file'){
        var max=(F.maxMB||5)*1048576, ok=(d.accept||'').split(',').map(function(x){return x.trim().toLowerCase()}).filter(Boolean);
        for(var i=0;i<v.length;i++){
          var nm=v[i].name.toLowerCase(), ex=nm.slice(nm.lastIndexOf('.'));
          if(ok.length && ok.indexOf(ex)===-1){msg='صيغة الملف غير مقبولة ('+ok.join('، ')+').';break}
          if(v[i].size>max){msg='حجم الملف أكبر من '+(F.maxMB||5)+' ميجابايت.';break}
        }
      } else if(d.pattern && !new RegExp(d.pattern).test(v.replace(/[٠-٩]/g,function(c){return c.charCodeAt(0)-1632}))) msg=d.patternMsg||'القيمة غير صحيحة.';
      else if(d.type==='url' && !/^https?:\/\/.+\..+/i.test(v)) msg='أدخل رابطًا يبدأ بـ https://';
      else if(d.type==='number' && (isNaN(+v)||+v<0)) msg='أدخل رقمًا صحيحًا.';
    }
    setErr(k,msg); return !msg;
  }
  function validateStep(i){
    var bad=null;
    [].forEach.call(steps[i].querySelectorAll('.field'),function(w){var k=w.getAttribute('data-k'); if(!validateField(k)&&!bad) bad=k});
    if(i===steps.length-1){
      var ok=form.elements.consent.checked; setErr2('consent',ok?'':'يلزم الإقرار لإرسال الطلب.'); if(!ok&&!bad) bad='consent';
    }
    if(bad){var w=bad==='consent'?form.querySelector('.consent'):wrap(bad), el=w&&w.querySelector('input,select,textarea'); if(el){el.focus({preventScroll:true});el.scrollIntoView({block:'center',behavior:'smooth'})}}
    return !bad;
  }
  function setErr2(k,msg){document.getElementById('e_'+k).textContent=msg}

  function show(i,scroll){
    cur=i; steps.forEach(function(s,j){s.hidden=j!==i});
    [].forEach.call(document.querySelectorAll('.stepper li'),function(li,j){li.className=j<i?'done':j===i?'cur':''});
    btnB.hidden=i===0; btnN.textContent = i===steps.length-1 ? 'إرسال الطلب' : 'التالي';
    var consent=form.querySelector('.consent'), ce=document.getElementById('e_consent');
    consent.hidden=ce.hidden=i!==steps.length-1;
    /* التمرير إلى بداية الاستمارة عند تغيير الخطوة فقط، لا عند أول عرض للصفحة */
    if(multi&&scroll){var top=document.getElementById('apply');top.scrollIntoView({behavior:'smooth',block:'start'})}
  }
  btnB.addEventListener('click',function(){show(cur-1,true)});
  btnN.addEventListener('click',function(){
    if(!validateStep(cur)) return;
    if(refreshGates()) {blocked.scrollIntoView({block:'center',behavior:'smooth'});return}
    if(cur<steps.length-1) return show(cur+1,true);
    send();
  });

  /* حفظ المسودة محليًّا */
  function save(){safe(function(){var o={};[].forEach.call(form.elements,function(el){if(!el.name||el.type==='file'||el.name==='website'||el.name==='consent')return;if(el.type==='radio'){if(el.checked)o[el.name]=el.value}else o[el.name]=el.value});localStorage.setItem(STORE,JSON.stringify(o))})}
  function restore(){safe(function(){var o=JSON.parse(localStorage.getItem(STORE)||'null'); if(!o) return;
    Object.keys(o).forEach(function(k){var el=form.elements[k]; if(!el||!o[k]) return; if(el.length&&el[0]&&el[0].type==='radio'){[].forEach.call(el,function(r){r.checked=r.value===o[k]})} else el.value=o[k]})})}
  form.addEventListener('input',function(e){
    var t=e.target; if(t.name&&fields[t.name]) setErr(t.name,'');
    if(t.tagName==='TEXTAREA'){var c=form.querySelector('[data-cnt="'+t.name+'"]'); if(c) c.textContent=t.value.length+' / '+t.maxLength}
    refreshReveals(); refreshGates(); save();
  });
  form.addEventListener('change',function(e){var t=e.target; if(t.name&&fields[t.name]) validateField(t.name); refreshReveals(); refreshGates()});
  form.addEventListener('keydown',function(e){if(e.key==='Enter'&&e.target.tagName!=='TEXTAREA'){e.preventDefault()}});

  function send(){
    if(form.elements.website.value){return done()} /* فخّ الآلات: نُظهر نجاحًا شكليًّا دون إرسال */
    var fd=new FormData(form); fd.delete('website'); fd.append('job_id',C.id); fd.append('job_title',C.title); fd.append('tier',C.tier);
    /* PHP لا يقرأ من الاسم المكرَّر إلا آخر ملف، فنرسل الملفات المتعددة بصيغة name[] */
    [].forEach.call(form.querySelectorAll('input[type=file][multiple]'),function(inp){fd.delete(inp.name);[].forEach.call(inp.files,function(f){fd.append(inp.name+'[]',f)})});
    btnN.disabled=true; btnN.textContent='جارٍ الإرسال…';
    if(!F.endpoint){ setTimeout(function(){done(null,true)},500); return }
    fetch(F.endpoint,{method:'POST',body:fd}).then(function(r){if(!r.ok) throw 0; return r.json().catch(function(){return {}})})
      .then(function(j){done(j&&j.ref)}).catch(function(){btnN.disabled=false;btnN.textContent='إعادة المحاولة';blocked.hidden=false;blocked.textContent='تعذّر الإرسال الآن. تأكد من الاتصال ثم أعد المحاولة، ولن تضيع بياناتك.'});
  }
  function done(ref,demo){
    safe(function(){localStorage.setItem(DONE,new Date().toISOString());localStorage.removeItem(STORE)});
    form.hidden=true; var s=document.getElementById('ok'); s.hidden=false;
    if(ref) document.getElementById('refline').textContent='رقم طلبك: '+ref;
    if(demo) document.getElementById('demo').hidden=false;
    document.querySelector('.stepper')&&(document.querySelector('.stepper').hidden=true);
    s.scrollIntoView({block:'center',behavior:'smooth'});
  }

  var prev=safe(function(){return localStorage.getItem(DONE)});
  if(prev){var d=document.getElementById('dupe'); d.hidden=false; d.textContent='سبق أن أرسلتَ طلبًا على هذه الوظيفة من هذا المتصفح. لا حاجة لإعادة الإرسال، ويمكنك ذلك إن أردت تصحيح بياناتك.'}
  restore(); refreshReveals(); refreshGates(); show(0);
  [].forEach.call(form.querySelectorAll('textarea'),function(t){var c=form.querySelector('[data-cnt="'+t.name+'"]'); if(c) c.textContent=t.value.length+' / '+t.maxLength});
  /* شريط التقديم في الجوال يختفي عند ظهور الاستمارة */
  if('IntersectionObserver' in window){var bar=document.getElementById('ctabar'); var vis={};var io=new IntersectionObserver(function(en){en.forEach(function(x){vis[x.target.id||'hero']=x.isIntersecting});bar.classList.toggle('hide',!!(vis.apply||vis.hero))});io.observe(document.getElementById('apply'));var hr=document.querySelector('.hero');if(hr)io.observe(hr)}
}

document.title=C.title+' — '+(C.org||'مجموعة العيسري');
app.innerHTML=view(); init();
})();
