// This will be inserted into universal_edit.blade.php
function solveFlexLayout() {
    const canvasH = fCanvas.internalH || fCanvas.getHeight();
    const canvasW = fCanvas.internalW || fCanvas.getWidth();
    const MAX_BOTTOM = canvasH - 30; // 30px padding at bottom
    const MIN_MARGIN = 30;
    
    const allObjects = fCanvas.getObjects();
    
    let loopCount = 0;
    let needsFontShrink = false;
    let finalPositions = new Map();
    let finalRatio = 1.0;
    let finalColumnTop = 0;
    
    do {
        needsFontShrink = false;
        finalPositions.clear();
        
        // 1. Find all text sources that have changed height from baseline
        const textSources = [];
        allObjects.forEach(obj => {
            if (obj.type !== 'textbox' && obj.type !== 'i-text' && obj.type !== 'text') return;
            if (typeof obj._origHeight === 'undefined') return;
            
            const currentH = obj.getScaledHeight ? obj.getScaledHeight() : (obj.height * obj.scaleY);
            const delta = currentH - obj._origHeight;
            
            if (Math.abs(delta) >= 1) {
                textSources.push({
                    obj: obj,
                    delta: delta,
                    origTop: obj._origTop,
                    origBottom: obj._origBottom,
                    origLeft: obj._origLeft,
                    origRight: obj._origRight
                });
            }
        });

        if (textSources.length === 0) break; // Nothing to do

        let columnTop = Math.min(...textSources.map(src => src.origTop));
        let maxNaturalBottom = 0;
        
        // 2. Calculate natural tops
        allObjects.forEach(obj => {
            if (obj._isDecorativeShape && obj.selectable === false) return;
            if (obj._isFrameLayer && !obj._isFrameImage) return;
            if (typeof obj._origTop === 'undefined') return;
            
            const objW = obj._origWidth || (obj.getScaledWidth ? obj.getScaledWidth() : (obj.width * obj.scaleX));
            if (objW >= canvasW * 0.9) return;
            
            let totalShift = 0;
            let inColumn = false;
            
            textSources.forEach(src => {
                const overlapsH = (obj._origRight > src.origLeft) && (obj._origLeft < src.origRight);
                if (overlapsH) inColumn = true;
                
                if (src.obj === obj) return;
                
                const isBelow = obj._origTop >= (src.origBottom - 10);
                if (isBelow && overlapsH) {
                    totalShift += src.delta;
                }
            });

            const naturalTop = obj._origTop + totalShift;
            const currentH = obj.getScaledHeight ? obj.getScaledHeight() : (obj.height * obj.scaleY);
            const naturalBottom = naturalTop + currentH;
            
            finalPositions.set(obj, { naturalTop, naturalBottom, currentH, inColumn });
            
            if (inColumn && naturalBottom > maxNaturalBottom) {
                maxNaturalBottom = naturalBottom;
            }
        });

        // 3. Check overflow and compression
        let overflow = maxNaturalBottom - MAX_BOTTOM;
        let compressionRatio = 1.0;
        let canCompress = true;

        if (overflow > 0) {
            const compressibleSpace = maxNaturalBottom - columnTop;
            if (compressibleSpace > 0) {
                compressionRatio = (compressibleSpace - overflow) / compressibleSpace;
            }
            
            // Check minimum margin violation
            const colObjs = Array.from(finalPositions.keys())
                .filter(obj => finalPositions.get(obj).inColumn && finalPositions.get(obj).naturalTop >= columnTop)
                .sort((a, b) => finalPositions.get(a).naturalTop - finalPositions.get(b).naturalTop);
                
            for (let i = 1; i < colObjs.length; i++) {
                const prev = colObjs[i-1];
                const curr = colObjs[i];
                const prevPos = finalPositions.get(prev);
                const currPos = finalPositions.get(curr);
                
                if (currPos.naturalTop > prevPos.naturalBottom - 5) {
                    const naturalGap = currPos.naturalTop - prevPos.naturalBottom;
                    
                    const prevNewTop = columnTop + (prevPos.naturalTop - columnTop) * compressionRatio;
                    const currNewTop = columnTop + (currPos.naturalTop - columnTop) * compressionRatio;
                    const prevNewBottom = prevNewTop + prevPos.currentH;
                    const compressedGap = currNewTop - prevNewBottom;
                    
                    if (compressedGap < MIN_MARGIN && naturalGap >= MIN_MARGIN) {
                        canCompress = false;
                        break;
                    }
                }
            }
        }

        // 4. Shrink fonts if we can't compress enough
        if (overflow > 0 && !canCompress) {
            textSources.forEach(src => {
                if (src.obj.fontSize > 10) {
                    src.obj.set('fontSize', src.obj.fontSize - 1);
                    if (src.obj.initDimensions) src.obj.initDimensions();
                    needsFontShrink = true;
                }
            });
        } else {
            finalRatio = compressionRatio;
            finalColumnTop = columnTop;
        }

    } while (needsFontShrink && loopCount++ < 100);

    // 5. Apply final positions
    finalPositions.forEach((pos, obj) => {
        let finalTop = pos.naturalTop;
        if (finalRatio < 1.0 && pos.inColumn && pos.naturalTop >= finalColumnTop) {
            finalTop = finalColumnTop + (pos.naturalTop - finalColumnTop) * finalRatio;
        }
        
        if (Math.abs(finalTop - obj.top) >= 1) {
            obj.set('top', finalTop);
            obj.setCoords();
        }
    });
    
    fCanvas.renderAll();
}
