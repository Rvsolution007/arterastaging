// Width constraint and Rigid Block Layout Algorithm

const MIN_MARGIN = 30;
let needsFontShrink = false;

do {
    needsFontShrink = false;
    
    // 1. Identify Text Sources and check WIDTH constraint first!
    const textSources = [];
    allObjects.forEach(obj => {
        if (obj.type !== 'textbox' && obj.type !== 'i-text' && obj.type !== 'text') return;
        
        // --- WIDTH FIX ---
        // If width exceeds original JSON width, we MUST shrink font!
        const currentW = obj.getScaledWidth ? obj.getScaledWidth() : (obj.width * obj.scaleX);
        const origW = obj._jsonOrigWidth || obj._origWidth; // Need to ensure _origWidth is strictly JSON width
        
        if (origW > 0 && currentW > origW + 2) { // 2px tolerance
            needsFontShrink = true; // Will trigger font reduction
        }
        
        const currentH = obj.getScaledHeight ? obj.getScaledHeight() : (obj.height * obj.scaleY);
        const delta = currentH - obj._origHeight;
        if (Math.abs(delta) >= 1) {
            textSources.push({
                obj: obj,
                delta: delta,
                origBottom: obj._origBottom,
                origLeft: obj._origLeft,
                origRight: obj._origRight,
                // We will calculate max allowed compression for this delta
                shrinkableGap: 0
            });
        }
    });

    if (needsFontShrink) {
        // Shrink fonts of oversized text layers
        allObjects.forEach(obj => {
            if (obj.type === 'textbox' || obj.type === 'text') {
                const currentW = obj.getScaledWidth ? obj.getScaledWidth() : (obj.width * obj.scaleX);
                const origW = obj._jsonOrigWidth || obj._origWidth;
                if (origW > 0 && currentW > origW + 2 && obj.fontSize > 10) {
                    obj.set('fontSize', obj.fontSize - 1);
                    if (obj.initDimensions) obj.initDimensions();
                }
            }
        });
        continue;
    }

    // 2. Determine shrinkable gap for each text source
    // The shrinkable gap is the distance from TextSource.origBottom to the highest object below it
    textSources.forEach(src => {
        let minGap = 9999;
        allObjects.forEach(obj => {
            if (src.obj === obj) return;
            if (obj._isDecorativeShape && obj.selectable === false) return;
            if (typeof obj._origTop === 'undefined') return;
            
            const overlapsH = (obj._origRight > src.origLeft) && (obj._origLeft < src.origRight);
            const isBelow = obj._origTop >= (src.origBottom - 5);
            
            if (isBelow && overlapsH) {
                const gap = obj._origTop - src.origBottom;
                if (gap < minGap) minGap = gap;
            }
        });
        
        if (minGap === 9999) minGap = 0; // Nothing below it
        src.shrinkableGap = Math.max(0, minGap - MIN_MARGIN);
    });

    // 3. Calculate natural bottom and total overflow
    let maxNaturalBottom = 0;
    
    // We assign a `naturalTop` and see how much we overflow
    const naturalTops = new Map();
    
    allObjects.forEach(obj => {
        if (obj._isDecorativeShape && obj.selectable === false) return;
        if (typeof obj._origTop === 'undefined') return;
        
        let totalShift = 0;
        textSources.forEach(src => {
            if (src.obj === obj) return;
            const overlapsH = (obj._origRight > src.origLeft) && (obj._origLeft < src.origRight);
            const isBelow = obj._origTop >= (src.origBottom - 5);
            if (isBelow && overlapsH) {
                totalShift += src.delta;
            }
        });

        const naturalTop = obj._origTop + totalShift;
        const currentH = obj.getScaledHeight ? obj.getScaledHeight() : (obj.height * obj.scaleY);
        const naturalBottom = naturalTop + currentH;
        naturalTops.set(obj, { naturalTop, totalShift, inColumn: totalShift > 0 });
        
        if (totalShift > 0 && naturalBottom > maxNaturalBottom) {
            maxNaturalBottom = naturalBottom;
        }
    });

    const canvasH = fCanvas.internalH || fCanvas.getHeight();
    const MAX_BOTTOM = canvasH - 30;
    let overflow = maxNaturalBottom - MAX_BOTTOM;

    // 4. Resolve overflow by reducing shifts (compressing margins)
    if (overflow > 0) {
        // We can reduce the shift of elements below each text source by up to src.shrinkableGap
        // Let's go through textSources and apply compression
        textSources.forEach(src => {
            if (overflow <= 0) return;
            if (src.shrinkableGap > 0) {
                const amountToCompress = Math.min(overflow, src.shrinkableGap);
                src.appliedCompression = amountToCompress;
                overflow -= amountToCompress;
            } else {
                src.appliedCompression = 0;
            }
        });

        if (overflow > 0) {
            // We still have overflow! We must shrink font of the text sources!
            textSources.forEach(src => {
                if (src.obj.fontSize > 10) {
                    src.obj.set('fontSize', src.obj.fontSize - 1);
                    if (src.obj.initDimensions) src.obj.initDimensions();
                    needsFontShrink = true;
                }
            });
            continue;
        }
    } else {
        textSources.forEach(src => src.appliedCompression = 0);
    }

    // 5. If we reach here, we have a valid layout! Apply the final positions.
    allObjects.forEach(obj => {
        if (obj._isDecorativeShape && obj.selectable === false) return;
        if (typeof obj._origTop === 'undefined') return;
        
        let finalShift = 0;
        textSources.forEach(src => {
            if (src.obj === obj) return;
            const overlapsH = (obj._origRight > src.origLeft) && (obj._origLeft < src.origRight);
            const isBelow = obj._origTop >= (src.origBottom - 5);
            if (isBelow && overlapsH) {
                finalShift += (src.delta - (src.appliedCompression || 0));
            }
        });

        const finalTop = obj._origTop + finalShift;
        if (Math.abs(finalTop - obj.top) >= 1) {
            obj.set('top', finalTop);
            obj.setCoords();
        }
    });

} while (needsFontShrink && loopCount++ < 100);
