/*
 * V10 pointer interaction regression checks.
 *
 * Run with: node tests/js/template_builder_v10_pointer_contract_test.js
 */
const assert = require('assert');
const fs = require('fs');
const path = require('path');

const sourcePath = path.resolve(__dirname, '../../assets/js/template_builder.js');
const source = fs.readFileSync(sourcePath, 'utf8');

assert.match(
    source,
    /const legacyEmptyCanvasPan = !usesV10PointerContract\(\)[\s\S]{0,120}?!opt\.target/,
    'V10 ordinary empty-canvas clicks must not be consumed by the pan handler'
);
assert.match(
    source,
    /if \(isText && canvas\.getActiveObject\(\) !== target\)[\s\S]{0,180}?canvas\.setActiveObject\(target\)/,
    'V10 text must become active on the first pointer down'
);
assert.match(
    source,
    /if \(canvas\._currentTransform[\s\S]{0,180}?canvas\._onMouseUp\(e\)/,
    'a swallowed release must finalize Fabric’s pending transform'
);
assert.match(
    source,
    /window\.addEventListener\('mouseup', finishV10CanvasInteraction\)/,
    'the release guard must run even when pointer-up occurs outside the canvas'
);
assert.match(
    source,
    /window\.addEventListener\('touchend', finishV10CanvasInteraction\)/,
    'touch release must use the same V10 interaction contract'
);

console.log('V10 pointer interaction contract passed');
