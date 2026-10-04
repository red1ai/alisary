var S = null, tier = 'light';
function clone(o){return JSON.parse(JSON.stringify(o))}
function $(id){return document.getElementById(id)}
function el(tag,a,kids){var e=document.createElement(tag);a=a||{};Object.keys(a).forEach(function(k){if(k==='class')e.className=a[k];else if(k==='text')e.textContent=a[k];else if(k.slice(0,2)==='on')e.addEventListener(k.slice(2),a[k]);else if(a[k]===true)e.setAttribute(k,'');else if(a[k]!==false&&a[k]!=null)e.setAttribute(k,a[k])});(kids||[]).forEach(function(c){if(c)e.appendChild(typeof c==='string'?document.createTextNode(c):c)});return e}
function toast(m){var t=$('toast');t.textContent=m;t.classList.add('on');setTimeout(function(){t.classList.remove('on')},1800)}
var timer;function preview(){clearTimeout(timer);timer=setTimeout(function(){$('pv').srcdoc=assemble(S,CSS,CATALOG,ENGINE)},250)}

function normalize(c){
  c.blocks=c.blocks||{}; BLOCKS.forEach(function(b){c.blocks[b.k]=Object.assign({on:false},c.blocks[b.k]||{})});
  c.form=c.form||{steps:[]}; c.chips=c.chips||[]; return c;
}
function load(t){tier=t;S=normalize(clone(PRESETS[t]));renderPanel();preview()}

/* ---- تحويلات النص ---- */
function linesOf(v){return String(v||'').split('\n').map(function(x){return x.trim()}).filter(Boolean)}
function pairsToText(arr,ka,kb){return (arr||[]).map(function(r){return r[ka]+(r[kb]!==''&&r[kb]!=null?': '+r[kb]:'')}).join('\n')}
function textToPairs(t,ka,kb){return linesOf(t).map(function(l){var i=l.search(/[:：]/);return i<0?(function(){var o={};o[ka]=l;o[kb]='';return o})():(function(){var o={};o[ka]=l.slice(0,i).trim();o[kb]=l.slice(i+1).trim();return o})()})}

