const fs = require('fs');
const path = require('path');

function findJson(dir) {
    let files = fs.readdirSync(dir);
    if (files.includes('json')) {
        let jsonFiles = fs.readdirSync(path.join(dir, 'json'));
        if (jsonFiles.length > 0) return path.join(dir, 'json', jsonFiles[0]);
    }
    for (let f of files) {
        let fullPath = path.join(dir, f);
        if (fs.statSync(fullPath).isDirectory()) {
            let res = findJson(fullPath);
            if (res) return res;
        }
    }
    return null;
}

let hiringJsonPath = findJson('C:/xampp/htdocs/Artera/temp_compare/hiring');
let workingJsonPath = findJson('C:/xampp/htdocs/Artera/temp_compare/working');

if (!hiringJsonPath || !workingJsonPath) {
    console.log("Could not find JSON files");
    process.exit(1);
}

let hiringData = JSON.parse(fs.readFileSync(hiringJsonPath, 'utf8'));
let workingData = JSON.parse(fs.readFileSync(workingJsonPath, 'utf8'));

// Compare structure of the first image layer
let hiringLayer0 = hiringData.layers.find(l => l.type === 'image');
let workingLayer0 = workingData.layers.find(l => l.type === 'image');

console.log("=== FIRST IMAGE LAYER ===");
console.log("HIRING KEYS:", Object.keys(hiringLayer0).join(', '));
console.log("WORKING KEYS:", Object.keys(workingLayer0).join(', '));

// Compare structure of the first text layer
let hiringTextLayer = hiringData.layers.find(l => l.type === 'text');
let workingTextLayer = workingData.layers.find(l => l.type === 'text');

console.log("\n=== FIRST TEXT LAYER ===");
if(hiringTextLayer) console.log("HIRING KEYS:", Object.keys(hiringTextLayer).join(', '));
if(workingTextLayer) console.log("WORKING KEYS:", Object.keys(workingTextLayer).join(', '));

// Compare top level keys
console.log("\n=== TOP LEVEL KEYS ===");
console.log("HIRING:", Object.keys(hiringData).join(', '));
console.log("WORKING:", Object.keys(workingData).join(', '));
