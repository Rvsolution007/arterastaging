/**
 * ExportAllLayers.jsx
 * 
 * Exports every visible layer in the active PSD as an individual transparent PNG.
 * Intended for AI dataset generation.
 * Compatible with Photoshop CC 2020-2025.
 */

#target photoshop

app.bringToFront();

function main() {
    if (app.documents.length === 0) {
        alert("No active document.");
        return;
    }

    var doc = app.activeDocument;
    var docPath;
    try {
        docPath = doc.path;
    } catch (e) {
        alert("Please save your PSD file first before exporting.");
        return;
    }

    var exportSelected = false;

    // --- UI Setup ---
    var win = new Window("dialog", "Export All Layers");
    win.alignChildren = "fill";
    var pnl = win.add("panel", undefined, "Export Mode");
    pnl.alignChildren = "left";
    var rbAll = pnl.add("radiobutton", undefined, "Export All Visible Layers");
    var rbSel = pnl.add("radiobutton", undefined, "Export Selected Layers Only");
    rbAll.value = true;

    var grpBtn = win.add("group");
    grpBtn.alignment = "center";
    var btnOk = grpBtn.add("button", undefined, "Export", {name: "ok"});
    var btnCancel = grpBtn.add("button", undefined, "Cancel", {name: "cancel"});

    btnOk.onClick = function() {
        exportSelected = rbSel.value;
        win.close(1);
    };
    btnCancel.onClick = function() {
        win.close(0);
    };

    if (win.show() === 0) return;

    // --- Folder & Log Setup ---
    var exportFolder = new Folder(docPath + "/ExportedLayers");
    if (!exportFolder.exists) {
        exportFolder.create();
    }

    var logFile = new File(exportFolder.fsName + "/ExportLog.txt");
    logFile.open("w");
    logFile.writeln("Export Log");
    logFile.writeln("------------------");

    var origUnits = app.preferences.rulerUnits;
    var origDialogs = app.displayDialogs;
    app.preferences.rulerUnits = Units.PIXELS;
    app.displayDialogs = DialogModes.NO;

    var progressWin = new Window("palette", "Exporting Layers");
    progressWin.alignChildren = "fill";
    var txtLayer = progressWin.add("statictext", undefined, "Collecting layers...");
    txtLayer.characters = 40;
    var progressBar = progressWin.add("progressbar", undefined, 0, 100);
    var btnStop = progressWin.add("button", undefined, "Cancel");
    var cancelExport = false;
    btnStop.onClick = function() { cancelExport = true; };
    progressWin.show();

    var savedState = doc.activeHistoryState;
    var visibilityMap = {};

    try {
        var selectedIDs = [];
        if (exportSelected) {
            selectedIDs = getSelectedLayerIDs();
        }

        var collectedLayers = [];
        collectLayers(doc, collectedLayers, selectedIDs, exportSelected ? "SELECTED" : "ALL");

        var exportableLayers = [];
        for (var i = 0; i < collectedLayers.length; i++) {
            if (isExportableLayer(collectedLayers[i].layer)) {
                exportableLayers.push(collectedLayers[i]);
            }
        }

        var total = exportableLayers.length;
        progressBar.maxvalue = total;

        if (total === 0) {
            alert("No exportable layers found.");
            progressWin.close();
            return;
        }

        storeVisibility(doc, visibilityMap);
        hideAllLayers(doc);

        var nameCounts = {};
        var manifest = [];

        for (var i = 0; i < total; i++) {
            if (cancelExport) {
                logFile.writeln("Export canceled by user.");
                break;
            }

            var item = exportableLayers[i];
            var layer = item.layer;

            txtLayer.text = "Exporting: " + layer.name + " (" + (i+1) + "/" + total + ")";
            progressBar.value = i + 1;
            progressWin.update();

            var tempDoc = null;
            try {
                layer.visible = true;
                showParents(layer);

                tempDoc = doc.duplicate("tempDoc");
                app.activeDocument = tempDoc;

                removeHiddenLayersFast();
                unlockAndUngroupRemaining(tempDoc);

                try {
                    tempDoc.trim(TrimType.TRANSPARENT, true, true, true, true);
                } catch(e) {
                    tempDoc.close(SaveOptions.DONOTSAVECHANGES);
                    app.activeDocument = doc;
                    layer.visible = false;
                    hideParents(layer);
                    logFile.writeln("Layer:\n" + layer.name + "\nStatus:\nSkipped\nReason:\nEmpty/Transparent after trim\n------------------");
                    continue;
                }

                if (tempDoc.width.value === 0 || tempDoc.height.value === 0) {
                    tempDoc.close(SaveOptions.DONOTSAVECHANGES);
                    app.activeDocument = doc;
                    layer.visible = false;
                    hideParents(layer);
                    continue;
                }

                var safeName = sanitizeFileName(layer.name);
                var uniqueName = getUniqueFileName(safeName, nameCounts);
                var outFile = new File(exportFolder.fsName + "/" + uniqueName + ".png");

                exportPNG(tempDoc, outFile);

                manifest.push({
                    originalName: layer.name,
                    fileName: uniqueName + ".png"
                });

                logFile.writeln("Layer:\n" + layer.name + "\nStatus:\nSuccess\nFile:\n" + uniqueName + ".png\n------------------");

                tempDoc.close(SaveOptions.DONOTSAVECHANGES);
                app.activeDocument = doc;

                layer.visible = false;
                hideParents(layer);

            } catch (err) {
                if (tempDoc && app.documents.length > 0 && app.activeDocument === tempDoc) {
                    tempDoc.close(SaveOptions.DONOTSAVECHANGES);
                }
                app.activeDocument = doc;
                
                layer.visible = false;
                hideParents(layer);

                logFile.writeln("Layer:\n" + layer.name + "\nStatus:\nFailed\nReason:\n" + err.message + "\n------------------");
            }

            if (i > 0 && i % 50 === 0) {
                app.purge(PurgeTarget.ALLCACHES);
            }
        }

        doc.activeHistoryState = savedState;
        restoreVisibility(doc, visibilityMap);

        var manifestFile = new File(exportFolder.fsName + "/manifest.json");
        manifestFile.open("w");
        var jsonStr = "[\n";
        for (var m = 0; m < manifest.length; m++) {
            jsonStr += '  {\n    "originalName": "' + escapeJSON(manifest[m].originalName) + '",\n    "fileName": "' + escapeJSON(manifest[m].fileName) + '"\n  }' + (m < manifest.length - 1 ? "," : "") + '\n';
        }
        jsonStr += "\n]";
        manifestFile.write(jsonStr);
        manifestFile.close();

        logFile.close();
        progressWin.close();

        if (!cancelExport) {
            alert("Export complete!\nSuccessfully exported " + manifest.length + " layers.");
        } else {
            alert("Export canceled. Check log for details.");
        }

    } catch(e) {
        doc.activeHistoryState = savedState;
        restoreVisibility(doc, visibilityMap);
        alert("Fatal Error: " + e.message);
    } finally {
        app.preferences.rulerUnits = origUnits;
        app.displayDialogs = origDialogs;
    }
}

