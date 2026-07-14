@extends('layouts.app')

@section('extra_css')
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

@if(isset($globalFonts) && $globalFonts->count() > 0)
    @foreach($globalFonts as $font)
        @font-face {
            font-family: '{{ $font->name }}';
            src: url('{{ asset($font->file_path) }}');
        }
    @endforeach
@endif

    .aim-container { font-family: 'Inter', sans-serif; padding: 1rem; background: #f8fafc; min-height: calc(100vh - 120px); display: flex; flex-direction: column; border-radius: 8px; }
    
    /* Header */
    .aim-header { display: flex; align-items: center; gap: 16px; margin-bottom: 1rem; flex-wrap: wrap; background: #fff; padding: 1rem 1.5rem; border-radius: 16px; border: 1px solid #e2e8f0; }
    .aim-header-icon { width: 48px; height: 48px; border-radius: 12px; background: linear-gradient(135deg, #6366f1, #8b5cf6); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.2rem; box-shadow: 0 4px 12px rgba(99,102,241,0.3); }
    .aim-header h2 { font-size: 1.25rem; font-weight: 800; color: #1e293b; margin: 0; }
    .aim-header p { font-size: 0.8rem; color: #64748b; margin: 0; }

    /* Buttons */
    .aim-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 8px 16px; border-radius: 10px; font-size: 0.8rem; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s; font-family: 'Inter', sans-serif; }
    .aim-btn-primary { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; box-shadow: 0 4px 12px rgba(99,102,241,0.3); }
    .aim-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(99,102,241,0.4); color: #fff; }
    .aim-btn-outline { background: #fff; border: 1.5px solid #e2e8f0; color: #475569; }
    .aim-btn-outline:hover { border-color: #cbd5e1; background: #f8fafc; color: #1e293b; }
    
    /* Layout — using .editor-sidebar to NOT conflict with admin's .main-sidebar */
    .builder-container { display: flex; flex-grow: 1; gap: 1rem; min-height: 600px; }
    .editor-sidebar { width: 280px; min-width: 280px; background: #fff; padding: 1.25rem; border-radius: 16px; border: 1px solid #e2e8f0; overflow-y: auto; display: flex; flex-direction: column; gap: 1.25rem; }
    .properties-panel { width: 300px; min-width: 300px; background: #fff; padding: 1.25rem; border-radius: 16px; border: 1px solid #e2e8f0; overflow-y: auto; }
    .canvas-container-wrap { flex-grow: 1; min-width: 0; display: block; text-align: center; background: #e2e8f0; border-radius: 16px; border: 1px inset #cbd5e1; overflow: auto; padding: 20px;}
    
    /* Form Elements */
    .aim-label { font-size: 0.75rem; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 6px; }
    .aim-input, .aim-select { width: 100%; padding: 8px 12px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 0.8rem; font-family: 'Inter', sans-serif; transition: all 0.2s; background: #fff; color: #1e293b; }
    .aim-input:focus, .aim-select:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
    
    /* Canvas */
    #canvas-wrapper { position: relative; display: inline-block; text-align: left; background: #fff; box-shadow: 0 8px 32px rgba(0,0,0,0.1); border-radius: 4px; overflow: hidden; transition: all 0.3s ease; }
    
    @if($mode === 'frame')
    .frame-guide {
        position: absolute;
        left: 0;
        right: 0;
        height: 0;
        border-top: 2px dashed rgba(148, 163, 184, 0.8); /* Light gray #94a3b8 */
        z-index: 10;
        pointer-events: none;
    }
    .frame-guide.header-guide { top: calc(140px * var(--canvas-scale, 1)); }
    .frame-guide.footer-guide { bottom: calc(140px * var(--canvas-scale, 1)); }
    @endif

    
    /* Force fabric canvas to respect container sizes and ignore adminLTE overrides */
    .canvas-container { margin: 0 auto !important; width: 100% !important; height: 100% !important; }
    .canvas-container canvas { 
        max-width: none !important; 
        max-height: none !important; 
        width: 100% !important; 
        height: 100% !important; 
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
    }
    
    /* Lists */
    .aim-list-group { display: flex; flex-direction: column; gap: 6px; margin: 0; padding: 0; list-style: none; }
    .aim-list-item { display: flex; justify-content: space-between; align-items: center; padding: 6px 10px; border-radius: 10px; background: #f8fafc; border: 1px solid #e2e8f0; font-size: 0.8rem; font-weight: 500; color: #334155; transition: all 0.15s; }
    .aim-list-item:hover { border-color: #cbd5e1; background: #f1f5f9; }
    
    .panel-section-title { font-size: 0.85rem; font-weight: 700; color: #1e293b; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 6px; }
    .panel-section-title i { color: #6366f1; }
    
    .aim-container hr { border-color: #e2e8f0; margin: 0; }

    /* Shapes grid */
    .shapes-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; }
    .shapes-grid .aim-btn { padding: 10px 6px; flex-direction: column; font-size: 0.7rem; gap: 4px; }
    .shapes-grid .aim-btn i { font-size: 1rem; }

    /* Icons grid */
    .icons-grid-wrap { max-height: 180px; overflow-y: auto; background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 8px; }
    .icons-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 4px; }
    .icons-grid .icon-item { display: flex; align-items: center; justify-content: center; width: 100%; aspect-ratio: 1; border: 1.5px solid #e2e8f0; border-radius: 8px; background: #fff; cursor: pointer; transition: all 0.15s; font-size: 1rem; color: #475569; }
    .icons-grid .icon-item:hover { border-color: #6366f1; background: #eef2ff; color: #6366f1; transform: scale(1.08); }

    /* Canvas BG gradient controls */
    .gradient-colors { display: flex; gap: 8px; margin-top: 8px; }
    .gradient-colors .color-field { flex: 1; }
    .gradient-colors .color-field input[type="color"] { height: 34px; }
</style>
@endsection

@section('content')
    <div class="aim-container">
        
        <!-- Header -->
        <div class="aim-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center" style="gap: 16px;">
                <div class="aim-header-icon"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
                <div>
                    <h2>Canva-Style Template Builder</h2>
                    <p>Design dynamic layouts powered by AI metadata</p>
                </div>
            </div>
            <div class="d-flex" style="gap: 8px;">
                <button class="aim-btn aim-btn-outline" id="btn-undo" disabled><i class="fa fa-undo"></i> Undo</button>
                <button class="aim-btn aim-btn-outline" id="btn-redo" disabled><i class="fa fa-redo"></i> Redo</button>
                @if($mode === 'frame')
                    <button class="aim-btn aim-btn-outline" data-toggle="modal" data-target="#frameTemplatesModal">
                        <i class="fa-solid fa-images"></i> Select Frame
                    </button>
                @else
                    <button class="aim-btn aim-btn-outline" data-toggle="modal" data-target="#customTemplatesModal">
                        <i class="fa-solid fa-folder-open"></i> Select Custom Template
                    </button>
                @endif
                <button class="aim-btn aim-btn-primary" id="btn-save" data-mode="{{ $mode }}"><i class="fa fa-save"></i> Publish @if($mode === 'frame') Frame @else Template @endif</button>
            </div>
        </div>

        <div class="builder-container">
            
            <!-- Left Sidebar: Tools & Layers -->
            <div class="editor-sidebar">
                
                @if($mode === 'frame')
                <div>
                    <div class="panel-section-title"><i class="fa-solid fa-tags"></i> Placeholders</div>
                    <select id="placeholder-select" class="aim-select mb-2">
                        <option value="logo">Business Logo</option>
                        <option value="name">Business Name</option>
                        <option value="phone_1">Mobile Number</option>
                        <option value="email">Email ID</option>
                        <option value="website">Website</option>
                        <option value="address">Address</option>
                    </select>
                    <div style="display: flex; gap: 6px;">
                        <button class="aim-btn aim-btn-outline" id="add-placeholder" style="flex-grow: 1; color: #10b981; border-color: #a7f3d0;"><i class="fa fa-plus"></i> Add Placeholder</button>
                        <button class="aim-btn aim-btn-outline" id="duplicate-placeholder" style="color: #6366f1; border-color: #c7d2fe; padding: 0 12px;" title="Duplicate Selected Layer (e.g. phone_1 -> phone_2)"><i class="fa fa-clone"></i></button>
                    </div>
                </div>
                
                <hr>
                @endif

                <div>
                    <div class="panel-section-title"><i class="fa-solid fa-plus-circle"></i> Add Elements</div>
                    <button class="aim-btn aim-btn-outline w-100 mb-2" id="add-text"><i class="fa fa-font"></i> Add Text</button>
                    <button class="aim-btn aim-btn-outline w-100" onclick="document.getElementById('image-upload').click()"><i class="fa fa-upload"></i> Upload Image</button>
                    <input type="file" id="image-upload" accept="image/*" style="display:none">
                </div>
                
                <hr>

                <!-- Shapes Section -->
                <div>
                    <div class="panel-section-title"><i class="fa-solid fa-shapes"></i> Shapes</div>
                    <div class="shapes-grid">
                        <button class="aim-btn aim-btn-outline" id="add-rect"><i class="fa-regular fa-square"></i> Rect</button>
                        <button class="aim-btn aim-btn-outline" id="add-circle"><i class="fa-regular fa-circle"></i> Circle</button>
                        <button class="aim-btn aim-btn-outline" id="add-triangle"><i class="fa-solid fa-play fa-rotate-270"></i> Triangle</button>
                        <button class="aim-btn aim-btn-outline" id="add-line"><i class="fa-solid fa-minus"></i> Line</button>
                        <button class="aim-btn aim-btn-outline" id="add-star"><i class="fa-regular fa-star"></i> Star</button>
                    </div>
                    <div class="mt-3">
                        <button class="aim-btn aim-btn-outline w-100 mb-2" id="btn-svg-library" style="border-color: #6366f1; color: #5538EE;"><i class="fa-solid fa-icons"></i> Shape Library</button>
                        <button class="aim-btn aim-btn-outline w-100" onclick="document.getElementById('svg-upload').click()"><i class="fa-solid fa-upload"></i> Upload Custom SVG</button>
                        <input type="file" id="svg-upload" accept=".svg" style="display:none">
                    </div>
                </div>

                <hr>

                <!-- Icons Section -->
                <div>
                    <div class="panel-section-title"><i class="fa-solid fa-icons"></i> Icons</div>
                    <input type="text" class="aim-input mb-2" id="icon-search" placeholder="Search icons...">
                    <div class="icons-grid-wrap">
                        <div class="icons-grid" id="icons-grid">
                            <!-- Icons will be dynamically populated here by JS -->
                        </div>
                    </div>
                </div>

                <hr>
                
                <div>
                    <div class="panel-section-title"><i class="fa-solid fa-note-sticky"></i> Stickers Library</div>
                    <div class="mb-2">
                        <input type="text" id="sticker-search-input" class="aim-input form-control form-control-sm" placeholder="Search stickers...">
                    </div>
                    <div id="asset-library-container" style="max-height: 200px; overflow-y: auto; background: #f8fafc; border: 1.5px dashed #cbd5e1; border-radius: 10px; padding: 10px; text-align: center;">
                        <div class="small text-muted my-3">
                            <i class="fa fa-spinner fa-spin mb-2" style="font-size: 20px;"></i><br>Loading Assets...
                        </div>
                    </div>
                </div>
                

                
                <div style="flex-grow: 1; display: flex; flex-direction: column; min-height: 0;">
                    <div class="panel-section-title"><i class="fa-solid fa-layer-group"></i> Layers</div>
                    <ul class="aim-list-group" id="layers-list" style="overflow-y: auto; flex-grow: 1;">
                        <!-- Layers will be populated here -->
                    </ul>
                </div>
            </div>

            <!-- Center: Canvas -->
            <div class="canvas-container-wrap">
                <div id="canvas-wrapper">
                    <canvas id="template-canvas"></canvas>
                    @if($mode === 'frame')
                        <div class="frame-guide header-guide"></div>
                        <div class="frame-guide footer-guide"></div>
                    @endif
                </div>
            </div>

            <!-- Right Sidebar: Properties -->
            <div class="properties-panel">
                <div class="panel-section-title"><i class="fa-solid fa-sliders"></i> Properties</div>
                
                <div id="no-selection" class="text-muted small mt-2">Select an element to edit its properties.</div>
                
                <div id="properties-form" style="display:none;" class="mt-3">
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="aim-label">X Position</label>
                            <input type="number" class="aim-input" id="prop-x">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="aim-label">Y Position</label>
                            <input type="number" class="aim-input" id="prop-y">
                        </div>
                    </div>
                    <!-- Flip -->
                    <div class="mb-3">
                        <label class="aim-label d-block">Flip</label>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="flip-horizontal" title="Flip Horizontal" style="min-width:32px;"><i class="fa-solid fa-arrows-left-right"></i></button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="flip-vertical" title="Flip Vertical" style="min-width:32px;"><i class="fa-solid fa-arrows-up-down"></i></button>
                        </div>
                    </div>
                    <!-- Edit Points (Node Editor) - visible only for path/SVG shapes -->
                    <div class="mb-3" id="node-edit-section" style="display:none;">
                        <button type="button" class="btn btn-sm btn-outline-primary w-100" id="btn-edit-points">
                            <i class="fa-solid fa-bezier-curve me-1"></i> <span id="btn-edit-points-text">Edit Points</span>
                        </button>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="aim-label">Width</label>
                            <input type="number" class="aim-input" id="prop-w">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="aim-label">Height</label>
                            <input type="number" class="aim-input" id="prop-h">
                        </div>
                    </div>
                    
                    <!-- Text only properties -->
                    <div id="text-properties" style="display:none;">
                        <div class="mb-3">
                            <label class="aim-label">Text content</label>
                            <input type="text" class="aim-input" id="prop-text">
                        </div>
                        <div class="mb-3">
                            <label class="aim-label">Font Family</label>
                            <select class="aim-select" id="prop-font-family">
                                <option value="Arial">Arial</option>
                                <option value="Helvetica">Helvetica</option>
                                <option value="Times New Roman">Times New Roman</option>
                                <option value="Courier New">Courier New</option>
                                <option value="Verdana">Verdana</option>
                                <option value="Georgia">Georgia</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="aim-label d-block">Style</label>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="prop-bold" title="Bold" style="font-weight:bold; min-width:36px;">B</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="prop-italic" title="Italic" style="font-style:italic; min-width:36px;">I</button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="aim-label d-block">Alignment</label>
                            <div class="btn-group w-100" role="group">
                                <button type="button" class="btn btn-sm btn-outline-secondary prop-text-align" data-align="left" style="border-radius: 10px 0 0 10px;"><i class="fa fa-align-left"></i></button>
                                <button type="button" class="btn btn-sm btn-outline-secondary prop-text-align" data-align="center"><i class="fa fa-align-center"></i></button>
                                <button type="button" class="btn btn-sm btn-outline-secondary prop-text-align" data-align="right"><i class="fa fa-align-right"></i></button>
                                <button type="button" class="btn btn-sm btn-outline-secondary prop-text-align" data-align="justify" style="border-radius: 0 10px 10px 0;"><i class="fa fa-align-justify"></i></button>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="aim-label">Font Size</label>
                                <input type="number" class="aim-input" id="prop-font-size">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="aim-label">Color</label>
                                <input type="text" class="aim-input w-100" id="prop-color" data-jscolor="{}" placeholder="#000000" style="height: 38px; padding-left: 10px;">
                            </div>
                        </div>
                        
                        <hr class="my-3">
                        <div class="panel-section-title" style="color: #475569;"><i class="fa-solid fa-text-width"></i> Spacing</div>
                        <div class="row">
                            <div class="col-4 mb-3">
                                <label class="aim-label" title="Letter Spacing (tracking)">Letter</label>
                                <input type="number" class="aim-input" id="prop-letter-spacing" step="0.1" placeholder="0" title="Letter Spacing (px)">
                            </div>
                            <div class="col-4 mb-3">
                                <label class="aim-label" title="Word Spacing">Word</label>
                                <input type="number" class="aim-input" id="prop-word-spacing" step="0.5" placeholder="0" title="Word Spacing (px)">
                            </div>
                            <div class="col-4 mb-3">
                                <label class="aim-label" title="Line Height (line spacing)">Line</label>
                                <input type="number" class="aim-input" id="prop-line-height" step="0.05" placeholder="1.16" title="Line Height (multiplier)">
                            </div>
                        </div>
                        <div class="mb-3 custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="prop-ai-autoscale">
                            <label class="custom-control-label small" for="prop-ai-autoscale">Auto-scale Font to Fit</label>
                        </div>
                    </div>

                    <!-- Image only properties -->
                    <div id="image-properties" style="display:none;">
                        <hr class="my-3">
                        <div class="panel-section-title" style="color: #6366f1;"><i class="fa-regular fa-image"></i> Image Settings</div>
                        <div class="mb-2 custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="prop-is-background">
                            <label class="custom-control-label small" for="prop-is-background">Set as Background</label>
                        </div>
                        <div class="mb-2 custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="prop-is-placeholder">
                            <label class="custom-control-label small" for="prop-is-placeholder">Is Post Images</label>
                        </div>
                        <div class="mb-2 custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="prop-is-colorizable-shape">
                            <label class="custom-control-label small" for="prop-is-colorizable-shape">Treat as Shape (Colorizable)</label>
                        </div>
                        @if($mode === 'frame')
                        <div class="mb-2 custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="prop-is-logo">
                            <label class="custom-control-label small" for="prop-is-logo">Set as Business Logo</label>
                        </div>
                        @endif
                        <div class="mb-3 mt-2">
                            <label class="aim-label">Mask with Shape <small class="text-muted">(Optional)</small></label>
                            <select class="aim-input p-1 w-100" id="prop-mask-layer" style="height: 34px;">
                                <option value="">-- None --</option>
                            </select>
                            <button type="button" id="btn-pick-mask" class="btn btn-sm btn-outline-primary mt-1 w-100"><i class="fa-solid fa-crosshairs"></i> Select Shape on Canvas</button>
                            <small class="text-muted" style="font-size: 11px;">Select a shape layer to cut this image exactly to that shape.</small>
                        </div>

                    </div>

                    <!-- Shape only properties -->
                    <div id="shape-properties" style="display:none;">
                        <hr class="my-3">
                        <div class="panel-section-title" style="color: #6366f1;"><i class="fa-solid fa-shapes"></i> Shape Settings</div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="aim-label d-block mb-1">Fill Color</label>
                                <input type="text" class="aim-input w-100" id="prop-fill-color" data-jscolor="{}" placeholder="#6366f1" style="height: 38px; padding-left: 10px;">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="aim-label d-block mb-1">Stroke Color</label>
                                <input type="text" class="aim-input w-100" id="prop-stroke-color" data-jscolor="{}" placeholder="#000000" style="height: 38px; padding-left: 10px;">
                            </div>
                        </div>
                        <div class="mb-3 custom-control custom-switch" id="shape-vector-gradient-wrapper">
                            <input type="checkbox" class="custom-control-input" id="prop-shape-gradient">
                            <label class="custom-control-label small" for="prop-shape-gradient">Use Gradient Fill</label>
                        </div>
                        <div id="shape-gradient-props" style="display:none;" class="pl-2 border-left mb-3">
                            <div class="row">
                                <div class="col-4 mb-2">
                                    <label class="aim-label">Start Color</label>
                                    <input type="text" class="aim-input w-100" id="prop-grad-color1" data-jscolor="{}" value="#6366f1" placeholder="#6366f1" style="height:30px; padding-left: 10px;">
                                </div>
                                <div class="col-4 mb-2">
                                    <label class="aim-label">Mid Color</label>
                                    <input type="text" class="aim-input w-100" id="prop-grad-color-mid" data-jscolor="{}" value="#a855f7" placeholder="#a855f7" style="height:30px; padding-left: 10px;">
                                </div>
                                <div class="col-4 mb-2">
                                    <label class="aim-label">End Color</label>
                                    <input type="text" class="aim-input w-100" id="prop-grad-color2" data-jscolor="{}" value="#ffffff" placeholder="#ffffff" style="height:30px; padding-left: 10px;">
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="aim-label">Start Opacity (<span id="grad-op1-val">100</span>%)</label>
                                <input type="range" class="custom-range" id="prop-grad-op1" step="0.01" min="0" max="1" value="1">
                            </div>
                            <div class="mb-2">
                                <label class="aim-label">Middle Opacity (<span id="grad-op-mid-val">100</span>%)</label>
                                <input type="range" class="custom-range" id="prop-grad-op-mid" step="0.01" min="0" max="1" value="1">
                            </div>
                            <div class="mb-2">
                                <label class="aim-label">End Opacity (<span id="grad-op2-val">100</span>%)</label>
                                <input type="range" class="custom-range" id="prop-grad-op2" step="0.01" min="0" max="1" value="1">
                            </div>
                            <div class="mb-2">
                                <label class="aim-label d-block">Direction</label>
                                <div class="btn-group w-100" role="group">
                                    <button type="button" class="btn btn-sm btn-outline-secondary prop-grad-dir" data-dir="top-bottom" style="border-radius: 10px 0 0 10px;" title="Top to Bottom">⬇️</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary prop-grad-dir" data-dir="bottom-top" title="Bottom to Top">⬆️</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary prop-grad-dir" data-dir="left-right" title="Left to Right">➡️</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary prop-grad-dir" data-dir="right-left" style="border-radius: 0 10px 10px 0;" title="Right to Left">⬅️</button>
                                </div>
                            </div>
                        </div>
                        <div class="row" id="shape-vector-controls-wrapper">
                            <div class="col-6 mb-3">
                                <label class="aim-label">Stroke Width</label>
                                <input type="number" class="aim-input" id="prop-stroke-width" value="0" min="0">
                            </div>
                            <div class="col-12 mb-3">
                                <label class="aim-label d-flex justify-content-between align-items-center mb-1">
                                    <span>Border Radius (TL, TR, BR, BL)</span>
                                    <button type="button" class="btn btn-sm btn-primary p-0 px-2" id="prop-radius-lock" title="Lock Uniform Radius" style="border-radius: 6px; font-size: 0.75rem; height: 22px;">
                                        <i class="fa-solid fa-lock" id="radius-lock-icon"></i>
                                    </button>
                                </label>
                                <div class="d-flex align-items-center gap-1">
                                    <input type="number" class="aim-input p-1 text-center" id="prop-radius-tl" value="0" min="0" title="Top-Left Corner" placeholder="TL" style="border-radius: 6px;">
                                    <input type="number" class="aim-input p-1 text-center" id="prop-radius-tr" value="0" min="0" title="Top-Right Corner" placeholder="TR" style="border-radius: 6px;">
                                    <input type="number" class="aim-input p-1 text-center" id="prop-radius-br" value="0" min="0" title="Bottom-Right Corner" placeholder="BR" style="border-radius: 6px;">
                                    <input type="number" class="aim-input p-1 text-center" id="prop-radius-bl" value="0" min="0" title="Bottom-Left Corner" placeholder="BL" style="border-radius: 6px;">
                                    <input type="hidden" id="prop-border-radius" value="0">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Shared Effects Properties -->
                    <div id="shared-properties">
                        <hr class="my-3">
                        <div class="panel-section-title"><i class="fa-solid fa-wand-magic"></i> Effects</div>
                        <div class="mb-3">
                            <label class="aim-label">Opacity (<span id="opacity-val">100</span>%)</label>
                            <input type="range" class="custom-range" id="prop-opacity" step="0.01" min="0" max="1" value="1">
                        </div>
                        
                        <div class="mb-3 custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="prop-has-shadow">
                            <label class="custom-control-label small" for="prop-has-shadow">Enable Drop Shadow</label>
                        </div>
                        <div id="shadow-properties" style="display:none;" class="pl-2 border-left mt-2">
                            <div class="row">
                                <div class="col-6 mb-2">
                                    <label class="aim-label">Blur</label>
                                    <input type="number" class="aim-input" id="prop-shadow-blur" value="5">
                                </div>
                                <div class="col-6 mb-2">
                                    <label class="aim-label">Color</label>
                                    <input type="text" class="aim-input w-100" id="prop-shadow-color" data-jscolor="{}" value="#000000" placeholder="#000000" style="height: 38px; padding-left: 10px;">
                                </div>
                                <div class="col-6 mb-2">
                                    <label class="aim-label">Offset X</label>
                                    <input type="number" class="aim-input" id="prop-shadow-x" value="2">
                                </div>
                                <div class="col-6 mb-2">
                                    <label class="aim-label">Offset Y</label>
                                    <input type="number" class="aim-input" id="prop-shadow-y" value="2">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button class="aim-btn aim-btn-outline text-danger w-100 mt-3" id="delete-element"><i class="fa fa-trash"></i> Delete Selected</button>
                </div>
                
                <hr class="my-4">
                <div class="panel-section-title"><i class="fa-solid fa-gear"></i> Template Settings</div>
                <div class="mb-3 mt-2">
                    <label class="aim-label">Template Name</label>
                    <input type="text" class="aim-input" id="template-title" value="My Custom Template">
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="aim-label">Canvas Width</label>
                        <input type="number" class="aim-input" id="template-w" value="1080">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="aim-label">Canvas Height</label>
                        <input type="number" class="aim-input" id="template-h" value="1080">
                    </div>
                </div>
                <button class="aim-btn aim-btn-outline w-100 mb-3" id="btn-resize-canvas"><i class="fa-solid fa-expand"></i> Resize Canvas</button>

                @if($mode === 'frame')
                <hr class="my-4">
                <div class="panel-section-title"><i class="fa-solid fa-images"></i> Frame Settings</div>
                <div class="mb-3 mt-2">
                    <label class="aim-label">Category</label>
                    <select id="frame-category" class="aim-select">
                        @foreach($frameCategories as $cat)
                            <option value="{{ $cat->id }}" {{ isset($editFrame) && $editFrame->poster_category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="aim-label">Template Type</label>
                    <select id="frame-template-type" class="aim-select">
                        <option value="1:1" {{ isset($editFrame) && $editFrame->template_type == '1:1' ? 'selected' : '' }}>1:1 (Square)</option>
                        <option value="frame" {{ isset($editFrame) && $editFrame->template_type == 'frame' ? 'selected' : '' }}>Frame</option>
                        <option value="poster" {{ isset($editFrame) && $editFrame->template_type == 'poster' ? 'selected' : '' }}>Poster</option>
                        <option value="video" {{ isset($editFrame) && $editFrame->template_type == 'video' ? 'selected' : '' }}>Video</option>
                    </select>
                </div>

                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="aim-label">Address Qty</label>
                        <input type="number" class="aim-input" id="req_address" min="0" value="{{ isset($editFrame) ? $editFrame->req_address : 0 }}">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="aim-label">Email Qty</label>
                        <input type="number" class="aim-input" id="req_email" min="0" value="{{ isset($editFrame) ? $editFrame->req_email : 0 }}">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="aim-label">Phone Qty</label>
                        <input type="number" class="aim-input" id="req_phone" min="0" value="{{ isset($editFrame) ? $editFrame->req_phone : 0 }}">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="aim-label">Website Qty</label>
                        <input type="number" class="aim-input" id="req_website" min="0" value="{{ isset($editFrame) ? $editFrame->req_website : 0 }}">
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Custom Templates Modal -->
    <div class="modal fade" id="customTemplatesModal" tabindex="-1" role="dialog" aria-labelledby="customTemplatesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content" style="border-radius: 16px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title font-weight-bold" id="customTemplatesModalLabel" style="font-family: 'Inter', sans-serif;"><i class="fa-solid fa-folder-open text-primary mr-2"></i> Custom Templates</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0" style="font-family: 'Inter', sans-serif; font-size: 0.9rem;">
                            <thead class="bg-light sticky-top">
                                <tr>
                                    <th>Preview</th>
                                    <th>ID</th>
                                    <th>Template Name</th>
                                    <th>Status</th>
                                    <th class="text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(isset($customFrames) && $customFrames->count() > 0)
                                    @foreach($customFrames as $frame)
                                    @php
                                        $zipFolder = str_replace('.zip', '', $frame->zip_file_path);
                                        $previewUrl = asset('assets/images/placeholder.png'); // Default fallback
                                        
                                        if (App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean') {
                                            if (Storage::disk('spaces')->exists('uploads/template/'.$zipFolder.'/preview.webp')) {
                                                $previewUrl = Storage::disk('spaces')->url('uploads/template/'.$zipFolder.'/preview.webp');
                                            } elseif (Storage::disk('spaces')->exists('uploads/template/'.$zipFolder.'/preview.jpg')) {
                                                $previewUrl = Storage::disk('spaces')->url('uploads/template/'.$zipFolder.'/preview.jpg');
                                            } elseif (Storage::disk('spaces')->exists('uploads/template/'.$zipFolder.'/preview.png')) {
                                                $previewUrl = Storage::disk('spaces')->url('uploads/template/'.$zipFolder.'/preview.png');
                                            } else {
                                                $previewUrl = Storage::disk('spaces')->url('uploads/template/'.$zipFolder.'/preview.jpg');
                                            }
                                        } else {
                                            $localDir = public_path('uploads/template/'.$zipFolder.'/');
                                            if (file_exists($localDir . 'preview.webp')) {
                                                $previewUrl = asset('uploads/template/'.$zipFolder.'/preview.webp');
                                            } elseif (file_exists($localDir . 'preview.jpg')) {
                                                $previewUrl = asset('uploads/template/'.$zipFolder.'/preview.jpg');
                                            } elseif (file_exists($localDir . 'preview.png')) {
                                                $previewUrl = asset('uploads/template/'.$zipFolder.'/preview.png');
                                            } else {
                                                $files = glob($localDir . '*.{webp,jpg,jpeg,png}', GLOB_BRACE);
                                                if (!empty($files)) {
                                                    $previewUrl = asset('uploads/template/'.$zipFolder.'/'.basename($files[0]));
                                                } else {
                                                    $previewUrl = asset('uploads/template/'.$zipFolder.'/preview.jpg');
                                                }
                                            }
                                        }
                                    @endphp
                                    <tr>
                                        <td>
                                            <img src="{{ $previewUrl }}" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; border: 1px solid #e2e8f0; background: #f8fafc;" onerror="this.src='{{ asset('assets/images/placeholder.png') }}'; this.onerror=null;">
                                        </td>
                                        <td>#{{ $frame->id }}</td>
                                        <td class="font-weight-bold text-dark">{{ $frame->original_zip_name ?? $frame->zip_file_path }}</td>
                                        <td>
                                            @if($frame->status == 1)
                                                <span class="badge badge-success px-2 py-1">Active</span>
                                            @else
                                                <span class="badge badge-secondary px-2 py-1">Inactive</span>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            <button class="aim-btn aim-btn-primary aim-btn-sm" onclick="loadExistingTemplate({{ $frame->id }})" data-dismiss="modal">
                                                <i class="fa fa-edit"></i> Edit
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No custom templates found.</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>

    <!-- Frame Templates Modal -->
    <div class="modal fade" id="frameTemplatesModal" tabindex="-1" role="dialog" aria-labelledby="frameTemplatesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content" style="border-radius: 16px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title font-weight-bold" id="frameTemplatesModalLabel" style="font-family: 'Inter', sans-serif;"><i class="fa-solid fa-images text-primary mr-2"></i> Select Frame</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <input type="text" id="frameSearchInput" class="form-control" placeholder="Search by name or ID..." onkeyup="filterFrames()">
                    </div>
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0" style="font-family: 'Inter', sans-serif; font-size: 0.9rem;">
                            <thead class="bg-light sticky-top">
                                <tr>
                                    <th>Preview</th>
                                    <th>ID</th>
                                    <th>Name (ZIP)</th>
                                    <th>Category</th>
                                    <th class="text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody id="frameTableBody">
                                @if(isset($posterMakers) && count($posterMakers) > 0)
                                    @foreach($posterMakers as $frame)
                                    <tr class="frame-row">
                                    @php
                                        $previewUrl = asset('assets/images/placeholder.png'); // Default fallback
                                        if ($frame->post_thumb) {
                                            $previewUrl = (App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean') 
                                                ? Storage::disk('spaces')->url('uploads/' . $frame->post_thumb) 
                                                : asset('uploads/' . $frame->post_thumb);
                                        }
                                    @endphp
                                    <tr>
                                        <td>
                                            <img src="{{ $previewUrl }}" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; border: 1px solid #e2e8f0; background: #f8fafc;" onerror="this.src='{{ asset('assets/images/placeholder.png') }}'; this.onerror=null;">
                                        </td>
                                        <td>#{{ $frame->id }}</td>
                                        <td class="font-weight-bold text-dark">{{ $frame->zip_name }}</td>
                                        <td>
                                            <span class="badge badge-light px-2 py-1">{{ $frame->poster_category->name ?? 'N/A' }}</span>
                                        </td>
                                        <td class="text-right">
                                            <button class="aim-btn aim-btn-primary aim-btn-sm" onclick="loadExistingFrame({{ $frame->id }})" data-dismiss="modal">
                                                <i class="fa fa-edit"></i> Edit
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No frames found.</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- SVG Library Modal -->
    <style>
        .premium-modal .modal-content {
            border: none;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            overflow: hidden;
        }
        .premium-modal .modal-header {
            background: #ffffff;
            border-bottom: 1px solid #f1f5f9;
            padding: 1.25rem 1.5rem;
        }
        .premium-modal .modal-title {
            font-size: 1.1rem;
            color: #1e293b;
        }
        .premium-modal .close {
            opacity: 0.6;
            transition: 0.2s;
        }
        .premium-modal .close:hover {
            opacity: 1;
            color: #ef4444;
        }
        .premium-tabs {
            border-bottom: none;
            gap: 10px;
            padding: 0 1.5rem;
            margin-top: 1rem;
        }
        .premium-tabs .nav-link {
            border: none;
            background: #f8fafc;
            color: #64748b;
            border-radius: 8px !important;
            padding: 8px 16px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        .premium-tabs .nav-link:hover {
            background: #f1f5f9;
            color: #475569;
        }
        .premium-tabs .nav-link.active {
            background: #eff6ff;
            color: #4f46e5;
        }
        .premium-modal-body {
            max-height: 60vh;
            overflow-y: auto;
            padding: 1.5rem;
            background: #ffffff;
        }
        /* Custom Scrollbar */
        .premium-modal-body::-webkit-scrollbar {
            width: 8px;
        }
        .premium-modal-body::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }
        .premium-modal-body::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        .premium-modal-body::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        .shape-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 18px;
        }
        .premium-btn-close {
            background: #f1f5f9;
            color: #475569;
            border: none;
            font-weight: 600;
            padding: 8px 20px;
            border-radius: 8px;
            transition: 0.2s;
        }
        .premium-btn-close:hover {
            background: #e2e8f0;
            color: #1e293b;
        }
    </style>
    <div class="modal fade premium-modal" id="svgLibraryModal" tabindex="-1" role="dialog" aria-labelledby="svgLibraryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content" style="border-radius: 16px;">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold" id="svgLibraryModalLabel" style="font-family: 'Inter', sans-serif;">
                        <i class="fa-solid fa-shapes text-primary mr-2"></i> Shape Library
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fa-solid fa-times"></i></span>
                    </button>
                </div>
                
                <div class="modal-body premium-modal-body">
                    <div class="shape-grid" id="custom-svg-container">
                        <!-- Custom SVGs will be populated here by JS -->
                    </div>
                    <div class="col-12 text-center text-muted py-5" id="custom-svg-empty" style="display:none;">
                        <div style="background: #f8fafc; border-radius: 50%; width: 80px; height: 80px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                            <i class="fa-solid fa-cloud-arrow-up fa-2x" style="color: #cbd5e1;"></i>
                        </div>
                        <h6 class="font-weight-bold text-slate-700">No Custom Shapes Yet</h6>
                        <p class="small text-slate-500 mb-0">Click "Upload Custom SVG" from the sidebar<br>to build your personal library.</p>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-2 pb-3 px-4">
                    <button type="button" class="premium-btn-close" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('script')
<script src="{{ asset('assets/js/fabric.min.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jscolor/2.5.2/jscolor.min.js"></script>
<script>
    function triggerNativeInput(picker) {
        if (picker && picker.targetElement) {
            if (picker.targetElement.dataset.isTriggering) return;
            picker.targetElement.dataset.isTriggering = true;
            picker.targetElement.dispatchEvent(new Event('input', { bubbles: true }));
            picker.targetElement.dataset.isTriggering = '';
        }
    }
    jscolor.presets.default = {
        format: 'hex',
        hash: true,
        onInput: 'triggerNativeInput(this)'
    };
    const parseUrl = "{{ route('template_builder.parse_zip') }}";
    const saveUrl = "{{ route('template_builder.save') }}";
    const saveFrameUrl = "{{ route('template_builder.save_frame') }}";
    const loadFrameZipUrl = "{{ route('template_builder.load_frame_zip', '') }}";
    window.editing_frame_id = "{{ isset($editFrame) ? $editFrame->id : '' }}";
    const csrfToken = "{{ csrf_token() }}";
    const apiBaseUrl = "{{ url(env('API_KEY', 'api')) }}";
    const GLOBAL_FONTS = @json($globalFonts ?? []);
</script>
<script src="{{ asset('assets/js/font_awesome_library.js') }}?v={{ time() }}"></script>
<script src="{{ asset('assets/js/template_builder.js') }}?v={{ time() }}"></script>
<script>
function filterFrames() {
    var input = document.getElementById("frameSearchInput");
    var filter = input.value.toUpperCase();
    var tbody = document.getElementById("frameTableBody");
    var tr = tbody.getElementsByClassName("frame-row");
    for (var i = 0; i < tr.length; i++) {
        var tdId = tr[i].getElementsByTagName("td")[1];
        var tdName = tr[i].getElementsByTagName("td")[2];
        if (tdId || tdName) {
            var txtValueId = tdId.textContent || tdId.innerText;
            var txtValueName = tdName.textContent || tdName.innerText;
            if (txtValueId.toUpperCase().indexOf(filter) > -1 || txtValueName.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }
    }
}
</script>

<!-- Version Diff Review Modal -->
<div id="diffReviewModal" class="modal fade" tabindex="-1" style="display:none; z-index: 9999;">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="background:#1a1a2e; color:#e0e0e0; border:1px solid #333;">
            <div class="modal-header" style="border-bottom:1px solid #333;">
                <h5 class="modal-title" style="font-family:'Poppins',sans-serif;">
                    <i class="fas fa-code-compare"></i> Version Upgrade Review
                </h5>
                <button type="button" class="btn-close btn-close-white" onclick="closeDiffModal()"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info" style="background:#162447; border:1px solid #1f4068; color:#c7d5e0;">
                    <strong>Version Upgrade:</strong> <span id="diffVersionLabel">V1 → V4</span>
                    <br><small>Review the changes below before publishing.</small>
                </div>
                <table class="table table-sm" style="color:#e0e0e0;">
                    <thead>
                        <tr style="border-bottom:2px solid #444;">
                            <th>Layer</th>
                            <th>Property</th>
                            <th>Old Value</th>
                            <th>New Value</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody id="diffTableBody">
                        <!-- Populated by JS -->
                    </tbody>
                </table>
            </div>
            <div class="modal-footer" style="border-top:1px solid #333;">
                <button class="btn btn-outline-secondary" onclick="closeDiffModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button class="btn btn-success" onclick="approveAndPublish()">
                    <i class="fas fa-check-circle"></i> Approve & Publish
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function showDiffReviewModal(diffs, newSchema, newLegacy, fd, btnSave) {
    window._pendingSchema = newSchema;
    window._pendingLegacy = newLegacy;
    window._pendingFd = fd;
    window._pendingBtnSave = btnSave;

    const tbody = document.getElementById('diffTableBody');
    tbody.innerHTML = '';

    document.getElementById('diffVersionLabel').textContent =
        'V' + (window._originalRenderVersion) + ' → V' + CURRENT_RENDER_VERSION;

    diffs.forEach(d => {
        const tr = document.createElement('tr');
        let typeClass = '';
        let typeIcon = '';
        if (d.type === 'version_upgrade') { typeClass = 'color:#4fc3f7'; typeIcon = '🔄'; }
        else if (d.type === 'numeric_shift') { typeClass = 'color:#ffb74d'; typeIcon = '📐'; }
        else if (d.type === 'type_upgrade') { typeClass = 'color:#81c784'; typeIcon = '⬆️'; }
        else if (d.type === 'layer_added') { typeClass = 'color:#66bb6a'; typeIcon = '➕'; }
        else if (d.type === 'layer_removed') { typeClass = 'color:#ef5350'; typeIcon = '➖'; }
        else { typeClass = 'color:#90a4ae'; typeIcon = '✏️'; }

        tr.innerHTML = `
            <td style="font-weight:600;">${d.layerName}</td>
            <td><code style="background:#2a2a4a;padding:2px 6px;border-radius:3px;">${d.property}</code></td>
            <td style="color:#ef5350;">${formatDiffValue(d.oldValue)}</td>
            <td style="color:#66bb6a;">${formatDiffValue(d.newValue)}</td>
            <td style="${typeClass}">${typeIcon} ${d.type.replace('_', ' ')}</td>
        `;
        tbody.appendChild(tr);
    });

    $('#diffReviewModal').modal('show');
}

function formatDiffValue(val) {
    if (val === null || val === undefined) return '—';
    if (typeof val === 'number') return Math.round(val * 100) / 100;
    if (typeof val === 'object') return JSON.stringify(val).substring(0, 50);
    return String(val).substring(0, 50);
}

function closeDiffModal() {
    $('#diffReviewModal').modal('hide');
    if (window._pendingBtnSave) {
        window._pendingBtnSave.disabled = false;
        window._pendingBtnSave.innerHTML = '<i class="fa fa-save"></i> Publish';
    }
    window._pendingSchema = null;
    window._pendingLegacy = null;
    window._pendingFd = null;
    window._pendingBtnSave = null;
}

function approveAndPublish() {
    $('#diffReviewModal').modal('hide');
    if (window.doSaveFrame) {
        window.doSaveFrame(window._pendingSchema, window._pendingLegacy, window._pendingFd, window._pendingBtnSave);
    }
}
</script>
@endsection
