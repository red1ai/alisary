/*
 * يبني أداة الصفحات (builder.html) وأمثلة الصفحات الأربع من المصادر في هذا المجلد.
 *
 *   node build.cjs
 *       يكتب في out/: builder.html وjob-<النوع>.html لكل نوع، وملف <المعرّف>.json لكل إعداد.
 *
 *   node build.cjs --publish=executive --endpoint=/api/job-pages/apply --dir=../../public/job-pages
 *       يكتب صفحة نوع واحد مستقلة (وعنوان الاستلام مضبوط) في المجلد المحدد.
 *
 * لا تعدّل ملفات out/ يدويًّا؛ عدّل المصادر ثم أعد البناء.
 */
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const here = __dirname;
const read = (file) => fs.readFileSync(path.join(here, file), 'utf8');
const embed = (text) => JSON.stringify(text).replace(/<\//g, '<\\/');
const arg = (name) => (process.argv.find((a) => a.startsWith(`--${name}=`)) || '').slice(name.length + 3);

const CSS = read('style.css');
const CATALOG = read('catalog.js');
const ENGINE = read('engine.js');
const PRESETS_SRC = read('presets.js');
const ASSEMBLE_SRC = read('assemble.js');

const context = vm.createContext({});
vm.runInContext(`${CATALOG}\n${PRESETS_SRC}\n${ASSEMBLE_SRC}\nthis.PRESETS = PRESETS; this.assemble = assemble;`, context);
const { PRESETS, assemble } = context;

const page = (config) => assemble(config, CSS, CATALOG, ENGINE);

const publish = arg('publish');
if (publish) {
    if (!PRESETS[publish]) {
        throw new Error(`Unknown preset "${publish}". Use: ${Object.keys(PRESETS).join(', ')}`);
    }

    const config = JSON.parse(JSON.stringify(PRESETS[publish]));
    config.form.endpoint = arg('endpoint');
    const dir = path.resolve(here, arg('dir') || 'out');
    fs.mkdirSync(dir, { recursive: true });
    fs.writeFileSync(path.join(dir, `${config.id}.html`), page(config));
    console.log(`${path.join(dir, `${config.id}.html`)} (endpoint: ${config.form.endpoint || 'demo'})`);
    process.exit(0);
}

const out = path.join(here, 'out');
fs.mkdirSync(out, { recursive: true });

const builder = read('builder.template.html')
    .replace('/*{{CSS}}*/', () => embed(CSS))
    .replace('/*{{CATALOG}}*/', () => embed(CATALOG))
    .replace('/*{{ENGINE}}*/', () => embed(ENGINE))
    .replace('/*{{CATALOG_SRC}}*/', () => CATALOG)
    .replace('/*{{PRESETS_SRC}}*/', () => PRESETS_SRC)
    .replace('/*{{ASSEMBLE_SRC}}*/', () => ASSEMBLE_SRC)
    .replace('/*{{BUILDER_UI}}*/', () => read('builder-ui.js'));
fs.writeFileSync(path.join(out, 'builder.html'), builder);

for (const [tier, config] of Object.entries(PRESETS)) {
    fs.writeFileSync(path.join(out, `job-${tier}.html`), page(config));
    fs.writeFileSync(path.join(out, `${config.id}.json`), `${JSON.stringify(config, null, 2)}\n`);
}

console.log(`built: ${fs.readdirSync(out).join(', ')}`);