// --- Helper Functions ---

function getSelectedLayerIDs() {
    var selectedIDs = [];
    var ref = new ActionReference();
    ref.putProperty(charIDToTypeID("Prpr"), stringIDToTypeID("targetLayers"));
    ref.putEnumerated(charIDToTypeID("Dcmn"), charIDToTypeID("Ordn"), charIDToTypeID("Trgt"));
    try {
        var docDesc = executeActionGet(ref);
        if (docDesc.hasKey(stringIDToTypeID("targetLayers"))) {
            var targetLayers = docDesc.getList(stringIDToTypeID("targetLayers"));
            for (var i = 0; i < targetLayers.count; i++) {
                var layerIndex = targetLayers.getReference(i).getIndex();
                var ref2 = new ActionReference();
                ref2.putIndex(charIDToTypeID("Lyr "), layerIndex);
                var layerDesc = executeActionGet(ref2);
                var layerID = layerDesc.getInteger(stringIDToTypeID("layerID"));
                selectedIDs.push(layerID);
            }
        }
    } catch(e) {}
    
    if (selectedIDs.length === 0) {
        selectedIDs.push(app.activeDocument.activeLayer.id);
    }
    return selectedIDs;
}

function collectLayers(container, collected, selectedIDs, mode) {
    var layers = container.layers;
    for (var i = 0; i < layers.length; i++) {
        var layer = layers[i];
        if (!layer.visible) continue;
        
        if (layer.typename === "LayerSet") {
            collectLayers(layer, collected, selectedIDs, mode);
        } else {
            if (mode === "SELECTED") {
                if (indexOf(selectedIDs, layer.id) !== -1) {
                    collected.push({layer: layer});
                }
            } else {
                collected.push({layer: layer});
            }
        }
    }
}

