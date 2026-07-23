/*
 * V10 icon round-trip regression checks.
 *
 * Run with: node tests/js/template_builder_v10_icon_contract_test.js
 * This deliberately exercises the helpers from the shipped editor source so
 * an SVG that relies on SVG's default black fill cannot regress unnoticed.
 */
const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const sourcePath = path.resolve(__dirname, '../../assets/js/template_builder.js');
const source = fs.readFileSync(sourcePath, 'utf8');

function extractFunction(name, nextName) {
    const start = source.indexOf(`function ${name}(`);
    const end = source.indexOf(`function ${nextName}(`, start);
    assert(start >= 0 && end > start, `${name} helper must exist in the editor source`);
    return source.slice(start, end);
}

const helpers = [
    extractFunction('normaliseV10IconSvg', 'iconColorFromObject'),
    extractFunction('iconColorFromObject', 'applyV10IconColor'),
    extractFunction('applyV10IconColor', 'v10IconInitialScale'),
    extractFunction('v10IconInitialScale', 'serialiseV10Icon')
].join('\n');
const sandbox = {};
vm.createContext(sandbox);
vm.runInContext(helpers, sandbox);

const normalized = sandbox.normaliseV10IconSvg('<svg viewBox="0 0 24 24"><path d="M0 0h24v24z"/></svg>');
assert.match(normalized, /<svg[^>]*fill="currentColor"/i, 'default-fill SVG must receive a colour source');

function node(fill, stroke, children = []) {
    return {
        fill,
        stroke,
        dirty: false,
        set(key, value) { this[key] = value; },
        getObjects: children.length ? () => children : undefined
    };
}

const solidChild = node(undefined, undefined);
const outlinedChild = node('none', '#111111');
const transparentChild = node('transparent', 'none');
const group = node(undefined, undefined, [solidChild, outlinedChild, transparentChild]);
sandbox.applyV10IconColor(group, '#123abc');
assert.equal(solidChild.fill, '#123abc', 'implicit SVG fill must be recoloured');
assert.equal(outlinedChild.fill, 'none', 'hollow SVG regions must remain hollow');
assert.equal(outlinedChild.stroke, '#123abc', 'explicit icon strokes must be recoloured');
assert.equal(transparentChild.fill, 'transparent', 'transparent SVG regions must remain transparent');
assert.equal(group._originalColor, '#123abc', 'canonical icon colour must be retained');

assert.equal(sandbox.v10IconInitialScale({ width: 24, height: 24 }, 48), 2, '24px icon keeps the established 48px default');
assert.equal(sandbox.v10IconInitialScale({ width: 512, height: 512 }, 48), 48 / 512, 'large-viewBox icon gets the same displayed size');
assert.equal(sandbox.v10IconInitialScale({ width: 16, height: 32 }, 48), 1.5, 'non-square icons preserve their aspect ratio');

assert.match(source, /requestedW[\s\S]{0,600}?requestedW\s*\/\s*originalW/, 'V10 reload must use saved icon width');
assert.doesNotMatch(source, /scaleX:\s*Math\.max\(1,\s*Number\(layer\.w/, 'V10 reload must allow an icon to scale down');
assert.match(source, /canvas\.moveTo\(object, index\)/, 'async V10 SVGs must be restored by exact z-order');

console.log('V10 icon round-trip contract passed');
