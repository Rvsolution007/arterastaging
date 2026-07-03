// Template Builder JS — runs immediately (no DOMContentLoaded since script loads at end of body)
(function() {
    'use strict';

    // Initialize Canvas
    const canvas = new fabric.Canvas('template-canvas', {
        width: 1080,
        height: 1080,
        backgroundColor: '#ffffff',
        preserveObjectStacking: true
    });

    let baseWidth = 1080;
    let baseHeight = 1080;
    
    function updateCanvasZoom() {
        const outerWrapper = document.querySelector('.canvas-container-wrap');
        if (!outerWrapper) return 1;
        
        let maxW = outerWrapper.clientWidth - 40;
        let maxH = outerWrapper.clientHeight - 40;
        
        // Fallback if container is hidden during init
        if (maxW <= 0) maxW = 600;
        if (maxH <= 0) maxH = 600;
        
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
        }

        canvas.renderAll();
        return scale;
    }
    
    let currentScale = updateCanvasZoom();
    window.addEventListener('resize', () => { currentScale = updateCanvasZoom(); });

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
            const gradientControls = $('gradient-controls');
            if (this.checked) {
                if (gradientControls) gradientControls.style.display = 'block';
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

    let templateImages = {};

    // --- Properties Panel References ---
    const propForm = $('properties-form');
    const noSelect = $('no-selection');
    const textProps = $('text-properties');
    const imageProps = $('image-properties');
    const shapeProps = $('shape-properties');

    const inputX = $('prop-x');
    const inputY = $('prop-y');
    const inputW = $('prop-w');
    const inputH = $('prop-h');
    const inputText = $('prop-text');
    const inputFontSize = $('prop-font-size');
    const inputColor = $('prop-color');

    const inputAiField = $('prop-ai-field');
    const inputAiRole = $('prop-ai-role');
    const inputAiPriority = $('prop-ai-priority');
    const inputAiMaxChars = $('prop-ai-max-chars');
    const inputAiReplaceable = $('prop-ai-replaceable');
    const inputAiAutoscale = $('prop-ai-autoscale');

    const inputIsBackground = $('prop-is-background');
    const inputIsSlot = $('prop-is-slot');

    const inputFillColor = $('prop-fill-color');
    const inputStrokeColor = $('prop-stroke-color');
    const inputStrokeWidth = $('prop-stroke-width');
    const inputBorderRadius = $('prop-border-radius');

    const inputFontFamily = $('prop-font-family');
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
        if (textProps) textProps.style.display = 'none';
        if (imageProps) imageProps.style.display = 'none';
        if (shapeProps) shapeProps.style.display = 'none';
    });
    canvas.on('object:modified', updateProps);
    canvas.on('object:scaling', updateProps);
    canvas.on('object:moving', updateProps);

    function updateProps() {
        const obj = canvas.getActiveObject();
        if (!obj) return;

        if (propForm) propForm.style.display = 'block';
        if (noSelect) noSelect.style.display = 'none';

        if (inputX) inputX.value = Math.round(obj.left);
        if (inputY) inputY.value = Math.round(obj.top);
        if (inputW) inputW.value = Math.round(obj.width * obj.scaleX);
        if (inputH) inputH.value = Math.round(obj.height * obj.scaleY);

        // Opacity
        if (inputOpacity) {
            inputOpacity.value = obj.opacity !== undefined ? obj.opacity : 1;
            if (textOpacityVal) textOpacityVal.innerText = Math.round(inputOpacity.value * 100);
        }
        
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
        if (obj.type === 'text' || obj.type === 'i-text') {
            if (textProps) textProps.style.display = 'block';
            if (imageProps) imageProps.style.display = 'none';
            if (shapeProps) shapeProps.style.display = 'none';
            if (inputText) inputText.value = obj.text;
            if (inputFontSize) inputFontSize.value = obj.fontSize;
            if (inputColor) inputColor.value = obj.fill || '#000000';
            if (inputFontFamily) inputFontFamily.value = obj.fontFamily || 'Arial';
            
            btnTextAlign.forEach(btn => {
                if(btn.dataset.align === obj.textAlign) btn.classList.replace('btn-outline-secondary', 'btn-secondary');
                else btn.classList.replace('btn-secondary', 'btn-outline-secondary');
            });
            
            if (inputAiField) inputAiField.value = obj.ai_field || '';
            if (inputAiRole) inputAiRole.value = obj.ai_semantic_role || '';
            if (inputAiPriority) inputAiPriority.value = obj.ai_priority || '';
            if (inputAiMaxChars) inputAiMaxChars.value = obj.ai_max_chars || '';
            if (inputAiReplaceable) inputAiReplaceable.checked = obj.ai_replaceable || false;
            if (inputAiAutoscale) inputAiAutoscale.checked = obj.auto_scale || false;
        } else if (obj.type === 'image') {
            if (textProps) textProps.style.display = 'none';
            if (imageProps) imageProps.style.display = 'block';
            if (shapeProps) shapeProps.style.display = 'none';
            if (inputIsBackground) inputIsBackground.checked = obj.is_background || false;
            if (inputIsSlot) inputIsSlot.checked = obj.is_slot || false;
        } else if (obj.customType === 'shape') {
            if (textProps) textProps.style.display = 'none';
            if (imageProps) imageProps.style.display = 'none';
            if (shapeProps) shapeProps.style.display = 'block';
            if (inputFillColor) inputFillColor.value = obj.fill || '#6366f1';
            if (inputStrokeColor) inputStrokeColor.value = obj.stroke || '#000000';
            if (inputStrokeWidth) inputStrokeWidth.value = obj.strokeWidth || 0;
            if (inputBorderRadius) {
                inputBorderRadius.value = obj.rx || 0;
                inputBorderRadius.closest('.mb-3').style.display = (obj.type === 'rect') ? 'block' : 'none';
            }
        } else {
            if (textProps) textProps.style.display = 'none';
            if (imageProps) imageProps.style.display = 'none';
            if (shapeProps) shapeProps.style.display = 'none';
        }
    }

    // --- Property Input Handlers ---
    [inputX, inputY, inputW, inputH].forEach(input => {
        if (!input) return;
        input.addEventListener('change', function() {
            const obj = canvas.getActiveObject();
            if(!obj) return;
            if(this.id === 'prop-x') obj.set('left', parseInt(this.value));
            if(this.id === 'prop-y') obj.set('top', parseInt(this.value));
            if(this.id === 'prop-w') obj.set({ scaleX: parseInt(this.value) / obj.width });
            if(this.id === 'prop-h') obj.set({ scaleY: parseInt(this.value) / obj.height });
            canvas.renderAll();
            saveHistory();
        });
    });

    if (inputText) inputText.addEventListener('input', function() {
        const obj = canvas.getActiveObject();
        if(obj && (obj.type === 'text' || obj.type === 'i-text')) {
            obj.set('text', this.value);
            canvas.renderAll();
        }
    });

    if (inputFontSize) inputFontSize.addEventListener('change', function() {
        const obj = canvas.getActiveObject();
        if(obj && (obj.type === 'text' || obj.type === 'i-text')) {
            obj.set('fontSize', parseInt(this.value));
            canvas.renderAll();
            saveHistory();
        }
    });

    if (inputColor) inputColor.addEventListener('input', function() {
        const obj = canvas.getActiveObject();
        if(obj && (obj.type === 'text' || obj.type === 'i-text')) {
            obj.set('fill', this.value);
            canvas.renderAll();
        }
    });

    // AI Fields
    function bindChange(el, propName) {
        if (!el) return;
        el.addEventListener('change', function() {
            const obj = canvas.getActiveObject();
            if (!obj) return;
            if (this.type === 'checkbox') { obj.set(propName, this.checked); }
            else if (this.type === 'number') { obj.set(propName, parseInt(this.value) || null); }
            else { obj.set(propName, this.value || null); }
        });
    }
    bindChange(inputAiField, 'ai_field');
    bindChange(inputAiRole, 'ai_semantic_role');
    bindChange(inputAiPriority, 'ai_priority');
    bindChange(inputAiMaxChars, 'ai_max_chars');
    bindChange(inputAiReplaceable, 'ai_replaceable');
    bindChange(inputAiAutoscale, 'auto_scale');

    if (inputFontFamily) inputFontFamily.addEventListener('change', function() {
        const obj = canvas.getActiveObject();
        if(obj && (obj.type === 'text' || obj.type === 'i-text')) {
            obj.set('fontFamily', this.value);
            canvas.renderAll();
            saveHistory();
        }
    });

    btnTextAlign.forEach(btn => {
        btn.addEventListener('click', function() {
            const obj = canvas.getActiveObject();
            if(obj && (obj.type === 'text' || obj.type === 'i-text')) {
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
    if (inputFillColor) inputFillColor.addEventListener('input', function() {
        const obj = canvas.getActiveObject();
        if (obj && obj.customType === 'shape') { obj.set('fill', this.value); canvas.renderAll(); }
    });
    if (inputStrokeColor) inputStrokeColor.addEventListener('input', function() {
        const obj = canvas.getActiveObject();
        if (obj && obj.customType === 'shape') { obj.set('stroke', this.value); canvas.renderAll(); }
    });
    if (inputStrokeWidth) inputStrokeWidth.addEventListener('change', function() {
        const obj = canvas.getActiveObject();
        if (obj && obj.customType === 'shape') { obj.set('strokeWidth', parseInt(this.value) || 0); canvas.renderAll(); saveHistory(); }
    });
    if (inputBorderRadius) inputBorderRadius.addEventListener('change', function() {
        const obj = canvas.getActiveObject();
        if (obj && obj.type === 'rect') { obj.set({ rx: parseInt(this.value) || 0, ry: parseInt(this.value) || 0 }); canvas.renderAll(); saveHistory(); }
    });

    // Image events
    if (inputIsBackground) inputIsBackground.addEventListener('change', function() {
        const obj = canvas.getActiveObject();
        if(obj && obj.type === 'image') {
            obj.set('is_background', this.checked);
            if (this.checked) canvas.sendToBack(obj);
            canvas.renderAll();
            updateLayersList();
        }
    });
    bindChange(inputIsSlot, 'is_slot');

    // Delete
    const deleteBtn = $('delete-element');
    if (deleteBtn) deleteBtn.addEventListener('click', function() {
        const obj = canvas.getActiveObject();
        if(obj) { canvas.remove(obj); canvas.discardActiveObject(); updateLayersList(); saveHistory(); }
    });

    // --- ADD TEXT ---
    const addTextBtn = $('add-text');
    if (addTextBtn) addTextBtn.addEventListener('click', function() {
        const text = new fabric.IText('Double click to edit', {
            left: 100, top: 100, fontSize: 60, fill: '#000000', fontFamily: 'Arial', customType: 'text'
        });
        canvas.add(text);
        canvas.setActiveObject(text);
        updateLayersList();
    });

    // --- ADD PLACEHOLDER ---
    const addPlaceholderBtn = $('add-placeholder');
    if (addPlaceholderBtn) addPlaceholderBtn.addEventListener('click', function() {
        const val = $('placeholder-select').value;
        const text = new fabric.IText('{{' + val + '}}', {
            left: 100, top: 200, fontSize: 60, fill: '#000000', fontFamily: 'Arial',
            customType: 'placeholder', placeholderKey: val, ai_field: val, ai_semantic_role: 'body_text'
        });
        canvas.add(text);
        canvas.setActiveObject(text);
        updateLayersList();
        saveHistory();
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
                shape = new fabric.Line([50, 50, 350, 50], { stroke: '#6366f1', strokeWidth: 4, customType: 'shape', fill: null });
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

    // --- ADD ICONS (Font Awesome as text objects) ---
    const iconsGrid = $('icons-grid');
    const iconSearch = $('icon-search');
    const iconUnicodeMap = {
        'fa-heart': '\uf004', 'fa-star': '\uf005', 'fa-house': '\uf015',
        'fa-user': '\uf007', 'fa-phone': '\uf095', 'fa-envelope': '\uf0e0',
        'fa-map-marker-alt': '\uf3c5', 'fa-location-dot': '\uf3c5',
        'fa-address-book': '\uf2b9', 'fa-address-card': '\uf2bb', 'fa-globe': '\uf0ac', 'fa-building': '\uf1ad',
        'fa-camera': '\uf030', 'fa-music': '\uf001',
        'fa-bolt': '\uf0e7', 'fa-gift': '\uf06b', 'fa-trophy': '\uf091',
        'fa-crown': '\uf521', 'fa-gem': '\uf3a5', 'fa-fire': '\uf06d',
        'fa-rocket': '\uf135', 'fa-flag': '\uf024', 'fa-bell': '\uf0f3',
        'fa-bookmark': '\uf02e', 'fa-thumbs-up': '\uf164'
    };

    if (iconsGrid) {
        const iconItems = iconsGrid.querySelectorAll('.icon-item');
        iconItems.forEach(item => {
            item.addEventListener('click', function() {
                const iconClass = this.getAttribute('data-icon') || '';
                const baseClass = iconClass.split(' ').pop(); // e.g., 'fa-heart'
                const unicode = iconUnicodeMap[baseClass] || '\uf005'; // default star
                const title = this.getAttribute('title') || 'Icon';
                
                // For FontAwesome 5/6 Free Solid
                const iconText = new fabric.IText(unicode, {
                    left: 200, top: 200, fontSize: 80, fill: '#6366f1',
                    fontFamily: '"Font Awesome 6 Free", "FontAwesome", "Font Awesome 5 Free"', fontWeight: 900,
                    customType: 'icon', customName: 'icon_' + title
                });
                canvas.add(iconText);
                canvas.setActiveObject(iconText);
                updateLayersList();
                saveHistory();
            });
        });
    }

    if (iconSearch) {
        iconSearch.addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            if (iconsGrid) {
                const iconItems = iconsGrid.querySelectorAll('.icon-item');
                iconItems.forEach(item => {
                    const title = (item.getAttribute('title') || '').toLowerCase();
                    if (title.includes(filter)) {
                        item.style.display = 'flex';
                    } else {
                        item.style.display = 'none';
                    }
                });
            }
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
                img.set({ left: 100, top: 100, customType: 'image' });
                img.scaleToWidth(300);
                canvas.add(img);
                canvas.setActiveObject(img);
                updateLayersList();
            });
        };
        reader.readAsDataURL(file);
        this.value = '';
    });

    // --- LAYERS LIST ---
    function updateLayersList() {
        const list = $('layers-list');
        if (!list) return;
        list.innerHTML = '';
        const objects = canvas.getObjects().slice().reverse();
        objects.forEach(obj => {
            const li = document.createElement('li');
            li.className = 'aim-list-item';
            if (canvas.getActiveObject() === obj) {
                li.style.borderColor = '#6366f1';
                li.style.background = '#eef2ff';
            }
            
            let name = obj.customName || obj.customType || obj.type;
            if(obj.customType === 'placeholder') name = '[P] ' + obj.placeholderKey;
            else if(obj.customType === 'icon') name = '[IC] ' + (obj.customName || 'Icon');
            else if(obj.customType === 'shape') name = '[S] ' + obj.type;
            else if(obj.type === 'i-text' || obj.type === 'text') name = '[T] ' + obj.text.substring(0,12);
            else if(obj.type === 'image') name = obj.is_background ? '[BG]' : '[IMG]';

            const nameSpan = document.createElement('span');
            nameSpan.innerText = name;
            nameSpan.style.cssText = 'cursor:pointer;flex-grow:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:0.72rem;';
            nameSpan.onclick = () => { canvas.setActiveObject(obj); canvas.renderAll(); };

            const ctrl = document.createElement('div');
            ctrl.style.cssText = 'display:flex;align-items:center;gap:3px;';

            function makeBtn(icon, color, handler) {
                const b = document.createElement('button');
                b.className = 'btn btn-sm btn-link p-0';
                b.style.cssText = 'font-size:0.65rem;color:' + color + ';min-width:18px;';
                b.innerHTML = '<i class="fa fa-' + icon + '"></i>';
                b.onclick = handler;
                return b;
            }

            ctrl.appendChild(makeBtn(obj.visible === false ? 'eye-slash' : 'eye', obj.visible === false ? '#94a3b8' : '#6366f1', e => {
                e.stopPropagation(); obj.set('visible', !obj.visible); canvas.renderAll(); updateLayersList(); saveHistory();
            }));
            ctrl.appendChild(makeBtn(obj.selectable === false ? 'lock' : 'unlock', obj.selectable === false ? '#ef4444' : '#94a3b8', e => {
                e.stopPropagation();
                const locked = obj.selectable === false;
                obj.set({ selectable: locked, evented: locked, hasControls: locked, hasBorders: locked });
                if(!locked) canvas.discardActiveObject();
                canvas.renderAll(); updateLayersList(); saveHistory();
            }));
            ctrl.appendChild(makeBtn('arrow-up', '#64748b', e => { e.stopPropagation(); canvas.bringForward(obj); updateLayersList(); saveHistory(); }));
            ctrl.appendChild(makeBtn('arrow-down', '#64748b', e => { e.stopPropagation(); canvas.sendBackwards(obj); updateLayersList(); saveHistory(); }));
            ctrl.appendChild(makeBtn('trash', '#ef4444', e => { e.stopPropagation(); canvas.remove(obj); canvas.discardActiveObject(); updateLayersList(); saveHistory(); }));

            li.appendChild(nameSpan);
            li.appendChild(ctrl);
            list.appendChild(li);
        });
    }

    canvas.on('selection:created', updateLayersList);
    canvas.on('selection:updated', updateLayersList);
    canvas.on('selection:cleared', updateLayersList);

    // --- Fonts & Assets API ---
    function loadFonts() {
        fetch('/api/editor/fonts').then(r => r.json()).then(data => {
            if (data.success && data.data) {
                const sel = $('prop-font-family');
                if (!sel) return;
                data.data.forEach(f => {
                    if (!Array.from(sel.options).some(o => o.value === f.family)) {
                        const opt = document.createElement('option');
                        opt.value = f.family; opt.innerText = f.name;
                        sel.appendChild(opt);
                    }
                    if (f.file_path) {
                        const lnk = document.createElement('link');
                        lnk.href = f.file_path; lnk.rel = 'stylesheet';
                        document.head.appendChild(lnk);
                    }
                });
            }
        }).catch(() => {});
    }
    loadFonts();

    function loadAssets() {
        fetch('/api/editor/assets').then(r => r.json()).then(data => {
            const c = $('asset-library-container');
            if (!c) return;
            if (data.success && data.data && data.data.length > 0) {
                c.innerHTML = '<div class="d-flex flex-wrap justify-content-center" style="gap:6px;"></div>';
                data.data.forEach(a => {
                    const img = document.createElement('img');
                    img.src = a.url;
                    img.style.cssText = 'width:50px;height:50px;object-fit:contain;cursor:pointer;border-radius:8px;border:1.5px solid #e2e8f0;padding:3px;transition:all 0.15s;';
                    img.title = a.name;
                    img.onmouseover = () => { img.style.borderColor='#6366f1'; };
                    img.onmouseout = () => { img.style.borderColor='#e2e8f0'; };
                    img.onclick = () => {
                        fabric.Image.fromURL(a.url, fImg => {
                            fImg.set({ left:100, top:100, customType:'image' });
                            fImg.scaleToWidth(200);
                            canvas.add(fImg); canvas.setActiveObject(fImg); updateLayersList(); saveHistory();
                        }, { crossOrigin:'anonymous' });
                    };
                    c.firstChild.appendChild(img);
                });
            } else {
                c.innerHTML = '<div class="small text-muted my-3"><i class="fa fa-inbox" style="font-size:18px;"></i><br>No assets yet</div>';
            }
        }).catch(() => {
            const c = $('asset-library-container');
            if (c) c.innerHTML = '<div class="small text-muted my-3"><i class="fa fa-inbox" style="font-size:18px;"></i><br>No assets yet</div>';
        });
    }
    loadAssets();

    // --- Undo/Redo ---
    let historyStack = [];
    let historyMods = 0;
    let isUndoing = false;
    const btnUndo = $('btn-undo');
    const btnRedo = $('btn-redo');
    const customAttrs = ['customType','customName','is_background','is_slot','color_group','ai_role','ai_max_chars','placeholderKey','ai_field','ai_semantic_role','ai_priority','auto_scale','ai_replaceable'];

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
    canvas.on('object:modified', () => { updateProps(); saveHistory(); });
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
    });

    // --- Load ZIP ---
    const zipUpload = $('zip-upload');
    if (zipUpload) zipUpload.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if(!file) return;
        const fd = new FormData();
        fd.append('zip_file', file);
        fetch(parseZipUrl, { method:'POST', headers:{'X-CSRF-TOKEN':csrfToken}, body:fd })
        .then(r => r.json()).then(data => {
            if(data.success) renderJsonToCanvas(data.config, data.images);
            else alert(data.message || 'Failed to parse ZIP');
        }).catch(err => alert('Error: ' + err.message));
    });

    function renderJsonToCanvas(config, images) {
        canvas.clear();
        templateImages = images;
        if(!config.layers) return;
        config.layers.sort((a,b) => (a.z_index||0) - (b.z_index||0)).forEach(layer => {
            if(layer.type === 'image') {
                const fn = layer.src.split('/').pop();
                fabric.Image.fromURL(images[fn] || layer.src, img => {
                    img.set({ left:layer.x, top:layer.y, customType:'image', customName:layer.name, is_background:layer.is_background, is_slot:layer.is_slot });
                    img.scaleToWidth(layer.w); img.scaleToHeight(layer.h);
                    canvas.add(img); img.moveTo(layer.z_index||0); updateLayersList();
                });
            } else if(layer.type === 'text') {
                let ct = 'text';
                if(layer.text && layer.text.startsWith('{{') && layer.text.endsWith('}}')) ct = 'placeholder';
                const t = new fabric.IText(layer.text || 'Text', {
                    left:layer.x, top:layer.y, fontSize:layer.size||60, fill:layer.color||'#000000',
                    fontFamily:layer.font||'Arial', fontWeight:layer.weight||'normal', customType:ct, customName:layer.name,
                    ai_role:layer.ai_role, ai_max_chars:layer.ai_max_chars
                });
                canvas.add(t); t.moveTo(layer.z_index||0);
            }
        });
        updateLayersList();
    }

    // --- Export Schemas ---
    function exportArteraSchema(objects, title) {
        return {
            schema_version: 1, template_id: 'tpl_' + Date.now(),
            canvas: { width: baseWidth, height: baseHeight, background_color: canvas.backgroundColor || '#FFFFFF' },
            elements: objects.map((obj, i) => {
                const z = i+1, w = Math.round(obj.width*obj.scaleX), h = Math.round(obj.height*obj.scaleY);
                let o = { id:'el_'+z, name:obj.customName||(obj.type==='image'?'layer_'+z:'text_'+z), type:obj.type==='i-text'?'text':obj.type,
                    x:Math.round(obj.left), y:Math.round(obj.top), w:w, h:h, rotation:obj.angle||0, opacity:obj.opacity??1, z_index:z, locked:!obj.selectable, visible:obj.visible!==false };
                if (obj.type === 'image') { o.src=obj.getSrc(); o.is_background=obj.is_background||false; o.is_slot=obj.is_slot||false; }
                else if (obj.type==='text'||obj.type==='i-text') {
                    o.text=obj.text; o.font={family:obj.fontFamily,size:obj.fontSize,weight:obj.fontWeight,color:obj.fill,justification:obj.textAlign||'left',auto_scale:obj.auto_scale||false};
                    o.placeholder=obj.customType==='placeholder'?{field_type:obj.placeholderKey,required:true}:null;
                    o.ai={field:obj.ai_field||null,role:obj.ai_semantic_role||null,priority:obj.ai_priority||null,max_chars:obj.ai_max_chars||null,replaceable:obj.ai_replaceable||false};
                } else if (obj.customType==='shape') { o.fill=obj.fill; o.stroke=obj.stroke; o.strokeWidth=obj.strokeWidth; }
                return o;
            }),
            assets: [], metadata: { title: title, created_at: new Date().toISOString() }
        };
    }

    function exportLegacyJson(objects, title) {
        const j = { name: title.replace(/\s+/g,'_'), info:{width:baseWidth,height:baseHeight}, layers:[] };
        objects.forEach((obj,i) => {
            const z=i+1, w=Math.round(obj.width*obj.scaleX), h=Math.round(obj.height*obj.scaleY);
            if (obj.type==='image') j.layers.push({name:obj.customName||'layer_'+z,type:'image',src:obj.getSrc(),x:Math.round(obj.left),y:Math.round(obj.top),w:w,h:h,z_index:z,is_background:obj.is_background||false,is_slot:obj.is_slot||false});
            else if (obj.type==='i-text'||obj.type==='text') j.layers.push({name:obj.customName||'text_'+z,type:'text',text:obj.text,x:Math.round(obj.left),y:Math.round(obj.top),w:w,h:h,z_index:z,color:obj.fill,weight:obj.fontWeight,size:obj.fontSize,font:obj.fontFamily,ai_role:obj.ai_role||null,ai_max_chars:obj.ai_max_chars||null});
        });
        return j;
    }

    // --- Publish ---
    const btnSave = $('btn-save');
    if (btnSave) btnSave.addEventListener('click', function() {
        this.disabled = true;
        this.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Publishing...';

        canvas.setZoom(1); canvas.setWidth(baseWidth); canvas.setHeight(baseHeight);
        canvas.discardActiveObject(); canvas.renderAll();

        const thumbnail = canvas.toDataURL({ format:'webp', quality:0.8 });
        const title = $('template-title').value;
        const objects = canvas.getObjects();

        const fd = new FormData();
        fd.append('title', title);
        fd.append('thumbnail', thumbnail);
        fd.append('schema_json', JSON.stringify(exportArteraSchema(objects, title)));
        fd.append('legacy_json', JSON.stringify(exportLegacyJson(objects, title)));

        fetch(saveUrl, { method:'POST', headers:{'X-CSRF-TOKEN':csrfToken}, body:fd })
        .then(r => r.json()).then(data => {
            btnSave.disabled = false;
            btnSave.innerHTML = '<i class="fa-solid fa-cloud-arrow-up"></i> Publish Template';
            alert(data.success ? '✅ Template Published!' : '❌ ' + (data.message||'Publishing failed'));
        }).catch(err => {
            btnSave.disabled = false;
            btnSave.innerHTML = '<i class="fa-solid fa-cloud-arrow-up"></i> Publish Template';
            alert('❌ ' + err.message);
        });

        currentScale = updateCanvasZoom();
    });

})();