function indexOf(arr, val) {
    for (var i=0; i<arr.length; i++) {
        if (arr[i] === val) return i;
    }
    return -1;
}

function isExportableLayer(layer) {
    if (layer.typename === "LayerSet") return false;
    return true;
}

function storeVisibility(container, map) {
    var layers = container.layers;
    for (var i = 0; i < layers.length; i++) {
        var l = layers[i];
        map[l.id] = l.visible;
        if (l.typename === "LayerSet") {
            storeVisibility(l, map);
        }
    }
}

function restoreVisibility(container, map) {
    var layers = container.layers;
    for (var i = 0; i < layers.length; i++) {
        var l = layers[i];
        if (map[l.id] !== undefined) {
            l.visible = map[l.id];
        }
        if (l.typename === "LayerSet") {
            restoreVisibility(l, map);
        }
    }
}

function hideAllLayers(container) {
    var layers = container.layers;
    for (var i = 0; i < layers.length; i++) {
        var l = layers[i];
        if (l.typename === "ArtLayer" && l.isBackgroundLayer) {
            l.isBackgroundLayer = false;
        }
        l.visible = false;
        if (l.typename === "LayerSet") {
            hideAllLayers(l);
        }
    }
}

function showParents(layer) {
    var p = layer.parent;
    while (p && p.typename === "LayerSet") {
        p.visible = true;
        p = p.parent;
    }
}

function hideParents(layer) {
    var p = layer.parent;
    while (p && p.typename === "LayerSet") {
        p.visible = false;
        p = p.parent;
    }
}

function removeHiddenLayersFast() {
    try {
        var idDlt = charIDToTypeID( "Dlt " );
        var desc = new ActionDescriptor();
        var idnull = charIDToTypeID( "null" );
        var ref = new ActionReference();
        ref.putEnumerated( charIDToTypeID( "Lyr " ), charIDToTypeID( "Ordn" ), stringIDToTypeID( "hidden" ) );
        desc.putReference( idnull, ref );
        executeAction( idDlt, desc, DialogModes.NO );
    } catch(e) {}
}

function unlockAndUngroupRemaining(container) {
    var layers = container.layers;
    for (var i = 0; i < layers.length; i++) {
        var l = layers[i];
        if (l.typename === "LayerSet") {
            if (l.allLocked) l.allLocked = false;
            unlockAndUngroupRemaining(l);
        } else {
            try { if (l.allLocked) l.allLocked = false; } catch(e) {}
            try { if (l.pixelsLocked) l.pixelsLocked = false; } catch(e) {}
            try { if (l.positionLocked) l.positionLocked = false; } catch(e) {}
            try { if (l.transparentPixelsLocked) l.transparentPixelsLocked = false; } catch(e) {}
            try { if (l.grouped) l.grouped = false; } catch(e) {}
        }
    }
}

function sanitizeFileName(name) {
    var invalidChars = new RegExp('[\\\\/:*?"<>|]', "g");
    return name.replace(invalidChars, "_");
}

function getUniqueFileName(name, counts) {
    if (counts[name] === undefined) {
        counts[name] = 1;
        return name;
    } else {
        counts[name]++;
        return name + "_" + counts[name];
    }
}

function exportPNG(doc, file) {
    var opts = new ExportOptionsSaveForWeb();
    opts.format = SaveDocumentType.PNG;
    opts.PNG8 = false;
    opts.transparency = true;
    opts.interlaced = false;
    opts.includeProfile = true;
    doc.exportDocument(file, ExportType.SAVEFORWEB, opts);
}

function escapeJSON(str) {
    var slashReg = new RegExp('\\\\', "g");
    var quoteReg = new RegExp('"', "g");
    return str.replace(slashReg, '\\\\').replace(quoteReg, '\\"');
}

main();
