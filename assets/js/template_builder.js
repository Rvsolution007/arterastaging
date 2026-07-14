// Template Builder JS — runs immediately (no DOMContentLoaded since script loads at end of body)
(function() {
    'use strict';
    // ── Suppress verbose debug/font/mask logs for clean console ──
    (function() {
        var _log = console.log, _warn = console.warn, _err = console.error;
        var _suppressPrefixes = ['[DEBUG]', '[FONTS]', '[MASK_AUTODETECT]', '[TEMPLATE_BUILDER]'];
        function _shouldSuppress(args) {
            if (args[0] && typeof args[0] === 'string') {
                for (var i = 0; i < _suppressPrefixes.length; i++) {
                    if (args[0].indexOf(_suppressPrefixes[i]) === 0) return true;
                }
            }
            return false;
        }
        console.log = function() { if (!_shouldSuppress(arguments)) _log.apply(console, arguments); };
        console.warn = function() { if (!_shouldSuppress(arguments)) _warn.apply(console, arguments); };
        console.error = function() { if (!_shouldSuppress(arguments)) _err.apply(console, arguments); };
    })();
    console.log('[TEMPLATE_BUILDER] v3.0 loaded — fill control + vector paths + complete effects');
    // ── Render Version: ALL rendering logic is versioned. Current code = version 1. ──
    // ── Future rendering changes MUST increment this and add version-gated code paths. ──
    const CURRENT_RENDER_VERSION = 4;
    try {
    
    // Fix fabric.js alphabetical warning globally
    if (typeof fabric !== 'undefined') {
        fabric.Object.prototype.textBaseline = 'alphabetic';
        fabric.Text.prototype.textBaseline = 'alphabetic';
        if (fabric.Textbox) fabric.Textbox.prototype.textBaseline = 'alphabetic';
        if (fabric.IText) fabric.IText.prototype.textBaseline = 'alphabetic';

        // ══ CANVA/PHOTOSHOP TEXT BEHAVIOR ══
        // 1. lockUniScaling forces all handles to scale proportionally (no stretch)
        // 2. Hide mt/mb (vertical stretch is nonsensical for text)
        // 3. Override fabric.Text to fabric.IText so double-click editing works on point text (avoids Textbox call stack crash)
        if (fabric.IText) {
            fabric.IText.prototype.lockUniScaling = true;
            fabric.IText.prototype.setControlsVisibility({ mt: false, mb: false });
        }
        if (fabric.Textbox) {
            fabric.Textbox.prototype.lockUniScaling = true;
            fabric.Textbox.prototype.setControlsVisibility({ mt: false, mb: false });
        }
        if (fabric.Text) {
            fabric.Text.prototype.lockUniScaling = true;
            fabric.Text.prototype.setControlsVisibility({ mt: false, mb: false });
        }

        (function() {
            var _OrigText = fabric.Text;
            fabric.Text = function(text, options) {
                return new fabric.IText(text, options || {});
            };
            for (var key in _OrigText) {
                if (_OrigText.hasOwnProperty(key)) fabric.Text[key] = _OrigText[key];
            }
            fabric.Text.prototype = fabric.IText.prototype;
            fabric.Text.fromObject = function(object, callback) {
                return fabric.IText.fromObject(object, callback);
            };
            fabric.Text.async = true;
        })();
        if (fabric.Rect) {
            if (fabric.Rect.prototype.cacheProperties) {
                fabric.Rect.prototype.cacheProperties = fabric.Rect.prototype.cacheProperties.concat(['rx_tl', 'rx_tr', 'rx_br', 'rx_bl']);
            }
            if (fabric.Rect.prototype.stateProperties) {
                fabric.Rect.prototype.stateProperties = fabric.Rect.prototype.stateProperties.concat(['rx_tl', 'rx_tr', 'rx_br', 'rx_bl']);
            }
            const originalRectRender = fabric.Rect.prototype._render;
            fabric.Rect.prototype._render = function(ctx) {
                const tl = this.rx_tl !== undefined ? Number(this.rx_tl) : Number(this.rx || 0);
                const tr = this.rx_tr !== undefined ? Number(this.rx_tr) : Number(this.rx || 0);
                const br = this.rx_br !== undefined ? Number(this.rx_br) : Number(this.rx || 0);
                const bl = this.rx_bl !== undefined ? Number(this.rx_bl) : Number(this.rx || 0);
                if (tl === 0 && tr === 0 && br === 0 && bl === 0 && !this.rx && !this.ry) {
                    originalRectRender.call(this, ctx);
                    return;
                }
                const w = this.width, h = this.height, x = -w / 2, y = -h / 2;
                const maxR = Math.min(w, h) / 2;
                const rTL = Math.min(tl, maxR);
                const rTR = Math.min(tr, maxR);
                const rBR = Math.min(br, maxR);
                const rBL = Math.min(bl, maxR);
                ctx.beginPath();
                ctx.moveTo(x + rTL, y);
                ctx.lineTo(x + w - rTR, y);
                if (rTR > 0) ctx.arcTo(x + w, y, x + w, y + rTR, rTR); else ctx.lineTo(x + w, y);
                ctx.lineTo(x + w, y + h - rBR);
                if (rBR > 0) ctx.arcTo(x + w, y + h, x + w - rBR, y + h, rBR); else ctx.lineTo(x + w, y + h);
                ctx.lineTo(x + rBL, y + h);
                if (rBL > 0) ctx.arcTo(x, y + h, x, y + h - rBL, rBL); else ctx.lineTo(x, y + h);
                ctx.lineTo(x, y + rTL);
                if (rTL > 0) ctx.arcTo(x, y, x + rTL, y, rTL); else ctx.lineTo(x, y);
                ctx.closePath();
                if (typeof this._renderFill === 'function') {
                    this._renderFill(ctx);
                } else if (this.fill) {
                    ctx.fillStyle = this.fill;
                    ctx.fill();
                }
                if (typeof this._renderStroke === 'function') {
                    this._renderStroke(ctx);
                } else if (this.stroke && this.strokeWidth !== 0) {
                    ctx.strokeStyle = this.stroke;
                    ctx.lineWidth = this.strokeWidth;
                    ctx.stroke();
                }
            };
            const originalToObject = fabric.Rect.prototype.toObject;
            fabric.Rect.prototype.toObject = function(propertiesToInclude) {
                return Object.assign(originalToObject.call(this, propertiesToInclude), {
                    rx_tl: this.rx_tl || 0, rx_tr: this.rx_tr || 0, rx_br: this.rx_br || 0, rx_bl: this.rx_bl || 0
                });
            };
        }
    }

    // Global tracking for editing an existing custom frame
    if (typeof window.editing_frame_id === 'undefined' || window.editing_frame_id === '') {
        window.editing_frame_id = null;
    }
    const isFrameMode = (document.getElementById('btn-save') && document.getElementById('btn-save').getAttribute('data-mode') === 'frame');
    
    // Auto-load if frame_id is present
    if (window.editing_frame_id) {
        if (isFrameMode) {
            setTimeout(() => { if(window.loadExistingFrame) window.loadExistingFrame(window.editing_frame_id); }, 500);
        } else {
            setTimeout(() => { if(window.loadExistingTemplate) window.loadExistingTemplate(window.editing_frame_id); }, 500);
        }
    }

    // Suppress "Canvas2D: willReadFrequently" warnings from fabric filters
    if (typeof HTMLCanvasElement !== 'undefined') {
        var _origGetContext = HTMLCanvasElement.prototype.getContext;
        HTMLCanvasElement.prototype.getContext = function(type, attrs) {
            if (type === '2d') {
                attrs = attrs || {};
                attrs.willReadFrequently = true;
            }
            return _origGetContext.call(this, type, attrs);
        };
    }

    // Initialize Canvas
    if (fabric.Canvas2dFilterBackend) {
        fabric.filterBackend = new fabric.Canvas2dFilterBackend();
    }
    const canvas = new fabric.Canvas('template-canvas', {
        width: 1080,
        height: 1080,
        backgroundColor: '#ffffff',
        preserveObjectStacking: true
    });

    let baseWidth = 1080;
    let baseHeight = 1080;
    
    function updateCanvasZoom() {
        // Max preview: 650x650 — bigger preview = less "small" appearance
        const maxW = 650;
        const maxH = 650;
        
        const scale = Math.min(maxW / baseWidth, maxH / baseHeight, 1);
        
        canvas.setZoom(scale);
        canvas.setDimensions({
            width: Math.round(baseWidth * scale),
            height: Math.round(baseHeight * scale)
        });
        
        // Explicitly set wrapper size to prevent CSS collapsing
        const wrapper = document.getElementById('canvas-wrapper');
        if (wrapper) {
            wrapper.style.width = Math.round(baseWidth * scale) + 'px';
            wrapper.style.height = Math.round(baseHeight * scale) + 'px';
            wrapper.style.setProperty('--canvas-scale', scale);
        }

        canvas.renderAll();
        return scale;
    }
    
    let currentScale = updateCanvasZoom();
    window.addEventListener('resize', () => { currentScale = updateCanvasZoom(); });

    // --- Ctrl + Mouse Wheel Zoom (in center canvas area) ---
    function handleZoomWheel(e) {
        if (!e.ctrlKey && !e.metaKey) return;
        e.preventDefault();
        e.stopPropagation();

        const wrapEl = document.querySelector('.canvas-container-wrap');
        const wrapper = document.getElementById('canvas-wrapper');
        if (!wrapEl || !wrapper) return;

        const delta = e.deltaY || e.detail || 0;
        let zoomStep = 0.05;
        let newScale = currentScale;
        if (delta > 0) {
            newScale = Math.max(0.1, currentScale - zoomStep);
        } else {
            newScale = Math.min(5.0, currentScale + zoomStep);
        }

        if (newScale === currentScale) return;

        // Calculate mouse position relative to canvas wrapper
        const wrapperRect = wrapper.getBoundingClientRect();
        const mouseX = e.clientX - wrapperRect.left;
        const mouseY = e.clientY - wrapperRect.top;

        // Calculate the ratio of the new scale to the old scale
        const scaleMultiplier = newScale / currentScale;
        
        currentScale = newScale;

        canvas.setZoom(currentScale);
        canvas.setDimensions({
            width: Math.round(baseWidth * currentScale),
            height: Math.round(baseHeight * currentScale)
        });

        wrapper.style.width = Math.round(baseWidth * currentScale) + 'px';
        wrapper.style.height = Math.round(baseHeight * currentScale) + 'px';
        wrapper.style.setProperty('--canvas-scale', currentScale);

        canvas.renderAll();

        // Adjust scroll position to keep the point under the mouse stationary
        const newMouseX = mouseX * scaleMultiplier;
        const newMouseY = mouseY * scaleMultiplier;
        
        wrapEl.scrollLeft += (newMouseX - mouseX);
        wrapEl.scrollTop += (newMouseY - mouseY);
    }

    const wrapEl = document.querySelector('.canvas-container-wrap');
    if (wrapEl) {
        wrapEl.addEventListener('wheel', handleZoomWheel, { passive: false });
    }
    canvas.on('mouse:wheel', function(opt) {
        if (opt.e && (opt.e.ctrlKey || opt.e.metaKey)) {
            handleZoomWheel(opt.e);
        }
    });

    // --- Pan (Scroll) by Dragging ---
    let isPanning = false;
    let panStartX = 0, panStartY = 0;
    let initialScrollLeft = 0, initialScrollTop = 0;
    let isSpaceDown = false;

    window.addEventListener('keydown', (e) => {
        if (e.code === 'Space' && e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
            isSpaceDown = true;
            if (wrapEl) wrapEl.style.cursor = 'grab';
            e.preventDefault();
        }
    });
    window.addEventListener('keyup', (e) => {
        if (e.code === 'Space') {
            isSpaceDown = false;
            if (wrapEl && !isPanning) wrapEl.style.cursor = '';
        }
    });

    function startPan(e) {
        isPanning = true;
        if (wrapEl) wrapEl.style.cursor = 'grabbing';
        panStartX = e.clientX !== undefined ? e.clientX : (e.touches && e.touches.length > 0 ? e.touches[0].clientX : 0);
        panStartY = e.clientY !== undefined ? e.clientY : (e.touches && e.touches.length > 0 ? e.touches[0].clientY : 0);
        if (wrapEl) {
            initialScrollLeft = wrapEl.scrollLeft;
            initialScrollTop = wrapEl.scrollTop;
        }
    }

    function doPan(e) {
        if (!isPanning || !wrapEl) return;
        const clientX = e.clientX !== undefined ? e.clientX : (e.touches && e.touches.length > 0 ? e.touches[0].clientX : panStartX);
        const clientY = e.clientY !== undefined ? e.clientY : (e.touches && e.touches.length > 0 ? e.touches[0].clientY : panStartY);
        const dx = clientX - panStartX;
        const dy = clientY - panStartY;
        wrapEl.scrollLeft = initialScrollLeft - dx;
        wrapEl.scrollTop = initialScrollTop - dy;
    }

    function endPan(e) {
        if (isPanning) {
            isPanning = false;
            if (wrapEl) wrapEl.style.cursor = isSpaceDown ? 'grab' : '';
            if (typeof canvas !== 'undefined' && canvas) canvas.selection = true;
        }
    }

    if (wrapEl) {
        wrapEl.addEventListener('mousedown', (e) => {
            if (e.target === wrapEl || e.button === 1 || (e.button === 0 && isSpaceDown)) {
                startPan(e);
                if (e.button === 1 && e.preventDefault) e.preventDefault();
            }
        });
        wrapEl.addEventListener('touchstart', (e) => {
            if (e.target === wrapEl) startPan(e);
        }, { passive: true });

        window.addEventListener('mousemove', doPan);
        window.addEventListener('touchmove', doPan, { passive: true });

        window.addEventListener('mouseup', endPan);
        window.addEventListener('touchend', endPan);
    }

    if (typeof canvas !== 'undefined' && canvas) {
        canvas.on('mouse:down', function(opt) {
            var e = opt.e;
            // Panning if Middle-click, Space+click, Alt+click, OR left-click on empty canvas area
            if (e.button === 1 || (e.button === 0 && isSpaceDown) || e.altKey || (!opt.target && (e.button === 0 || e.type === 'touchstart'))) {
                startPan(e);
                canvas.selection = false;
                if (e.preventDefault) e.preventDefault();
            }
        });
    }

    // --- Helper: safe getElementById ---
    function $(id) { return document.getElementById(id); }
    function $$(selector) { return document.querySelectorAll(selector); }

    // --- Canvas Resize ---
    const btnResize = $('btn-resize-canvas');
    if (btnResize) btnResize.addEventListener('click', function() {
        const w = parseInt($('template-w').value);
        const h = parseInt($('template-h').value);
        if (w > 0 && h > 0) {
            baseWidth = w;
            baseHeight = h;
            currentScale = updateCanvasZoom();
            saveHistory();
        }
    });

    // --- Canvas Background Color ---
    const bgColorInput = $('canvas-bg-color');
    if (bgColorInput) {
        bgColorInput.value = '#ffffff';
        bgColorInput.addEventListener('input', function() {
            const gradToggle = $('canvas-gradient-toggle');
            if (gradToggle && gradToggle.checked) return; // gradient mode
            canvas.setBackgroundColor(this.value, canvas.renderAll.bind(canvas));
            saveHistory();
        });
    }

    // --- Canvas Gradient Background ---
    const gradToggle = $('canvas-gradient-toggle');
    const gradStart = $('canvas-gradient-start');
    const gradEnd = $('canvas-gradient-end');

    function applyGradient() {
        if (!gradToggle || !gradToggle.checked) return;
        const startColor = gradStart ? gradStart.value : '#6366f1';
        const endColor = gradEnd ? gradEnd.value : '#8b5cf6';
        const gradient = new fabric.Gradient({
            type: 'linear',
            coords: { x1: 0, y1: 0, x2: 0, y2: baseHeight },
            colorStops: [
                { offset: 0, color: startColor },
                { offset: 1, color: endColor }
            ]
        });
        canvas.setBackgroundColor(gradient, canvas.renderAll.bind(canvas));
        saveHistory();
    }

    if (gradToggle) {
        gradToggle.addEventListener('change', function() {
            const gradientControls = $('gradient-color-fields');
            if (this.checked) {
                if (gradientControls) gradientControls.style.display = 'flex';
                applyGradient();
            } else {
                if (gradientControls) gradientControls.style.display = 'none';
                const bgColor = bgColorInput ? bgColorInput.value : '#ffffff';
                canvas.setBackgroundColor(bgColor, canvas.renderAll.bind(canvas));
                saveHistory();
            }
        });
    }
    if (gradStart) gradStart.addEventListener('input', applyGradient);
    if (gradEnd) gradEnd.addEventListener('input', applyGradient);

    // ── Helper: Convert any CSS color string to #rrggbb hex ──
    // HTML <input type="color"> only accepts #rrggbb format.
    // Fabric.js sometimes stores fill as rgb()/rgba() which breaks the picker.
    function toHex(color) {
        if (!color || typeof color !== 'string') return '#000000';
        color = color.trim();
        // Already hex
        if (/^#[0-9a-fA-F]{6}$/.test(color)) return color;
        if (/^#[0-9a-fA-F]{3}$/.test(color)) {
            return '#' + color[1]+color[1] + color[2]+color[2] + color[3]+color[3];
        }
        // rgb(r,g,b) or rgba(r,g,b,a)
        var m = color.match(/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/);
        if (m) {
            var r = parseInt(m[1]).toString(16).padStart(2,'0');
            var g = parseInt(m[2]).toString(16).padStart(2,'0');
            var b = parseInt(m[3]).toString(16).padStart(2,'0');
            return '#' + r + g + b;
        }
        // Use a temporary canvas to resolve named colors
        try {
            var ctx = document.createElement('canvas').getContext('2d');
            ctx.fillStyle = color;
            var resolved = ctx.fillStyle; // browsers return #rrggbb
            if (/^#[0-9a-fA-F]{6}$/.test(resolved)) return resolved;
        } catch(e) {}
        return '#000000';
    }

    let templateImages = {};

    // --- Properties Panel References ---
    const propForm = $('properties-form');
    const noSelect = $('no-selection');
    const textProps = $('text-properties');
    const imageProps = $('image-properties');
    const shapeProps = $('shape-properties');
    const sharedProps = $('shared-properties');

    const inputX = $('prop-x');
    const inputY = $('prop-y');
    const inputW = $('prop-w');
    const inputH = $('prop-h');
    const inputText = $('prop-text');
    const inputFontSize = $('prop-font-size');
    const inputColor = $('prop-color');

    const inputLetterSpacing = $('prop-letter-spacing');
    const inputWordSpacing = $('prop-word-spacing');
    const inputLineHeight = $('prop-line-height');
    const inputAiAutoscale = $('prop-ai-autoscale');

    const inputIsBackground = $('prop-is-background');
    const inputIsPlaceholder = $('prop-is-placeholder');
    const inputIsColorizableShape = $('prop-is-colorizable-shape');
    const inputIsLogo = $('prop-is-logo');
    const inputMaskLayer = $('prop-mask-layer');
    const btnPickMask = $('btn-pick-mask');

    const inputFillColor = $('prop-fill-color');
    const inputStrokeColor = $('prop-stroke-color');
    const inputStrokeWidth = $('prop-stroke-width');
    const inputBorderRadius = $('prop-border-radius');
    const inputRadiusTL = $('prop-radius-tl');
    const inputRadiusTR = $('prop-radius-tr');
    const inputRadiusBR = $('prop-radius-br');
    const inputRadiusBL = $('prop-radius-bl');
    const btnRadiusLock = $('prop-radius-lock');
    let isRadiusLocked = true;

    const inputShapeGradient = $('prop-shape-gradient');
    const shapeGradientProps = $('shape-gradient-props');
    const inputGradColor1 = $('prop-grad-color1');
    const inputGradColor2 = $('prop-grad-color2');
    const inputGradOp1 = $('prop-grad-op1');
    const inputGradOp2 = $('prop-grad-op2');
    const textGradOp1Val = $('grad-op1-val');
    const textGradOp2Val = $('grad-op2-val');
    const btnGradDirs = $$('.prop-grad-dir');

    const inputFontFamily = $('prop-font-family');
    const btnBold = $('prop-bold');
    const btnItalic = $('prop-italic');
    const btnTextAlign = $$('.prop-text-align');
    const inputOpacity = $('prop-opacity');
    const textOpacityVal = $('opacity-val');
    const inputHasShadow = $('prop-has-shadow');
    const shadowPropsDiv = $('shadow-properties');
    const inputShadowBlur = $('prop-shadow-blur');
    const inputShadowColor = $('prop-shadow-color');
    const inputShadowX = $('prop-shadow-x');
    const inputShadowY = $('prop-shadow-y');

    // --- Selection & Properties ---
    canvas.on('selection:created', updateProps);
    canvas.on('selection:updated', updateProps);
    canvas.on('selection:cleared', function() {
        if (propForm) propForm.style.display = 'none';
        if (noSelect) noSelect.style.display = 'block';
    });
    canvas.on('object:modified', updateProps);

    // ── Anti-stretch: convert scale → width + fontSize on text objects & width/height on rects ──
    canvas.on('object:modified', function(e) {
        const obj = e.target;
        if (!obj) return;
        
        // Fix for Rect shape border radius stretching (prevents ellipse effect like CorelDraw)
        if (obj.type === 'rect') {
            const sx = obj.scaleX || 1;
            const sy = obj.scaleY || 1;
            if (Math.abs(sx - 1) > 0.001 || Math.abs(sy - 1) > 0.001) {
                const newWidth = Math.max(1, obj.width * sx);
                const newHeight = Math.max(1, obj.height * sy);
                obj.set({
                    width: newWidth,
                    height: newHeight,
                    scaleX: 1,
                    scaleY: 1
                });
                obj.setCoords();
                canvas.renderAll();
                if (inputW) inputW.value = Math.round(newWidth);
                if (inputH) inputH.value = Math.round(newHeight);
            }
            return;
        }

        const isText = (obj.type === 'text' || obj.type === 'i-text' || obj.type === 'textbox');
        if (!isText) return;
        const sx = obj.scaleX || 1;
        const sy = obj.scaleY || 1;
        if (Math.abs(sx - 1) < 0.001 && Math.abs(sy - 1) < 0.001) return;
        
        // lockUniScaling makes sx and sy equal on corner drag
        const uniformScale = Math.max(sx, sy);
        const newWidth = obj.width * uniformScale;
        const newFontSize = Math.round(obj.fontSize * uniformScale);
        
        obj.set({
            width: newWidth,
            fontSize: newFontSize > 1 ? newFontSize : 1,
            scaleX: 1,
            scaleY: 1
        });
        obj.setCoords();
        canvas.renderAll();
        if (inputFontSize) inputFontSize.value = newFontSize > 1 ? newFontSize : 1;
    });


    function updateCoords() {
        const obj = canvas.getActiveObject();
        if (!obj) return;
        if (inputX) inputX.value = Math.round(obj.left);
        if (inputY) inputY.value = Math.round(obj.top);
        if (inputW) inputW.value = Math.round(obj.width * obj.scaleX);
        if (inputH) inputH.value = Math.round(obj.height * obj.scaleY);
        if (inputFontSize && (obj.type === 'text' || obj.type === 'i-text' || obj.type === 'textbox')) {
            inputFontSize.value = Math.round(obj.fontSize * Math.abs(obj.scaleY || 1));
        }
    }
    
    canvas.on('object:scaling', updateCoords);
    canvas.on('object:moving', updateCoords);

    // --- Canva-style Smart Guidelines & Alignment Snapping ---
    let alignmentLines = { vertical: [], horizontal: [] };
    const SNAP_DIST = 6;

    canvas.on('object:moving', function(e) {
        alignmentLines = { vertical: [], horizontal: [] };
        const target = e.target;
        if (!target) return;

        const zoom = canvas.getZoom() || 1;
        const cW = canvas.width / zoom;
        const cH = canvas.height / zoom;
        const targetRect = target.getBoundingRect(true);

        const targetX = [
            { val: targetRect.left },
            { val: targetRect.left + targetRect.width / 2 },
            { val: targetRect.left + targetRect.width }
        ];
        const targetY = [
            { val: targetRect.top },
            { val: targetRect.top + targetRect.height / 2 },
            { val: targetRect.top + targetRect.height }
        ];

        let snappedX = false;
        let snappedY = false;

        let refX = [0, cW / 2, cW];
        let refY = [0, cH / 2, cH];

        canvas.getObjects().forEach(obj => {
            if (obj === target || !obj.visible || obj.evented === false || obj.id === 'workarea' || obj.id === 'frame_bg' || obj.isFrameStructural) return;
            const r = obj.getBoundingRect(true);
            refX.push(r.left, r.left + r.width / 2, r.left + r.width);
            refY.push(r.top, r.top + r.height / 2, r.top + r.height);
        });

        for (let i = 0; i < targetX.length && !snappedX; i++) {
            for (let j = 0; j < refX.length; j++) {
                if (Math.abs(targetX[i].val - refX[j]) <= SNAP_DIST) {
                    const dx = refX[j] - targetX[i].val;
                    target.set('left', target.left + dx);
                    target.setCoords();
                    alignmentLines.vertical.push(refX[j]);
                    snappedX = true;
                    break;
                }
            }
        }

        for (let i = 0; i < targetY.length && !snappedY; i++) {
            for (let j = 0; j < refY.length; j++) {
                if (Math.abs(targetY[i].val - refY[j]) <= SNAP_DIST) {
                    const dy = refY[j] - targetY[i].val;
                    target.set('top', target.top + dy);
                    target.setCoords();
                    alignmentLines.horizontal.push(refY[j]);
                    snappedY = true;
                    break;
                }
            }
        }
        if (typeof updateCoords === 'function') updateCoords();
        else if (typeof updateProps === 'function') updateProps();
    });

    canvas.on('after:render', function(opt) {
        if (alignmentLines.vertical.length === 0 && alignmentLines.horizontal.length === 0) return;
        const ctx = opt.ctx || canvas.contextContainer;
        if (!ctx) return;
        ctx.save();
        const vpt = canvas.viewportTransform || [1, 0, 0, 1, 0, 0];
        ctx.transform(vpt[0], vpt[1], vpt[2], vpt[3], vpt[4], vpt[5]);
        const zoom = canvas.getZoom() || 1;
        const cW = canvas.width / zoom;
        const cH = canvas.height / zoom;
        ctx.lineWidth = 1.5 / zoom;
        ctx.strokeStyle = '#ff007f';
        ctx.setLineDash([5 / zoom, 5 / zoom]);

        alignmentLines.vertical.forEach(x => {
            ctx.beginPath();
            ctx.moveTo(x, 0);
            ctx.lineTo(x, cH);
            ctx.stroke();
        });

        alignmentLines.horizontal.forEach(y => {
            ctx.beginPath();
            ctx.moveTo(0, y);
            ctx.lineTo(cW, y);
            ctx.stroke();
        });

        ctx.restore();
    });

    const clearGuidelines = function() {
        if (alignmentLines.vertical.length > 0 || alignmentLines.horizontal.length > 0) {
            alignmentLines = { vertical: [], horizontal: [] };
            canvas.requestRenderAll();
        }
    };

    canvas.on('mouse:up', clearGuidelines);
    canvas.on('object:modified', clearGuidelines);

    function updateProps() {
        let obj = canvas.getActiveObject();
        if (!obj) return;

        updateCoords();

        if (obj.type === 'group') {
            if (textProps) textProps.style.display = 'none';
            if (imageProps) imageProps.style.display = 'none';
            if (shapeProps) shapeProps.style.display = 'none';
            if (sharedProps) sharedProps.style.display = 'none';
            return;
        }

        if (obj.type === 'activeSelection') {
            let firstText = obj._objects.find(o => o.type === 'text' || o.type === 'i-text' || o.type === 'textbox');
            let firstShape = obj._objects.find(o => o.customType === 'shape' || o.is_shape || ['rect','circle','triangle','path','polygon','line','ellipse'].includes(o.type));
            let firstImage = obj._objects.find(o => o.type === 'image');
            
            if (firstText) obj = firstText;
            else if (firstShape) obj = firstShape;
            else if (firstImage) obj = firstImage;
            else {
                if (textProps) textProps.style.display = 'none';
                if (imageProps) imageProps.style.display = 'none';
                if (shapeProps) shapeProps.style.display = 'none';
                if (sharedProps) sharedProps.style.display = 'none';
                return;
            }
        }

        if (propForm) propForm.style.display = 'block';
        if (noSelect) noSelect.style.display = 'none';
        if (sharedProps) sharedProps.style.display = 'block';
        if (inputOpacity) inputOpacity.value = obj.opacity !== undefined ? obj.opacity : 1;
        if (textOpacityVal) textOpacityVal.innerText = Math.round((obj.opacity !== undefined ? obj.opacity : 1) * 100);

        // Shadow
        if (inputHasShadow) {
            if (obj.shadow) {
                inputHasShadow.checked = true;
                if (shadowPropsDiv) shadowPropsDiv.style.display = 'block';
                if (inputShadowBlur) inputShadowBlur.value = obj.shadow.blur || 0;
                if (inputShadowColor) inputShadowColor.value = obj.shadow.color || '#000000';
                if (inputShadowX) inputShadowX.value = obj.shadow.offsetX || 0;
                if (inputShadowY) inputShadowY.value = obj.shadow.offsetY || 0;
            } else {
                inputHasShadow.checked = false;
                if (shadowPropsDiv) shadowPropsDiv.style.display = 'none';
            }
        }

        // Text
        if (obj.type === 'text' || obj.type === 'i-text' || obj.type === 'textbox') {
            if (textProps) textProps.style.display = 'block';
            if (imageProps) imageProps.style.display = 'none';
            if (shapeProps) shapeProps.style.display = 'none';
            if (inputText) inputText.value = obj.text;
            if (inputFontSize) inputFontSize.value = Math.round(obj.fontSize * Math.abs(obj.scaleY || 1));
            if (inputColor) {
                var hexColor = toHex(obj.fill || '#000000');
                inputColor.value = hexColor;
                // JSColor v2: update the swatch/picker to match the new value
                if (inputColor.jscolor) {
                    inputColor.jscolor.fromString(hexColor);
                }
            }
            if (inputFontFamily) inputFontFamily.value = obj.fontFamily || 'Arial';
            
            // Bold/Italic button state
            if (btnBold) {
                var isBold = obj.fontWeight === 'bold' || obj.fontWeight === 700 || obj.fontWeight === '700';
                btnBold.classList.replace(isBold ? 'btn-outline-secondary' : 'btn-secondary', isBold ? 'btn-secondary' : 'btn-outline-secondary');
            }
            if (btnItalic) {
                var isItal = obj.fontStyle === 'italic';
                btnItalic.classList.replace(isItal ? 'btn-outline-secondary' : 'btn-secondary', isItal ? 'btn-secondary' : 'btn-outline-secondary');
            }

            btnTextAlign.forEach(btn => {
                if(btn.dataset.align === obj.textAlign) btn.classList.replace('btn-outline-secondary', 'btn-secondary');
                else btn.classList.replace('btn-secondary', 'btn-outline-secondary');
            });
            
            if (inputLetterSpacing) inputLetterSpacing.value = (obj.charSpacing || 0);
            if (inputWordSpacing) inputWordSpacing.value = (obj.wordSpacing || 0);
            if (inputLineHeight) inputLineHeight.value = (obj.lineHeight || 1.16);
            if (inputAiAutoscale) inputAiAutoscale.checked = obj.auto_scale || false;
        } else if (obj.type === 'image' && !(obj.customType === 'shape' || obj.is_shape)) {
            if (textProps) textProps.style.display = 'none';
            if (imageProps) imageProps.style.display = 'block';
            if (shapeProps) shapeProps.style.display = 'none';
            if (inputIsBackground) inputIsBackground.checked = obj.is_background || false;
            if (inputIsPlaceholder) inputIsPlaceholder.checked = obj.is_placeholder || false;
            if (inputIsColorizableShape) inputIsColorizableShape.checked = false;
            if (inputIsLogo) inputIsLogo.checked = obj.is_logo || (obj.customName || '').toLowerCase().includes('logo');
            
            if (inputMaskLayer) {
                inputMaskLayer.innerHTML = '<option value="">-- None --</option>';
                canvas.getObjects().forEach(o => {
                    if (o !== obj) {
                        let opt = document.createElement('option');
                        opt.value = o.id || o.customName;
                        opt.text = o.customName || o.id;
                        if (obj.mask_layer_id === opt.value) opt.selected = true;
                        inputMaskLayer.appendChild(opt);
                    }
                });
            }

        } else if (obj.customType === 'shape' || obj.is_shape || ['rect','circle','triangle','path','polygon','line','ellipse'].includes(obj.type)) {
            if (textProps) textProps.style.display = 'none';
            if (imageProps) imageProps.style.display = 'none';
            if (shapeProps) shapeProps.style.display = 'block';
            
            // Hide vector-only controls if shape is rasterized (image)
            const isRaster = obj.type === 'image';
            const vecGrad = document.getElementById('shape-vector-gradient-wrapper');
            const vecCtrls = document.getElementById('shape-vector-controls-wrapper');
            if (vecGrad) vecGrad.style.display = isRaster ? 'none' : 'block';
            if (vecCtrls) vecCtrls.style.display = isRaster ? 'none' : 'flex';
            
            const isGradient = obj.fill && obj.fill.type === 'linear';
            if (inputShapeGradient) inputShapeGradient.checked = isGradient;
            if (shapeGradientProps) shapeGradientProps.style.display = (isGradient && !isRaster) ? 'block' : 'none';

            if (isGradient) {
                const stops = obj.fill.colorStops || [];
                const c1 = stops[0] ? new fabric.Color(stops[0].color) : new fabric.Color('#6366f1');
                const c2 = stops[1] ? new fabric.Color(stops[1].color) : new fabric.Color('#ffffff');
                if (inputGradColor1) inputGradColor1.value = '#' + c1.toHex();
                if (inputGradColor2) inputGradColor2.value = '#' + c2.toHex();
                if (inputGradOp1) { inputGradOp1.value = c1.getAlpha(); if(textGradOp1Val) textGradOp1Val.innerText = Math.round(c1.getAlpha()*100); }
                if (inputGradOp2) { inputGradOp2.value = c2.getAlpha(); if(textGradOp2Val) textGradOp2Val.innerText = Math.round(c2.getAlpha()*100); }
                
                const coords = obj.fill.coords || {x1:0,y1:0,x2:0,y2:obj.height};
                let dir = 'top-bottom';
                if (coords.x1 < coords.x2) dir = 'left-right';
                else if (coords.x1 > coords.x2) dir = 'right-left';
                else if (coords.y1 > coords.y2) dir = 'bottom-top';
                
                if (btnGradDirs) {
                    btnGradDirs.forEach(btn => {
                        if (btn.dataset.dir === dir) btn.classList.replace('btn-outline-secondary', 'btn-secondary');
                        else btn.classList.replace('btn-secondary', 'btn-outline-secondary');
                    });
                }
            } else {
                const rawFill = obj.fill || '#6366f1';
                let hexFill = '#6366f1';
                if (typeof rawFill === 'string') {
                    if (rawFill.startsWith('#') && rawFill.length >= 7) hexFill = rawFill.substring(0,7);
                    else try { hexFill = '#' + new fabric.Color(rawFill).toHex(); } catch(e) { hexFill = rawFill; }
                }
                if (inputFillColor) {
                    if (inputFillColor.jscolor) {
                        inputFillColor.jscolor.fromString(hexFill);
                    } else {
                        inputFillColor.value = hexFill;
                    }
                }
            }

            const rawStroke = obj.stroke || '#000000';
            let hexStroke = '#000000';
            if (typeof rawStroke === 'string') {
                if (rawStroke.startsWith('#') && rawStroke.length >= 7) hexStroke = rawStroke.substring(0,7);
                else try { hexStroke = '#' + new fabric.Color(rawStroke).toHex(); } catch(e) { hexStroke = rawStroke; }
            }
            if (inputStrokeColor) {
                if (inputStrokeColor.jscolor) {
                    inputStrokeColor.jscolor.fromString(hexStroke);
                } else {
                    inputStrokeColor.value = hexStroke;
                }
            }
            if (inputStrokeWidth) inputStrokeWidth.value = obj.strokeWidth || 0;
            if (inputBorderRadius) inputBorderRadius.value = obj.rx || 0;
            const tl = obj.rx_tl !== undefined ? obj.rx_tl : (obj.rx || 0);
            const tr = obj.rx_tr !== undefined ? obj.rx_tr : (obj.rx || 0);
            const br = obj.rx_br !== undefined ? obj.rx_br : (obj.rx || 0);
            const bl = obj.rx_bl !== undefined ? obj.rx_bl : (obj.rx || 0);
            if (inputRadiusTL) inputRadiusTL.value = tl;
            if (inputRadiusTR) inputRadiusTR.value = tr;
            if (inputRadiusBR) inputRadiusBR.value = br;
            if (inputRadiusBL) inputRadiusBL.value = bl;
        } else {
            if (textProps) textProps.style.display = 'none';
            if (imageProps) imageProps.style.display = 'none';
            if (shapeProps) shapeProps.style.display = 'none';
        }
    }

    // Helper to ensure text objects are fabric.Textbox with scaleX=1 so container width & textAlign work
    function ensureTextbox(obj, actionName = 'unknown') {
        if (!obj || (obj.type !== 'textbox' && obj.type !== 'i-text' && obj.type !== 'text')) return obj;
        
        console.log(`=== [DIAGNOSIS: ${actionName}] Before ===`, {
            type: obj.type, width: obj.width, scaleX: obj.scaleX, height: obj.height, scaleY: obj.scaleY, textAlign: obj.textAlign, text: obj.text
        });

        // If it's already a textbox, we MUST still normalize scaleX to 1!
        // In Fabric.js, if scaleX != 1, textAlign ('right'/'center') calculates alignment across unscaled width (which tightly wraps text)
        // rather than visual width, leaving text stuck on the left!
        if (obj.type === 'textbox') {
            if (obj.scaleX && obj.scaleX !== 1) {
                const actualW = Math.round(obj.width * obj.scaleX);
                obj.set({ width: actualW, scaleX: 1 });
                obj.setCoords();
            }
            console.log(`=== [DIAGNOSIS: ${actionName}] After (Normalized Textbox) ===`, {
                type: obj.type, width: obj.width, scaleX: obj.scaleX, textAlign: obj.textAlign
            });
            return obj;
        }

        // Convert Point Text (fabric.Text / fabric.IText) to Paragraph Text (fabric.Textbox)
        const props = obj.toObject();
        props.type = 'textbox';
        props.width = Math.round(obj.width * (obj.scaleX || 1));
        props.scaleX = 1;
        props.scaleY = obj.scaleY || 1;
        const tb = new fabric.Textbox(obj.text, props);
        tb.set({
            customName: obj.customName,
            customType: obj.customType || 'text',
            placeholderKey: obj.placeholderKey,
            ai_field: obj.ai_field,
            ai_role: obj.ai_role,
            ai_max_chars: obj.ai_max_chars,
            is_placeholder: obj.is_placeholder,
            _psdData: obj._psdData,
            _originalYOffset: obj._originalYOffset
        });
        const idx = canvas.getObjects().indexOf(obj);
        canvas.remove(obj);
        canvas.add(tb);
        if (idx >= 0 && typeof canvas.moveTo === 'function') canvas.moveTo(tb, idx);
        canvas.setActiveObject(tb);
        if (typeof updateLayersList === 'function') updateLayersList();

        console.log(`=== [DIAGNOSIS: ${actionName}] After (Converted to Textbox) ===`, {
            type: tb.type, width: tb.width, scaleX: tb.scaleX, textAlign: tb.textAlign
        });
        return tb;
    }

    // --- Property Input Handlers ---
    [inputX, inputY, inputW, inputH].forEach(input => {
        if (!input) return;
        input.addEventListener('change', function() {
            let obj = canvas.getActiveObject();
            if(!obj) return;
            if(this.id === 'prop-x') obj.set('left', parseInt(this.value));
            if(this.id === 'prop-y') obj.set('top', parseInt(this.value));
            if(this.id === 'prop-w') {
                const newW = parseInt(this.value, 10);
                if (!isNaN(newW) && newW > 0) {
                    if (obj.type === 'textbox' || obj.type === 'i-text' || obj.type === 'text') {
                        obj = ensureTextbox(obj, 'prop_w_change');
                        obj.set({ width: newW, scaleX: 1 });
                    } else {
                        obj.set({ scaleX: newW / obj.width });
                    }
                }
            }
            if(this.id === 'prop-h') {
                const newH = parseInt(this.value, 10);
                if (!isNaN(newH) && newH > 0) {
                    if (obj.type === 'textbox' || obj.type === 'i-text' || obj.type === 'text') {
                        obj = ensureTextbox(obj, 'prop_h_change');
                        obj.set({ height: newH, scaleY: 1 });
                    } else {
                        obj.set({ scaleY: newH / obj.height });
                    }
                }
            }
            canvas.renderAll();
            saveHistory();
        });
    });

    if (inputIsPlaceholder) {
        inputIsPlaceholder.addEventListener('change', function() {
            const obj = canvas.getActiveObject();
            if(obj) { obj.set('is_placeholder', this.checked); saveHistory(); }
        });
    }

    function applyVisualMaskPreview(targetImg, maskId) {
        if (!maskId) {
            targetImg.set('clipPath', null);
            canvas.requestRenderAll();
            return;
        }
        const maskShape = canvas.getObjects().find(o => o.id === maskId || o.customName === maskId);
        if (!maskShape) {
            console.warn('[MASK_PREVIEW] ⚠️ maskShape not found for id="' + maskId + '"');
            return;
        }
        
        console.log('[MASK_PREVIEW] maskShape type=' + maskShape.type + ' name="' + maskShape.customName + '" is_shape=' + maskShape.is_shape);

        if (maskShape.type !== 'image') {
            // True vector shape (rect, circle, triangle, etc.) — clone directly as clipPath
            maskShape.clone(function(cloned) {
                cloned.absolutePositioned = true;
                targetImg.set('clipPath', cloned);
                canvas.requestRenderAll();
                console.log('[MASK_PREVIEW] ✅ Vector clipPath applied for "' + targetImg.customName + '"');
            });
        } else {
            // Rasterized PSD shape — clone the IMAGE itself as clipPath
            // This preserves the alpha channel (transparency) of the original shape PNG
            // so non-rectangular shapes (triangles, custom cuts) clip properly.
            console.log('[MASK_PREVIEW] Cloning rasterized shape image as clipPath...');
            maskShape.clone(function(cloned) {
                cloned.absolutePositioned = true;
                targetImg.set('clipPath', cloned);
                canvas.requestRenderAll();
                console.log('[MASK_PREVIEW] ✅ Rasterized image clipPath applied for "' + targetImg.customName + '" (using alpha channel of "' + maskShape.customName + '")');
            });
        }
    }

    function snapImageToMask(targetImg, maskId) {
        if (!maskId) return;
        const maskShape = canvas.getObjects().find(o => o.id === maskId || o.customName === maskId);
        if (!maskShape) return;
        
        // Calculate scale to "cover" the shape, and center it
        const maskW = maskShape.width * maskShape.scaleX;
        const maskH = maskShape.height * maskShape.scaleY;
        const scaleX = maskW / targetImg.width;
        const scaleY = maskH / targetImg.height;
        const scale = Math.max(scaleX, scaleY);
        
        // Handle rotation if needed, but normally we just match position
        const centerX = maskShape.left + (maskShape.originX === 'center' ? 0 : maskW / 2);
        const centerY = maskShape.top + (maskShape.originY === 'center' ? 0 : maskH / 2);
        
        targetImg.set({
            scaleX: scale,
            scaleY: scale,
            left: centerX - (targetImg.width * scale) / 2,
            top: centerY - (targetImg.height * scale) / 2,
            originX: 'left',
            originY: 'top'
        });
        targetImg.setCoords();
    }

    if (inputMaskLayer) {
        inputMaskLayer.addEventListener('change', function() {
            const obj = canvas.getActiveObject();
            if(obj && obj.type === 'image') {
                obj.set('mask_layer_id', this.value || null);
                snapImageToMask(obj, this.value);
                applyVisualMaskPreview(obj, this.value);
                saveHistory();
            }
        });
    }

    let isPickingMaskFor = null;

    if (btnPickMask) {
        btnPickMask.addEventListener('click', function() {
            const obj = canvas.getActiveObject();
            if (obj && obj.type === 'image') {
                isPickingMaskFor = obj;
                canvas.defaultCursor = 'crosshair';
                canvas.discardActiveObject();
                canvas.requestRenderAll();
                
                // Show a quick visual alert
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fa-solid fa-hand-pointer"></i> Click a shape...';
                this.classList.replace('btn-outline-primary', 'btn-warning');
                
                // Reset button if they cancel (click empty space handled in mouse:down)
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.classList.replace('btn-warning', 'btn-outline-primary');
                }, 4000);
            }
        });
    }
    
    // Canvas mouse down for picking mode
    canvas.on('mouse:down', function(options) {
        if (isPickingMaskFor) {
            const target = options.target;
            
            // If they clicked an object and it's not the image itself
            if (target && target !== isPickingMaskFor) {
                const imgToMask = isPickingMaskFor; // capture reference before it gets cleared
                const newMaskId = target.id || target.customName;
                imgToMask.set('mask_layer_id', newMaskId);
                
                // Snap the image to the selected mask and apply visual preview
                snapImageToMask(imgToMask, newMaskId);
                applyVisualMaskPreview(imgToMask, newMaskId);
                
                saveHistory();
                
                // Reselect the original image
                canvas.setActiveObject(imgToMask);
            }
            
            // Reset picking mode
            isPickingMaskFor = null;
            canvas.defaultCursor = 'default';
            if (btnPickMask) {
                btnPickMask.innerHTML = '<i class="fa-solid fa-crosshairs"></i> Select Shape on Canvas';
                btnPickMask.classList.replace('btn-warning', 'btn-outline-primary');
            }
            if(!target) updatePropertiesPanel(); // if clicked empty space, update props to hide picking state
        }
    });

    if (inputText) inputText.addEventListener('input', function() {
        const obj = canvas.getActiveObject();
        if(obj && (obj.type === 'text' || obj.type === 'i-text' || obj.type === 'textbox')) {
            obj.set('text', this.value);
            canvas.renderAll();
        }
    });

    if (inputFontSize) inputFontSize.addEventListener('change', function() {
        const objs = canvas.getActiveObjects();
        if(objs.length) {
            objs.forEach(obj => {
                if(obj.type === 'text' || obj.type === 'i-text' || obj.type === 'textbox') {
                    obj.set('fontSize', parseInt(this.value));
                }
            });
            canvas.renderAll();
            saveHistory();
        }
    });

    if (inputColor) inputColor.addEventListener('input', function() {
        const objs = canvas.getActiveObjects();
        if(objs.length) {
            objs.forEach(obj => {
                if(obj.type === 'text' || obj.type === 'i-text' || obj.type === 'textbox') {
                    obj.set('fill', this.value);
                }
            });
            canvas.renderAll();
        }
    });

    // Spacing & Auto Scale
    function bindChange(el, propName, isFloat = false) {
        if (!el) return;
        el.addEventListener('input', function() {
            const obj = canvas.getActiveObject();
            if (!obj) return;
            if (this.type === 'checkbox') { 
                obj.set(propName, this.checked); 
            } else if (this.type === 'number') { 
                let val = isFloat ? parseFloat(this.value) : parseInt(this.value);
                if (isNaN(val)) val = 0;
                obj.set(propName, val); 
            } else { 
                obj.set(propName, this.value || null); 
            }
            canvas.renderAll();
            saveHistory();
        });
    }
    bindChange(inputLetterSpacing, 'charSpacing', true);
    bindChange(inputWordSpacing, 'wordSpacing', true);
    bindChange(inputLineHeight, 'lineHeight', true);
    
    // Auto-scale uses checkbox so 'isFloat' doesn't matter
    if (inputAiAutoscale) {
        inputAiAutoscale.addEventListener('change', function() {
            const obj = canvas.getActiveObject();
            if (!obj) return;
            obj.set('auto_scale', this.checked);
            canvas.renderAll();
            saveHistory();
        });
    }

    if (inputFontFamily) inputFontFamily.addEventListener('change', function() {
        const objs = canvas.getActiveObjects();
        if(objs.length) {
            const fontVal = this.value;
            let fontsToLoad = [];
            
            objs.forEach(obj => {
                if(obj.type === 'text' || obj.type === 'i-text' || obj.type === 'textbox') {
                    obj.set('fontFamily', fontVal);
                    let weight = obj.fontWeight || 'normal';
                    let style = obj.fontStyle || 'normal';
                    fontsToLoad.push(`${style} ${weight} 1em "${fontVal}"`);
                }
            });
            
            if (document.fonts && document.fonts.load && fontsToLoad.length > 0) {
                Promise.all(fontsToLoad.map(f => document.fonts.load(f))).then(function() {
                    canvas.renderAll();
                });
            }
            
            canvas.renderAll();
            saveHistory();
        }
    });

    // Bold toggle
    if (btnBold) btnBold.addEventListener('click', function() {
        const obj = canvas.getActiveObject();
        if(obj && (obj.type === 'text' || obj.type === 'i-text' || obj.type === 'textbox')) {
            var isBold = obj.fontWeight === 'bold' || obj.fontWeight === 700 || obj.fontWeight === '700';
            const newWeight = isBold ? 'normal' : 'bold';
            obj.set('fontWeight', newWeight);
            this.classList.replace(isBold ? 'btn-secondary' : 'btn-outline-secondary', isBold ? 'btn-outline-secondary' : 'btn-secondary');
            
            if (document.fonts && document.fonts.load && obj.fontFamily) {
                let style = obj.fontStyle || 'normal';
                document.fonts.load(`${style} ${newWeight} 1em "${obj.fontFamily}"`).then(function() {
                    canvas.renderAll();
                });
            }
            
            canvas.renderAll();
            saveHistory();
        }
    });

    // Italic toggle
    if (btnItalic) btnItalic.addEventListener('click', function() {
        const obj = canvas.getActiveObject();
        if(obj && (obj.type === 'text' || obj.type === 'i-text' || obj.type === 'textbox')) {
            var isItalic = obj.fontStyle === 'italic';
            const newStyle = isItalic ? 'normal' : 'italic';
            obj.set('fontStyle', newStyle);
            this.classList.replace(isItalic ? 'btn-secondary' : 'btn-outline-secondary', isItalic ? 'btn-outline-secondary' : 'btn-secondary');
            
            if (document.fonts && document.fonts.load && obj.fontFamily) {
                let weight = obj.fontWeight || 'normal';
                document.fonts.load(`${newStyle} ${weight} 1em "${obj.fontFamily}"`).then(function() {
                    canvas.renderAll();
                });
            }
            
            canvas.renderAll();
            saveHistory();
        }
    });

    btnTextAlign.forEach(btn => {
        btn.addEventListener('click', function() {
            let obj = canvas.getActiveObject();
            if(obj && (obj.type === 'text' || obj.type === 'i-text' || obj.type === 'textbox')) {
                obj = ensureTextbox(obj, 'align_' + this.dataset.align);
                obj.set('textAlign', this.dataset.align);
                btnTextAlign.forEach(b => b.classList.replace('btn-secondary', 'btn-outline-secondary'));
                this.classList.replace('btn-outline-secondary', 'btn-secondary');
                canvas.renderAll();
                saveHistory();
            }
        });
    });

    // Opacity
    if (inputOpacity) inputOpacity.addEventListener('input', function() {
        const obj = canvas.getActiveObject();
        if(obj) {
            obj.set('opacity', parseFloat(this.value));
            if (textOpacityVal) textOpacityVal.innerText = Math.round(this.value * 100);
            canvas.renderAll();
        }
    });

    // Shadow
    function updateShadow() {
        const obj = canvas.getActiveObject();
        if(!obj || !inputHasShadow) return;
        if(inputHasShadow.checked) {
            if (shadowPropsDiv) shadowPropsDiv.style.display = 'block';
            obj.set('shadow', new fabric.Shadow({
                color: inputShadowColor ? inputShadowColor.value : '#000000',
                blur: inputShadowBlur ? (parseInt(inputShadowBlur.value) || 5) : 5,
                offsetX: inputShadowX ? (parseInt(inputShadowX.value) || 2) : 2,
                offsetY: inputShadowY ? (parseInt(inputShadowY.value) || 2) : 2
            }));
        } else {
            if (shadowPropsDiv) shadowPropsDiv.style.display = 'none';
            obj.set('shadow', null);
        }
        canvas.renderAll();
    }
    if (inputHasShadow) inputHasShadow.addEventListener('change', updateShadow);
    [inputShadowBlur, inputShadowColor, inputShadowX, inputShadowY].forEach(i => { if(i) i.addEventListener('input', updateShadow); });

    // --- Shape Properties ---
    function updateShapeGradient() {
        const obj = canvas.getActiveObject();
        if(!obj || obj.customType !== 'shape') return;
        
        if (inputShapeGradient && inputShapeGradient.checked) {
            if (shapeGradientProps) shapeGradientProps.style.display = 'block';
            const c1 = inputGradColor1 ? inputGradColor1.value : '#6366f1';
            const c2 = inputGradColor2 ? inputGradColor2.value : '#ffffff';
            const o1 = inputGradOp1 ? parseFloat(inputGradOp1.value) : 1;
            const o2 = inputGradOp2 ? parseFloat(inputGradOp2.value) : 1;
            
            let dir = 'top-bottom';
            if (btnGradDirs) {
                const activeBtn = Array.from(btnGradDirs).find(b => b.classList.contains('btn-secondary'));
                if (activeBtn) dir = activeBtn.dataset.dir;
            }
            
            let coords = { x1: 0, y1: 0, x2: 0, y2: obj.height };
            if (dir === 'left-right') coords = { x1: 0, y1: 0, x2: obj.width, y2: 0 };
            else if (dir === 'right-left') coords = { x1: obj.width, y1: 0, x2: 0, y2: 0 };
            else if (dir === 'bottom-top') coords = { x1: 0, y1: obj.height, x2: 0, y2: 0 };
            
            const color1 = new fabric.Color(c1).setAlpha(o1).toRgba();
            const color2 = new fabric.Color(c2).setAlpha(o2).toRgba();
            
            obj.set('fill', new fabric.Gradient({
                type: 'linear',
                coords: coords,
                colorStops: [
                    { offset: 0, color: color1 },
                    { offset: 1, color: color2 }
                ]
            }));
            
            if (textGradOp1Val) textGradOp1Val.innerText = Math.round(o1 * 100);
            if (textGradOp2Val) textGradOp2Val.innerText = Math.round(o2 * 100);
        } else {
            if (shapeGradientProps) shapeGradientProps.style.display = 'none';
            obj.set('fill', inputFillColor ? inputFillColor.value : '#6366f1');
        }
        canvas.renderAll();
    }
    
    if (inputShapeGradient) inputShapeGradient.addEventListener('change', function() { updateShapeGradient(); saveHistory(); });
    
    [inputGradColor1, inputGradColor2, inputGradOp1, inputGradOp2].forEach(i => {
        if(i) {
            i.addEventListener('input', updateShapeGradient);
            i.addEventListener('change', saveHistory);
        }
    });
    
    if (btnGradDirs) {
        btnGradDirs.forEach(btn => {
            btn.addEventListener('click', function() {
                btnGradDirs.forEach(b => b.classList.replace('btn-secondary', 'btn-outline-secondary'));
                this.classList.replace('btn-outline-secondary', 'btn-secondary');
                updateShapeGradient();
                saveHistory();
            });
        });
    }

    if (inputFillColor) inputFillColor.addEventListener('input', function() {
        if (inputShapeGradient && inputShapeGradient.checked) return; // Don't apply solid color if gradient is on
        const objs = canvas.getActiveObjects();
        if (objs.length) {
            objs.forEach(obj => {
                if (obj.customType === 'shape' || obj.is_shape || ['rect','circle','triangle','path','polygon','line','ellipse', 'group'].includes(obj.type)) { 
                    if (obj.fill === this.value && obj.type !== 'group') return; // Prevent infinite loop
                    obj.set('fill', this.value); 
                    
                    if (obj.type === 'group' && typeof obj.getObjects === 'function') {
                        obj.getObjects().forEach(child => {
                            if (child.set) child.set('fill', this.value);
                        });
                    }
                    
                    if (obj.type === 'image' && typeof fabric.Image.filters.BlendColor !== 'undefined') {
                        obj.filters = [new fabric.Image.filters.BlendColor({
                            color: this.value,
                            mode: 'tint',
                            alpha: 1
                        })];
                        obj.applyFilters();
                    }
                }
            });
            canvas.renderAll(); 
        }
    });

    if (inputStrokeColor) inputStrokeColor.addEventListener('input', function() {
        const objs = canvas.getActiveObjects();
        if (objs.length) {
            objs.forEach(obj => {
                if (obj.customType === 'shape' || obj.is_shape || ['rect','circle','triangle','path','polygon','line','ellipse', 'group'].includes(obj.type)) {
                    if (obj.stroke === this.value && obj.type !== 'group') return; // Prevent infinite loop
                    obj.set('stroke', this.value); 
                    
                    if (obj.type === 'group' && typeof obj.getObjects === 'function') {
                        obj.getObjects().forEach(child => {
                            if (child.set) child.set('stroke', this.value);
                        });
                    }
                }
            });
            canvas.renderAll();
        }
    });
    if (inputFillColor) inputFillColor.addEventListener('change', saveHistory);

    if (inputStrokeWidth) inputStrokeWidth.addEventListener('change', function() {
        const obj = canvas.getActiveObject();
        if (obj && (obj.customType === 'shape' || obj.type === 'group')) { 
            const val = parseInt(this.value) || 0;
            obj.set('strokeWidth', val); 
            
            if (obj.type === 'group' && typeof obj.getObjects === 'function') {
                obj.getObjects().forEach(child => {
                    if (child.set) child.set('strokeWidth', val);
                });
            }
            
            canvas.renderAll(); 
            saveHistory(); 
        }
    });
    if (inputBorderRadius) inputBorderRadius.addEventListener('change', function() {
        const obj = canvas.getActiveObject();
        if (obj && obj.type === 'rect') { 
            let val = parseInt(this.value) || 0;
            obj.set({ rx: val, ry: val, rx_tl: val, rx_tr: val, rx_br: val, rx_bl: val }); 
            canvas.renderAll(); saveHistory(); 
        }
    });
    if (btnRadiusLock) {
        btnRadiusLock.addEventListener('click', function() {
            isRadiusLocked = !isRadiusLocked;
            const icon = this.querySelector('i');
            if (icon) icon.className = isRadiusLocked ? 'fa-solid fa-lock' : 'fa-solid fa-lock-open';
            this.classList.toggle('btn-outline-secondary', !isRadiusLocked);
            this.classList.toggle('btn-primary', isRadiusLocked);
            this.title = isRadiusLocked ? 'Lock Uniform Radius (Click to Unlock)' : 'Unlock Independent Corners (Click to Lock)';
        });
    }
    const updateCornerRadius = function(sourceInput) {
        const obj = canvas.getActiveObject();
        if (!obj || obj.type !== 'rect') return;
        if (isRadiusLocked) {
            let val = parseInt(sourceInput.value) || 0;
            if (inputRadiusTL) inputRadiusTL.value = val;
            if (inputRadiusTR) inputRadiusTR.value = val;
            if (inputRadiusBR) inputRadiusBR.value = val;
            if (inputRadiusBL) inputRadiusBL.value = val;
            if (inputBorderRadius) inputBorderRadius.value = val;
            obj.set({ rx: val, ry: val, rx_tl: val, rx_tr: val, rx_br: val, rx_bl: val });
            obj.dirty = true;
        } else {
            const tl = parseInt(inputRadiusTL ? inputRadiusTL.value : 0) || 0;
            const tr = parseInt(inputRadiusTR ? inputRadiusTR.value : 0) || 0;
            const br = parseInt(inputRadiusBR ? inputRadiusBR.value : 0) || 0;
            const bl = parseInt(inputRadiusBL ? inputRadiusBL.value : 0) || 0;
            obj.set({ rx: 0, ry: 0, rx_tl: tl, rx_tr: tr, rx_br: br, rx_bl: bl });
            obj.dirty = true;
        }
        canvas.renderAll();
        saveHistory();
    };
    [inputRadiusTL, inputRadiusTR, inputRadiusBR, inputRadiusBL].forEach(inp => {
        if (inp) {
            inp.addEventListener('input', function() { updateCornerRadius(this); });
            inp.addEventListener('change', function() { updateCornerRadius(this); });
        }
    });

    // Image events
    if (inputIsBackground) inputIsBackground.addEventListener('change', function() {
        const obj = canvas.getActiveObject();
        if(obj && obj.type === 'image') {
            obj.set('is_background', this.checked);
            canvas.renderAll();
            updateLayersList();
        }
    });
    if (inputIsPlaceholder) inputIsPlaceholder.addEventListener('change', function() {
        const obj = canvas.getActiveObject();
        if (obj && obj.type === 'image') {
            obj.set('is_placeholder', this.checked);
            canvas.renderAll();
        }
    });

    if (inputIsColorizableShape) inputIsColorizableShape.addEventListener('change', function() {
        const objs = canvas.getActiveObjects();
        const isChecked = this.checked;
        if (objs.length) {
            let filtersChanged = false;
            objs.forEach(obj => {
                if (obj.type === 'image') {
                    obj.is_shape = isChecked;
                    obj.customType = isChecked ? 'shape' : 'image';
                    
                    if (isChecked && obj._element && (!obj.filters || !obj.filters.some(f => f && f.type === 'BlendColor'))) {
                        try {
                            const tempCanvas = document.createElement('canvas');
                            tempCanvas.width = 1; tempCanvas.height = 1;
                            const ctx = tempCanvas.getContext('2d');
                            ctx.drawImage(obj._element, 0, 0, 1, 1);
                            const data = ctx.getImageData(0, 0, 1, 1).data;
                            if (data[3] > 0) { 
                                const hex = '#' + [data[0], data[1], data[2]].map(x => x.toString(16).padStart(2, '0')).join('');
                                obj.set('fill', hex);
                            } else {
                                obj.set('fill', '#6366f1');
                            }
                        } catch(e) { 
                            obj.set('fill', '#6366f1');
                            console.warn('Could not guess color:', e); 
                        }
                    } else if (!isChecked && obj.filters) {
                        const originalLength = obj.filters.length;
                        obj.filters = obj.filters.filter(f => !(f && f.type === 'BlendColor'));
                        if (obj.filters.length !== originalLength) {
                            obj.applyFilters();
                            filtersChanged = true;
                        }
                    }
                }
            });
            if (filtersChanged) canvas.renderAll();
            updateProps();
            saveHistory();
        }
    });

    if (inputIsLogo) inputIsLogo.addEventListener('change', function() {
        const obj = canvas.getActiveObject();
        if (obj && obj.type === 'image') {
            obj.set('is_logo', this.checked);
            if (this.checked) {
                obj.set('customName', 'logo');
            } else if (obj.customName === 'logo') {
                obj.set('customName', 'layer_' + Date.now().toString().substr(-4));
            }
            canvas.renderAll();
            updateLayersList();
        }
    });

    // Delete
    const deleteBtn = $('delete-element');
    if (deleteBtn) deleteBtn.addEventListener('click', function() {
        const obj = canvas.getActiveObject();
        if(obj) { canvas.remove(obj); canvas.discardActiveObject(); updateLayersList(); saveHistory(); }
    });

    // --- ADD TEXT ---
    const addTextBtn = $('add-text');
    if (addTextBtn) addTextBtn.addEventListener('click', function() {
        const text = new fabric.Textbox('Double click to edit', {
            left: 100, top: 100, fontSize: 60, fill: '#000000', fontFamily: 'Arial', customType: 'text',
            textBaseline: 'alphabetic'
        });
        canvas.add(text);
        canvas.setActiveObject(text);
        updateLayersList();
    });

    // --- ADD PLACEHOLDER ---
    const addPlaceholderBtn = $('add-placeholder');
    if (addPlaceholderBtn) addPlaceholderBtn.addEventListener('click', function() {
        const selectEl = $('placeholder-select');
        if (!selectEl) return;
        const val = selectEl.value;
        
        if (val === 'logo') {
            const tmpCanvas = document.createElement('canvas');
            tmpCanvas.width = 200;
            tmpCanvas.height = 200;
            const ctx = tmpCanvas.getContext('2d');
            ctx.fillStyle = '#6366f1';
            ctx.fillRect(0, 0, 200, 200);
            ctx.fillStyle = '#ffffff';
            ctx.beginPath();
            ctx.arc(100, 75, 35, 0, Math.PI * 2);
            ctx.fill();
            ctx.beginPath();
            ctx.arc(100, 170, 55, Math.PI, Math.PI * 2);
            ctx.fill();
            ctx.font = 'bold 26px Arial';
            ctx.textAlign = 'center';
            ctx.fillText('LOGO', 100, 185);
            
            const logoDataUrl = tmpCanvas.toDataURL('image/png');
            fabric.Image.fromURL(logoDataUrl, function(img) {
                img.set({
                    left: 100, top: 100, width: 200, height: 200,
                    scaleX: 0.6, scaleY: 0.6,
                    customType: 'image', customName: 'logo', is_logo: true
                });
                canvas.add(img);
                canvas.setActiveObject(img);
                updateLayersList();
                saveHistory();
            });
            return;
        }

        let displayStr = '{{' + val + '}}';
        if (val === 'phone_1') displayStr = '+91 9876543210';
        else if (val === 'email') displayStr = 'example@email.com';
        else if (val === 'website') displayStr = 'www.yourwebsite.com';
        else if (val === 'address') displayStr = 'Your Business Address Here';
        else if (val === 'name') displayStr = 'Your Business Name';

        const text = new fabric.IText(displayStr, {
            left: 100, top: 200, fontSize: 30, fill: '#000000', fontFamily: 'Arial',
            textBaseline: 'alphabetic',
            customType: 'placeholder', placeholderKey: val, customName: val, ai_field: val, ai_semantic_role: 'body_text'
        });
        canvas.add(text);
        canvas.setActiveObject(text);
        updateLayersList();
        saveHistory();
    });

    // --- DUPLICATE PLACEHOLDER / LAYER ---
    const duplicatePlaceholderBtn = $('duplicate-placeholder');
    if (duplicatePlaceholderBtn) duplicatePlaceholderBtn.addEventListener('click', function() {
        let targetObj = canvas.getActiveObject();
        if (!targetObj) {
            const selectEl = $('placeholder-select');
            if (selectEl) {
                const searchVal = selectEl.value;
                targetObj = canvas.getObjects().find(o => (o.customName || o.placeholderKey) === searchVal || (o.customName || '').startsWith(searchVal.replace(/_\d+$/, '')));
            }
        }
        if (!targetObj) {
            alert('Please select a layer on the canvas to duplicate!');
            return;
        }

        let baseName = (targetObj.customName || targetObj.placeholderKey || 'layer').replace(/_\d+$/, '');
        let newNum = 1;
        const existingNames = canvas.getObjects().map(o => o.customName || o.placeholderKey || '');
        while (existingNames.includes(baseName + (newNum === 1 && !baseName.includes('phone') ? '' : '_' + newNum))) {
            newNum++;
        }
        let newName = baseName + '_' + newNum;

        targetObj.clone(function(clonedObj) {
            canvas.discardActiveObject();
            clonedObj.set({
                left: (targetObj.left || 100) + 20,
                top: (targetObj.top || 200) + 30,
                customName: newName,
                placeholderKey: newName,
                ai_field: newName,
                evented: true
            });
            if (clonedObj.text && clonedObj.text.startsWith('{{') && clonedObj.text.endsWith('}}')) {
                clonedObj.set('text', '{{' + newName + '}}');
            }
            if (clonedObj.type === 'activeSelection') {
                clonedObj.canvas = canvas;
                clonedObj.forEachObject(function(obj) { canvas.add(obj); });
                clonedObj.setCoords();
            } else {
                canvas.add(clonedObj);
            }
            canvas.setActiveObject(clonedObj);
            canvas.requestRenderAll();
            updateLayersList();
            saveHistory();
        }, customAttrs);
    });

    // --- ADD SHAPES ---
    function addShape(type) {
        let shape;
        const defaultProps = { left: 150, top: 150, fill: '#6366f1', stroke: '#4f46e5', strokeWidth: 0, customType: 'shape', opacity: 1 };

        switch(type) {
            case 'rect':
                shape = new fabric.Rect({ ...defaultProps, width: 200, height: 150, rx: 0, ry: 0 });
                break;
            case 'circle':
                shape = new fabric.Circle({ ...defaultProps, radius: 100 });
                break;
            case 'triangle':
                shape = new fabric.Triangle({ ...defaultProps, width: 200, height: 180 });
                break;
            case 'line':
                // Using Rect for line to ensure it has a physical height (5px) while staying at 0 degrees.
                shape = new fabric.Rect({ left: 50, top: 50, width: 300, height: 5, fill: '#6366f1', customType: 'shape' });
                break;
            case 'star':
                // Star as polygon
                const points = [];
                const outerR = 100, innerR = 45, spikes = 5;
                for (let i = 0; i < spikes * 2; i++) {
                    const r = i % 2 === 0 ? outerR : innerR;
                    const angle = (Math.PI / spikes) * i - Math.PI / 2;
                    points.push({ x: Math.cos(angle) * r + outerR, y: Math.sin(angle) * r + outerR });
                }
                shape = new fabric.Polygon(points, { ...defaultProps, fill: '#f59e0b', stroke: '#d97706' });
                break;
        }
        if (shape) {
            canvas.add(shape);
            canvas.setActiveObject(shape);
            updateLayersList();
            saveHistory();
        }
    }

    ['rect', 'circle', 'triangle', 'line', 'star'].forEach(type => {
        const btn = $('add-' + type);
        if (btn) btn.addEventListener('click', () => addShape(type));
    });

    // --- SVG UPLOAD ---
    const svgUploadInput = $('svg-upload');
    if (svgUploadInput) {
        svgUploadInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function(f) {
                const svgString = f.target.result;
                fabric.loadSVGFromString(svgString, function(objects, options) {
                    const obj = fabric.util.groupSVGElements(objects, options);
                    obj.set({
                        left: 150,
                        top: 150,
                        customType: 'shape',
                        customName: 'Custom Shape'
                    });
                    
                    // Automatically color all internal paths to black by default for easier recoloring,
                    // unless we want to keep original colors. But for shapes, solid tint_color is usually desired.
                    if (obj.isSameColor && obj.isSameColor() || obj.paths) {
                         obj.set({ fill: '#000000' });
                    }
                    
                    // Save to custom shapes library
                    try {
                        let customShapes = JSON.parse(localStorage.getItem('artera_custom_shapes') || '[]');
                        // Add to beginning of array
                        customShapes.unshift({
                            name: file.name.replace('.svg', ''),
                            data: svgString,
                            id: 'cs_' + Date.now()
                        });
                        // Keep only last 20 custom shapes to avoid quota issues
                        if (customShapes.length > 20) customShapes = customShapes.slice(0, 20);
                        localStorage.setItem('artera_custom_shapes', JSON.stringify(customShapes));
                        
                        // If library modal is open, reload it
                        const customContainer = $('custom-svg-container');
                        if (customContainer && customContainer.children.length > 1) { // more than just empty state
                            customContainer.innerHTML = '<div class="col-12 text-center text-muted py-4" id="custom-svg-empty"><i class="fa-solid fa-cloud-arrow-up fa-3x mb-3 text-light"></i><p>No custom SVGs uploaded yet.<br>Click "Upload Custom SVG" from the sidebar to add shapes here.</p></div>';
                            loadCustomSvgLibrary();
                        }
                    } catch(e) {
                        console.error('Failed to save custom shape:', e);
                    }
                    
                    canvas.add(obj);
                    canvas.setActiveObject(obj);
                    updateLayersList();
                    saveHistory();
                });
            };
            reader.readAsText(file);
            svgUploadInput.value = ''; // Reset
        });
    }

    // --- SVG LIBRARY ---
    const btnSvgLibrary = $('btn-svg-library');
    if (btnSvgLibrary) {
        btnSvgLibrary.addEventListener('click', function() {
            window.jQuery('#svgLibraryModal').modal('show');
            loadCustomSvgLibrary();
        });
    }

    function loadCustomSvgLibrary() {
        const container = $('custom-svg-container');
        const emptyState = $('custom-svg-empty');
        if (!container) return;
        
        let customShapes = [];
        try {
            customShapes = JSON.parse(localStorage.getItem('artera_custom_shapes') || '[]');
        } catch(e) {}
        
        // Remove existing shapes (skip empty state)
        Array.from(container.children).forEach(child => {
            if (child.id !== 'custom-svg-empty') child.remove();
        });
        
        if (customShapes.length === 0) {
            if (emptyState) emptyState.style.display = 'block';
            return;
        }
        
        if (emptyState) emptyState.style.display = 'none';
        
        customShapes.forEach((shape, index) => {
            const item = document.createElement('div');
            item.className = 'text-center position-relative';
            item.style.cursor = 'pointer';
            
            // Render SVG string as data URI
            const encodedData = 'data:image/svg+xml;base64,' + btoa(unescape(encodeURIComponent(shape.data)));
            
            item.innerHTML = '<div style="border:1px solid #f1f5f9; border-radius:12px; padding:5px 15px; transition: all 0.25s ease; background:#f8fafc; min-height:60px; display:flex; align-items:center; justify-content:center; position:relative;" ' +
                'onmouseover="this.style.borderColor=\'#6366f1\'; this.style.boxShadow=\'0 4px 12px rgba(99,102,241,0.15)\'; this.style.transform=\'translateY(-2px)\'; this.querySelector(\'.delete-shape\').style.display=\'flex\';" ' +
                'onmouseout="this.style.borderColor=\'#f1f5f9\'; this.style.boxShadow=\'none\'; this.style.transform=\'none\'; this.querySelector(\'.delete-shape\').style.display=\'none\';">' +
                '<button class="delete-shape btn btn-danger position-absolute" style="display:none; align-items:center; justify-content:center; top:-8px; right:-8px; width:22px; height:22px; padding:0; border-radius:50%; z-index:10; box-shadow:0 2px 4px rgba(239,68,68,0.3); border:2px solid #fff;"><i class="fa-solid fa-times" style="font-size:10px;"></i></button>' +
                '<img src="' + encodedData + '" style="width:100%; height:70px; object-fit:contain;">' +
                '</div><div class="small mt-2 text-truncate" style="font-weight:600; font-size:0.8rem; color:#475569;" title="'+shape.name+'">' + shape.name + '</div>';
            
            // Delete handler
            item.querySelector('.delete-shape').addEventListener('click', function(e) {
                e.stopPropagation();
                customShapes.splice(index, 1);
                localStorage.setItem('artera_custom_shapes', JSON.stringify(customShapes));
                loadCustomSvgLibrary(); // Refresh
            });
            
            item.addEventListener('click', function() {
                fabric.loadSVGFromString(shape.data, function(objects, options) {
                    if (!objects || objects.length === 0) return;
                    const obj = fabric.util.groupSVGElements(objects, options);
                    obj.set({
                        left: 150,
                        top: 150,
                        customType: 'shape',
                        customName: shape.name
                    });
                    if (obj.isSameColor && obj.isSameColor() || obj.paths) {
                         obj.set({ fill: '#000000' });
                    }
                    canvas.add(obj);
                    canvas.setActiveObject(obj);
                    updateLayersList();
                    saveHistory();
                    window.jQuery('#svgLibraryModal').modal('hide');
                });
            });
            container.appendChild(item);
        });
    }

    // --- ADD ICONS (Dynamic via Iconify API) ---
    const iconsGrid = $('icons-grid');
    const iconSearch = $('icon-search');

    if (iconsGrid) {
        // Initial popular icons to show before search
        const initialIcons = ['mdi-light:home', 'mdi-light:star', 'mdi-light:heart', 'mdi-light:account', 'mdi-light:phone', 'mdi-light:email', 'mdi-light:map-marker', 'mdi-light:camera', 'mdi-light:magnify', 'mdi-light:bell', 'mdi-light:cog'];
        
        function renderIcons(icons) {
            iconsGrid.innerHTML = '';
            icons.forEach(iconName => {
                const item = document.createElement('div');
                item.className = 'icon-item';
                item.title = iconName;
                item.style.display = 'flex';
                // Fetch the SVG directly from Iconify API for rendering
                const svgUrl = `https://api.iconify.design/${iconName.replace(':', '/')}.svg?color=%23475569`;
                item.innerHTML = `<img src="${svgUrl}" style="width:24px; height:24px;" alt="${iconName}">`;
                
                item.addEventListener('click', function() {
                    const addSvgUrl = `https://api.iconify.design/${iconName.replace(':', '/')}.svg?color=%23333333`;
                    // Fetch SVG as text first, then use loadSVGFromString to avoid CORS tainted canvas
                    fetch(addSvgUrl)
                        .then(function(response) { return response.text(); })
                        .then(function(svgText) {
                            fabric.loadSVGFromString(svgText, function(objects, options) {
                                if (!objects || objects.length === 0) {
                                    console.error('[ICON] Failed to parse SVG for:', iconName);
                                    return;
                                }
                                const obj = fabric.util.groupSVGElements(objects, options);
                                obj.set({
                                    left: 150,
                                    top: 150,
                                    scaleX: 2,
                                    scaleY: 2,
                                    customType: 'icon',
                                    customName: 'Icon',
                                    fill: '#333333'
                                });
                                // ── Non-Destructive Metadata (Phase 1A) ──
                                obj._iconName = iconName;
                                obj._iconProvider = 'iconify';
                                obj._originalSvgMarkup = svgText;
                                canvas.add(obj);
                                canvas.setActiveObject(obj);
                                updateLayersList();
                                saveHistory();
                                console.log('[ICON] Added icon:', iconName, 'type:', obj.type);
                            });
                        })
                        .catch(function(err) {
                            console.error('[ICON] Failed to fetch SVG:', iconName, err);
                        });
                });
                iconsGrid.appendChild(item);
            });
        }
        
        // Render initial icons
        renderIcons(initialIcons);

        let debounceTimer;
        iconSearch.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const query = this.value.trim();
            if (query.length < 2) {
                if (query.length === 0) {
                    // Reset to initial
                    const initialIcons = ['mdi-light:home', 'mdi-light:star', 'mdi-light:heart', 'mdi-light:account', 'mdi-light:phone', 'mdi-light:email', 'mdi-light:map-marker', 'mdi-light:camera', 'mdi-light:magnify', 'mdi-light:bell', 'mdi-light:cog'];
                    if (typeof renderIcons === 'function') renderIcons(initialIcons);
                }
                return;
            }
            
            debounceTimer = setTimeout(() => {
                iconsGrid.innerHTML = '<div style="width:100%; text-align:center; padding:10px;"><i class="fa fa-spinner fa-spin"></i></div>';
                fetch(`https://api.iconify.design/search?query=${encodeURIComponent(query)}&limit=30`)
                    .then(res => res.json())
                    .then(data => {
                        if (data && data.icons && typeof renderIcons === 'function') {
                            renderIcons(data.icons);
                        } else {
                            iconsGrid.innerHTML = '<div style="width:100%; text-align:center; font-size:12px; color:#888;">No icons found</div>';
                        }
                    }).catch(err => {
                        console.error('Iconify API error', err);
                        iconsGrid.innerHTML = '<div style="width:100%; text-align:center; font-size:12px; color:red;">Error fetching icons</div>';
                    });
            }, 500);
        });
    }

    // --- IMAGE UPLOAD ---
    const imgUpload = $('image-upload');
    if (imgUpload) imgUpload.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if(!file) return;
        const reader = new FileReader();
        reader.onload = function(f) {
            fabric.Image.fromURL(f.target.result, function(img) {
                img.set({ left: 100, top: 100, customType: 'image', customName: 'new_image_' + Date.now() });
                img.scaleToWidth(300);
                canvas.add(img);
                canvas.setActiveObject(img);
                updateLayersList();
            });
        };
        reader.readAsDataURL(file);
        this.value = '';
    });

    // --- LAYERS LIST (with thumbnails + drag-drop reorder) ---
    var _dragSrcIndex = null;

    function updateLayersList() {
        const list = $('layers-list');
        if (!list) return;
        list.innerHTML = '';
        const objects = canvas.getObjects().slice().reverse(); // top layer first

        objects.forEach(function(obj, displayIdx) {
            // Exclude node editor UI elements
            if (obj._isNodeHandle || obj._isNodeGuide) return;

            var li = document.createElement('li');
            li.className = 'aim-list-item';
            li.setAttribute('draggable', 'true');
            li.dataset.displayIdx = displayIdx;
            li.style.cssText = 'display:flex;align-items:center;gap:6px;padding:4px 6px;cursor:grab;transition:background 0.15s,border-color 0.15s;';
            if (canvas.getActiveObject() === obj) {
                li.style.borderColor = '#6366f1';
                li.style.background = '#eef2ff';
            }

            // --- Thumbnail ---
            var thumbWrap = document.createElement('div');
            thumbWrap.style.cssText = 'width:36px;height:36px;border-radius:4px;overflow:hidden;background:#f1f5f9;border:1px solid #e2e8f0;flex-shrink:0;display:flex;align-items:center;justify-content:center;';
            try {
                var thumbCanvas = document.createElement('canvas');
                thumbCanvas.width = 36; thumbCanvas.height = 36;
                var tCtx = thumbCanvas.getContext('2d');
                // Clone and render just this object for thumbnail
                var br = obj.getBoundingRect();
                var scale = Math.min(32 / (br.width || 1), 32 / (br.height || 1), 1);
                tCtx.save();
                tCtx.translate(18, 18);
                tCtx.scale(scale, scale);
                tCtx.translate(-(br.left + br.width / 2), -(br.top + br.height / 2));
                // Use fabric's toCanvasElement for accurate rendering
                var elCanvas = obj.toCanvasElement({ multiplier: 0.15 });
                if (elCanvas && elCanvas.width > 0) {
                    tCtx.drawImage(elCanvas, br.left, br.top, br.width, br.height);
                }
                tCtx.restore();
                var thumbImg = document.createElement('img');
                thumbImg.src = thumbCanvas.toDataURL();
                thumbImg.style.cssText = 'max-width:100%;max-height:100%;object-fit:contain;';
                thumbWrap.appendChild(thumbImg);
            } catch(e) {
                // Fallback: show type icon if thumbnail fails
                var fallbackIcon = document.createElement('i');
                fallbackIcon.className = 'fa fa-' + (obj.type === 'image' ? 'image' : obj.type === 'text' || obj.type === 'i-text' || obj.type === 'textbox' ? 'font' : 'shapes');
                fallbackIcon.style.cssText = 'color:#94a3b8;font-size:14px;';
                thumbWrap.appendChild(fallbackIcon);
            }

            // --- Name + type badge ---
            var nameDiv = document.createElement('div');
            nameDiv.style.cssText = 'flex-grow:1;overflow:hidden;min-width:0;';

            // Type badge
            var badge = document.createElement('span');
            var badgeText = 'EL';
            var badgeColor = '#6366f1';
            if (obj.customType === 'placeholder') { badgeText = 'P'; badgeColor = '#10b981'; }
            else if (obj.customType === 'icon') { badgeText = 'IC'; badgeColor = '#f59e0b'; }
            else if (obj.customType === 'shape') { badgeText = 'S'; badgeColor = '#8b5cf6'; }
            else if (obj.type === 'text' || obj.type === 'i-text' || obj.type === 'textbox') { badgeText = 'T'; badgeColor = '#3b82f6'; }
            else if (obj.type === 'image') { badgeText = obj.is_background ? 'BG' : 'IMG'; badgeColor = obj.is_background ? '#64748b' : '#ef4444'; }
            badge.style.cssText = 'display:inline-block;font-size:0.55rem;font-weight:700;color:#fff;background:' + badgeColor + ';padding:1px 4px;border-radius:3px;margin-right:4px;vertical-align:middle;letter-spacing:0.5px;';
            badge.innerText = badgeText;

            // Layer name
            var nameSpan = document.createElement('span');
            var name = obj.customName || '';
            if (obj.customType === 'placeholder') name = obj.customName || obj.placeholderKey || 'Placeholder';
            else if (obj.customType === 'icon') name = obj.customName || 'Icon';
            else if (obj.customType === 'shape') name = obj.type;
            else if (obj.type === 'text' || obj.type === 'i-text' || obj.type === 'textbox') name = obj.text ? obj.text.substring(0, 18) : 'Text';
            else if (obj.type === 'image') name = obj.is_background ? 'Background' : (obj.customName || 'Image');
            nameSpan.innerText = name;
            nameSpan.style.cssText = 'font-size:0.7rem;color:#334155;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;vertical-align:middle;';

            nameDiv.appendChild(badge);
            nameDiv.appendChild(nameSpan);
            nameDiv.style.cursor = 'pointer';
            nameDiv.onclick = function() { canvas.setActiveObject(obj); canvas.renderAll(); updateLayersList(); };

            // --- Controls ---
            var ctrl = document.createElement('div');
            ctrl.style.cssText = 'display:flex;align-items:center;gap:2px;flex-shrink:0;';

            function makeBtn(icon, color, handler) {
                var b = document.createElement('button');
                b.className = 'btn btn-sm btn-link p-0';
                b.style.cssText = 'font-size:0.6rem;color:' + color + ';min-width:16px;';
                b.innerHTML = '<i class="fa fa-' + icon + '"></i>';
                b.onclick = handler;
                return b;
            }

            ctrl.appendChild(makeBtn(obj.visible === false ? 'eye-slash' : 'eye', obj.visible === false ? '#94a3b8' : '#6366f1', function(e) {
                e.stopPropagation(); obj.set('visible', !obj.visible); canvas.renderAll(); updateLayersList(); saveHistory();
            }));
            ctrl.appendChild(makeBtn(obj.selectable === false ? 'lock' : 'unlock', obj.selectable === false ? '#ef4444' : '#94a3b8', function(e) {
                e.stopPropagation();
                var locked = obj.selectable === false;
                obj.set({ selectable: locked, evented: locked, hasControls: locked, hasBorders: locked });
                if (!locked) canvas.discardActiveObject();
                canvas.renderAll(); updateLayersList(); saveHistory();
            }));
            ctrl.appendChild(makeBtn('arrow-up', '#64748b', function(e) { e.stopPropagation(); canvas.bringForward(obj); updateLayersList(); saveHistory(); }));
            ctrl.appendChild(makeBtn('arrow-down', '#64748b', function(e) { e.stopPropagation(); canvas.sendBackwards(obj); updateLayersList(); saveHistory(); }));
            ctrl.appendChild(makeBtn('trash', '#ef4444', function(e) { e.stopPropagation(); canvas.remove(obj); canvas.discardActiveObject(); updateLayersList(); saveHistory(); }));

            li.appendChild(thumbWrap);
            li.appendChild(nameDiv);
            li.appendChild(ctrl);

            // --- Drag & Drop ---
            li.addEventListener('dragstart', function(e) {
                _dragSrcIndex = displayIdx;
                li.style.opacity = '0.4';
                e.dataTransfer.effectAllowed = 'move';
            });
            li.addEventListener('dragend', function() {
                li.style.opacity = '1';
                // Remove all drag-over highlights
                list.querySelectorAll('.aim-list-item').forEach(function(el) { el.style.borderTop = ''; el.style.borderBottom = ''; });
            });
            li.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                var rect = li.getBoundingClientRect();
                var mid = rect.top + rect.height / 2;
                list.querySelectorAll('.aim-list-item').forEach(function(el) { el.style.borderTop = ''; el.style.borderBottom = ''; });
                if (e.clientY < mid) { li.style.borderTop = '2px solid #6366f1'; }
                else { li.style.borderBottom = '2px solid #6366f1'; }
            });
            li.addEventListener('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                li.style.borderTop = ''; li.style.borderBottom = '';
                if (_dragSrcIndex === null || _dragSrcIndex === displayIdx) return;
                // Convert display indices (reversed) to canvas z-indices
                var total = objects.length;
                var srcZIndex = total - 1 - _dragSrcIndex;
                var dstZIndex = total - 1 - displayIdx;
                var srcObj = canvas.item(srcZIndex);
                if (!srcObj) return;
                // Move object to new z-index
                canvas.moveTo(srcObj, dstZIndex);
                _dragSrcIndex = null;
                updateLayersList();
                saveHistory();
            });

            list.appendChild(li);
        });
    }

    canvas.on('selection:created', updateLayersList);
    canvas.on('selection:updated', updateLayersList);
    canvas.on('selection:cleared', updateLayersList);

    // --- Fonts & Assets API ---
    function loadFonts() {
        const baseUrl = typeof saveUrl !== 'undefined' ? saveUrl.split('/admin/')[0] : '';
        const fontsApiUrl = baseUrl + '/admin/editor/fonts';
        fetch(fontsApiUrl).then(r => r.json()).then(data => {
            if (data.success && data.data) {
                const sel = $('prop-font-family');
                if (!sel) return;
                let googleFontsToLoad = [];
                let uniqueFamilies = new Set();
                data.data.forEach(f => {
                    let displayName = f.name || f;
                    const fontInfo = normalizePSFont(displayName);
                    let baseFamily = fontInfo.family;
                    let weight = fontInfo.weight;
                    let style = fontInfo.style;
                    
                    if (!uniqueFamilies.has(baseFamily)) {
                        uniqueFamilies.add(baseFamily);
                        if (!Array.from(sel.options).some(o => o.value === baseFamily)) {
                            const opt = document.createElement('option');
                            opt.value = baseFamily; opt.innerText = baseFamily;
                            sel.appendChild(opt);
                        }
                    }

                    if (f.file_path) {
                        const styleId = 'font-' + baseFamily.replace(/\s+/g, '-').toLowerCase() + '-' + weight + '-' + style;
                        if (!document.getElementById(styleId)) {
                            let basePath = window.location.origin;
                            if (typeof apiBaseUrl !== 'undefined') {
                                basePath = apiBaseUrl.replace(/\/api$/, '');
                            } else if (window.location.pathname.includes('/Artera/')) {
                                basePath += '/Artera';
                            }
                            let fontUrl = f.file_path;
                            if (fontUrl.startsWith('uploads/')) {
                                fontUrl = basePath + '/' + fontUrl;
                            }
                            
                            const cssStyle = document.createElement('style');
                            cssStyle.id = styleId;
                            cssStyle.innerHTML = `@font-face { font-family: '${baseFamily}'; src: url('${fontUrl}'); font-weight: ${weight}; font-style: ${style}; font-display: swap; }`;
                            document.head.appendChild(cssStyle);
                        }
                    } else if (baseFamily && !['Arial', 'Times New Roman', 'Courier New', 'Verdana'].includes(baseFamily)) {
                        if (!googleFontsToLoad.includes(baseFamily)) {
                            googleFontsToLoad.push(baseFamily);
                        }
                    }
                });
                
                if (googleFontsToLoad.length > 0) {
                    const googleFontsLink = 'https://fonts.googleapis.com/css2?' + 
                        googleFontsToLoad.map(f => 'family=' + f.replace(/\s+/g, '+')).join('&') +
                        '&display=swap';
                    if (!document.querySelector(`link[href="${googleFontsLink}"]`)) {
                        const link = document.createElement('link');
                        link.href = googleFontsLink;
                        link.rel = 'stylesheet';
                        document.head.appendChild(link);
                        console.log('[FONTS] Loading Google Fonts:', googleFontsLink);
                    }
                }
            }
        }).catch(() => {});
    }
    loadFonts();

    let stickerSearchTimeout = null;
    const stickerSearchInput = $('sticker-search-input');
    if (stickerSearchInput) {
        stickerSearchInput.addEventListener('input', (e) => {
            clearTimeout(stickerSearchTimeout);
            stickerSearchTimeout = setTimeout(() => {
                loadAssets(e.target.value);
            }, 500);
        });
    }

    function loadAssets(searchQuery = '') {
        const baseUrl = typeof saveUrl !== 'undefined' ? saveUrl.split('/admin/')[0] : '';
        const searchParam = searchQuery ? `&search=${encodeURIComponent(searchQuery)}` : '';
        const assetsApiUrl = baseUrl + '/admin/template-builder/stickers?t=' + Date.now() + searchParam;
        
        const c = $('asset-library-container');
        if (!c) return;
        c.innerHTML = '<div class="small text-muted my-3"><i class="fa fa-spinner fa-spin mb-2" style="font-size: 20px;"></i><br>Loading...</div>';

        fetch(assetsApiUrl).then(r => r.json()).then(data => {
            if (data.success && data.data && data.data.length > 0) {
                c.innerHTML = '<div class="d-flex flex-wrap justify-content-center" style="gap:6px;" id="stickers-grid"></div>';
                const grid = document.getElementById('stickers-grid');
                
                let count = 0;
                data.data.forEach(cat => {
                    if (cat.stickers && cat.stickers.length > 0) {
                        cat.stickers.forEach(a => {
                            count++;
                            const img = document.createElement('img');
                            img.src = a.url;
                            img.style.cssText = 'width:50px;height:50px;object-fit:contain;cursor:pointer;border-radius:8px;border:1.5px solid #e2e8f0;padding:3px;transition:all 0.15s;';
                            img.title = cat.category_name;
                            img.onmouseover = () => { img.style.borderColor='#6366f1'; };
                            img.onmouseout = () => { img.style.borderColor='#e2e8f0'; };
                            img.onclick = () => {
                                fabric.Image.fromURL(a.url, fImg => {
                                    fImg.set({ left:100, top:100, customType:'image' });
                                    fImg.scaleToWidth(200);
                                    canvas.add(fImg); canvas.setActiveObject(fImg); updateLayersList(); saveHistory();
                                }, { crossOrigin:'anonymous' });
                            };
                            grid.appendChild(img);
                        });
                    }
                });
                
                if (count === 0) {
                    c.innerHTML = '<div class="small text-muted my-3"><i class="fa fa-inbox" style="font-size:18px;"></i><br>No stickers found</div>';
                }
            } else {
                c.innerHTML = '<div class="small text-muted my-3"><i class="fa fa-inbox" style="font-size:18px;"></i><br>No stickers found</div>';
            }
        }).catch(() => {
            if (c) c.innerHTML = '<div class="small text-muted my-3"><i class="fa fa-inbox" style="font-size:18px;"></i><br>No stickers found</div>';
        });
    }
    loadAssets();

    // --- Undo/Redo ---
    let historyStack = [];
    let historyMods = 0;
    let isUndoing = false;
    const btnUndo = $('btn-undo');
    const btnRedo = $('btn-redo');
    const customAttrs = ['customType','customName','is_background','is_placeholder','is_slot','color_group','ai_role','ai_max_chars','placeholderKey','ai_field','ai_semantic_role','ai_priority','auto_scale','ai_replaceable', 'mask_layer_id'];

    function saveHistory() {
        if (isUndoing) return;
        if (historyMods < historyStack.length) historyStack.length = historyMods;
        historyStack.push(JSON.stringify(canvas.toJSON(customAttrs)));
        if(historyStack.length > 50) historyStack.shift(); else historyMods++;
        updateUndoRedoUI();
    }

    function undo() {
        if (historyMods > 1) {
            isUndoing = true;
            canvas.clear().renderAll();
            canvas.loadFromJSON(historyStack[historyMods - 2], () => {
                canvas.renderAll(); isUndoing = false; historyMods--; updateUndoRedoUI(); updateLayersList();
            });
        }
    }

    function redo() {
        if (historyMods < historyStack.length) {
            isUndoing = true;
            canvas.clear().renderAll();
            canvas.loadFromJSON(historyStack[historyMods], () => {
                canvas.renderAll(); isUndoing = false; historyMods++; updateUndoRedoUI(); updateLayersList();
            });
        }
    }

    function updateUndoRedoUI() {
        if (btnUndo) btnUndo.disabled = historyMods <= 1;
        if (btnRedo) btnRedo.disabled = historyMods === historyStack.length;
    }

    if (btnUndo) btnUndo.addEventListener('click', undo);
    if (btnRedo) btnRedo.addEventListener('click', redo);

    canvas.on('object:added', () => { updateLayersList(); saveHistory(); });
    canvas.on('object:modified', (e) => { 
        const obj = e.target;
        if (obj && (obj.type === 'text' || obj.type === 'i-text' || obj.type === 'textbox')) {
            if (obj.scaleX !== 1 || obj.scaleY !== 1) {
                obj.fontSize = Math.round(obj.fontSize * Math.abs(obj.scaleY || 1));
                if (obj.type === 'textbox') {
                    obj.width = Math.round(obj.width * Math.abs(obj.scaleX || 1));
                }
                obj.scaleX = 1;
                obj.scaleY = 1;
                obj.setCoords();
            }
        }
        updateProps(); 
        saveHistory(); 
    });
    canvas.on('object:removed', () => { updateLayersList(); });

    setTimeout(saveHistory, 100);

    // --- Keyboard Shortcuts ---
    document.addEventListener('keydown', function(e) {
        const tag = document.activeElement.tagName.toLowerCase();
        if (tag === 'input' || tag === 'textarea' || tag === 'select') return;

        if ((e.ctrlKey || e.metaKey) && e.key === 'z' && !e.shiftKey) { e.preventDefault(); undo(); }
        if ((e.ctrlKey || e.metaKey) && (e.key === 'y' || (e.shiftKey && e.key.toLowerCase() === 'z'))) { e.preventDefault(); redo(); }
        if (e.key === 'Delete' || (e.key === 'Backspace' && !e.ctrlKey)) {
            const objs = canvas.getActiveObjects();
            if (objs.length) { e.preventDefault(); canvas.discardActiveObject(); objs.forEach(o => canvas.remove(o)); updateLayersList(); saveHistory(); }
        }
        
        // Copy (Ctrl+C)
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'c') {
            const activeObject = canvas.getActiveObject();
            if (activeObject && !activeObject.isEditing) {
                e.preventDefault();
                try {
                    const jsonObj = activeObject.toObject(customAttrs);
                    localStorage.setItem('artera_clipboard', JSON.stringify(jsonObj));
                    localStorage.setItem('artera_clipboard_time', Date.now().toString());
                    // Also keep in-memory for same-tab fast paste
                    window._canvasClipboard = jsonObj;
                    console.log('[Artera] Copied to clipboard (localStorage + memory)');
                } catch(err) { console.warn('[Artera] Copy failed:', err); }
            }
        }
        
        // Paste (Ctrl+V)
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'v') {
            const activeObj = canvas.getActiveObject();
            if (activeObj && activeObj.isEditing) return; // don't paste when editing text
            
            e.preventDefault();
            try {
                // Read from localStorage (works cross-tab)
                const clipStr = localStorage.getItem('artera_clipboard');
                if (!clipStr) { console.log('[Artera] No clipboard data'); return; }
                const parsed = JSON.parse(clipStr);
                
                fabric.util.enlivenObjects([parsed], function(objects) {
                    if (objects.length) {
                        const clonedObj = objects[0];
                        // Restore custom attributes that enlivenObjects may not preserve
                        customAttrs.forEach(function(attr) {
                            if (parsed[attr] !== undefined) clonedObj.set(attr, parsed[attr]);
                        });
                        
                        canvas.discardActiveObject();
                        clonedObj.set({
                            left: clonedObj.left + 20,
                            top: clonedObj.top + 20,
                            evented: true,
                        });
                        if (clonedObj.type === 'activeSelection') {
                            clonedObj.canvas = canvas;
                            clonedObj.forEachObject(function(obj) { canvas.add(obj); });
                            clonedObj.setCoords();
                        } else {
                            canvas.add(clonedObj);
                        }
                        
                        // Update clipboard position so next paste offsets further
                        parsed.top = clonedObj.top;
                        parsed.left = clonedObj.left;
                        localStorage.setItem('artera_clipboard', JSON.stringify(parsed));
                        
                        canvas.setActiveObject(clonedObj);
                        canvas.requestRenderAll();
                        updateLayersList();
                        saveHistory();
                        console.log('[Artera] Pasted from clipboard');
                    }
                });
            } catch(err) { console.warn('[Artera] Paste failed:', err); }
        }

        // Arrow keys: move selected object (1px default, 10px with Shift)
        if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].indexOf(e.key) >= 0) {
            var obj = canvas.getActiveObject();
            if (obj) {
                e.preventDefault();
                var step = e.shiftKey ? 10 : 1;
                if (e.key === 'ArrowLeft')  obj.set('left', obj.left - step);
                if (e.key === 'ArrowRight') obj.set('left', obj.left + step);
                if (e.key === 'ArrowUp')    obj.set('top', obj.top - step);
                if (e.key === 'ArrowDown')  obj.set('top', obj.top + step);
                obj.setCoords();
                canvas.renderAll();
                updateProps();
            }
        }
    });

    // Flip buttons
    const btnFlipH = $('flip-horizontal');
    const btnFlipV = $('flip-vertical');
    
    if (btnFlipH) {
        btnFlipH.addEventListener('click', function() {
            const obj = canvas.getActiveObject();
            if (obj) {
                obj.set('flipX', !obj.flipX);
                canvas.renderAll();
                saveHistory();
            }
        });
    }
    
    if (btnFlipV) {
        btnFlipV.addEventListener('click', function() {
            const obj = canvas.getActiveObject();
            if (obj) {
                obj.set('flipY', !obj.flipY);
                canvas.renderAll();
                saveHistory();
            }
        });
    }

    // Layer buttons removed as per user request

    // ═══════════════════════════════════════════════════════════════
    // NODE / POINT EDITOR  (CorelDRAW-style "Convert to Curves")
    // ═══════════════════════════════════════════════════════════════
    var _nodeEditMode = false;
    var _nodeCircles = [];
    var _nodeLines = [];
    var _nodeSnapLines = { h: null, v: null };
    var _nodeEditTarget = null;
    var _nodeEditPathObj = null;   // The resolved path/polygon object
    var _nodeEditWrapper = null;   // The wrapper (group or same as pathObj)
    var _nodeMovingHandler = null;
    var _nodeModifiedHandler = null;
    var _lastInteractedNodeIdx = -1; // Track last dragged node for Delete key
    var nodeEditSection = $('node-edit-section');
    var btnEditPoints = $('btn-edit-points');
    var btnEditPointsText = $('btn-edit-points-text');

    /**
     * Resolve to the inner fabric.Path from any object:
     *  - fabric.Path → return directly
     *  - fabric.Group with single Path child → return that child path
     *  - otherwise → null
     */
    function _resolvePathObj(obj) {
        if (!obj) return null;
        if (['path', 'polygon', 'polyline', 'rect'].includes(obj.type)) return obj;
        
        if (obj.type === 'group' && obj._objects && obj._objects.length > 0) {
            // Find the first editable shape in the group
            for (var i = 0; i < obj._objects.length; i++) {
                var child = obj._objects[i];
                if (['path', 'polygon', 'polyline', 'rect'].includes(child.type)) {
                    return child;
                }
            }
        }
        return null;
    }

    /** Can this object enter node-edit mode? */
    function canEditNodes(obj) {
        var resolved = _resolvePathObj(obj);
        if (!resolved) return false;
        // Allow editing for paths, polygons, polylines, and rects
        return true;
    }

    /** Convert a rect to a polygon for node editing */
    function _convertToPolygon(rectObj) {
        if (rectObj.type !== 'rect') return rectObj;
        var w = rectObj.width;
        var h = rectObj.height;
        
        // Always use 0-based points (Fabric polygon handles pathOffset internally)
        var pts = [
            {x: 0, y: 0},
            {x: w, y: 0},
            {x: w, y: h},
            {x: 0, y: h}
        ];
        
        var poly = new fabric.Polygon(pts, {
            fill: rectObj.fill,
            stroke: rectObj.stroke,
            strokeWidth: rectObj.strokeWidth,
            scaleX: rectObj.scaleX,
            scaleY: rectObj.scaleY,
            angle: rectObj.angle,
            skewX: rectObj.skewX,
            skewY: rectObj.skewY,
            opacity: rectObj.opacity,
            originX: rectObj.originX,
            originY: rectObj.originY,
            flipX: rectObj.flipX,
            flipY: rectObj.flipY,
            customName: rectObj.customName || 'Converted Polygon',
            customType: rectObj.customType || 'shape',
            is_shape: true
        });
        
        // CRITICAL: Position the polygon so it visually matches the rect exactly
        // Use the rect's actual bounding rect to align
        var rectCenter = rectObj.getCenterPoint();
        poly.set({ left: rectCenter.x, top: rectCenter.y, originX: 'center', originY: 'center' });
        poly.setCoords();
        
        console.log('[NODE_EDIT] Converted rect to polygon. Rect center:', rectCenter, 'Poly left/top:', poly.left, poly.top, 'Points:', pts);
        
        return poly;
    }

    /** Show / hide the "Edit Points" button based on selected object */
    function updateNodeEditButton() {
        var obj = canvas.getActiveObject();
        if (nodeEditSection) {
            var canEdit = obj && canEditNodes(obj);
            console.log('[NODE_EDIT] updateNodeEditButton: obj type=' + (obj ? obj.type : 'null') + ', canEdit=' + canEdit + ', nodeEditSection exists=' + !!nodeEditSection);
            if (canEdit) {
                nodeEditSection.style.display = 'block';
                if (btnEditPointsText) {
                    btnEditPointsText.textContent = _nodeEditMode ? 'Exit Edit Points' : 'Edit Points';
                }
                if (btnEditPoints) {
                    if (_nodeEditMode) {
                        btnEditPoints.classList.remove('btn-outline-primary');
                        btnEditPoints.classList.add('btn-primary');
                    } else {
                        btnEditPoints.classList.remove('btn-primary');
                        btnEditPoints.classList.add('btn-outline-primary');
                    }
                }
            } else {
                nodeEditSection.style.display = 'none';
            }
        } else {
            console.warn('[NODE_EDIT] nodeEditSection DOM element not found!');
        }
    }

    /**
     * Convert a point from a path's coordinate space to canvas coordinates
     */
    function _pathPointToCanvas(pathObj, wrapperObj, px, py) {
        // For polygon/polyline: pathOffset is the center of the points bounding box
        // For path: pathOffset is set by Fabric when parsing the path
        var offsetX = 0, offsetY = 0;
        if (pathObj.pathOffset) {
            offsetX = pathObj.pathOffset.x;
            offsetY = pathObj.pathOffset.y;
        } else if (pathObj.type === 'polygon' || pathObj.type === 'polyline') {
            // Fallback: compute from points
            var pts = pathObj.points || [];
            var minX = Infinity, maxX = -Infinity, minY = Infinity, maxY = -Infinity;
            pts.forEach(function(p) { minX = Math.min(minX, p.x); maxX = Math.max(maxX, p.x); minY = Math.min(minY, p.y); maxY = Math.max(maxY, p.y); });
            offsetX = (minX + maxX) / 2;
            offsetY = (minY + maxY) / 2;
        }
        
        // Convert point from path-local space to center-relative
        var lx = px - offsetX;
        var ly = py - offsetY;
        
        // Use the actual object on canvas for transform (targetObj, not inner pathObj if different)
        var transformObj = (wrapperObj && wrapperObj !== pathObj) ? wrapperObj : pathObj;
        var matrix = transformObj.calcTransformMatrix();
        var result = fabric.util.transformPoint(new fabric.Point(lx, ly), matrix);
        
        return result;
    }

    /**
     * Convert a canvas coordinate back to the path's coordinate space
     */
    function _canvasPointToPath(pathObj, wrapperObj, cx, cy) {
        var transformObj = (wrapperObj && wrapperObj !== pathObj) ? wrapperObj : pathObj;
        var matrix = transformObj.calcTransformMatrix();
        var inv = fabric.util.invertTransform(matrix);
        var pt = fabric.util.transformPoint(new fabric.Point(cx, cy), inv);
        
        var offsetX = 0, offsetY = 0;
        if (pathObj.pathOffset) {
            offsetX = pathObj.pathOffset.x;
            offsetY = pathObj.pathOffset.y;
        } else if (pathObj.type === 'polygon' || pathObj.type === 'polyline') {
            var pts = pathObj.points || [];
            var minX = Infinity, maxX = -Infinity, minY = Infinity, maxY = -Infinity;
            pts.forEach(function(p) { minX = Math.min(minX, p.x); maxX = Math.max(maxX, p.x); minY = Math.min(minY, p.y); maxY = Math.max(maxY, p.y); });
            offsetX = (minX + maxX) / 2;
            offsetY = (minY + maxY) / 2;
        }
        
        var px = pt.x + offsetX;
        var py = pt.y + offsetY;
        
        return { x: px, y: py };
    }

    /**
     * Extract all editable node coordinates from a path's data array or polygon's points array.
     */
    function _extractNodes(targetObj) {
        var nodes = [];
        
        if (targetObj.type === 'polygon' || targetObj.type === 'polyline') {
            var pts = targetObj.points || [];
            for (var j = 0; j < pts.length; j++) {
                nodes.push({ isPolygon: true, pathIndex: j, xIdx: 0, yIdx: 0, x: pts[j].x, y: pts[j].y });
            }
            return nodes;
        }

        var pathData = targetObj.path || [];
        for (var i = 0; i < pathData.length; i++) {
            var cmd = pathData[i];
            var c = cmd[0].toUpperCase();
            if (c === 'Z') continue;

            if (c === 'M' || c === 'L' || c === 'T') {
                nodes.push({ pathIndex: i, xIdx: 1, yIdx: 2, x: cmd[1], y: cmd[2] });
            } else if (c === 'H') {
                nodes.push({ pathIndex: i, xIdx: 1, yIdx: -1, x: cmd[1], y: 0 });
            } else if (c === 'V') {
                nodes.push({ pathIndex: i, xIdx: -1, yIdx: 1, x: 0, y: cmd[1] });
            } else if (c === 'C') {
                nodes.push({ pathIndex: i, xIdx: 5, yIdx: 6, x: cmd[5], y: cmd[6] });
            } else if (c === 'S') {
                nodes.push({ pathIndex: i, xIdx: 3, yIdx: 4, x: cmd[3], y: cmd[4] });
            } else if (c === 'Q') {
                nodes.push({ pathIndex: i, xIdx: 3, yIdx: 4, x: cmd[3], y: cmd[4] });
            } else if (c === 'A') {
                nodes.push({ pathIndex: i, xIdx: 6, yIdx: 7, x: cmd[6], y: cmd[7] });
            }
        }
        return nodes;
    }

    /**
     * Draw connecting lines between nodes for visual feedback
     */
    function _drawNodeLines(pathObj, wrapperObj, nodes) {
        if (nodes.length < 2) return;

        // If the number of lines doesn't match the nodes, recreate them (e.g. initial draw or node added/removed)
        if (_nodeLines.length !== nodes.length) {
            _nodeLines.forEach(function(l) { canvas.remove(l); });
            _nodeLines = [];
            for (var i = 0; i < nodes.length; i++) {
                var line = new fabric.Line([0, 0, 0, 0], {
                    stroke: '#6366f1',
                    strokeWidth: 1,
                    strokeDashArray: [4, 3],
                    selectable: false,
                    evented: false,
                    excludeFromExport: true,
                    _isNodeGuide: true
                });
                canvas.add(line);
                _nodeLines.push(line);
            }
        }

        // Just update coordinates for extreme smoothness during drag
        for (var i = 0; i < nodes.length; i++) {
            var n1 = nodes[i];
            var n2 = nodes[(i + 1) % nodes.length]; // wrap around
            var p1 = _pathPointToCanvas(pathObj, wrapperObj, n1.x, n1.y);
            var p2 = _pathPointToCanvas(pathObj, wrapperObj, n2.x, n2.y);
            
            _nodeLines[i].set({
                x1: p1.x,
                y1: p1.y,
                x2: p2.x,
                y2: p2.y
            });
        }
    }

    /**
     * ENTER node editing mode
     */
    function enterNodeEditMode(targetObj) {
        if (_nodeEditMode) exitNodeEditMode();

        var pathObj = _resolvePathObj(targetObj);
        if (!pathObj) return;

        // If it's a rect, convert it to polygon first
        if (pathObj.type === 'rect') {
            var poly = _convertToPolygon(pathObj);
            
            if (targetObj === pathObj) {
                // Not in a group, replace directly on canvas
                canvas.remove(pathObj);
                canvas.add(poly);
                canvas.setActiveObject(poly);
                targetObj = poly;
                pathObj = poly;
            } else if (targetObj.type === 'group' && targetObj._objects) {
                // It's inside a group
                var idx = targetObj._objects.indexOf(pathObj);
                if (idx !== -1) {
                    targetObj.removeWithUpdate(pathObj);
                    targetObj.insertAt(poly, idx, true);
                    pathObj = poly;
                }
            }
        }
        
        if (!pathObj.path && !pathObj.points) return;

        _nodeEditMode = true;
        _nodeEditTarget = targetObj;

        // Determine the wrapper (for transforms). If the path is inside a group, use the group.
        var wrapperObj = (targetObj !== pathObj) ? targetObj : pathObj;
        _nodeEditPathObj = pathObj;
        _nodeEditWrapper = wrapperObj;

        // Lock the target from regular transforms while in edit mode
        targetObj.set({
            lockMovementX: true,
            lockMovementY: true,
            lockScalingX: true,
            lockScalingY: true,
            lockRotation: true,
            hasControls: true, // MUST BE TRUE to show custom node controls
            hasBorders: false,
            objectCaching: false // Fix visual artifacts when moving points out of original bounds
        });
        if (pathObj !== targetObj) {
            pathObj.set({ objectCaching: false });
        }

        var nodes = _extractNodes(pathObj);
        
        // Pre-compute pathOffset once for this edit session
        var _editOffsetX = 0, _editOffsetY = 0;
        if (pathObj.pathOffset) {
            _editOffsetX = pathObj.pathOffset.x;
            _editOffsetY = pathObj.pathOffset.y;
        } else if (pathObj.type === 'polygon' || pathObj.type === 'polyline') {
            var pts = pathObj.points || [];
            var minX = Infinity, maxX = -Infinity, minY = Infinity, maxY = -Infinity;
            pts.forEach(function(p) { minX = Math.min(minX, p.x); maxX = Math.max(maxX, p.x); minY = Math.min(minY, p.y); maxY = Math.max(maxY, p.y); });
            _editOffsetX = (minX + maxX) / 2;
            _editOffsetY = (minY + maxY) / 2;
        }
        
        console.log('[NODE_EDIT] Extracted ' + nodes.length + ' nodes. pathOffset: (' + _editOffsetX.toFixed(1) + ', ' + _editOffsetY.toFixed(1) + ')');
        console.log('[NODE_EDIT] pathObj type:', pathObj.type, 'left:', pathObj.left, 'top:', pathObj.top, 'scaleX:', pathObj.scaleX, 'scaleY:', pathObj.scaleY);
        console.log('[NODE_EDIT] targetObj === pathObj:', targetObj === pathObj, ', wrapperObj === pathObj:', wrapperObj === pathObj);

        // Draw initial guide lines (these are fabric objects, use canvas coordinates)
        _drawNodeLines(pathObj, wrapperObj, nodes);

        // Save original controls to restore them later
        targetObj._originalControls = targetObj.controls;
        var nodeControls = {};

        // Create a custom control for each node
        nodes.forEach(function(nd, idx) {
            nodeControls['node_' + idx] = new fabric.Control({
                positionHandler: function(dim, finalMatrix, fabricObject) {
                    // Convert path-local point to canvas coordinates using the full
                    // transform matrix (includes translate + rotate + scale + flip).
                    // Then convert canvas coords to screen coords via viewport transform,
                    // because controls are drawn WITHOUT the viewport transform applied.
                    var canvasPt = _pathPointToCanvas(pathObj, wrapperObj, nd.x, nd.y);
                    var vpt = canvas.viewportTransform;
                    return fabric.util.transformPoint(canvasPt, vpt);
                },
                actionHandler: function(eventData, transform, x, y) {
                    _lastInteractedNodeIdx = nd.pathIndex;
                    
                    var snapDist = 6;
                    var snappedX = false, snappedY = false;
                    var snapTargetX = null, snapTargetY = null;

                    // Clear previous snap lines
                    if (_nodeSnapLines.h) { canvas.remove(_nodeSnapLines.h); _nodeSnapLines.h = null; }
                    if (_nodeSnapLines.v) { canvas.remove(_nodeSnapLines.v); _nodeSnapLines.v = null; }

                    // Check snapping against other nodes
                    nodes.forEach(function(otherNd, i) {
                        if (i === idx) return; // skip self
                        var otherCanvasPt = _pathPointToCanvas(pathObj, wrapperObj, otherNd.x, otherNd.y);
                        
                        if (!snappedX && Math.abs(x - otherCanvasPt.x) < snapDist) {
                            x = otherCanvasPt.x;
                            snappedX = true;
                            snapTargetX = otherCanvasPt;
                        }
                        if (!snappedY && Math.abs(y - otherCanvasPt.y) < snapDist) {
                            y = otherCanvasPt.y;
                            snappedY = true;
                            snapTargetY = otherCanvasPt;
                        }
                    });

                    // Draw snap lines if snapped
                    if (snappedX && snapTargetX) {
                        _nodeSnapLines.v = new fabric.Line([x, -9999, x, 9999], {
                            stroke: '#a0aec0',
                            strokeWidth: 1,
                            strokeDashArray: [4, 4],
                            selectable: false,
                            evented: false,
                            excludeFromExport: true
                        });
                        canvas.add(_nodeSnapLines.v);
                    }
                    if (snappedY && snapTargetY) {
                        _nodeSnapLines.h = new fabric.Line([-9999, y, 9999, y], {
                            stroke: '#a0aec0',
                            strokeWidth: 1,
                            strokeDashArray: [4, 4],
                            selectable: false,
                            evented: false,
                            excludeFromExport: true
                        });
                        canvas.add(_nodeSnapLines.h);
                    }

                    // x, y are canvas pointer coordinates (now potentially snapped)
                    var pathPt = _canvasPointToPath(pathObj, wrapperObj, x, y);

                    if (nd.isPolygon) {
                        pathObj.points[nd.pathIndex].x = pathPt.x;
                        pathObj.points[nd.pathIndex].y = pathPt.y;
                    } else {
                        var cmd = pathObj.path[nd.pathIndex];
                        if (nd.xIdx > 0) cmd[nd.xIdx] = pathPt.x;
                        if (nd.yIdx > 0) cmd[nd.yIdx] = pathPt.y;
                    }
                    nd.x = pathPt.x;
                    nd.y = pathPt.y;

                    pathObj.dirty = true;
                    if (wrapperObj !== pathObj) wrapperObj.dirty = true;
                    
                    // Update guide lines
                    _drawNodeLines(pathObj, wrapperObj, nodes);

                    return true;
                },
                mouseUpHandler: function(eventData, transform, x, y) {
                    if (_nodeSnapLines.h) { canvas.remove(_nodeSnapLines.h); _nodeSnapLines.h = null; }
                    if (_nodeSnapLines.v) { canvas.remove(_nodeSnapLines.v); _nodeSnapLines.v = null; }
                    return true;
                },
                cursorStyle: 'crosshair',
                actionName: 'modifyNode',
                render: function(ctx, left, top, styleOverride, fabricObject) {
                    ctx.save();
                    ctx.beginPath();
                    ctx.arc(left, top, 6, 0, 2 * Math.PI, false);
                    ctx.fillStyle = '#ffffff';
                    ctx.strokeStyle = '#6366f1';
                    ctx.lineWidth = 2.5;
                    ctx.fill();
                    ctx.stroke();
                    ctx.restore();
                }
            });
        });

        // ── MIDPOINT "+" CONTROLS — click or drag to insert a new point ──
        if (pathObj.type === 'polygon' || pathObj.type === 'polyline') {
            for (var mi = 0; mi < nodes.length; mi++) {
                (function(edgeIdx, n1, n2) {
                    var inserted = false;
                    var insertedPointIdx = -1;

                    nodeControls['mid_' + edgeIdx] = new fabric.Control({
                        positionHandler: function(dim, finalMatrix, fabricObject) {
                            if (inserted && insertedPointIdx >= 0 && insertedPointIdx < pathObj.points.length) {
                                var pt = pathObj.points[insertedPointIdx];
                                var canvasPt = _pathPointToCanvas(pathObj, wrapperObj, pt.x, pt.y);
                                return fabric.util.transformPoint(canvasPt, canvas.viewportTransform);
                            }
                            var midX = (n1.x + n2.x) / 2;
                            var midY = (n1.y + n2.y) / 2;
                            var canvasPt = _pathPointToCanvas(pathObj, wrapperObj, midX, midY);
                            return fabric.util.transformPoint(canvasPt, canvas.viewportTransform);
                        },
                        actionHandler: function(eventData, transform, x, y) {
                            if (!inserted) {
                                // First drag call — insert the new point
                                var midX = (n1.x + n2.x) / 2;
                                var midY = (n1.y + n2.y) / 2;
                                insertedPointIdx = edgeIdx + 1;
                                pathObj.points.splice(insertedPointIdx, 0, { x: midX, y: midY });
                                inserted = true;
                                pathObj.dirty = true;
                                console.log('[NODE_EDIT] Inserted new point at index', insertedPointIdx);
                            }
                            // Move the newly inserted point with the drag
                            var pathPt = _canvasPointToPath(pathObj, wrapperObj, x, y);
                            pathObj.points[insertedPointIdx].x = pathPt.x;
                            pathObj.points[insertedPointIdx].y = pathPt.y;
                            pathObj.dirty = true;
                            if (wrapperObj !== pathObj) wrapperObj.dirty = true;
                            return true;
                        },
                        mouseUpHandler: function(eventData, transformData) {
                            if (!inserted) {
                                // Simple click (no drag) — insert at midpoint
                                var midX = (n1.x + n2.x) / 2;
                                var midY = (n1.y + n2.y) / 2;
                                insertedPointIdx = edgeIdx + 1;
                                pathObj.points.splice(insertedPointIdx, 0, { x: midX, y: midY });
                                inserted = true;
                                pathObj.dirty = true;
                            }
                            // Rebuild controls to reflect the new point
                            var target = _nodeEditTarget;
                            exitNodeEditMode();
                            enterNodeEditMode(target);
                            return true;
                        },
                        cursorStyle: 'copy',
                        actionName: 'addNode',
                        render: function(ctx, left, top, styleOverride, fabricObject) {
                            ctx.save();
                            // Semi-transparent purple circle
                            ctx.beginPath();
                            ctx.arc(left, top, 5, 0, 2 * Math.PI, false);
                            ctx.fillStyle = 'rgba(99, 102, 241, 0.45)';
                            ctx.fill();
                            ctx.strokeStyle = '#6366f1';
                            ctx.lineWidth = 1.5;
                            ctx.stroke();
                            // "+" sign
                            ctx.strokeStyle = '#ffffff';
                            ctx.lineWidth = 1.5;
                            ctx.beginPath();
                            ctx.moveTo(left - 3, top);
                            ctx.lineTo(left + 3, top);
                            ctx.moveTo(left, top - 3);
                            ctx.lineTo(left, top + 3);
                            ctx.stroke();
                            ctx.restore();
                        }
                    });
                })(mi, nodes[mi], nodes[(mi + 1) % nodes.length]);
            }
        }

        targetObj.controls = nodeControls;
        canvas.renderAll();
        console.log('[NODE_EDIT] Node edit mode ENTERED. Controls count:', Object.keys(nodeControls).length);

        // Update button state
        updateNodeEditButton();
    }

    /**
     * EXIT node editing mode
     */
    function exitNodeEditMode() {
        if (!_nodeEditMode) return;

        // Remove guide lines
        _nodeLines.forEach(function(l) { canvas.remove(l); });
        _nodeLines = [];
        
        if (_nodeSnapLines.h) { canvas.remove(_nodeSnapLines.h); _nodeSnapLines.h = null; }
        if (_nodeSnapLines.v) { canvas.remove(_nodeSnapLines.v); _nodeSnapLines.v = null; }

        // Restore original controls
        if (_nodeEditTarget && _nodeEditTarget._originalControls) {
            _nodeEditTarget.controls = _nodeEditTarget._originalControls;
            delete _nodeEditTarget._originalControls;
        }

        // Restore object interactivity
        if (_nodeEditTarget) {
            _nodeEditTarget.set({
                lockMovementX: false,
                lockMovementY: false,
                lockScalingX: false,
                lockScalingY: false,
                lockRotation: false,
                hasControls: true,
                hasBorders: true,
                objectCaching: true
            });

            // Recalculate dimensions after edits
            var pathObj = _resolvePathObj(_nodeEditTarget);
            if (pathObj) {
                if (pathObj !== _nodeEditTarget) {
                    pathObj.set({ objectCaching: true });
                }
                if (pathObj.type === 'polygon' || pathObj.type === 'polyline') {
                    // Force fabric to recalculate the polygon's bounding box and offset
                    var points = pathObj.points;
                    pathObj.initialize(points, pathObj);
                } else if (pathObj.type === 'path') {
                    var pathData = pathObj.path;
                    pathObj.initialize(pathData, pathObj);
                }
                
                pathObj.setCoords();
                if (_nodeEditTarget !== pathObj) {
                    _nodeEditTarget.setCoords();
                    // Recalculate group dimensions
                    if (typeof _nodeEditTarget.addWithUpdate === 'function') {
                        // Force group to recalculate
                        _nodeEditTarget._calcBounds();
                        _nodeEditTarget.setCoords();
                    }
                }
            }

            canvas.setActiveObject(_nodeEditTarget);
        }

        _nodeEditTarget = null;
        _nodeEditPathObj = null;
        _nodeEditWrapper = null;
        _lastInteractedNodeIdx = -1;
        _nodeEditMode = false;

        canvas.renderAll();
        saveHistory();
        updateNodeEditButton();
    }

    // Button click handler
    if (btnEditPoints) {
        btnEditPoints.addEventListener('click', function() {
            if (_nodeEditMode) {
                exitNodeEditMode();
            } else {
                var obj = canvas.getActiveObject();
                if (obj && canEditNodes(obj)) {
                    enterNodeEditMode(obj);
                }
            }
        });
    }

    // Escape key to exit node editing
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && _nodeEditMode) {
            exitNodeEditMode();
        }
        // Delete key removes the last-interacted point (min 3 points)
        if ((e.key === 'Delete' || e.key === 'Backspace') && _nodeEditMode && _nodeEditPathObj) {
            if (_nodeEditPathObj.type === 'polygon' || _nodeEditPathObj.type === 'polyline') {
                if (_lastInteractedNodeIdx >= 0 && _nodeEditPathObj.points.length > 3) {
                    e.preventDefault();
                    _nodeEditPathObj.points.splice(_lastInteractedNodeIdx, 1);
                    _nodeEditPathObj.dirty = true;
                    var target = _nodeEditTarget;
                    exitNodeEditMode();
                    enterNodeEditMode(target);
                    console.log('[NODE_EDIT] Deleted point at index', _lastInteractedNodeIdx);
                }
            }
        }
    });

    // Double-click on canvas to add point on closest edge (alternative method)
    canvas.on('mouse:dblclick', function(opt) {
        if (!_nodeEditMode || !_nodeEditPathObj || !_nodeEditWrapper) return;
        var pathObj = _nodeEditPathObj;
        if (pathObj.type !== 'polygon' && pathObj.type !== 'polyline') return;

        var pointer = canvas.getPointer(opt.e);
        var pathPt = _canvasPointToPath(pathObj, _nodeEditWrapper, pointer.x, pointer.y);
        var points = pathObj.points;

        // Find closest edge segment
        var bestDist = Infinity, bestIdx = 0;
        for (var ei = 0; ei < points.length; ei++) {
            var p1 = points[ei], p2 = points[(ei + 1) % points.length];
            var dx = p2.x - p1.x, dy = p2.y - p1.y;
            var len2 = dx * dx + dy * dy;
            var t = len2 > 0 ? Math.max(0, Math.min(1, ((pathPt.x - p1.x) * dx + (pathPt.y - p1.y) * dy) / len2)) : 0;
            var projX = p1.x + t * dx, projY = p1.y + t * dy;
            var dist = Math.sqrt((pathPt.x - projX) * (pathPt.x - projX) + (pathPt.y - projY) * (pathPt.y - projY));
            if (dist < bestDist) { bestDist = dist; bestIdx = ei; }
        }

        // Insert point at the click position on the closest edge
        pathObj.points.splice(bestIdx + 1, 0, { x: pathPt.x, y: pathPt.y });
        pathObj.dirty = true;
        console.log('[NODE_EDIT] Double-click added point at index', bestIdx + 1);

        var target = _nodeEditTarget;
        exitNodeEditMode();
        enterNodeEditMode(target);
    });

    // Exit node editing when selection changes
    canvas.on('before:selection:cleared', function() {
        if (_nodeEditMode) {
            exitNodeEditMode();
        }
    });

    // Hook into updateProps to show/hide the Edit Points button
    var _origUpdateProps = updateProps;
    updateProps = function() {
        _origUpdateProps();
        updateNodeEditButton();
    };
    // Also hook selection events for button visibility
    canvas.on('selection:created', updateNodeEditButton);
    canvas.on('selection:updated', function() {
        if (_nodeEditMode) exitNodeEditMode();
        updateNodeEditButton();
    });

    // --- Load JSON ---
    const jsonUpload = $('json-upload');
    console.log('[DEBUG] json-upload element:', jsonUpload);
    if (jsonUpload) jsonUpload.addEventListener('change', function(e) {
        console.log('[DEBUG] JSON/Image file change event fired');
        const files = Array.from(e.target.files);
        if(!files || files.length === 0) { console.log('[DEBUG] No file selected'); return; }

        const jsonFile = files.find(f => f.name.endsWith('.json'));
        if (!jsonFile) {
            alert('Please select a .json file along with the images.');
            e.target.value = '';
            return;
        }

        const imageFiles = files.filter(f => !f.name.endsWith('.json'));
        console.log('[DEBUG] Files selected: 1 JSON,', imageFiles.length, 'Images');

        const imagesMap = {};
        const imagePromises = imageFiles.map(f => {
            return new Promise((resolve) => {
                const reader = new FileReader();
                reader.onload = evt => { 
                    imagesMap[f.name] = evt.target.result; 
                    imagesMap[encodeURIComponent(f.name)] = evt.target.result;
                    imagesMap[f.name.replace(/ /g, '%20')] = evt.target.result;
                    resolve(); 
                };
                reader.readAsDataURL(f);
            });
        });

        Promise.all(imagePromises).then(() => {
            const reader = new FileReader();
            reader.onload = function(evt) {
                console.log('[DEBUG] JSON FileReader onload fired, length:', evt.target.result.length);
                try {
                    const jsonStr = evt.target.result;
                    const jsonObj = JSON.parse(jsonStr);
                    console.log('[DEBUG] JSON parsed successfully. Keys:', Object.keys(jsonObj));
                    
                    // Clear existing canvas
                    canvas.clear();

                    if (jsonObj.objects || jsonObj.version) {
                        console.log('[DEBUG] Detected: Fabric.js JSON format');
                        // Modify image sources in Fabric JSON to use our uploaded local images if possible
                        if (jsonObj.objects) {
                            jsonObj.objects.forEach(obj => {
                                if (obj.type === 'image' && obj.src) {
                                    const fn = obj.src.split('/').pop();
                                    if (imagesMap[fn]) obj.src = imagesMap[fn];
                                    else {
                                        const decoded = decodeURIComponent(fn);
                                        if (imagesMap[decoded]) obj.src = imagesMap[decoded];
                                    }
                                }
                            });
                        }
                        if (jsonObj.backgroundImage && jsonObj.backgroundImage.src) {
                            const fn = jsonObj.backgroundImage.src.split('/').pop();
                            if (imagesMap[fn]) jsonObj.backgroundImage.src = imagesMap[fn];
                            else {
                                const decoded = decodeURIComponent(fn);
                                if (imagesMap[decoded]) jsonObj.backgroundImage.src = imagesMap[decoded];
                            }
                        }

                        canvas.loadFromJSON(jsonObj, () => {
                            canvas.renderAll();
                            if (jsonObj.backgroundImage) {
                                baseWidth = jsonObj.backgroundImage.width * jsonObj.backgroundImage.scaleX;
                                baseHeight = jsonObj.backgroundImage.height * jsonObj.backgroundImage.scaleY;
                            } else if (jsonObj.width && jsonObj.height) {
                                baseWidth = jsonObj.width;
                                baseHeight = jsonObj.height;
                            }
                            const wInput = $('template-w');
                            const hInput = $('template-h');
                            if (wInput) wInput.value = baseWidth || 1080;
                            if (hInput) hInput.value = baseHeight || 1080;
                            
                            currentScale = updateCanvasZoom();
                            updateLayersList();
                            saveHistory();
                            
                            // Visual mask apply
                            canvas.getObjects().forEach(o => {
                                if (o.type === 'image' && o.mask_layer_id) applyVisualMaskPreview(o, o.mask_layer_id);
                            });
                            console.log('[DEBUG] Fabric.js JSON loaded.');
                        });
                    } else if (jsonObj.schema_version) {
                        console.log('[DEBUG] Detected: Artera Schema JSON');
                        const legacyConfig = {
                            info: { width: jsonObj.canvas.width, height: jsonObj.canvas.height },
                            layers: jsonObj.elements.map(el => {
                                let l = {
                                    type: el.type, name: el.name,
                                    x: el.x, y: el.y, w: el.w, h: el.h,
                                    rotation: el.rotation, opacity: el.opacity, z_index: el.z_index,
                                    is_background: el.is_background, is_placeholder: el.is_placeholder, is_slot: el.is_slot, mask_layer_id: el.mask_layer_id
                                };
                                if (el.type === 'image') {
                                    l.src = el.src;
                                    if (el.tint_color) l.tint_color = el.tint_color;
                                    if (el.is_shape) l.is_shape = el.is_shape;
                                    if (el._originalType) l._originalType = el._originalType;
                                }
                                else if (el.type === 'text' || el.type === 'i-text' || el.type === 'textbox') {
                                    l.type = 'text';
                                    l.text = el.text;
                                    if (el.font) {
                                        l.font = el.font.family;
                                        l.size = el.font.size;
                                        l.weight = el.font.weight;
                                        l.style = el.font.style;
                                        l.color = el.font.color;
                                        l.justification = el.font.justification;
                                    }
                                    if (el.ai) {
                                        l.ai_role = el.ai.role;
                                        l.ai_max_chars = el.ai.max_chars;
                                    }
                                } else if (el.type === 'shape' || ['rect','circle','triangle','path','polygon'].includes(el.type)) {
                                    l.type = 'shape';
                                    l.shapeType = el.type === 'shape' ? (el.shapeType || 'rectangle') : el.type;
                                    l.fill = el.fill;
                                    l.stroke = el.stroke;
                                    l.strokeWidth = el.strokeWidth;
                                    if (el.points) l.points = el.points;
                                    if (el.svgPath) { l.svgPath = el.svgPath; l.scaleX = el.scaleX; l.scaleY = el.scaleY; }
                                    if (el.rx) l.rx = el.rx;
                                    if (el.ry) l.ry = el.ry;
                                    if (el.text) {
                                        l.text = el.text;
                                        l.font_name = el.font ? el.font.family : (el.fontFamily || 'FontAwesome');
                                        l.size = el.font ? el.font.size : (el.fontSize || el.size || 20);
                                    }
                                    if (el.src) l.src = el.src;
                                }
                                return l;
                            })
                        };
                        renderJsonToCanvas(legacyConfig, imagesMap);
                    } else if (jsonObj.layers) {
                        console.log('[DEBUG] Detected: Legacy Artera JSON with', jsonObj.layers.length, 'layers');
                        renderJsonToCanvas(jsonObj, imagesMap);
                    } else {
                        console.log('[DEBUG] Unrecognized format! Keys:', Object.keys(jsonObj));
                        alert('Unrecognized JSON format!');
                    }
                } catch (err) {
                    console.error('[DEBUG] JSON parse/load error:', err);
                    alert('Invalid JSON file format: ' + err.message);
                }
            };
            reader.onerror = function(err) {
                console.error('[DEBUG] FileReader error:', err);
            };
            reader.readAsText(jsonFile);
        });
        
        // Reset file input so same file can be uploaded again if needed
        e.target.value = '';
    });

    // --- Load Existing Custom Template ZIP ---
    window.loadExistingTemplate = function(frameId) {
        if (!frameId) return;
        console.log('[DEBUG] Loading existing template ZIP ID:', frameId);
        
        // Build the correct URL for the load-zip route
        // saveUrl = ".../admin/template-builder/save" — derive base from it
        const baseAdminUrl = (typeof saveUrl !== 'undefined')
            ? saveUrl.replace(/\/save$/, '')
            : (window.location.pathname.replace(/\/template-builder.*/, '') + '/template-builder');
        const zipApiUrl = baseAdminUrl + '/load-zip/' + frameId;
        console.log('[DEBUG] Fetching ZIP from:', zipApiUrl);

        // Show loading indicator
        const canvasWrapper = document.getElementById('canvas-wrapper');
        if (canvasWrapper) canvasWrapper.style.opacity = '0.5';
        
        fetch(zipApiUrl)
            .then(response => response.json())
            .then(data => {
                if (canvasWrapper) canvasWrapper.style.opacity = '1';

                if (data.success && data.config) {
                    console.log('[DEBUG] Server returned template config');
                    window.editing_frame_id = data.frame_id || null;
                    if (window.editing_frame_id) {
                        try {
                            const newUrl = new URL(window.location.href);
                            newUrl.searchParams.set('mode', 'template');
                            newUrl.searchParams.set('frame_id', window.editing_frame_id);
                            window.history.replaceState({ path: newUrl.href }, '', newUrl.href);
                        } catch(e) {}
                    }
                    
                    const titleInput = document.getElementById('template-title');
                    if (titleInput && data.title) {
                        titleInput.value = (data.title || '').replace('.zip', '');
                    }
                    
                    canvas.clear();
                    
                    const jsonObj = data.config;
                    const imagesMap = data.images || {};
                    const fontsMap = data.fonts || {};

                    // Load embedded and server fonts from fontsMap
                    var zipFontFamilies = new Set();
                    Object.keys(fontsMap).forEach(fontName => {
                        const fontInfo = normalizePSFont(fontName);
                        const fontFace = new FontFace(fontInfo.family, 'url(' + fontsMap[fontName] + ')', {
                            weight: fontInfo.weight,
                            style: fontInfo.style
                        });
                        fontFace.load().then(loaded => {
                            document.fonts.add(loaded);
                            console.log('[FONTS] Loaded custom font:', fontName, 'as', fontInfo.family, 'weight=' + fontInfo.weight, 'style=' + fontInfo.style);
                        }).catch(err => {
                            // console.warn('[FONTS] Failed to load custom font:', fontName, err);
                        });
                        // Collect family names for Google Fonts fallback
                        if (fontInfo.family && fontInfo.family !== 'Arial') zipFontFamilies.add(fontInfo.family);
                    });
                    // Also load from Google Fonts as fallback for any missing weight variants
                    if (zipFontFamilies.size > 0) {
                        var gfParts = Array.from(zipFontFamilies).map(function(f) {
                            return 'family=' + encodeURIComponent(f) + ':ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,700';
                        });
                        var gfUrl = 'https://fonts.googleapis.com/css2?' + gfParts.join('&') + '&display=swap';
                        var existingLink = document.querySelector('link[data-gfonts]');
                        if (existingLink) existingLink.remove();
                        var link = document.createElement('link');
                        link.rel = 'stylesheet'; link.href = gfUrl; link.setAttribute('data-gfonts', '1');
                        document.head.appendChild(link);
                        console.log('[FONTS] Also loading Google Fonts fallback:', gfUrl);
                    }
                    
                    if (jsonObj.objects || jsonObj.version) {
                        // Fabric.js native JSON format
                        console.log('[DEBUG] Detected: Fabric.js JSON format from ZIP');
                        if (jsonObj.objects) {
                            jsonObj.objects.forEach(obj => {
                                if (obj.type === 'image' && obj.src) {
                                    const fn = obj.src.split('/').pop();
                                    if (imagesMap[fn]) {
                                        obj.src = imagesMap[fn];
                                    } else {
                                        const normFn = fn.toLowerCase().replace(/[ \-_]/g, '');
                                        const match = Object.keys(imagesMap).find(k => k.toLowerCase().replace(/[ \-_]/g, '') === normFn);
                                        if (match) obj.src = imagesMap[match];
                                    }
                                }
                            });
                        }
                        if (jsonObj.backgroundImage && jsonObj.backgroundImage.src) {
                            const fn = jsonObj.backgroundImage.src.split('/').pop();
                            if (imagesMap[fn]) {
                                jsonObj.backgroundImage.src = imagesMap[fn];
                            } else {
                                const normFn = fn.toLowerCase().replace(/[ \-_]/g, '');
                                const match = Object.keys(imagesMap).find(k => k.toLowerCase().replace(/[ \-_]/g, '') === normFn);
                                if (match) jsonObj.backgroundImage.src = imagesMap[match];
                            }
                        }
                        canvas.loadFromJSON(jsonObj, () => {
                            canvas.renderAll();
                            if (jsonObj.backgroundImage) {
                                baseWidth = jsonObj.backgroundImage.width * jsonObj.backgroundImage.scaleX;
                                baseHeight = jsonObj.backgroundImage.height * jsonObj.backgroundImage.scaleY;
                            } else if (jsonObj.width && jsonObj.height) {
                                baseWidth = jsonObj.width;
                                baseHeight = jsonObj.height;
                            }
                            const wInput = document.getElementById('template-w');
                            const hInput = document.getElementById('template-h');
                            if (wInput) wInput.value = baseWidth || 1080;
                            if (hInput) hInput.value = baseHeight || 1080;
                            
                            currentScale = updateCanvasZoom();
                            updateLayersList();
                            saveHistory();
                            
                            // Visual mask apply
                            canvas.getObjects().forEach(o => {
                                if (o.type === 'image' && o.mask_layer_id) applyVisualMaskPreview(o, o.mask_layer_id);
                            });
                            console.log('[DEBUG] Fabric.js JSON loaded.');
                        });
                    } else if (jsonObj.schema_version) {
                        // Artera Schema JSON format
                        console.log('[DEBUG] Detected: Artera Schema JSON from ZIP, elements:', jsonObj.elements.length);
                        const shapeTypes = ['rect','circle','triangle','path','polygon','line','ellipse'];
                        const legacyConfig = {
                            info: { width: jsonObj.canvas.width, height: jsonObj.canvas.height },
                            layers: jsonObj.elements.map((el, idx) => {
                                let l = {
                                    type: el.type, name: el.name,
                                    x: el.x, y: el.y, w: el.w, h: el.h,
                                    rotation: el.rotation, opacity: el.opacity, z_index: el.z_index,
                                    is_background: el.is_background, is_placeholder: el.is_placeholder, is_slot: el.is_slot, mask_layer_id: el.mask_layer_id
                                };
                                if (el.type === 'image') {
                                    l.src = el.src;
                                    if (el.is_shape || /shape|rect|circle|triangle|polygon|ellipse|path/i.test(el.name)) l.is_shape = true;
                                    if (el.tint_color) l.tint_color = el.tint_color;
                                    if (el._originalType) l._originalType = el._originalType;
                                    console.log('[LOAD] Layer ' + idx + ' "' + el.name + '" → image' + (l.is_shape ? ' (rasterized shape)' : '') + ', tint_color=' + (l.tint_color||'none') + ', src=' + (el.src||'').substring(0,60));
                                } else if (el.type === 'text' || el.type === 'i-text' || el.type === 'textbox') {
                                    l.type = 'text';
                                    l.text = el.text;
                                    if (el.font) {
                                        l.font = el.font.family;
                                        l.size = el.font.size;
                                        l.weight = el.font.weight;
                                        l.style = el.font.style;
                                        l.color = el.font.color;
                                        l.justification = el.font.justification;
                                    }
                                    if (el.ai) {
                                        l.ai_role = el.ai.role;
                                        l.ai_max_chars = el.ai.max_chars;
                                    }
                                    if (el.kind) {
                                        l.kind = el.kind;
                                        l.textKind = el.kind.toLowerCase(); // _doRender checks 'point'/'paragraph'
                                    }
                                    console.log('[LOAD] Layer ' + idx + ' "' + el.name + '" → text: "' + (el.text||'').substring(0,30) + '"');
                                } else if (el.type === 'shape' || shapeTypes.includes(el.type)) {
                                    // Fix for previously broken data: rasterized shapes saved as type='shape' with src but no fill
                                    if (el.src && !el.fill && !el.shapeType) {
                                        l.type = 'image';
                                        l.src = el.src;
                                        l.is_shape = true;
                                        console.log('[LOAD] Layer ' + idx + ' "' + el.name + '" → RECOVERED rasterized shape as image');
                                    } else {
                                        l.type = 'shape';
                                        l.shapeType = el.type === 'shape' ? (el.shapeType || 'rectangle') : el.type;
                                        l.fill = el.fill;
                                        l.stroke = el.stroke;
                                        l.strokeWidth = el.strokeWidth;
                                        console.log('[LOAD] Layer ' + idx + ' "' + el.name + '" → SHAPE type=' + l.shapeType + ' fill=' + l.fill + ' stroke=' + l.stroke);
                                    }
                                } else {
                                    console.warn('[LOAD] Layer ' + idx + ' "' + el.name + '" → UNKNOWN type: ' + el.type + ' — WILL NOT RENDER!');
                                }
                                return l;
                            })
                        };
                        console.log('[DEBUG] Converted schema → legacy config with', legacyConfig.layers.length, 'layers');
                        renderJsonToCanvas(legacyConfig, imagesMap);
                    } else if (jsonObj.layers) {
                        // Legacy Artera JSON (direct layers array)
                        console.log('[DEBUG] Detected: Legacy Artera JSON from ZIP');
                        renderJsonToCanvas(jsonObj, imagesMap);
                    } else {
                        alert('Unrecognized JSON format in ZIP!');
                    }
                } else {
                    alert('Error loading template: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(err => {
                if (canvasWrapper) canvasWrapper.style.opacity = '1';
                console.error('[DEBUG] Fetch error:', err);
                alert('Error fetching ZIP data: ' + (err.message || err));
            });
    };

    // --- Load Existing Frame ZIP ---
    window.loadExistingFrame = function(frameId) {
        if (!frameId) return;
        console.log('[DEBUG] Loading existing frame ZIP ID:', frameId);
        
        const zipApiUrl = loadFrameZipUrl + '/' + frameId;
        console.log('[DEBUG] Fetching Frame ZIP from:', zipApiUrl);

        const canvasWrapper = document.getElementById('canvas-wrapper');
        if (canvasWrapper) canvasWrapper.style.opacity = '0.5';
        
        fetch(zipApiUrl)
            .then(response => response.json())
            .then(data => {
                if (canvasWrapper) canvasWrapper.style.opacity = '1';

                if (data.success && data.config) {
                    console.log('[DEBUG] Server returned frame config');
                    window.editing_frame_id = data.frame_id || null;
                    if (window.editing_frame_id) {
                        try {
                            const newUrl = new URL(window.location.href);
                            newUrl.searchParams.set('mode', 'frame');
                            newUrl.searchParams.set('frame_id', window.editing_frame_id);
                            window.history.replaceState({ path: newUrl.href }, '', newUrl.href);
                        } catch(e) {}
                    }
                    
                    const titleInput = document.getElementById('template-title');
                    if (titleInput && data.title) {
                        titleInput.value = (data.title || '').replace('.zip', '');
                    }

                    if (data.frameData) {
                        if ($('frame-category')) $('frame-category').value = data.frameData.poster_category_id;
                        if ($('frame-template-type')) $('frame-template-type').value = data.frameData.template_type;
                        if ($('req_address')) $('req_address').value = data.frameData.req_address || 0;
                        if ($('req_email')) $('req_email').value = data.frameData.req_email || 0;
                        if ($('req_phone')) $('req_phone').value = data.frameData.req_phone || 0;
                        if ($('req_website')) $('req_website').value = data.frameData.req_website || 0;
                    }
                    
                    canvas.clear();
                    
                    const jsonObj = data.config;
                    const imagesMap = data.images || {};
                    const fontsMap = data.fonts || {};

                    var zipFontFamilies = new Set();
                    Object.keys(fontsMap).forEach(fontName => {
                        const fontInfo = normalizePSFont(fontName);
                        const fontFace = new FontFace(fontInfo.family, 'url(' + fontsMap[fontName] + ')', {
                            weight: fontInfo.weight,
                            style: fontInfo.style
                        });
                        fontFace.load().then(loaded => {
                            document.fonts.add(loaded);
                            console.log('[FONTS] Loaded custom font:', fontName, 'as', fontInfo.family, 'weight=' + fontInfo.weight, 'style=' + fontInfo.style);
                        }).catch(err => {
                            // console.warn('[FONTS] Failed to load custom font:', fontName, err);
                        });
                        if (fontInfo.family && fontInfo.family !== 'Arial') zipFontFamilies.add(fontInfo.family);
                    });
                    
                    if (zipFontFamilies.size > 0) {
                        var gfParts = Array.from(zipFontFamilies).map(function(f) {
                            return 'family=' + encodeURIComponent(f) + ':ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,700';
                        });
                        var gfUrl = 'https://fonts.googleapis.com/css2?' + gfParts.join('&') + '&display=swap';
                        var existingLink = document.querySelector('link[data-gfonts]');
                        if (existingLink) existingLink.remove();
                        var link = document.createElement('link');
                        link.rel = 'stylesheet'; link.href = gfUrl; link.setAttribute('data-gfonts', '1');
                        document.head.appendChild(link);
                    }
                    
                    if (jsonObj.objects || jsonObj.version) {
                        if (jsonObj.objects) {
                            jsonObj.objects.forEach(obj => {
                                if (obj.type === 'image' && obj.src) {
                                    const fn = obj.src.split('/').pop();
                                    if (imagesMap[fn]) obj.src = imagesMap[fn];
                                }
                            });
                        }
                        canvas.loadFromJSON(jsonObj, function() {
                            canvas.renderAll();
                            saveState();
                        });
                    } else if (jsonObj.elements) {
                        const legacyConfig = {
                            width: jsonObj.canvas.width || 1080,
                            height: jsonObj.canvas.height || 1080,
                            layers: jsonObj.elements.map((el, idx) => {
                                let l = {
                                    id: 'Layer_' + idx, name: el.name, type: 'unknown',
                                    x: el.x, y: el.y, w: el.w, h: el.h,
                                    opacity: el.opacity, rotation: el.rotation,
                                    visible: el.visible,
                                    blendMode: el.blendMode,
                                    mask_layer_id: el.mask_layer_id,
                                    is_used_as_mask: el.is_used_as_mask
                                };
                                if (el.type === 'image' || el.type === 'frame') {
                                    l.type = 'image'; l.src = el.src;
                                    const fn = (el.src || '').split('/').pop();
                                    if (imagesMap[fn]) l.src = imagesMap[fn];
                                    // Carry over shape/icon metadata so _doRender can restore tint
                                    if (el.tint_color) l.tint_color = el.tint_color;
                                    if (el.is_shape) l.is_shape = el.is_shape;
                                    if (el._originalType) l._originalType = el._originalType;
                                    if (el.is_background) l.is_background = el.is_background;
                                    if (el.is_placeholder) l.is_placeholder = el.is_placeholder;
                                    if (el.is_slot) l.is_slot = el.is_slot;
                                } else if (el.type === 'text') {
                                    l.type = 'text'; l.text = el.text;
                                    // Artera schema stores font as object {family, size, weight, style, color, ...}
                                    // Legacy layer format expects flat fields: font (string), fontSize, color, etc.
                                    if (el.font && typeof el.font === 'object') {
                                        l.font = el.font.family || 'Arial';
                                        l.font_name = el.font.family || 'Arial';
                                        l.fontSize = el.font.size || el.fontSize || 20;
                                        l.size = l.fontSize;
                                        l.weight = el.font.weight || '400';
                                        l.style = el.font.style || 'normal';
                                        l.color = el.font.color || el.color || '#000000';
                                        l.textAlign = el.font.justification || el.textAlign || 'left';
                                        l.justification = l.textAlign;
                                        l.charSpacing = el.font.charSpacing || 0;
                                        l.letterSpacing = l.charSpacing;
                                        l.lineHeight = el.font.lineHeight || el.lineHeight || 1.16;
                                        l.auto_scale = el.font.auto_scale || false;
                                    } else {
                                        l.font = el.font || el.fontFamily || 'Arial';
                                        l.font_name = l.font;
                                        l.fontSize = el.fontSize || 20;
                                        l.size = l.fontSize;
                                        l.color = el.color || '#000000';
                                        l.textAlign = el.textAlign || 'left';
                                        l.justification = l.textAlign;
                                        l.lineHeight = el.lineHeight || 1.16;
                                        l.charSpacing = el.letterSpacing || 0;
                                        l.letterSpacing = l.charSpacing;
                                    }
                                    if (el.kind) l.kind = el.kind;
                                    if (el.textKind) l.textKind = el.textKind;
                                    if (el.placeholder) l.placeholder = el.placeholder;
                                    if (el.shadow) {
                                        l.shadow = { color: el.shadow.color, blur: el.shadow.blur, x: el.shadow.offsetX, y: el.shadow.offsetY };
                                    }
                                } else if (['rect','circle','triangle','path','polygon','line','ellipse'].includes(el.type) || el.type === 'shape') {
                                    l.type = 'shape';
                                    // Use el.shapeType if available (e.g. 'polygon'), fall back to el.type
                                    l.shapeType = el.shapeType || (el.type === 'shape' ? 'rect' : el.type);
                                    l.fill = el.fill || '#000000'; l.stroke = el.stroke;
                                    l.strokeWidth = el.strokeWidth || 0;
                                    // Pass through polygon points and path SVG for round-trip
                                    if (el.points) l.points = el.points;
                                    if (el.svgPath) { l.svgPath = el.svgPath; l.scaleX = el.scaleX; l.scaleY = el.scaleY; }
                                    if (el.rx) l.rx = el.rx;
                                    if (el.ry) l.ry = el.ry;
                                    // Fix: pass through text and font properties for FontAwesome fallback
                                    if (el.text) {
                                        l.text = el.text;
                                        l.font_name = el.font ? el.font.family : (el.fontFamily || 'FontAwesome');
                                        l.size = el.font ? el.font.size : (el.fontSize || el.size || 20);
                                    }
                                    // Fix: pass through src for shapes that were image-based
                                    if (el.src) l.src = el.src;
                                }
                                return l;
                            })
                        };
                        renderJsonToCanvas(legacyConfig, imagesMap);
                    } else if (jsonObj.layers) {
                        renderJsonToCanvas(jsonObj, imagesMap);
                    } else {
                        alert('Unrecognized JSON format in ZIP!');
                    }
                } else {
                    alert('Error loading frame: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(err => {
                if (canvasWrapper) canvasWrapper.style.opacity = '1';
                console.error('[DEBUG] Fetch error:', err);
                alert('Error fetching ZIP data: ' + (err.message || err));
            });
    };

    // ── FONT NORMALIZATION ────────────────────────────────────────────────────
    // Converts Photoshop font names (e.g. "Poppins-ExtraBold") to
    // { family, weight, style } for Fabric.js Textbox
    const FONT_WEIGHT_MAP = {
        'thin': '100', 'extralight': '200', 'ultralight': '200',
        'light': '300', 'regular': '400', 'normal': '400',
        'medium': '500', 'semibold': '600', 'demibold': '600',
        'bold': '700', 'extrabold': '800', 'ultrabold': '800',
        'black': '900', 'heavy': '900',
        // Numeric CSS weights (preserved from previous save/publish cycles)
        '100': '100', '200': '200', '300': '300', '400': '400',
        '500': '500', '600': '600', '700': '700', '800': '800', '900': '900'
    };
    const FONT_STYLE_MAP = { 'italic': 'italic', 'oblique': 'oblique' };

    function normalizePSFont(psFont) {
        if (!psFont) return { family: 'Arial', weight: '400', style: 'normal' };
        // Guard: if psFont is an object (e.g. Artera schema font object), extract family
        if (typeof psFont === 'object') {
            return { family: psFont.family || 'Arial', weight: psFont.weight || '400', style: psFont.style || 'normal' };
        }
        if (typeof psFont !== 'string') return { family: 'Arial', weight: '400', style: 'normal' };
        
        let cleanName = psFont.replace(/[-_]/g, ' ');
        let words = cleanName.split(/\s+/);
        
        let weight = '400';
        let style = 'normal';
        
        while (words.length > 1) {
            let lastWord = words[words.length - 1];
            let raw = lastWord.replace(/([a-z])([A-Z])/g, '$1 $2').toLowerCase().trim();
            
            let matchedAny = false;
            
            if (raw.indexOf('italic') >= 0) {
                style = 'italic';
                raw = raw.replace(/\s*italic\s*/g, ' ').trim();
                matchedAny = true;
            }
            if (raw.indexOf('oblique') >= 0) {
                style = 'oblique';
                raw = raw.replace(/\s*oblique\s*/g, ' ').trim();
                matchedAny = true;
            }
            
            let joinedWeight = raw.replace(/\s+/g, '');
            if (joinedWeight && FONT_WEIGHT_MAP[joinedWeight]) {
                weight = FONT_WEIGHT_MAP[joinedWeight];
                matchedAny = true;
            }
            
            if (matchedAny) {
                words.pop();
            } else {
                break;
            }
        }
        
        let family = words.join(' ').trim();
        return { family, weight, style };
    }

    // Load Google Fonts for all unique font families in config
    // Uses CSS2 API for correct variable-weight font loading
    function loadGoogleFonts(layers) {
        const families = new Set();
        // Fonts that only have weight 400 (no variants)
        const SINGLE_WEIGHT_FONTS = new Set(['Pacifico', 'Dancing Script', 'Cookie', 'Lobster', 'Satisfy']);

        const customFontsLoaded = [];
        const loadedCustomFamilies = new Set(); // Track families whose custom variants are already loaded

        function walk(ls) {
            if (!ls) return;
            ls.forEach(function(l) {
                if ((l.type === 'text' || l.type === 'i-text' || l.type === 'textbox') && l.font) {
                    const baseFamily = normalizePSFont(l.font).family;
                    
                    // Load ALL custom font variants for this family (if not already done)
                    if (typeof GLOBAL_FONTS !== 'undefined' && Array.isArray(GLOBAL_FONTS) && !loadedCustomFamilies.has(baseFamily)) {
                        // Find ALL GLOBAL_FONT entries whose normalized family matches
                        // e.g. for "Montserrat": finds "Montserrat", "Montserrat-Bold", "Montserrat-Italic", etc.
                        const allVariants = GLOBAL_FONTS.filter(function(f) {
                            return normalizePSFont(f.name).family === baseFamily;
                        });
                        
                        if (allVariants.length > 0) {
                            loadedCustomFamilies.add(baseFamily);
                            
                            const basePath = window.location.origin + window.location.pathname.replace(/\/admin\/template-builder.*/, '');
                            
                            // Load EACH variant at its correct CSS weight
                            allVariants.forEach(function(variant) {
                                const fontInfo = normalizePSFont(variant.name);
                                const fontUrl = basePath + '/' + variant.file_path;
                                const fontFace = new FontFace(fontInfo.family, 'url(' + fontUrl + ')', {
                                    weight: fontInfo.weight,
                                    style: fontInfo.style
                                });
                                const p = fontFace.load().then(function(loaded) {
                                    document.fonts.add(loaded);
                                    console.log('[FONTS] Loaded custom font:', variant.name, 'as', fontInfo.family, 'weight=' + fontInfo.weight, 'style=' + fontInfo.style);
                                }).catch(function(err) {
                                    // console.warn('[FONTS] Failed to load custom font:', variant.name, err);
                                });
                                customFontsLoaded.push(p);
                            });
                        }
                    }
                    
                    // ALWAYS also load from Google Fonts as fallback
                    // (provides any weight variants not available as custom fonts)
                    if (baseFamily && baseFamily !== 'Arial') families.add(baseFamily);
                }
                if (l.children) walk(l.children);
                if (l.layers)   walk(l.layers);
            });
        }
        walk(layers);
        
        const customFontsPromise = Promise.all(customFontsLoaded);

        if (families.size === 0) return customFontsPromise;

        // Build Google Fonts CSS2 API URL
        // Multi-weight: family=Poppins:ital,wght@0,100;0,200;...;1,900
        // Single-weight: family=Pacifico
        const familyParts = Array.from(families).map(function(f) {
            if (SINGLE_WEIGHT_FONTS.has(f)) return 'family=' + encodeURIComponent(f);
            return 'family=' + encodeURIComponent(f) + ':ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,700';
        });
        const url = 'https://fonts.googleapis.com/css2?' + familyParts.join('&') + '&display=swap';

        const googleFontsPromise = new Promise(function(resolve) {
            if (document.querySelector('link[data-gfonts]')) {
                document.querySelector('link[data-gfonts]').remove();
            }
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = url;
            link.setAttribute('data-gfonts', '1');
            link.onload = function() { setTimeout(resolve, 400); };
            link.onerror = resolve;
            document.head.appendChild(link);
            console.log('[FONTS] Loading Google Fonts:', url);
        });

        return Promise.all([customFontsPromise, googleFontsPromise]);
    }

    function pathDataToSVG(pathData, layerX, layerY) {
        if (!pathData || !Array.isArray(pathData) || pathData.length === 0) return null;
        let d = '';
        pathData.forEach(function(subpath) {
            if (!subpath.points || subpath.points.length === 0) return;
            const pts = subpath.points;
            // Move to first anchor (relative to layer position)
            d += 'M ' + (pts[0].anchor.x - layerX) + ' ' + (pts[0].anchor.y - layerY) + ' ';
            for (let i = 1; i < pts.length; i++) {
                const prev = pts[i - 1];
                const curr = pts[i];
                // Cubic bezier: C cp1x,cp1y cp2x,cp2y x,y
                const cp1x = prev.forward ? (prev.forward.x - layerX) : (prev.anchor.x - layerX);
                const cp1y = prev.forward ? (prev.forward.y - layerY) : (prev.anchor.y - layerY);
                const cp2x = curr.backward ? (curr.backward.x - layerX) : (curr.anchor.x - layerX);
                const cp2y = curr.backward ? (curr.backward.y - layerY) : (curr.anchor.y - layerY);
                d += 'C ' + cp1x + ',' + cp1y + ' ' + cp2x + ',' + cp2y + ' ' + (curr.anchor.x - layerX) + ',' + (curr.anchor.y - layerY) + ' ';
            }
            // Close path: last point back to first
            if (subpath.closed !== false) {
                const last = pts[pts.length - 1];
                const first = pts[0];
                const cp1x = last.forward ? (last.forward.x - layerX) : (last.anchor.x - layerX);
                const cp1y = last.forward ? (last.forward.y - layerY) : (last.anchor.y - layerY);
                const cp2x = first.backward ? (first.backward.x - layerX) : (first.anchor.x - layerX);
                const cp2y = first.backward ? (first.backward.y - layerY) : (first.anchor.y - layerY);
                d += 'C ' + cp1x + ',' + cp1y + ' ' + cp2x + ',' + cp2y + ' ' + (first.anchor.x - layerX) + ',' + (first.anchor.y - layerY) + ' ';
                d += 'Z ';
            }
        });
        return d.trim() || null;
    }

    function buildFabricGradient(gradientData, objWidth, objHeight) {
        if (!gradientData || !gradientData.colorStops || gradientData.colorStops.length < 2) return null;
        const angle = (gradientData.angle || 0) * Math.PI / 180;
        const type = (gradientData.type === 'radial') ? 'radial' : 'linear';
        
        let coords;
        if (type === 'linear') {
            // Proper linear gradient coords from angle
            const cos = Math.cos(angle);
            const sin = Math.sin(angle);
            coords = {
                x1: objWidth / 2 - cos * objWidth / 2,
                y1: objHeight / 2 - sin * objHeight / 2,
                x2: objWidth / 2 + cos * objWidth / 2,
                y2: objHeight / 2 + sin * objHeight / 2
            };
        } else {
            coords = {
                x1: objWidth / 2, y1: objHeight / 2,
                x2: objWidth / 2, y2: objHeight / 2,
                r1: 0, r2: Math.max(objWidth, objHeight) / 2
            };
        }
        
        return new fabric.Gradient({
            type: type,
            coords: coords,
            colorStops: gradientData.colorStops.map(function(cs) {
                return {
                    offset: Math.min(1, Math.max(0, cs.location / 4096)),
                    color: cs.color || '#000000'
                };
            })
        });
    }

    function renderJsonToCanvas(config, images) {

        console.log('[DEBUG] renderJsonToCanvas called. Layers count:', config.layers ? config.layers.length : 0);
        // Load Google Fonts first, then render
        loadGoogleFonts(config.layers).then(function() {
            try {
                _doRender(config, images);
                if (document.fonts && document.fonts.ready) {
                    document.fonts.ready.then(function() {
                        if (canvas) canvas.renderAll();
                    });
                }
            } catch (e) {
                console.error('[RENDER ERROR] _doRender crashed:', e);

            }
        }).catch(function(e) {
            console.error('[RENDER ERROR] loadGoogleFonts failed:', e);

            try { 
                _doRender(config, images); 
                if (document.fonts && document.fonts.ready) {
                    document.fonts.ready.then(function() {
                        if (canvas) canvas.renderAll();
                    });
                }
            } catch (e2) { console.error('[RENDER ERROR] _doRender crashed:', e2); }
        });
    }

    function _doRender(config, images) {
        // ── Read render version from config (default to 1 for legacy frames) ──
        const renderVersion = config.render_version || 1;
        console.log('[DEBUG] _doRender called. render_version:', renderVersion, '| images map keys:', images ? Object.keys(images).length + ' keys: ' + Object.keys(images).slice(0,5).join(', ') : 'NO IMAGES MAP');
        canvas.clear();
        templateImages = images;
        
        if (config.info && config.info.width && config.info.height) {
            baseWidth = parseInt(config.info.width);
            baseHeight = parseInt(config.info.height);
            // Read document DPI — exported by JSX for 300 DPI accuracy verification
            var psdDpi = config.info.dpi ? parseInt(config.info.dpi) : 72;
            console.log('[DEBUG] Canvas:', baseWidth, 'x', baseHeight, '| Document DPI:', psdDpi);
            if (psdDpi !== 72 && psdDpi !== 96) {
                console.log('[INFO] High-DPI PSD (' + psdDpi + ' DPI). Font sizes & coords in JSON are in document pixels (already DPI-corrected by JSX).');
            }
            const wInput = $('template-w');
            const hInput = $('template-h');
            if (wInput) wInput.value = baseWidth;
            if (hInput) hInput.value = baseHeight;
            currentScale = updateCanvasZoom();
        }
        
        if(!config.layers) { console.log('[DEBUG] No layers found!'); return; }

        // Flatten nested layers (from groups) — supports both 'children' and 'layers' keys
        function flattenLayers(layersArray, depth) {
            depth = depth || 0;
            let result = [];
            if (!layersArray || !Array.isArray(layersArray)) return result;
            layersArray.forEach(l => {
                const nested = l.children || l.layers;
                if (l.type === 'group' && nested && Array.isArray(nested) && nested.length > 0) {
                    console.log('[DEBUG] Flatten: group "' + l.name + '" has ' + nested.length + ' children at depth ' + depth);
                    result = result.concat(flattenLayers(nested, depth + 1));
                } else if (l.type === 'group') {
                    // Group with no children — skip it (empty group)
                    console.log('[DEBUG] Flatten: empty group "' + l.name + '" skipped');
                } else {
                    console.log('[DEBUG] Flatten: leaf "' + (l.name || l.id) + '" type=' + l.type + ' at depth ' + depth);
                    result.push(l);
                }
            });
            return result;
        }

        const flatLayers = flattenLayers(config.layers);
        console.log('[DEBUG] After flatten: ' + flatLayers.length + ' renderable layers from ' + config.layers.length + ' top-level entries');

        // Sort layers: ASCENDING z_index (z_index 1 = bottom/background added first, highest = foreground added last)
        const sortedLayers = flatLayers.sort((a,b) => (a.z_index||0) - (b.z_index||0));
        console.log('[DEBUG] Sorted layers (first 5):', sortedLayers.slice(0,5).map(l => l.name + '|z:' + l.z_index + '|type:' + l.type));

        // Resolve base URL for image paths
        // JSON src paths look like: "../skins/PSD_NAME/filename.png"
        // These map to: /Artera/public/uploads/template/UUID/skins/PSD_NAME/filename.png
        const baseUrl = window.location.origin + window.location.pathname.replace(/\/admin\/template-builder.*/, '');

        let totalAsyncLoads = 0;
        let completedAsyncLoads = 0;
        
        function checkAllLoaded() {
            if (completedAsyncLoads >= totalAsyncLoads && totalAsyncLoads > 0) {
                console.log('[DEBUG] All async images loaded. Running PSD clipping mask auto-detection...');
                
                // ── AUTO-DETECT PSD Clipping Masks ──
                // In Photoshop, a Clipping Mask clips an image to the shape below it.
                // When exported, both layers get the same x/y/w/h but no mask_layer_id.
                // We auto-detect: if an image layer (non-shape) shares exact position/size
                // with a shape layer directly below it, treat as clipping mask.
                const allObjs = canvas.getObjects();
                allObjs.forEach((obj, idx) => {
                    if (obj.type === 'image' && !obj.mask_layer_id && obj.customType !== 'shape') {
                        const myX = Math.round(obj.left);
                        const myY = Math.round(obj.top);
                        const myW = Math.round(obj.width * obj.scaleX);
                        const myH = Math.round(obj.height * obj.scaleY);
                        
                        console.log('[MASK_AUTODETECT] Checking "' + obj.customName + '" (idx=' + idx + ') pos=(' + myX + ',' + myY + ',' + myW + ',' + myH + ')');
                        
                        // Search backward for a shape with matching bounds
                        for (let i = idx - 1; i >= 0; i--) {
                            const cand = allObjs[i];
                            const isShape = cand.customType === 'shape' || cand.is_shape === true;
                            if (!isShape) continue;
                            
                            const cx = Math.round(cand.left);
                            const cy = Math.round(cand.top);
                            const cw = Math.round(cand.width * cand.scaleX);
                            const ch = Math.round(cand.height * cand.scaleY);
                            
                            console.log('[MASK_AUTODETECT]   vs "' + cand.customName + '" (idx=' + i + ') pos=(' + cx + ',' + cy + ',' + cw + ',' + ch + ') isShape=' + isShape);
                            
                            if (Math.abs(myX - cx) < 3 && Math.abs(myY - cy) < 3 && 
                                Math.abs(myW - cw) < 3 && Math.abs(myH - ch) < 3) {
                                console.log('[MASK_AUTODETECT] ✅ MATCH! "' + obj.customName + '" clips to "' + cand.customName + '" (PSD Clipping Mask)');
                                const maskId = cand.id || cand.customName;
                                obj.set('mask_layer_id', maskId);
                                break;
                            }
                        }
                    }
                });
                
                console.log('[DEBUG] Applying visual masks...');
                canvas.getObjects().forEach(obj => {
                    if (obj.type === 'image' && obj.mask_layer_id) {
                        console.log('[MASK_DIAG] Applying mask for "' + obj.customName + '" → mask_layer_id="' + obj.mask_layer_id + '"');
                        applyVisualMaskPreview(obj, obj.mask_layer_id);
                    }
                });
                canvas.requestRenderAll();

                // Store original JSON for diff comparison (read-only copy)
                // Use the passed config which holds the raw loaded JSON, or canvas.toJSON if needed
                window._originalLoadedJson = JSON.parse(JSON.stringify(config));
                window._originalRenderVersion = window._originalLoadedJson.render_version || 1;
            }
        }

        function resolveImageSrc(src) {
            if (!src) return '';
            // Already absolute URL or data URI
            if (src.startsWith('http') || src.startsWith('data:')) return src;
            // Base64 from images map
            const fn = src.split('/').pop();
            if (images && images[fn]) return images[fn];
            
            // Try normalized matching (handle spaces replaced by dashes/underscores on server)
            if (images) {
                const normalizedFn = fn.toLowerCase().replace(/[ \-_]/g, '');
                const matchKey = Object.keys(images).find(k => k.toLowerCase().replace(/[ \-_]/g, '') === normalizedFn);
                if (matchKey) return images[matchKey];
            }

            // Relative path like "../skins/Hiring_103/filename.png"
            if (src.includes('../skins/')) {
                const parts = src.split('../skins/')[1].split('/');
                const psdName = parts[0];
                const filename = parts.slice(1).join('/');
                const uuid = window.currentTemplateZipUuid || psdName;
                return baseUrl + '/uploads/template/' + uuid + '/skins/' + psdName + '/' + filename;
            }
            return src;
        }

        sortedLayers.forEach((layer, idx) => {
            console.log('[DEBUG] Rendering layer ' + idx + ': "' + layer.name + '" type=' + layer.type + ' x=' + layer.x + ' y=' + layer.y + ' w=' + layer.w + ' h=' + layer.h);
            const opacity  = (layer.opacity !== undefined && layer.opacity !== null) ? parseFloat(layer.opacity) : 1;
            const rotation = layer.rotation || 0;
            const visible  = layer.visible !== false;

            // ── Build fabric Shadow from PSD effects ─────────────────────────
            let shadow = null;
            if (layer.effects && layer.effects.dropShadow) {
                const ds = layer.effects.dropShadow;
                const angle = ((ds.angle || 135) * Math.PI) / 180;
                const dist  = ds.distance || 4;
                // Build shadow color with opacity
                let shadowColor = ds.color || 'rgba(0,0,0,0.5)';
                if (ds.opacity !== undefined && ds.opacity < 100 && shadowColor.charAt(0) === '#') {
                    const hex = shadowColor.replace('#', '');
                    const sr = parseInt(hex.substring(0,2), 16);
                    const sg = parseInt(hex.substring(2,4), 16);
                    const sb = parseInt(hex.substring(4,6), 16);
                    shadowColor = 'rgba(' + sr + ',' + sg + ',' + sb + ',' + (ds.opacity / 100) + ')';
                }
                shadow = new fabric.Shadow({
                    color:   shadowColor,
                    blur:    ds.blur || 4,
                    offsetX: Math.round(Math.cos(angle) * dist),
                    offsetY: Math.round(Math.sin(angle) * dist)
                });
            }

            // Inner shadow (simulated)
            let innerShadow = null;
            if (layer.effects && layer.effects.innerShadow) {
                const is = layer.effects.innerShadow;
                const isAngle = ((is.angle || 135) * Math.PI) / 180;
                const isDist = is.distance || 4;
                let isColor = is.color || 'rgba(0,0,0,0.5)';
                if (is.opacity !== undefined && is.opacity < 100 && isColor.charAt(0) === '#') {
                    const hex = is.color.replace('#', '');
                    isColor = 'rgba(' + parseInt(hex.substring(0,2),16) + ',' + parseInt(hex.substring(2,4),16) + ',' + parseInt(hex.substring(4,6),16) + ',' + (is.opacity/100) + ')';
                }
                // Fabric doesn't have native inner shadow — simulate via negative offset shadow
                innerShadow = new fabric.Shadow({
                    color: isColor,
                    blur: is.blur || 4,
                    offsetX: -Math.round(Math.cos(isAngle) * isDist),
                    offsetY: -Math.round(Math.sin(isAngle) * isDist)
                });
            }

            // Outer glow (simulated as zero-offset shadow)
            if (layer.effects && layer.effects.outerGlow && !shadow) {
                const og = layer.effects.outerGlow;
                let ogColor = og.color || 'rgba(255,255,255,0.5)';
                if (og.opacity !== undefined && og.opacity < 100 && ogColor.charAt(0) === '#') {
                    const hex = og.color.replace('#', '');
                    ogColor = 'rgba(' + parseInt(hex.substring(0,2),16) + ',' + parseInt(hex.substring(2,4),16) + ',' + parseInt(hex.substring(4,6),16) + ',' + (og.opacity/100) + ')';
                }
                shadow = new fabric.Shadow({
                    color: ogColor,
                    blur: og.blur || 10,
                    offsetX: 0,
                    offsetY: 0
                });
            }

            // ── Build stroke props ────────────────────────────────────────────
            let strokeColor = null, strokeWidth = 0;
            if (layer.stroke) {
                strokeColor = layer.stroke.color || null;
                strokeWidth = layer.stroke.width || 0;
            }

            // Smart Objects ALWAYS render as images (they have raster content)
            if (layer.isSmartObject && layer.type === 'shape') {
                layer.type = 'image';
            }

            // Shapes with valid src should prioritize rendering as images to preserve PSD layer effects
            if (layer.is_shape === true && layer.src && layer.src !== '') {
                layer.type = 'image';
                layer.is_shape = false; // Bypass shape vector logic
                // But we still want them to act like shapes in the UI
                // Preserve _originalType from JSON if already set (e.g., 'icon'), else default to 'shape'
                if (!layer._originalType) layer._originalType = 'shape'; 
            }

            // ─────────────────────────────────────────────────────────────────
            // IMAGE LAYER
            // ─────────────────────────────────────────────────────────────────
            if (layer.type === 'image' && layer.is_shape !== true) {
                totalAsyncLoads++;
                const imgSrc = resolveImageSrc(layer.src);
                console.log('[DEBUG] Image "' + layer.name + '": src=' + (layer.src || '').substring(0,60) + ' → resolved=' + (imgSrc || '').substring(0,80) + (imgSrc && imgSrc.startsWith('data:') ? ' [BASE64 len=' + imgSrc.length + ']' : ''));

                // Helper: show placeholder rect without trying to load the image
                function showImgPlaceholder() {
                    const ph = new fabric.Rect({
                        left: layer.x, top: layer.y,
                        width: layer.w, height: layer.h,
                        fill: 'rgba(255,255,255,0.01)',
                        stroke: 'transparent', strokeWidth: 0,
                        opacity: opacity, angle: rotation,
                        customType: 'image', customName: layer.name,
                        is_background: layer.is_background,
                        is_placeholder: layer.is_placeholder,
                        is_slot: layer.is_slot,
                        mask_layer_id: layer.mask_layer_id,
                        visible: visible,
                        is_image_placeholder: true,
                        _src: layer.src || layer._fallback_src || ''
                    });
                    canvas.add(ph);
                    if (typeof sortCanvasLayers === 'function') {
                        try { sortCanvasLayers(); } catch(e) { console.error('[SORT ERROR]', e); }
                    }
                    updateLayersList();
                    completedAsyncLoads++;
                    checkAllLoaded();
                }

                // Helper: load image via Fabric
                function doFabricLoad() {
                    console.log('[DEBUG] doFabricLoad for "' + layer.name + '" - calling fabric.Image.fromURL');
                    try {
                        fabric.Image.fromURL(imgSrc, function(img) {
                            if (!img || !img.width) { console.warn('[DEBUG] Image FAILED for "' + layer.name + '" - img:', img, 'width:', img ? img.width : 'null'); showImgPlaceholder(); return; }
                            console.log('[DEBUG] Image LOADED for "' + layer.name + '" - ' + img.width + 'x' + img.height);
                            // Determine the correct customType to restore
                            var restoredCustomType = 'image';
                            if (layer._originalType === 'shape' || layer._originalType === 'icon') restoredCustomType = layer._originalType;
                            else if (layer.is_shape) restoredCustomType = 'shape';
                            img.set({
                                left: layer.x, top: layer.y,
                                originX: 'left', originY: 'top',
                                angle: rotation, opacity: opacity,
                                shadow: shadow,
                                customType: restoredCustomType,
                                customName: layer.name,
                                is_background: layer.is_background,
                                is_placeholder: layer.is_placeholder,
                                is_slot: layer.is_slot,
                                mask_layer_id: layer.mask_layer_id,
                                visible: visible,
                                z_index: layer.z_index || idx
                            });
                            img.set({ scaleX: layer.w / img.width, scaleY: layer.h / img.height });
                            
                            // ── Non-Destructive Metadata (Phase 1A) ──
                            if (layer._source_meta) {
                                img._iconName = layer._source_meta.iconName;
                                img._iconProvider = layer._source_meta.provider;
                                img._originalSvgMarkup = layer._source_meta.originalSvg;
                            }

                            // Restore is_shape flag so color picker + export works
                            if (layer.is_shape || layer._originalType === 'shape' || layer._originalType === 'icon') {
                                img.is_shape = true;
                            }
                            console.log("--> _doRender image layer:", layer.name, "tint_color:", layer.tint_color, "blendColor type:", typeof fabric.Image.filters.BlendColor, "is_shape:", layer.is_shape);
                            // Restore tint_color (saved fill color) for image-converted shapes
                            if (layer.tint_color && typeof fabric.Image.filters.BlendColor !== 'undefined') {
                                img.set('fill', layer.tint_color);
                                img.filters = [new fabric.Image.filters.BlendColor({ color: layer.tint_color, mode: 'tint', alpha: 1 })];
                                img.applyFilters();
                                console.log('[LOAD] Applied tint_color=' + layer.tint_color + ' to "' + layer.name + '"');
                            }
                            // Remove any temporary placeholder rectangle added before actual image load
                            canvas.getObjects().forEach(function(o) {
                                if (o.is_image_placeholder && o.customName === layer.name) {
                                    canvas.remove(o);
                                }
                            });
                            canvas.add(img);
                            if (typeof sortCanvasLayers === 'function') {
                                try { sortCanvasLayers(); } catch(e) { console.error('[SORT ERROR]', e); }
                            }
                            canvas.renderAll();
                            updateLayersList();
                            completedAsyncLoads++;
                            checkAllLoaded();
                        }, imgSrc.startsWith('data:') || imgSrc.startsWith('blob:') ? {} : { crossOrigin: 'anonymous' });
                    } catch (e) {
                        console.error('[FABRIC IMAGE ERROR] for layer:', layer.name, e);
                        completedAsyncLoads++;
                        checkAllLoaded();
                    }
                }

                // If we have a blob URL or data URI, load directly without HEAD check
                if (imgSrc.startsWith('blob:') || imgSrc.startsWith('data:')) {
                    doFabricLoad();
                } else {
                    // HEAD-check first to prevent console 404 spam from Fabric
                    fetch(imgSrc, { method: 'HEAD' })
                        .then(function(r) { r.ok ? doFabricLoad() : showImgPlaceholder(); })
                        .catch(function() { showImgPlaceholder(); });
                }


            // ─────────────────────────────────────────────────────────────────

            // TEXT LAYER
            // ─────────────────────────────────────────────────────────────────
            } else if (layer.type === 'text' || layer.type === 'i-text' || layer.type === 'textbox') {
                // ── Flatten nested font object (Artera Schema format) ──
                // When re-loading a saved Artera frame, font properties are nested under
                // layer.font = { size, family, weight, style, color, justification, ... }.
                // Flatten them to top-level so the rest of the parsing works uniformly
                // with both PSD-imported (flat) and Artera-saved (nested) JSON formats.
                if (layer.font && typeof layer.font === 'object') {
                    const f = layer.font;
                    if (f.size != null && !layer.size)                          layer.size = f.size;
                    if (f.family && layer.font_name == null)                    layer.font_name = f.family;
                    if (f.weight && !layer.weight)                              layer.weight = f.weight;
                    if (f.style && !layer.style)                                layer.style = f.style;
                    if (f.color && !layer.color && !layer.fill)                 layer.color = f.color;
                    if (f.justification && !layer.justification)                layer.justification = f.justification;
                    if (f.charSpacing != null && layer.letterSpacing == null)    layer.letterSpacing = f.charSpacing;
                    if (f.wordSpacing != null && layer.wordSpacing == null)      layer.wordSpacing = f.wordSpacing;
                    if (f.lineHeight != null && layer.lineHeight == null)        layer.lineHeight = f.lineHeight;
                    if (f.auto_scale != null && layer.auto_scale == null)        layer.auto_scale = f.auto_scale;
                    // Replace the font object with the family string for normalizePSFont()
                    layer.font = f.family || layer.font_name;
                    console.log('[FLATTEN] Flattened font object for "' + layer.name + '": size=' + layer.size + ', justification=' + layer.justification + ', font=' + layer.font);
                }

                // Font size: PSD stores in points, Fabric uses px. 1pt = 1.333px @ 96dpi.
                // But our JSX already outputs size in px from the descriptor (getDouble returns px).
                // Use size directly. Fallback: derive from layer height.
                let parsedFontSize = 24;
                if (layer.size) {
                    parsedFontSize = parseFloat(layer.size);
                } else if (layer.font_size) {
                    parsedFontSize = parseFloat(layer.font_size);
                    // Native app uses AutoSizeText to fit bounds. If legacy PSD font_size is abnormally small (e.g., 6px for 35px height), override it to match the visual height.
                    if (layer.h && parsedFontSize < (layer.h * 0.4)) {
                        parsedFontSize = Math.round(layer.h * 0.72);
                    }
                } else if (layer.h) {
                    parsedFontSize = Math.round(layer.h * 0.72);
                }
                const fontSize = parsedFontSize;

                // charSpacing: Fabric uses 1/1000 em units. PSD letterSpacing is in thousandths.
                const charSpacing = layer.letterSpacing !== undefined ? parseFloat(layer.letterSpacing) : 0;

                // lineHeight: PSD leading / fontSize gives fabric lineHeight multiplier
                // Guard: values < 0.5 are corrupted (from legacy export bug: multiplier / fontSize).
                let lineHeight = 1.16;
                if (layer.lineHeight && layer.lineHeight !== 'auto' && fontSize > 0) {
                    let parsedLineHeight = parseFloat(layer.lineHeight);
                    if (parsedLineHeight < 0.5) {
                        // Corrupted value (e.g. 0.0387 = 1.16/30) — use default
                        lineHeight = 1.16;
                    } else if (parsedLineHeight < 10) {
                        lineHeight = parsedLineHeight; // It's already a multiplier
                    } else {
                        lineHeight = parsedLineHeight / fontSize; // Convert from pixels to multiplier
                    }
                }

                const fontInfo   = normalizePSFont(layer.font || layer.font_name);
                const fontFamily = fontInfo.family;
                // layer.weight may be a PS keyword like "medium", "bold", "regular", or a CSS weight like "700"
                // Map it through FONT_WEIGHT_MAP to get a valid CSS weight ("500", "700" etc.)
                const rawLayerWeight = layer.weight ? String(layer.weight).toLowerCase().trim() : null;
                const fontWeight = (rawLayerWeight && FONT_WEIGHT_MAP[rawLayerWeight])
                    ? FONT_WEIGHT_MAP[rawLayerWeight]
                    : fontInfo.weight;
                const fontStyle  = layer.style || fontInfo.style || 'normal';

                // Intelligent placeholder for legacy frames that didn't supply text
                let defaultText = '';
                if (layer.text === undefined || layer.text === null || layer.text === '') {
                    const lName = (layer.name || layer.id || '').toLowerCase();
                    if (lName.includes('number') || lName.includes('phone') || lName.includes('call')) defaultText = '+91 9876543210';
                    else if (lName.includes('email') || lName.includes('mail')) defaultText = 'example@email.com';
                    else if (lName.includes('web') || lName.includes('site')) defaultText = 'www.yourwebsite.com';
                    else if (lName.includes('address') || lName.includes('location')) defaultText = 'Your Business Address Here';
                    else defaultText = 'Your Text Here';
                }
                // Normalize line breaks (\r → \n) from Photoshop
                const rawText = (layer.text !== undefined && layer.text !== null && layer.text !== '' ? String(layer.text) : defaultText).replace(/\r\n/g, '\n').replace(/\r/g, '\n');
                
                // Detect Point Text vs Paragraph Text (area text):
                // textKind is exported by PhotoshopExtractorV3.jsx (new).
                // Point Text: no wrapping needed → fabric.Text
                // Paragraph Text: wraps within text frame → fabric.Textbox with exact width
                // Fallback heuristic for old JSONs without textKind:
                //   single-line AND height < 2× fontSize → treat as point text
                const hasHardBreak = rawText.includes('\n');
                const isPointText = (layer.textKind === 'point' || layer.kind === 'Point' || layer.kind === 'point')
                    || (!layer.textKind && !layer.kind && !hasHardBreak && (layer.h < fontSize * 2.2));
                const isParagraphText = (layer.textKind === 'paragraph' || layer.kind === 'Paragraph' || layer.kind === 'paragraph')
                    || (!layer.textKind && !layer.kind && (hasHardBreak || (layer.h >= fontSize * 2.2)));

                // Common text properties
                // Y-OFFSET FIX: Photoshop boundsNoEffects.y = top of VISUAL (tight) bounds (cap height).
                // Fabric.js top = top of em-box, which includes internal leading ABOVE cap height.
                // This internal leading ≈ fontSize * 0.12 in most fonts.
                // Apply this offset to ALL text to perfectly sync Web Editor, Native App, and Photoshop.
                const fabricYOffset = Math.round(fontSize * 0.12);
                const commonTextProps = {
                    left:        layer.x,
                    top:         layer.y - fabricYOffset,
                    fontSize:    fontSize,
                    fontFamily:  fontFamily,
                    fontWeight:  fontWeight,

                    fontStyle:   fontStyle,
                    fill:        layer.color || layer.fill || '#000000',
                    textAlign:   layer.justification || 'left',
                    charSpacing: charSpacing,
                    lineHeight:  lineHeight,
                    originX:     'left',
                    originY:     'top',
                    angle:       rotation,
                    opacity:     opacity,
                    shadow:      shadow,
                    visible:     visible,
                    customName:  layer.name,
                    customType:  'text',
                    ai_role:     layer.ai_role,
                    ai_max_chars: layer.ai_max_chars,
                    // Save original data to prevent drift on export
                    _originalYOffset: fabricYOffset,
                    _psdData: { x: layer.x, y: layer.y, w: layer.w, h: layer.h, text: rawText, fontFamily: fontFamily }
                };

                let t;
                if (isPointText) {
                    // Point Text: fabric.Text — never wraps, glyph bounds match PS Transform panel
                    t = new fabric.Text(rawText, commonTextProps);
                } else {
                    // Paragraph Text: fabric.Textbox — wraps within the text frame.
                    // Use exact width from JSON (JSX exports the actual Photoshop text frame width).
                    // No 15% buffer needed since the frame width IS from PS.
                    t = new fabric.Textbox(rawText, {
                        ...commonTextProps,
                        width:           layer.w || 200,
                        splitByGrapheme: false
                    });
                }

                canvas.add(t);
                if (typeof sortCanvasLayers === 'function') {
                    try { sortCanvasLayers(); } catch(e) { console.error('[SORT ERROR]', e); }
                }
                updateLayersList();

            // ─────────────────────────────────────────────────────────────────
            // SHAPE LAYER — full shapeType support
            // ─────────────────────────────────────────────────────────────────
            } else if (layer.type === 'shape' || layer.is_shape === true) {
                // Respect fillEnabled flag from Photoshop
                let shapeColor;
                if (layer.fillEnabled === false) {
                    shapeColor = 'transparent';
                } else {
                    shapeColor = layer.fillColor || layer.fill || layer.color || 'transparent';
                    if (typeof shapeColor === 'object' && shapeColor.type === 'linear') {
                        shapeColor = new fabric.Gradient(shapeColor);
                    }
                }
                // Handle fillOpacity (separate from layer opacity)
                const fillOpacity = (layer.fillOpacity !== undefined && layer.fillOpacity !== null) 
                    ? parseFloat(layer.fillOpacity) / 100 : 1;
                const st         = (layer.shapeType || layer.shape_type || 'rectangle').toLowerCase();
                let obj = null;

                const baseProps = {
                    left:        layer.x,
                    top:         layer.y,
                    fill:        shapeColor,
                    stroke:      strokeColor,
                    strokeWidth: strokeWidth,
                    originX:     'left',
                    originY:     'top',
                    angle:       rotation,
                    opacity:     opacity,
                    shadow:      shadow,
                    visible:     visible,
                    customType:  'shape',
                    customName:  layer.name
                };

                // ── ELLIPSE / CIRCLE ─────────────────────────────────────────
                if (st === 'ellipse' || st === 'circle') {
                    obj = new fabric.Ellipse({
                        ...baseProps,
                        rx: Math.round(layer.w / 2),
                        ry: Math.round(layer.h / 2)
                    });
                }
                // ── ROUNDED RECT ──────────────────────────────────────────────
                else if (st === 'roundedrect' || st === 'rounded_rect' || st === 'rounded-rect') {
                    let rx = 0, ry = 0;
                    if (layer.cornerRadius !== undefined && layer.cornerRadius !== null) {
                        const cr = layer.cornerRadius;
                        if (typeof cr === 'number') { rx = cr; ry = cr; }
                        else if (typeof cr === 'object') {
                            // Use average for fabric.js which has single rx/ry
                            rx = Math.round((cr.topLeft + cr.topRight + cr.bottomRight + cr.bottomLeft) / 4);
                            ry = rx;
                        }
                    } else if (layer.borderRadius !== undefined) {
                        const br = layer.borderRadius;
                        if (typeof br === 'number') { rx = br; ry = br; }
                        else if (typeof br === 'object') {
                            rx = Math.round((br.topLeft + br.topRight + br.bottomRight + br.bottomLeft) / 4);
                            ry = rx;
                        }
                    }
                    obj = new fabric.Rect({
                        ...baseProps,
                        width:  layer.w,
                        height: layer.h,
                        rx:     rx,
                        ry:     ry
                    });
                }
                // ── RECTANGLE ─────────────────────────────────────────────────
                else if (st === 'rectangle' || st === 'rect') {
                    obj = new fabric.Rect({
                        ...baseProps,
                        width:  layer.w,
                        height: layer.h,
                        rx: 0, ry: 0
                    });
                }
                // ── POLYGON / TRIANGLE ────────────────────────────────────────
                else if (st === 'polygon' || st === 'triangle') {
                    let points = [];
                    if (layer.points && Array.isArray(layer.points) && layer.points.length >= 3) {
                        // Use absolute points, offset by layer position
                        points = layer.points.map(function(p) {
                            return { x: p.x - layer.x, y: p.y - layer.y };
                        });
                    } else {
                        // Default triangle
                        points = [
                            { x: layer.w / 2, y: 0 },
                            { x: layer.w, y: layer.h },
                            { x: 0, y: layer.h }
                        ];
                    }
                    obj = new fabric.Polygon(points, {
                        ...baseProps,
                    });
                }
                // ── LINE ──────────────────────────────────────────────────────
                else if (st === 'line') {
                    obj = new fabric.Line(
                        [layer.x, layer.y, layer.x + layer.w, layer.y + layer.h],
                        {
                            stroke:      strokeColor || shapeColor,
                            strokeWidth: Math.max(strokeWidth, 2),
                            opacity:     opacity,
                            angle:       rotation,
                            shadow:      shadow,
                            visible:     visible,
                            customType:  'shape',
                            customName:  layer.name,
                            fill:        null
                        }
                    );
                }
                // ── CUSTOM SHAPE — render from svgPath or pathData ────
                else {
                    // First try our exported svgPath string (from polygon-to-path or path round-trip)
                    if (layer.svgPath && typeof layer.svgPath === 'string') {
                        obj = new fabric.Path(layer.svgPath, {
                            left: layer.x,
                            top: layer.y,
                            fill: shapeColor,
                            stroke: strokeColor,
                            strokeWidth: strokeWidth,
                            originX: 'left',
                            originY: 'top',
                            scaleX: layer.scaleX || 1,
                            scaleY: layer.scaleY || 1,
                            angle: rotation,
                            opacity: opacity,
                            shadow: shadow,
                            visible: visible,
                            customType: 'shape',
                            customName: layer.name
                        });
                        console.log('[LOAD] Path from svgPath:', layer.name);
                    }
                    // Then try PSD-format pathData
                    else if (layer.pathData && Array.isArray(layer.pathData) && layer.pathData.length > 0) {
                        const svgPath = pathDataToSVG(layer.pathData, layer.x, layer.y);
                        if (svgPath) {
                            obj = new fabric.Path(svgPath, {
                                left: layer.x,
                                top: layer.y,
                                fill: shapeColor,
                                stroke: strokeColor,
                                strokeWidth: strokeWidth,
                                originX: 'left',
                                originY: 'top',
                                angle: rotation,
                                opacity: opacity,
                                shadow: shadow,
                                visible: visible,
                                customType: 'shape',
                                customName: layer.name
                            });
                        }
                    }
                    // Fallback if no pathData: try loading src as image
                    if (!obj && layer.src) {
                        const imgSrc = resolveImageSrc(layer.src);
                        fabric.Image.fromURL(imgSrc, function(img) {
                            if (!img || !img.width) return;
                            img.set({
                                left: layer.x, top: layer.y,
                                originX: 'left', originY: 'top',
                                angle: rotation, opacity: opacity,
                                shadow: shadow, visible: visible,
                                customType: 'shape', customName: layer.name,
                                is_background: layer.is_background,
                                is_slot: layer.is_slot,
                                z_index: layer.z_index || idx
                            });
                            img.set({ scaleX: layer.w / img.width, scaleY: layer.h / img.height });
                            
                            canvas.add(img);
                            if (typeof sortCanvasLayers === 'function') {
                                try { sortCanvasLayers(); } catch(e) { console.error('[SORT ERROR]', e); }
                            }
                            canvas.renderAll();
                            updateLayersList();
                        }, { crossOrigin: 'anonymous' });
                        return; // Skip the canvas.add below since it's async
                    }
                    // Last resort fallback: use fillColor if available, else skip (invisible)
                    if (!obj) {
                        const layerNameLC = (layer.name || '').toLowerCase();
                        // Skip shadow/blur layers that can't be rendered as vectors
                        if (layerNameLC.includes('shadow') || layerNameLC.includes('blur')) {
                            // Intentionally skip — these need raster render
                        } else {
                            // Render as solid rect using fillColor from JSON (fix: proper ternary precedence)
                            const fallbackFill = (layer.fillEnabled === false) ? 'transparent' : (layer.fillColor || shapeColor || 'transparent');
                            obj = new fabric.Rect({
                                ...baseProps,
                                width:  layer.w,
                                height: layer.h,
                                fill:   fallbackFill,
                                stroke: strokeColor || null,
                                strokeWidth: strokeWidth || 0
                            });
                        }
                    }
                }

                if (obj) {
                    // Apply fill opacity (separate from layer opacity)
                    if (fillOpacity < 1 && shapeColor !== 'transparent') {
                        const hex = shapeColor.replace('#', '');
                        if (hex.length === 6) {
                            const r = parseInt(hex.substring(0,2), 16);
                            const g = parseInt(hex.substring(2,4), 16);
                            const b = parseInt(hex.substring(4,6), 16);
                            obj.set('fill', 'rgba(' + r + ',' + g + ',' + b + ',' + fillOpacity + ')');
                        }
                    }

                    // Apply gradient fill if present
                    if (layer.fillGradient && layer.fillGradient.colorStops && layer.fillGradient.colorStops.length >= 2) {
                        const grad = buildFabricGradient(layer.fillGradient, layer.w, layer.h);
                        if (grad) obj.set('fill', grad);
                    }

                    // Stroke alignment adjustment
                    if (layer.stroke && layer.stroke.alignment && strokeWidth > 0) {
                        const align = layer.stroke.alignment;
                        if (align === 'strokeStyleAlignInside') {
                            // Inside stroke: offset inward
                            if (obj.type === 'ellipse') {
                                obj.set({ rx: obj.rx - strokeWidth/2, ry: obj.ry - strokeWidth/2 });
                            } else if (obj.type === 'rect') {
                                obj.set({
                                    width: (obj.width || layer.w) - strokeWidth,
                                    height: (obj.height || layer.h) - strokeWidth,
                                    left: layer.x + strokeWidth/2,
                                    top: layer.y + strokeWidth/2
                                });
                            }
                        } else if (align === 'strokeStyleAlignOutside') {
                            // Outside stroke: offset outward
                            if (obj.type === 'ellipse') {
                                obj.set({ rx: obj.rx + strokeWidth/2, ry: obj.ry + strokeWidth/2 });
                            } else if (obj.type === 'rect') {
                                obj.set({
                                    width: (obj.width || layer.w) + strokeWidth,
                                    height: (obj.height || layer.h) + strokeWidth,
                                    left: layer.x - strokeWidth/2,
                                    top: layer.y - strokeWidth/2
                                });
                            }
                        }
                        // Center alignment is fabric's default — no adjustment needed
                    }

                    // Apply Gaussian Blur from smart filters
                    if (layer.smartFilters && layer.smartFilters.gaussianBlur) {
                        const blurRadius = layer.smartFilters.gaussianBlur.radius || 0;
                        if (blurRadius > 0 && typeof fabric.Image !== 'undefined' && fabric.Image.filters && fabric.Image.filters.Blur) {
                            // fabric.Path and fabric.Rect don't support filters directly
                            // Apply via shadow simulation: zero-offset shadow with large blur
                            obj.set('shadow', new fabric.Shadow({
                                color: obj.fill || 'rgba(0,0,0,0.5)',
                                blur: blurRadius,
                                offsetX: 0,
                                offsetY: 0
                            }));
                            // Make the original object slightly transparent to let shadow show
                            obj.set('opacity', (opacity || 1) * 0.8);
                        }
                    }

                    obj.set('z_index', layer.z_index || idx);
                    canvas.add(obj);
                    if (typeof sortCanvasLayers === 'function') {
                        try { sortCanvasLayers(); } catch(e) { console.error('[SORT ERROR]', e); }
                    }
                    updateLayersList();
                }
            }
        });
        console.log('[DEBUG] All layers processed synchronously. Objects on canvas:', canvas.getObjects().length);
        if (typeof sortCanvasLayers === 'function') sortCanvasLayers();
        canvas.renderAll();
        
        // If there were no async images at all, apply masks immediately
        if (totalAsyncLoads === 0) {
            canvas.getObjects().forEach(obj => {
                if (obj.type === 'image' && obj.mask_layer_id) {
                    applyVisualMaskPreview(obj, obj.mask_layer_id);
                }
            });
        }

        console.log(`[DEBUG] renderJsonToCanvas COMPLETE`);
        updateLayersList();
        saveHistory();
    }
    
    window.sortCanvasLayers = function() {
        if (!canvas) return;
        
        // Get a shallow copy of objects to avoid mutation issues during sort
        const objects = [...canvas.getObjects()];
        
        objects.sort((a, b) => {
            const za = typeof a.z_index === 'number' ? a.z_index : 9999;
            const zb = typeof b.z_index === 'number' ? b.z_index : 9999;
            return za - zb; // Ascending z_index
        });
        
        // Safer re-ordering for older fabric versions:
        // Iterate backwards and send to back. The last one sent to back will be at the very bottom.
        for (let i = objects.length - 1; i >= 0; i--) {
            canvas.sendToBack(objects[i]);
        }
    };

    // --- Export Schemas ---
    function exportArteraSchema(objects, title) {
        return {
            schema_version: 1, render_version: CURRENT_RENDER_VERSION, template_id: 'tpl_' + Date.now(),
            canvas: { width: baseWidth, height: baseHeight, background_color: canvas.backgroundColor || '#FFFFFF' },
            elements: objects.map((obj, i) => {
                const z = i+1;
                let w = Math.round(obj.width * (obj.scaleX || 1));
                let h = Math.round(obj.height * (obj.scaleY || 1));
                
                // --- Phase 2 Math Fix (Text box width) ---
                if ((obj.type === 'text' || obj.type === 'i-text' || obj.type === 'textbox') && w === 0) {
                    w = Math.round(obj.getScaledWidth() || obj.width || 100);
                    h = Math.round(obj.getScaledHeight() || obj.height || 50);
                }

                // Fabric returns absolute left/top when using setCoords()
                obj.setCoords();
                let aCoords = obj.aCoords;
                let x = Math.round(aCoords ? aCoords.tl.x : obj.left);
                let y = Math.round(aCoords ? aCoords.tl.y : obj.top);
                let fontSize = obj.fontSize;

                if (obj.type === 'text' || obj.type === 'i-text' || obj.type === 'textbox') {
                    if (obj.type !== 'textbox' && obj._psdData && obj.scaleX === 1 && obj.scaleY === 1 && obj.text === obj._psdData.text && obj.fontFamily === obj._psdData.fontFamily) {
                        if (Math.abs(w - obj._psdData.w) < 10) w = Math.round(obj._psdData.w);
                        if (Math.abs(h - obj._psdData.h) < 10) h = Math.round(obj._psdData.h);
                    } else {
                        // User scaled it. Bake the scale into the font size!
                        fontSize = Math.round(obj.fontSize * Math.abs(obj.scaleY));
                    }
                    let isPointText = (obj.type !== 'textbox');
                    let yOffset = obj._originalYOffset !== undefined ? obj._originalYOffset : Math.round(fontSize * 0.12);
                    y = Math.round(obj.top + yOffset);
                }

                let o = { id:'el_'+z, name:obj.customName||(obj.type==='image'?'layer_'+z:'text_'+z), type:(obj.type==='i-text'||obj.type==='textbox')?'text':((obj.customType==='shape' && obj.type!=='image')?'shape':obj.type),
                    x:x, y:y, w:w, h:h, width:w, height:h, scaleX:1, scaleY:1, originX:'left', originY:'top', rotation:obj.angle||0, opacity:obj.opacity??1, z_index:z, locked:!obj.selectable, visible:obj.visible!==false };
                if (obj.type === 'image' || obj.customType === 'icon' || (obj.customType === 'shape' && obj.type === 'group') || (obj.customType === 'image' && obj.is_image_placeholder)) { 
                    o.type = 'image';
                    let isShapeRaster = (obj.customType === 'shape' || obj.customType === 'icon' || obj.is_shape);
                    try { 
                        if (obj.is_image_placeholder) {
                            o.src = obj._src || '';
                        } else if (obj.type === 'image' && !isShapeRaster) {
                            o.src = obj.getSrc();
                        } else {
                            let origFill = obj.fill;
                            let oldFilters = obj.filters ? [...obj.filters] : [];
                            console.log('[EXPORT] Shape "' + (obj.customName||obj.type) + '": type=' + obj.type + ' customType=' + obj.customType + ' is_shape=' + obj.is_shape + ' fill=' + origFill + ' filters=' + oldFilters.length);
                            
                            let childFills = [];
                            if (obj.type === 'group' && typeof obj.getObjects === 'function') {
                                obj.getObjects().forEach(c => { 
                                    let clr = (c.fill && c.fill !== 'none') ? c.fill : c.stroke;
                                    if (clr && clr !== 'none') childFills.push(clr);
                                });
                            }
                            if (!origFill && childFills.length > 0) {
                                origFill = childFills[0];
                                obj.fill = origFill; // Temporarily set so tint_color extraction catches it
                            }
                            if (!origFill && obj.stroke && obj.stroke !== 'none') {
                                origFill = obj.stroke;
                                obj.fill = origFill;
                            }
                            
                            if (obj.filters && obj.filters.length > 0) {
                                obj.filters = [];
                                if (obj.applyFilters) obj.applyFilters();
                                obj.dirty = true;
                                if (obj._cacheCanvas) obj._cacheCanvas = null;
                            }
                            
                            o.src = obj.toDataURL({format: 'png', multiplier: 2}); 
                            console.log('[EXPORT] Shape "' + (obj.customName||obj.type) + '" rasterized: src length=' + (o.src||'').length + ' starts=' + (o.src||'').substring(0,30));
                            
                            if (oldFilters.length > 0) {
                                obj.filters = oldFilters;
                                if (obj.applyFilters) obj.applyFilters();
                                obj.dirty = true;
                            }
                            // Restore cache state after export if needed
                            obj.dirty = true;
                        }
                    } catch(e) { console.error('Failed rasterizing shape:', e); }
                    
                    o.is_background=obj.is_background||false; o.is_placeholder=obj.is_placeholder||false; o.is_slot=obj.is_slot||false; 
                    if(isShapeRaster) o.is_shape=true; 
                    if(obj.mask_layer_id) o.mask_layer_id = obj.mask_layer_id;
                    // Save _originalType so icons keep their identity through save/load
                    if(obj.customType === 'icon') o._originalType = 'icon';
                    else if(isShapeRaster) o._originalType = 'shape';
                    if(isShapeRaster && obj.fill && typeof obj.fill === 'string') o.tint_color = obj.fill;
                    
                    // ── Non-Destructive Metadata (Phase 1A) ──
                    if (obj.customType === 'icon') {
                        o._source_meta = {
                            type: 'icon',
                            iconName: obj._iconName || null,
                            provider: obj._iconProvider || 'iconify',
                            originalSvg: obj._originalSvgMarkup || null
                        };
                    } else if (obj.customType === 'shape' && obj.type === 'group') {
                        o._source_meta = {
                            type: 'shape_group',
                            childCount: (typeof obj.getObjects === 'function') ? obj.getObjects().length : 0
                        };
                    }

                    if(isShapeRaster) console.log('[EXPORT] Schema element "' + o.name + '": is_shape=' + o.is_shape + ' tint_color=' + o.tint_color + ' _originalType=' + (o._originalType||'') + ' src_len=' + (o.src||'').length);
                }
                else if (obj.type==='text'||obj.type==='i-text'||obj.type==='textbox') {
                    o.text=obj.text; o.font={family:obj.fontFamily,size:fontSize,weight:(obj.fontWeight==='700'||obj.fontWeight===700)?'bold':obj.fontWeight,style:obj.fontStyle,color:obj.fill,justification:obj.textAlign||'left',auto_scale:obj.auto_scale||false,charSpacing:obj.charSpacing||0,wordSpacing:obj.wordSpacing||0,lineHeight:obj.lineHeight||1.16};
                    o.placeholder=obj.customType==='placeholder'?{field_type:obj.placeholderKey,required:true}:null;
                    o.kind = (obj.type === 'textbox') ? 'Paragraph' : 'Point';
                    o.textKind = (obj.type === 'textbox') ? 'paragraph' : 'point';
                } else if (!obj.is_image_placeholder && obj.customType !== 'image' && (obj.customType==='shape' || ['rect','circle','triangle','path','polygon','line','ellipse'].includes(obj.type))) {
                    o.type = 'shape';
                    o.shapeType = obj.type; // e.g. 'rect', 'ellipse'
                    
                    if (obj.fill && typeof obj.fill === 'object' && obj.fill.type === 'linear') {
                        o.fill = JSON.parse(JSON.stringify(obj.fill));
                        if (o.fill.coords) {
                            if (o.fill.coords.x1) o.fill.coords.x1 *= Math.abs(obj.scaleX);
                            if (o.fill.coords.x2) o.fill.coords.x2 *= Math.abs(obj.scaleX);
                            if (o.fill.coords.y1) o.fill.coords.y1 *= Math.abs(obj.scaleY);
                            if (o.fill.coords.y2) o.fill.coords.y2 *= Math.abs(obj.scaleY);
                        }
                    } else {
                        o.fill = obj.fill;
                    }
                    
                    o.stroke=obj.stroke; o.strokeWidth=obj.strokeWidth;
                    o.width=w; o.height=h;
                    if (obj.rx) o.rx = obj.rx;
                    if (obj.ry) o.ry = obj.ry;

                    // ── Save polygon points as absolute coordinates for round-trip ──
                    if ((obj.type === 'polygon' || obj.type === 'polyline') && obj.points && obj.points.length >= 3) {
                        var polyMatrix = obj.calcTransformMatrix();
                        var polyOffX = obj.pathOffset ? obj.pathOffset.x : 0;
                        var polyOffY = obj.pathOffset ? obj.pathOffset.y : 0;
                        var absPoints = obj.points.map(function(p) {
                            var lx = p.x - polyOffX;
                            var ly = p.y - polyOffY;
                            var abs = fabric.util.transformPoint(new fabric.Point(lx, ly), polyMatrix);
                            return { x: Math.round(abs.x), y: Math.round(abs.y) };
                        });
                        // Recompute bounding box from absolute points
                        var pMinX = Infinity, pMinY = Infinity, pMaxX = -Infinity, pMaxY = -Infinity;
                        absPoints.forEach(function(p) { pMinX = Math.min(pMinX, p.x); pMinY = Math.min(pMinY, p.y); pMaxX = Math.max(pMaxX, p.x); pMaxY = Math.max(pMaxY, p.y); });
                        o.points = absPoints;
                        o.x = Math.round(pMinX); o.y = Math.round(pMinY);
                        o.w = Math.round(pMaxX - pMinX); o.h = Math.round(pMaxY - pMinY);
                        o.rotation = 0; // rotation is baked into point coordinates
                        console.log('[EXPORT] Polygon points saved:', absPoints.length, 'pts, bbox:', o.x, o.y, o.w, o.h);
                    }
                    // ── Save path SVG string for round-trip ──
                    else if (obj.type === 'path' && obj.path && obj.path.length > 0) {
                        o.svgPath = fabric.util.joinPath(obj.path);
                        o.scaleX = obj.scaleX || 1;
                        o.scaleY = obj.scaleY || 1;
                        console.log('[EXPORT] Path SVG saved, commands:', obj.path.length);
                    }
                }
                return o;
            }),
            assets: [], metadata: { title: title, created_at: new Date().toISOString() }
        };
    }

    function exportLegacyJson(objects, title) {
        const j = { name: title.replace(/\s+/g,'_'), render_version: CURRENT_RENDER_VERSION, info:{width:baseWidth,height:baseHeight}, layers:[] };
        objects.forEach((obj,i) => {
            const z=i+1;
            let w = Math.round(obj.width * (obj.scaleX || 1));
            let h = Math.round(obj.height * (obj.scaleY || 1));
            
            // --- Phase 2 Math Fix (Text box width) ---
            if ((obj.type === 'text' || obj.type === 'i-text' || obj.type === 'textbox') && w === 0) {
                w = Math.round(obj.getScaledWidth() || obj.width || 100);
                h = Math.round(obj.getScaledHeight() || obj.height || 50);
            }

            // Fabric returns absolute left/top when using setCoords()
            obj.setCoords();
            let aCoords = obj.aCoords;
            let x = Math.round(aCoords ? aCoords.tl.x : obj.left);
            let y = Math.round(aCoords ? aCoords.tl.y : obj.top);
            let fontSize = obj.fontSize;

            if (obj.type === 'text' || obj.type === 'i-text' || obj.type === 'textbox') {
                if (obj.type !== 'textbox' && obj._psdData && obj.scaleX === 1 && obj.scaleY === 1 && obj.text === obj._psdData.text && obj.fontFamily === obj._psdData.fontFamily) {
                    if (Math.abs(w - obj._psdData.w) < 10) w = Math.round(obj._psdData.w);
                    if (Math.abs(h - obj._psdData.h) < 10) h = Math.round(obj._psdData.h);
                } else {
                    fontSize = Math.round(obj.fontSize * Math.abs(obj.scaleY));
                }
                let isPointText = (obj.type !== 'textbox');
                let yOffset = obj._originalYOffset !== undefined ? obj._originalYOffset : (isPointText ? Math.round(fontSize * 0.12) : 0);
                y = Math.round(obj.top + yOffset);
            }

            if (obj.type==='image' || (obj.customType === 'image' && obj.is_image_placeholder)) {
                let srcToUse = obj.is_image_placeholder ? (obj._src || '') : obj.getSrc();
                let imgData = {name:obj.customName||'layer_'+z,type:'image',src:srcToUse,x:x,y:y,w:w,h:h,width:w,height:h,z_index:z,is_background:obj.is_background||false,is_placeholder:obj.is_placeholder||false,is_slot:obj.is_slot||false, image_type: obj.image_type||'', is_shape: obj.customType === 'shape' || obj.customType === 'icon' || obj.is_shape === true};
                if (obj.mask_layer_id) imgData.mask_layer_id = obj.mask_layer_id;
                if ((obj.customType==='shape' || obj.customType==='icon' || obj.is_shape) && obj.fill && typeof obj.fill === 'string') imgData.tint_color = obj.fill;
                j.layers.push(imgData);
            }
            else if (obj.type==='i-text'||obj.type==='text'||obj.type==='textbox') j.layers.push({name:obj.customName||'text_'+z,type:'text',kind: (obj.type==='textbox'?'Paragraph':'Point'),textKind: (obj.type==='textbox'?'paragraph':'point'),text:obj.text,x:x,y:y,w:w,h:h,width:w,height:h,z_index:z,color:obj.fill,weight:(obj.fontWeight==='700'||obj.fontWeight===700)?'bold':obj.fontWeight,style:obj.fontStyle,size:fontSize,font_size:fontSize,font:obj.fontFamily,font_name:obj.fontFamily,justification:obj.textAlign||'left',letterSpacing:obj.charSpacing||0,wordSpacing:obj.wordSpacing||0,lineHeight:obj.lineHeight||1.16,opacity:obj.opacity??1,rotation:obj.angle||0,visible:obj.visible!==false,ai_role:obj.ai_role||null,ai_max_chars:obj.ai_max_chars||null});
            else if (!obj.is_image_placeholder && obj.customType !== 'image' && (obj.customType==='shape' || obj.customType==='icon' || obj.is_shape || ['rect','circle','triangle','path','polygon','line'].includes(obj.type))) {
                // ══════════════════════════════════════════════════════════════════
                // RENDER VERSION 4: Save shapes/icons as VECTOR DATA, not PNG
                // This preserves exact color, border radius, shape type, and icon name.
                // A _fallback_src PNG is kept for backward compatibility.
                // ══════════════════════════════════════════════════════════════════

                // ── Determine the exact fill color ──
                let fillColor = null;
                if (obj.fill && typeof obj.fill === 'string' && obj.fill !== 'none') {
                    fillColor = toHex(obj.fill);
                } else if (obj.fill && typeof obj.fill === 'object' && obj.fill.type === 'linear') {
                    fillColor = JSON.parse(JSON.stringify(obj.fill));
                }
                // Fallback: check child objects in groups
                if (!fillColor && obj.type === 'group' && typeof obj.getObjects === 'function') {
                    obj.getObjects().forEach(function(c) {
                        var clr = (c.fill && c.fill !== 'none') ? c.fill : c.stroke;
                        if (clr && clr !== 'none' && !fillColor) fillColor = toHex(clr);
                    });
                }
                if (!fillColor && obj.stroke && obj.stroke !== 'none') {
                    fillColor = toHex(obj.stroke);
                }

                // ── Build vector layer data ──
                var vectorData = {
                    name: obj.customName || 'shape_' + z,
                    type: 'shape',
                    shapeType: obj.type,
                    x: x, y: y, w: w, h: h,
                    width: w, height: h,
                    z_index: z,
                    fill: fillColor,
                    stroke: (obj.stroke && obj.stroke !== 'none') ? toHex(obj.stroke) : null,
                    strokeWidth: obj.strokeWidth || 0,
                    rx: obj.rx || 0,
                    ry: obj.ry || 0,
                    opacity: obj.opacity ?? 1,
                    rotation: obj.angle || 0,
                    is_background: obj.is_background || false,
                    is_slot: obj.is_slot || false,
                    is_shape: true,
                    visible: obj.visible !== false
                };

                // ── Icon-specific metadata ──
                if (obj.customType === 'icon') {
                    vectorData.type = 'icon';
                    vectorData.iconName = obj._iconName || null;
                    vectorData.iconProvider = obj._iconProvider || 'iconify';
                    vectorData.color = fillColor;
                }

                // ── Polygon points (for star, custom shapes) ──
                if ((obj.type === 'polygon' || obj.type === 'polyline') && obj.points && obj.points.length >= 3) {
                    var polyMatrix = obj.calcTransformMatrix();
                    var polyOffX = obj.pathOffset ? obj.pathOffset.x : 0;
                    var polyOffY = obj.pathOffset ? obj.pathOffset.y : 0;
                    vectorData.points = obj.points.map(function(p) {
                        var lx = p.x - polyOffX;
                        var ly = p.y - polyOffY;
                        var abs = fabric.util.transformPoint(new fabric.Point(lx, ly), polyMatrix);
                        return { x: Math.round(abs.x), y: Math.round(abs.y) };
                    });
                }

                // ── SVG path data (for custom shapes) ──
                if (obj.type === 'path' && obj.path && obj.path.length > 0) {
                    vectorData.svgPath = fabric.util.joinPath(obj.path);
                }

                // ── Clipping mask ──
                if (obj.mask_layer_id) vectorData.mask_layer_id = obj.mask_layer_id;

                // ── BACKWARD COMPAT: Also save a rasterized PNG as _fallback_src ──
                // Old native app versions (render_version < 4) will use this PNG.
                try {
                    var oldFilters = obj.filters ? obj.filters.slice() : [];
                    if (obj.filters && obj.filters.length > 0) {
                        obj.filters = [];
                        if (obj.applyFilters) obj.applyFilters();
                        obj.dirty = true;
                        if (obj._cacheCanvas) obj._cacheCanvas = null;
                    }
                    vectorData._fallback_src = obj.toDataURL({format: 'png', multiplier: 2});
                    if (oldFilters.length > 0) {
                        obj.filters = oldFilters;
                        if (obj.applyFilters) obj.applyFilters();
                        obj.dirty = true;
                    }
                    obj.dirty = true;
                } catch(e) { console.warn('[EXPORT_V4] Fallback raster failed:', e); }

                console.log('[EXPORT_V4] Vector shape:', vectorData.name, 'type:', vectorData.shapeType,
                    'fill:', vectorData.fill, 'icon:', vectorData.iconName);

                j.layers.push(vectorData);
            }
        });
        return j;
    }

    /**
     * Compare old JSON (loaded version) vs new JSON (about to save).
     * Returns an array of diffs per layer.
     *
     * @param {Object} oldJson - The JSON that was loaded when template opened
     * @param {Object} newJson - The JSON that exportArteraSchema() just generated
     * @returns {Array<{layerName, property, oldValue, newValue, type}>}
     */
    function computeVersionDiff(oldJson, newJson) {
        const diffs = [];
        const oldVersion = oldJson.render_version || 1;
        const newVersion = newJson.render_version || CURRENT_RENDER_VERSION;

        // Add version change itself
        if (oldVersion !== newVersion) {
            diffs.push({
                layerName: '(Template)',
                property: 'render_version',
                oldValue: 'V' + oldVersion,
                newValue: 'V' + newVersion,
                type: 'version_upgrade',
            });
        }

        // Build layer maps by name
        const oldLayers = {};
        const newLayers = {};
        (oldJson.layers || oldJson.objects || []).forEach(l => {
            oldLayers[l.name || l.id || 'unknown'] = l;
        });
        (newJson.layers || newJson.objects || []).forEach(l => {
            newLayers[l.name || l.id || 'unknown'] = l;
        });

        // Compare properties for each layer
        const propsToCompare = ['x', 'y', 'w', 'h', 'width', 'height', 'fontSize', 'font_size',
            'size', 'type', 'fill', 'color', 'font_color', 'fontFamily', 'font_name',
            'scaleX', 'scaleY', 'rx', 'ry', 'stroke', 'strokeWidth', 'shapeType'];

        const allLayerNames = new Set([...Object.keys(oldLayers), ...Object.keys(newLayers)]);

        allLayerNames.forEach(name => {
            const oldL = oldLayers[name];
            const newL = newLayers[name];

            if (!oldL && newL) {
                diffs.push({
                    layerName: name,
                    property: '(entire layer)',
                    oldValue: '—',
                    newValue: 'NEW (added by V' + newVersion + ')',
                    type: 'layer_added',
                });
                return;
            }

            if (oldL && !newL) {
                diffs.push({
                    layerName: name,
                    property: '(entire layer)',
                    oldValue: 'Existed in V' + oldVersion,
                    newValue: 'REMOVED',
                    type: 'layer_removed',
                });
                return;
            }

            // Both exist — compare properties
            propsToCompare.forEach(prop => {
                const ov = oldL[prop];
                const nv = newL[prop];
                if (ov !== undefined || nv !== undefined) {
                    // Normalize for comparison
                    const ovStr = JSON.stringify(ov ?? null);
                    const nvStr = JSON.stringify(nv ?? null);
                    if (ovStr !== nvStr) {
                        diffs.push({
                            layerName: name,
                            property: prop,
                            oldValue: ov ?? '—',
                            newValue: nv ?? '—',
                            type: typeof ov === 'number' && typeof nv === 'number' ? 'numeric_shift' : 'value_change',
                        });
                    }
                }
            });

            // Check type upgrade (e.g., raster PNG → vector shape)
            if (oldL.type === 'image' && (newL.type === 'shape' || newL.type === 'rect')) {
                diffs.push({
                    layerName: name,
                    property: 'type',
                    oldValue: 'Raster PNG (' + (oldL.src ? (oldL.src.length > 30 ? oldL.src.substring(0,30) + '...' : oldL.src) : 'no src') + ')',
                    newValue: 'Vector Shape (fill: ' + (newL.fill || newL.color || '?') + ')',
                    type: 'type_upgrade',
                });
            }
        });

        return diffs;
    }

    function validateBeforePublish(schemaJson, legacyJson) {
        const errors = [];

        // 1. Check for NaN in coordinates
        const checkNaN = (elements, format) => {
            (elements || []).forEach((el, i) => {
                if (isNaN(el.x) || isNaN(el.y) || isNaN(el.w) || isNaN(el.h)) {
                    errors.push(`[${format}] Layer "${el.name}" (index ${i}) has NaN coordinates: x=${el.x}, y=${el.y}, w=${el.w}, h=${el.h}`);
                }
                if (el.w === 0 && el.h === 0 && el.type === 'image') {
                    errors.push(`[${format}] Image layer "${el.name}" has zero dimensions`);
                }
            });
        };

        checkNaN(schemaJson.elements, 'Schema');
        checkNaN(legacyJson.layers, 'Legacy');

        // 2. Check image sources are not empty
        const checkImages = (elements, format) => {
            (elements || []).forEach((el, i) => {
                if (el.type === 'image' && (!el.src || el.src.length < 50)) {
                    errors.push(`[${format}] Image "${el.name}" has empty or broken src (length: ${(el.src||'').length})`);
                }
            });
        };

        checkImages(schemaJson.elements, 'Schema');
        checkImages(legacyJson.layers, 'Legacy');

        // 3. Check render_version exists
        if (!schemaJson.render_version) errors.push('Schema is missing render_version');
        if (!legacyJson.render_version) errors.push('Legacy is missing render_version');

        return errors;
    }

    // --- Publish ---
    const btnSave = $('btn-save');
    if (btnSave) btnSave.addEventListener('click', function() {
        this.disabled = true;
        this.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Publishing...';

        canvas.setZoom(1); canvas.setWidth(baseWidth); canvas.setHeight(baseHeight);
        canvas.discardActiveObject(); canvas.renderAll();

        const thumbnail = canvas.toDataURL({ format:'webp', quality:0.8 });
        const thumbData = canvas.toDataURL({ format:'webp', quality:0.8 });
        const title = $('template-title').value;
        const objects = canvas.getObjects();

        const fd = new FormData();
        fd.append('title', title);
        fd.append('thumbnail', thumbData);
        if (window.editing_frame_id) {
            fd.append('frame_id', window.editing_frame_id);
            console.log('[PUBLISH] Updating existing frame_id:', window.editing_frame_id);
        } else {
            console.log('[PUBLISH] Creating NEW template (no frame_id)');
        }
        fd.append('purpose_id', 1);

        const schemaData = exportArteraSchema(objects, title);
        const legacyData = exportLegacyJson(objects, title);

        // Check if version upgrade happened
        if (window._originalRenderVersion < CURRENT_RENDER_VERSION) {
            // Compute diff
            const diffs = computeVersionDiff(window._originalLoadedJson || {}, legacyData);

            if (diffs.length > 0) {
                // Show diff modal instead of direct save
                if (typeof showDiffReviewModal === 'function') {
                    showDiffReviewModal(diffs, schemaData, legacyData, fd, btnSave);
                    return;
                } else {
                    console.warn('[PUBLISH] showDiffReviewModal not found, proceeding with direct save.');
                }
            }
        }

        // No version change or no diffs — proceed with normal save
        doSaveFrame(schemaData, legacyData, fd, btnSave);
    });

    // Make doSaveFrame globally accessible for the modal to call
    window.doSaveFrame = function(schemaData, legacyData, fd, btnSave) {
        // ── Phase 1B: Pre-Publish Validation ──
        const validationErrors = validateBeforePublish(schemaData, legacyData);
        if (validationErrors.length > 0) {
            console.error('[PUBLISH BLOCKED]', validationErrors);
            alert('⚠️ Publish blocked! Found ' + validationErrors.length + ' issue(s):\n\n' + validationErrors.join('\n'));
            btnSave.disabled = false;
            btnSave.innerHTML = '<i class="fa fa-save"></i> Publish';
            return;
        }

        const objects = canvas.getObjects();
        // Debug: log every element being published
        console.log('[PUBLISH] Canvas objects count:', objects.length);
        objects.forEach((obj, i) => {
            const t = obj.type;
            const ct = obj.customType || '';
            const n = obj.customName || obj.type;
            console.log('[PUBLISH] Object ' + i + ': type=' + t + ' customType=' + ct + ' name="' + n + '" w=' + Math.round(obj.width*(obj.scaleX||1)) + ' h=' + Math.round(obj.height*(obj.scaleY||1)));
        });
        console.log('[PUBLISH] Schema elements:', schemaData.elements.length, '| Legacy layers:', legacyData.layers.length);

        fd.append('schema_json', JSON.stringify(schemaData));
        fd.append('legacy_json', JSON.stringify(legacyData));

        const mode = btnSave.getAttribute('data-mode') || 'template';
        const targetUrl = mode === 'frame' ? saveFrameUrl : saveUrl;

        if (mode === 'frame') {
            fd.append('poster_category_id', $('frame-category') ? $('frame-category').value : '');
            fd.append('template_type', $('frame-template-type') ? $('frame-template-type').value : '');
            fd.append('req_address', $('req_address') ? parseInt($('req_address').value) || 0 : 0);
            fd.append('req_email', $('req_email') ? parseInt($('req_email').value) || 0 : 0);
            fd.append('req_phone', $('req_phone') ? parseInt($('req_phone').value) || 0 : 0);
            fd.append('req_website', $('req_website') ? parseInt($('req_website').value) || 0 : 0);
        }

        fetch(targetUrl, { method:'POST', headers:{'X-CSRF-TOKEN':csrfToken, 'Accept':'application/json'}, body:fd })
        .then(async r => {
            if (!r.ok) {
                const text = await r.text();
                console.error('[PUBLISH] Server returned error status:', r.status);
                console.error('[PUBLISH] Raw server response:', text);
                throw new Error('HTTP ' + r.status + ': ' + text.substring(0, 500));
            }
            return r.json();
        }).then(data => {
            btnSave.disabled = false;
            btnSave.innerHTML = `<i class="fa fa-save"></i> Publish ${mode === 'frame' ? 'Frame' : 'Template'}`;
            if (data.success && (data.frame_id || data.template_id)) {
                window.editing_frame_id = data.frame_id || data.template_id;
                try {
                    const newUrl = new URL(window.location.href);
                    newUrl.searchParams.set('mode', mode);
                    newUrl.searchParams.set('frame_id', window.editing_frame_id);
                    window.history.replaceState({ path: newUrl.href }, '', newUrl.href);
                } catch(e) {}
            }
            alert(data.success ? `✅ ${mode === 'frame' ? 'Frame' : 'Template'} Published!` : '❌ ' + (data.message||'Publishing failed'));
        }).catch(err => {
            btnSave.disabled = false;
            btnSave.innerHTML = `<i class="fa fa-save"></i> Publish ${mode === 'frame' ? 'Frame' : 'Template'}`;
            alert('❌ ' + err.message);
        });

        currentScale = updateCanvasZoom();
    };

    } catch (err) {
        alert("CRITICAL JS ERROR: " + err.message + "\nStack: " + err.stack);
        console.error(err);
    }
})();