/* ---- اللوحة ---- */
function renderPanel(){
  var P=$('panel'); P.innerHTML='';
  /* 1 النوع */
  var types=el('div',{class:'types'});
  Object.keys(PRESET_META).forEach(function(k){var m=PRESET_META[k];
    types.appendChild(el('button',{class:'type'+(k===tier?' on':''),type:'button',onclick:function(){if(k===tier||confirm('تحميل قالب «'+m.name+'» سيستبدل تعديلاتك الحالية. متابعة؟'))load(k)}},[el('b',{text:m.name}),el('small',{text:m.desc}),el('em',{text:m.ex})]))});
  P.appendChild(el('section',{class:'sec'},[el('h2',{text:'١ — نوع الوظيفة'}),el('p',{text:'كل نوع يبدأ بقدرٍ مختلف من التفاصيل والحقول، وتعدّله كما تشاء.'}),types]));

  /* 2 الأساسيات */
  var b2=el('section',{class:'sec'},[el('h2',{text:'٢ — الأساسيات'})]);
  function txt(label,get,set,area){var i=el(area?'textarea':'input',{class:'in',type:'text'});i.value=get();i.addEventListener('input',function(){set(i.value);preview()});return el('div',{},[el('label',{class:'l',text:label}),i])}
  b2.appendChild(txt('المسمى الوظيفي',function(){return S.title},function(v){S.title=v}));
  b2.appendChild(txt('سطر تعريفي تحت المسمى',function(){return S.subtitle||''},function(v){S.subtitle=v}));
  b2.appendChild(txt('الجهة / الفرع',function(){return S.unit||''},function(v){S.unit=v}));
  b2.appendChild(txt('بيانات سريعة في الأعلى (سطر لكل بيان — «العنوان: القيمة»)',function(){return pairsToText(S.chips,'k','v')},function(v){S.chips=textToPairs(v,'k','v')},true));
  b2.appendChild(txt('آخر موعد للتقديم (اختياري)',function(){return S.deadline||''},function(v){S.deadline=v}));
  b2.appendChild(txt('معرّف الوظيفة (إنجليزي بلا مسافات)',function(){return S.id},function(v){S.id=v.replace(/[^\w-]/g,'')}));
  var dr=el('input',{type:'checkbox'});dr.checked=!!S.draft;dr.onchange=function(){S.draft=dr.checked;preview()};
  b2.appendChild(el('label',{class:'ck',style:'margin-top:10px'},[dr,'إظهار شريط «نسخة تجريبية» (أوقفه عند النشر)']));
  b2.appendChild(txt('عنوان استلام الطلبات (رابط الخادم). فارغ = وضع تجريبي لا يُرسل شيئًا',function(){return S.form.endpoint||''},function(v){S.form.endpoint=v.trim()}));
  P.appendChild(b2);

  /* 3 الأقسام */
  var b3=el('section',{class:'sec'},[el('h2',{text:'٣ — أقسام الصفحة'}),el('p',{text:'شغّل ما تحتاجه وأطفئ الباقي. الوظيفة اليسيرة تكتفي بقسمين أو ثلاثة.'})]);
  BLOCKS.forEach(function(d){
    var b=S.blocks[d.k], body=el('div',{class:'body',hidden:!b.on}), sw=el('input',{type:'checkbox','aria-label':'تفعيل '+d.t});sw.checked=!!b.on;
    sw.onchange=function(){b.on=sw.checked;body.hidden=!b.on;preview()};
    var tin=el('input',{class:'in',type:'text',placeholder:d.t});tin.value=b.title||'';tin.oninput=function(){b.title=tin.value;preview()};
    body.appendChild(el('label',{class:'l',text:'عنوان القسم'}));body.appendChild(tin);
    function area(label,get,set,hint){var a=el('textarea',{class:'in'});a.value=get();a.oninput=function(){set(a.value);preview()};body.appendChild(el('label',{class:'l',text:label}));body.appendChild(a);if(hint)body.appendChild(el('div',{class:'hint',text:hint}))}
    if(d.kind==='text') area('النص',function(){return b.text||''},function(v){b.text=v});
    if(d.kind==='lines') area('بند في كل سطر',function(){return (b.items||[]).join('\n')},function(v){b.items=linesOf(v)});
    if(d.kind==='who'){area('لا بدّ منه (سطر لكل شرط)',function(){return (b.must||[]).join('\n')},function(v){b.must=linesOf(v)});area('يُقدَّم من عنده (اختياري)',function(){return (b.prefer||[]).join('\n')},function(v){b.prefer=linesOf(v)})}
    if(d.kind==='pairs'){
      if(d.k==='branches') area('فرع في كل سطر — «الاسم: عدد المقاعد»',function(){return pairsToText(b.rows,'name','seats')},function(v){b.rows=textToPairs(v,'name','seats')},'حقل «الفرع المفضّل» في الاستمارة يأخذ هذه الأسماء تلقائيًّا.');
      else area('سطر لكل بند — «العنوان: التفصيل»',function(){return pairsToText(b.items,'k','v')},function(v){b.items=textToPairs(v,'k','v')});
    }
    if(d.kind==='values'){
      var w=el('div',{class:'row',style:'flex-wrap:wrap;margin-top:6px'});
      PILLARS.forEach(function(p){var c=el('input',{type:'checkbox'});c.checked=(b.emph||[]).indexOf(p.id)>-1;c.onchange=function(){b.emph=PILLARS.filter(function(q){return q.id===p.id?c.checked:(b.emph||[]).indexOf(q.id)>-1}).map(function(q){return q.id});preview()};w.appendChild(el('label',{class:'ck'},[c,p.n]))});
      body.appendChild(el('label',{class:'l',text:'الأعمدة التي تتأكد عليها هذه الوظيفة'}));body.appendChild(w);
      area('جملة توضيحية (اختياري)',function(){return b.note||''},function(v){b.note=v});
    }
    b3.appendChild(el('div',{class:'blk'},[el('div',{class:'hd'},[el('b',{text:d.t}),el('label',{class:'sw'},[sw,el('i')])]),body]));
  });
  P.appendChild(b3);

  /* 4 الاستمارة */
  var b4=el('section',{class:'sec'},[el('h2',{text:'٤ — حقول الاستمارة'}),el('p',{text:'كل خطوة شاشة مستقلة للمتقدّم. اجعل الحقول بقدر الحاجة؛ ما يُطلب بعد القبول لا يُطلب الآن.'})]);
  var steps=S.form.steps;
  steps.forEach(function(st,si){
    var box=el('div',{class:'step'});
    var ti=el('input',{class:'in',type:'text',placeholder:'عنوان الخطوة'});ti.value=st.title||'';ti.oninput=function(){st.title=ti.value;preview()};
    box.appendChild(el('div',{class:'row'},[ti,steps.length>1?el('button',{class:'b sm x',type:'button',text:'حذف الخطوة',onclick:function(){if(confirm('حذف الخطوة وحقولها؟')){steps.splice(si,1);renderPanel();preview()}}}):null]));
    st.fields.forEach(function(f,fi){
      var def=f.k?FIELDS[f.k]:null, r=el('div',{class:'fld'+(f.h?' h':'')});
      var nm=el('div',{class:'nm'});
      if(f.h){var hi=el('input',{class:'in',type:'text'});hi.value=f.h;hi.oninput=function(){f.h=hi.value;preview()};nm.appendChild(hi)}
      else{var li=el('input',{class:'in',type:'text',placeholder:def?def.label:f.k});li.value=f.label||'';li.title='تعديل نص السؤال (اتركه فارغًا لاستخدام النص الافتراضي)';li.oninput=function(){f.label=li.value||undefined;preview()};nm.appendChild(li);nm.appendChild(el('small',{text:def?def.group+' — '+def.label:f.k}))}
      r.appendChild(nm);
      if(f.gate) r.appendChild(el('span',{class:'gate',text:'شرط حاسم'}));
      if(!f.h){var rq=el('input',{type:'checkbox'});rq.checked=!!f.req;rq.onchange=function(){f.req=rq.checked;preview()};r.appendChild(el('label',{class:'ck'},[rq,'إلزامي']))}
      r.appendChild(el('button',{class:'b sm',type:'button','aria-label':'أعلى',text:'↑',onclick:function(){if(fi>0){var t=st.fields[fi-1];st.fields[fi-1]=st.fields[fi];st.fields[fi]=t;renderPanel();preview()}}}));
      r.appendChild(el('button',{class:'b sm',type:'button','aria-label':'أسفل',text:'↓',onclick:function(){if(fi<st.fields.length-1){var t=st.fields[fi+1];st.fields[fi+1]=st.fields[fi];st.fields[fi]=t;renderPanel();preview()}}}));
      r.appendChild(el('button',{class:'b sm x',type:'button','aria-label':'حذف',text:'×',onclick:function(){st.fields.splice(fi,1);renderPanel();preview()}}));
      box.appendChild(r);
    });
    var sel=el('select',{class:'in','aria-label':'إضافة حقل'},[el('option',{value:'',text:'+ إضافة حقل…'})]);
    var used={};st.fields.forEach(function(f){if(f.k)used[f.k]=1});
    var groups={};Object.keys(FIELDS).forEach(function(k){(groups[FIELDS[k].group]=groups[FIELDS[k].group]||[]).push(k)});
    Object.keys(groups).forEach(function(g){var og=el('optgroup',{label:g});groups[g].forEach(function(k){og.appendChild(el('option',{value:k,text:FIELDS[k].label}))});sel.appendChild(og)});
    sel.appendChild(el('option',{value:'__h',text:'— عنوان فرعي —'}));
    sel.onchange=function(){var v=sel.value;if(!v)return;if(v==='__h')st.fields.push({h:'عنوان جديد'});else st.fields.push({k:v,req:false});renderPanel();preview()};
    box.appendChild(el('div',{style:'margin-top:8px'},[sel]));
    b4.appendChild(box);
  });
  b4.appendChild(el('button',{class:'b sm',type:'button',text:'+ خطوة جديدة',onclick:function(){steps.push({title:'خطوة جديدة',fields:[]});renderPanel();preview()}}));
  P.appendChild(b4);
  P.appendChild(el('section',{class:'sec'},[el('h2',{text:'٥ — الاستيراد'}),el('p',{text:'للصق إعداد محفوظ سابقًا (JSON).'}),(function(){var a=el('textarea',{class:'in',placeholder:'{ ... }'}),bt=el('button',{class:'b sm',type:'button',text:'استيراد',onclick:function(){try{var c=JSON.parse(a.value);S=normalize(c);tier=c.tier||tier;renderPanel();preview();toast('تم الاستيراد')}catch(e){toast('JSON غير صالح')}}});return el('div',{},[a,el('div',{style:'margin-top:6px'},[bt])])})()]));
}

$('dev').addEventListener('click',function(e){var b=e.target.closest('button');if(!b)return;[].forEach.call($('dev').children,function(x){x.classList.toggle('on',x===b)});$('frame').style.maxWidth=b.getAttribute('data-w')==='100%'?'none':b.getAttribute('data-w')+'px'});
$('bDl').onclick=function(){var s=clone(S);s.draft=!!S.draft;var blob=new Blob([assemble(s,CSS,CATALOG,ENGINE)],{type:'text/html;charset=utf-8'});var a=document.createElement('a');a.href=URL.createObjectURL(blob);a.download=(S.id||'job')+'.html';document.body.appendChild(a);a.click();a.remove();toast('تم تنزيل الصفحة')};
$('bJson').onclick=function(){var t=JSON.stringify(S,null,2);(navigator.clipboard?navigator.clipboard.writeText(t):Promise.reject()).then(function(){toast('نُسخ الإعداد')},function(){var a=document.createElement('textarea');a.value=t;document.body.appendChild(a);a.select();try{document.execCommand('copy');toast('نُسخ الإعداد')}catch(e){toast('تعذّر النسخ')}a.remove()})};
load('light');
