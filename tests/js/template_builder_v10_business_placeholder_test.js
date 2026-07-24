const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const sourcePath = path.resolve(__dirname, '../../assets/js/template_builder.js');
const source = fs.readFileSync(sourcePath, 'utf8');

assert.match(
    source,
    /function refreshV10PlaceholderBindingOptions\(editorCanvas, selectedValue\)/,
    'V10 placeholder option refresh must receive the block-scoped canvas explicitly'
);
assert.match(
    source,
    /const select = document\.getElementById\('prop-placeholder-bind'\)/,
    'V10 placeholder option refresh must not close over a block-scoped input reference'
);
assert.doesNotMatch(
    source,
    /function refreshV10PlaceholderBindingOptions\([^)]*\)\s*\{\s*const select = inputPlaceholderBind/,
    'V10 placeholder option refresh cannot reference inputPlaceholderBind outside its try-block scope'
);
const start = source.indexOf('const V10_BUSINESS_PLACEHOLDER_FIELDS');
const end = source.indexOf('function makeV10LayerId', start);

assert(start >= 0 && end > start, 'V10 business placeholder helpers must exist');

const sandbox = {
    isV10RenderVersion(version) {
        return Number(version) >= 10;
    }
};
vm.createContext(sandbox);
vm.runInContext(source.slice(start, end), sandbox);

assert.deepEqual(
    JSON.parse(JSON.stringify(sandbox.parseV10BusinessPlaceholder('phone_1'))),
    { field: 'phone', index: 0, key: 'phone_1' }
);
assert.deepEqual(
    JSON.parse(JSON.stringify(sandbox.parseV10BusinessPlaceholder('mobile_number_2'))),
    { field: 'phone', index: 1, key: 'phone_2' }
);
assert.deepEqual(
    JSON.parse(JSON.stringify(sandbox.parseV10BusinessPlaceholder('email'))),
    { field: 'email', index: 0, key: 'email_1' }
);
assert.equal(
    sandbox.nextV10BusinessPlaceholderKey([
        { placeholderKey: 'email_1' },
        { placeholderKey: 'email_2' }
    ], 'email'),
    'email_3'
);
assert.equal(
    sandbox.nextV10BusinessPlaceholderKey([
        { placeholderKey: 'phone_1' },
        { placeholderKey: 'phone_3' }
    ], 'phone'),
    'phone_2',
    'duplicate binding should fill the first available indexed slot'
);

const exported = {};
sandbox.applyV10BusinessBinding(exported, {
    placeholderKey: 'website_2'
}, 10);
assert.deepEqual(exported, {
    placeholder_key: 'website_2',
    ai_field: 'website_2',
    business_field: 'website',
    business_field_index: 1
});

const legacyExport = {};
sandbox.applyV10BusinessBinding(legacyExport, {
    placeholderKey: 'website_2'
}, 9);
assert.deepEqual(legacyExport, {}, 'V1-V9 placeholder payloads must remain unchanged');

const legacyExportStart = source.indexOf('function exportLegacyJson');
const legacyExportEnd = source.indexOf('function validateBeforePublish', legacyExportStart);
const legacyExportSource = source.slice(legacyExportStart, legacyExportEnd);
assert.match(
    legacyExportSource,
    /applyV10BusinessBinding\(textLayer,\s*obj,\s*targetRenderVer\)/,
    'legacy V10 payload must persist indexed business binding'
);
assert.match(
    source,
    /customType:\s+v10BusinessBinding\s*\?\s*'placeholder'\s*:\s*'text'/,
    'V10 reload must restore placeholder identity'
);

console.log('V10 indexed business placeholder contract passed');
