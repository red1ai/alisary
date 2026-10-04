/* يجمع صفحة HTML مستقلة من الإعداد — يعمل في المتصفح (الأداة) وفي Node (البناء) */
function assemble(C, CSS, CATALOG, ENGINE){
  var ls = String.fromCharCode(0x2028), ps = String.fromCharCode(0x2029);
  var json = JSON.stringify(C).replace(/</g,'\\u003c').split(ls).join('\\u2028').split(ps).join('\\u2029');
  var safeJs = function(s){return s.replace(/<\/script/gi,'<\\/script')};
  return '<!doctype html>\n<html lang="ar" dir="rtl">\n<head>\n<meta charset="utf-8">\n'
   +'<meta name="viewport" content="width=device-width,initial-scale=1">\n'
   +'<title>'+String(C.title||'وظيفة').replace(/</g,'&lt;')+'<\/title>\n'
   +'<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>\n'
   +'<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">\n'
   +'<style>\n'+CSS+'\n<\/style>\n<\/head>\n<body>\n<div id="app"></div>\n'
   +'<script>\n'+safeJs(CATALOG)+'\nwindow.CONFIG='+json+';\n<\/script>\n'
   +'<script>\n'+safeJs(ENGINE)+'\n<\/script>\n<\/body>\n<\/html>\n';
}
