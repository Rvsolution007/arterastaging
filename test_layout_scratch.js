function solveFlexLayout(textSources) {
    const canvasH = fCanvas.internalH || fCanvas.getHeight();
    const canvasW = fCanvas.internalW || fCanvas.getWidth();
    const allObjects = fCanvas.getObjects();
    
    // Filter objects that participate in layout
    const layoutObjs = allObjects.filter(obj => {
        if (obj._isDecorativeShape && obj.selectable === false) return false;
        if (obj._isFrameLayer && !obj._isFrameImage) return false;
        if (typeof obj._origTop === 'undefined') return false;
        const objW = obj._origWidth || (obj.getScaledWidth ? obj.getScaledWidth() : (obj.width * obj.scaleX));
        if (objW >= canvasW * 0.9) return false; // skip full-width backgrounds
        return true;
    });

    // Determine the affected column for each text source
    // A text source affects objects below it that overlap horizontally.
    // To handle multiple sources, we can group objects into "Columns" based on horizontal overlap.
    // For simplicity, let's just find the chain for ALL text sources combined if they overlap each other,
    // or independently if they don't.
    // Actually, in most templates, there's just one main column of text.
    // Let's find all objects that overlap horizontally with ANY textSource.
    let minLeft = 9999, maxRight = 0;
    textSources.forEach(src => {
        minLeft = Math.min(minLeft, src._origLeft);
        maxRight = Math.max(maxRight, src._origRight);
    });

    const columnObjs = layoutObjs.filter(obj => {
        return (obj._origRight > minLeft) && (obj._origLeft < maxRight);
    }).sort((a, b) => a._origTop - b._origTop);

    if (columnObjs.length === 0) return;

    const MIN_MARGIN = 30;
    const CANVAS_PADDING_BOTTOM = 30;
    const MAX_ALLOWED_BOTTOM = canvasH - CANVAS_PADDING_BOTTOM;

    // Loop until layout fits or we can't shrink anymore
    let maxIterations = 50;
    while (maxIterations-- > 0) {
        // Step 1: Calculate natural positions (maintaining original margins)
        // Original margin between columnObjs[i] and columnObjs[i-1] is:
        // origGap[i] = columnObjs[i]._origTop - columnObjs[i-1]._origBottom;
        
        let currentTop = columnObjs[0]._origTop; // The top-most element stays at its original Y
        
        // To track positions
        const positions = []; // array of { obj, top, bottom, gapToPrev, origGapToPrev }
        
        for (let i = 0; i < columnObjs.length; i++) {
            const obj = columnObjs[i];
            const currentH = obj.getScaledHeight ? obj.getScaledHeight() : (obj.height * obj.scaleY);
            
            let gapToPrev = 0;
            let origGapToPrev = 0;
            if (i > 0) {
                origGapToPrev = obj._origTop - columnObjs[i-1]._origBottom;
                // If elements originally overlapped vertically, gap is negative. We preserve it.
                gapToPrev = origGapToPrev;
                currentTop = positions[i-1].bottom + gapToPrev;
            } else {
                // Keep the first element anchored, or apply delta if it's not the first original element?
                // Actually, if columnObjs[0] is not a textSource, it shouldn't move unless pushed.
                // It's better to calculate shift based on text sources above.
            }
        }
    }
}
