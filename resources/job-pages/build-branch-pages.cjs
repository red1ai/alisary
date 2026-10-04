/*
 * يبني الصفحات المستقلة للوظائف الأربع من out/branch-pages.json بقالب الصفحات الحالي.
 *   node build-branch-pages.cjs [--dir=out/branch-pages]
 * التقديم معطّل (applyClosed) إلى أن تتوفر جهة استلام فعلية؛ لا يُعرض زر تقديم ولا نموذج.
 */
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const here = __dirname;
const read = (file) => fs.readFileSync(path.join(here, file), 'utf8');
const arg = (name) => (process.argv.find((a) => a.startsWith(`--${name}=`)) || '').slice(name.length + 3);

const context = vm.createContext({});
vm.runInContext(`${read('catalog.js')}\n${read('assemble.js')}\nthis.assemble = assemble;`, context);

const pages = JSON.parse(read('out/branch-pages.json'));
const dir = path.resolve(here, arg('dir') || 'out/branch-pages');
fs.mkdirSync(dir, { recursive: true });

for (const [slug, config] of Object.entries(pages)) {
    fs.writeFileSync(path.join(dir, `${slug}.html`), context.assemble(config, read('style.css'), read('catalog.js'), read('engine.js')));
    console.log(path.join(dir, `${slug}.html`));
}
