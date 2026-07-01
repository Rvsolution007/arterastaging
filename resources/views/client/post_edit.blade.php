@extends('layouts.client')

@section('title', 'Select Frame')

@section('styles')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Roboto:wght@400;700;900&family=Poppins:wght@400;700;900&family=Montserrat:wght@400;700;900&family=Bebas+Neue&family=Pacifico&family=Dancing+Script:wght@400;700&family=Playfair+Display:wght@400;700;900&family=Oswald:wght@400;700&family=Lato:wght@400;700;900&family=Open+Sans:wght@400;700;800&family=Raleway:wght@400;700;900&family=Abril+Fatface&family=Comfortaa:wght@400;700&family=Righteous&family=Varela+Round&family=Caveat:wght@400;700&family=Lobster&display=swap" rel="stylesheet">
    <style>
    /* CRITICAL: Disable parent scrolling to ensure fixed elements stay fixed */
    #main-content {
        overflow: hidden !important;
        padding-bottom: 0 !important;
        height: 100vh !important;
        height: 100dvh !important;
    }

    /* Hide the default navigation and FAB */
    nav,
    #fab-container,
    #fab-backdrop {
        display: none !important;
    }

    .editor-container {
        display: flex;
        flex-direction: column;
        height: 100vh;
        height: 100dvh;
        background-color: #f1f5f9;
        overflow: hidden;
        transition: padding-bottom 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .editor-container.panel-open .scroll-editor {
        display: none !important;
    }

    .editor-container.panel-open .canvas-section {
        flex: 1;
        align-items: center;
        padding-bottom: 380px;
        transition: all 0.4s ease;
    }

    /* Header */
    .app-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 16px;
        height: 56px;
        background: #ffffff;
        border-bottom: 1px solid #f1f5f9;
        flex-shrink: 0;
        z-index: 2500;
    }

    .back-link {
        color: #334155;
        padding: 8px;
        margin-left: -8px;
    }

    .header-title {
        flex: 1;
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
        margin-left: 8px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .next-button {
        display: flex;
        align-items: center;
        gap: 6px;
        background: #0f766e;
        color: white;
        border: none;
        padding: 8px 20px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: opacity 0.2s;
    }

    /* Canvas Section */
    .canvas-section {
        background-color: #f8fafc;
        padding: 24px 16px;
        flex-shrink: 0;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    #canvas-wrapper {
        position: relative;
        border-radius: 12px;
        box-shadow: 0 10px 40px -10px rgba(0, 0, 0, 0.2);
        border: 1px solid rgba(0, 0, 0, 0.05);
        overflow: visible;
        transform-origin: top left;
    }

    /* Scrolling Editor Logic */
    .scroll-editor {
        flex: 1;
        overflow-y: auto;
        background-color: #ffffff;
        border-top-left-radius: 24px;
        border-top-right-radius: 24px;
        min-height: 300px;
    }

    /* Quick Toggle Icons */
    .toggle-bar {
        display: flex;
        gap: 10px;
        padding: 16px;
        overflow-x: auto;
        border-bottom: 1px solid #f1f5f9;
        -webkit-overflow-scrolling: touch;
    }

    .toggle-bar::-webkit-scrollbar {
        display: none;
    }

    .toggle-btn {
        width: 42px;
        height: 42px;
        border-radius: 8px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background-color: #2e0ee6ff;
        color: white;
        font-size: 8px;
        font-weight: 800;
        flex-shrink: 0;
        z-index: 1500;
        transition: all 0.2s;
        border: none;
    }

    .toggle-btn.inactive {
        background-color: #f1f5f9;
        color: #94a3b8;
    }

    .toggle-btn i {
        width: 18px;
        height: 18px;
    }

    /* Frame Grid */
    .frames-grid {
        display: flex;
        gap: 12px;
        padding: 4px 16px 12px;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .frames-grid::-webkit-scrollbar {
        display: none;
    }

    .frame-item {
        width: 85px;
        height: 85px;
        min-width: 85px;
        border-radius: 12px;
        overflow: hidden;
        border: 2px solid #f1f5f9;
        background: #ffffff;
        cursor: pointer;
        flex-shrink: 0;
        transition: all 0.2s;
    }

    .frame-item.selected {
        border-color: #4f46e5;
        box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.1);
    }

    .frame-item img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        padding: 4px;
    }

    /* Bottom Toolbox */
    .toolbox {
        display: flex;
        justify-content: space-around;
        padding: 12px 4px;
        padding-bottom: calc(20px + env(safe-area-inset-bottom));
        border-top: 1px solid #f1f5f9;
        background: white;
        position: relative;
        z-index: 2500;
    }

    .tool-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 5px;
        flex: 1;
        cursor: pointer;
        min-width: 0;
    }

    .tool-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        background-color: #f1f5f9;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #1e293b;
        transition: all 0.2s;
    }

    .tool-item:active .tool-icon {
        transform: scale(0.95);
        background-color: #e2e8f0;
    }

    .tool-label {
        font-size: 9px;
        font-weight: 700;
        color: #334155;
        white-space: nowrap;
        text-align: center;
        width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Add Text Modal */
    #textModal {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 4000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .modal-content-box {
        background: #ffffff;
        width: 100%;
        max-width: 320px;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    }

    .modal-header {
        padding: 16px;
        text-align: center;
        font-weight: 700;
        font-size: 18px;
        color: #1e293b;
    }

    .modal-divider {
        height: 1px;
        background: #e2e8f0;
        margin: 0 16px;
    }

    .modal-body {
        padding: 20px 16px;
    }

    #modalTextArea {
        width: 100%;
        height: 100px;
        border: none;
        outline: none;
        font-size: 20px;
        color: #334155;
        resize: none;
        background: transparent;
    }

    .modal-footer {
        display: flex;
        gap: 12px;
        padding: 16px;
    }

    .modal-btn {
        flex: 1;
        padding: 12px;
        border-radius: 8px;
        font-weight: 700;
        border: none;
        cursor: pointer;
        text-transform: uppercase;
    }

    .btn-cancel {
        background: #10b981;
        color: white;
    }

    .btn-add {
        background: #10b981;
        color: white;
    }

    /* Sticker Modal */
    #stickerModal {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 3000;
        display: none;
        align-items: flex-end;
        justify-content: center;
    }

    .sticker-modal-content {
        background: #ffffff;
        width: 100%;
        max-width: 448px;
        border-top-left-radius: 20px;
        border-top-right-radius: 20px;
        max-height: 80vh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        transform: translateX(0);
        animation: slideUpCenterStack 0.3s ease-out;
    }

    @keyframes slideUpCenterStack {
        from { transform: translateY(100%); }
        to { transform: translateY(0); }
    }

    .sticker-header {
        padding: 16px;
        border-bottom: 1px solid #f1f5f9;
    }

    .sticker-cat-bar {
        display: flex;
        gap: 12px;
        overflow-x: auto;
        padding-bottom: 4px;
        -webkit-overflow-scrolling: touch;
    }

    .sticker-cat-bar::-webkit-scrollbar {
        display: none;
    }

    .sticker-cat-btn {
        padding: 8px 16px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        color: #475569;
        white-space: nowrap;
        cursor: pointer;
    }

    .sticker-cat-btn.active {
        background: #2e0ee6;
        color: white;
        border-color: #2e0ee6;
    }

    .sticker-body {
        flex: 1;
        overflow-y: auto;
        padding: 16px;
        -webkit-overflow-scrolling: touch;
    }

    .sticker-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
    }

    .sticker-item {
        aspect-ratio: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: transform 0.2s;
    }

    .sticker-item:active {
        transform: scale(0.9);
    }

    .sticker-item img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    /* Layers Panel */
    #layersModal {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 4000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .layers-box {
        background: white;
        width: 100%;
        max-width: 448px;
        border-radius: 20px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        max-height: 70vh;
    }

    .layer-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        border-bottom: 1px solid #f1f5f9;
        cursor: pointer;
    }

    .layer-item:active {
        background: #f8fafc;
    }

    .layer-item i {
        color: #64748b;
    }

    .layer-text {
        flex: 1;
        font-size: 14px;
        font-weight: 600;
        color: #1e293b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .hidden {
        display: none;
    }

    /* Canva Premium Property Bar */
    #contextualBar {
        position: fixed;
        bottom: 0;
        left: 50% !important;
        transform: translateX(-50%);
        width: 100%;
        max-width: 448px;
        background: #ffffff;
        z-index: 5000;
        display: none;
        padding-bottom: env(safe-area-inset-bottom);
        flex-direction: column;
        border-top-left-radius: 24px;
        border-top-right-radius: 24px;
        box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.15);
        animation: slideUpCenter 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes slideUpCenter {
        from { transform: translateX(-50%) translateY(100%); }
        to { transform: translateX(-50%) translateY(0); }
    }

    .bar-scroll {
        display: flex;
        gap: 12px;
        padding: 12px 16px;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .bar-scroll::-webkit-scrollbar {
        display: none;
    }

    .tool-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
        min-width: 56px;
        cursor: pointer;
    }

    .tool-btn-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #475569;
        transition: all 0.2s;
    }

    .tool-btn:active .tool-btn-icon {
        transform: scale(0.9);
        background: #e2e8f0;
    }

    .tool-btn-label {
        font-size: 10px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .tool-sub-panel {
        display: none;
        padding: 16px 20px;
        border-bottom: 1px solid #f1f5f9;
        background: #f8fafc;
    }

    /* ========== DRAG & SELECT SYSTEM (Mobile-First) ========== */
    /* CRITICAL: Prevents WebView from hijacking touch events for scroll/zoom */
    #capture-area .draggable {
        touch-action: none;
        -webkit-touch-callout: none;
        -webkit-user-select: none;
        user-select: none;
        cursor: grab;
    }

    #capture-area .draggable.dragging {
        cursor: grabbing;
        opacity: 0.85;
        z-index: 9999 !important;
    }

    /* Selection handles — hidden by default, shown when element has .selected */
    #capture-area .draggable .selection-overlay {
        display: none;
        pointer-events: none;
    }
    #capture-area .draggable.selected .selection-overlay {
        display: block;
        pointer-events: auto;
    }
    #capture-area .draggable.selected {
        outline: 2px solid #4f46e5;
        outline-offset: 2px;
    }
    #capture-area .draggable.dragging {
        outline-color: #818cf8;
    }

    /* Smart alignment guides */
    #v-guide, #h-guide {
        position: absolute;
        background: #4f46e5;
        z-index: 9998;
        display: none;
        pointer-events: none;
    }
    #v-guide {
        width: 1px;
        top: 0;
        bottom: 0;
        left: 50%;
    }
    #h-guide {
        height: 1px;
        left: 0;
        right: 0;
        top: 50%;
    }
    </style>
    <style>
        #frameColorPanel {
            background: #ffffff;
            padding: 16px;
            display: none;
            margin-top: 10px;
            border-bottom: 1px solid #f1f5f9;
        }

        .panel-header-simple {
            text-align: left;
            margin-bottom: 16px;
        }

        .panel-title-simple {
            font-size: 14px;
            font-weight: 800;
            color: #1e293b;
        }

        /* Bubbles Row */
        .layer-bubbles {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            overflow-x: auto;
            padding-bottom: 4px;
        }

        .layer-bubbles::-webkit-scrollbar {
            display: none;
        }

        .layer-bubble {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            border: 2px solid #f1f5f9;
            cursor: pointer;
            flex-shrink: 0;
            overflow: hidden;
            position: relative;
            background: #f8fafc;
            transition: all 0.2s;
        }

        .layer-bubble.active {
            border-color: #4f46e5;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.15);
        }

        /* Palette Grid */
        .palette-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 24px;
        }

        .color-swatch {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: transform 0.1s;
        }

        .color-swatch:active {
            transform: scale(0.9);
        }

        .custom-picker-btn {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Action Buttons */
        .panel-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .btn-action {
            height: 44px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .btn-action:active {
            opacity: 0.8;
        }

        .btn-apply {
            background: #4f46e5;
            color: white;
            border: none;
        }

        /* Toggle Button Styling */
        .toggle-bar {
            padding: 10px 16px 8px;
            display: flex;
            gap: 14px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .toggle-bar::-webkit-scrollbar {
            display: none;
        }

        .toggle-btn {
            background: #4f46e5;
            border: none;
            border-radius: 10px;
            min-width: 40px;
            height: 40px;
            padding: 0 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 9px;
            font-weight: 900;
            text-transform: uppercase;
            cursor: pointer;
            flex-shrink: 0;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2);
            transition: all 0.2s;
        }

        .toggle-btn i {
            width: 18px;
            height: 18px;
        }

        .toggle-btn.inactive {
            background: #f1f5f9;
            color: #94a3b8;
            box-shadow: none;
        }

        .icon-btn-small {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #64748b;
        }

        .icon-btn-small:hover {
            background: #f8fafc;
            color: #475569;
        }

        .icon-btn-small:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Filter Menu Styles */
        .filter-container {
            position: relative;
            margin-right: 12px;
        }

        .filter-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0;
            background: #2e0ee6ff;
            color: #ffffff;
            border: none;
            padding: 10px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .filter-btn:hover {
            opacity: 0.9;
        }

        .filter-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 8px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            width: 200px;
            z-index: 200;
            display: none;
            overflow: hidden;
            animation: fadeIn 0.15s ease-out;
        }

        .filter-dropdown.active {
            display: block;
        }

        .filter-option {
            display: flex;
            align-items: center;
            padding: 10px 16px;
            font-size: 14px;
            color: #475569;
            cursor: pointer;
            transition: background 0.1s;
        }

        .filter-option:hover {
            background: #f1f5f9;
            color: #0f172a;
        }

        .filter-option.selected {
            background: #f0fdfa;
            color: #0d9488;
            font-weight: 600;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Font Panel Drawer */
        #fontPanel {
            position: fixed;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 448px;
            background: #ffffff;
            border-top-left-radius: 24px;
            border-top-right-radius: 24px;
            z-index: 5000;
            display: none;
            flex-direction: column;
            max-height: 60vh;
            overflow-y: auto;
            padding: 20px;
            box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.15);
            animation: slideUpCenterStack 0.3s ease-out;
            -webkit-overflow-scrolling: touch;
        }

        .font-panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            position: sticky;
            top: 0;
            background: white;
            padding-bottom: 10px;
            z-index: 10;
        }

        .font-panel-title {
            font-size: 18px;
            font-weight: 800;
            color: #1e293b;
        }

        .font-close {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #64748b;
        }

        .font-option {
            padding: 14px 16px;
            font-size: 17px;
            color: #334155;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 4px;
            background: transparent;
        }

        .font-option:hover {
            background: #f1f5f9;
            color: #4f46e5;
        }

        .font-option:active {
            transform: scale(0.98);
            background: #e2e8f0;
        }
    </style>
    <!-- Fabric.js v5 — Canvas editor library -->
    <script src="{{ asset('assets/js/fabric.min.js') }}"></script>
@endsection

@section('content')
    <div class="editor-container">
        <header class="app-header">
            <a href="{{ route('universal.details', ['type' => $type, 'id' => $id]) }}" class="back-link">
                <i data-lucide="chevron-left"></i>
            </a>
            <h1 class="header-title">Post Editor</h1>

            <div style="display: flex; align-items: center;">
                <!-- Filter Dropdown -->
                <div class="filter-container">
                    <button class="filter-btn" onclick="toggleFilterMenu()" title="Filter Frames">
                        <i data-lucide="layout-template" style="width: 20px; height: 20px;"></i>
                    </button>
                    <div class="filter-dropdown" id="filterMenu">
                        <div class="filter-option selected" onclick="filterFrames('all', 'All Categories', this)">All
                            Categories</div>
                        @if(isset($poster_categories))
                            @foreach($poster_categories as $cat)
                                <div class="filter-option" onclick="filterFrames('{{ $cat->id }}', '{{ $cat->name }}', this)">
                                    {{ $cat->name }}
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>

                <button class="next-button" onclick="exportImage()"
                    style="background-color: #2e0ee6ff; color: white; padding: 8px 20px; border-radius: 6px; font-weight: 600; font-size: 14px;">
                    Download
                </button>
            </div>
        </header>

        <!-- Fixed Preview Area — Fabric.js Canvas -->
        <div class="canvas-section">
            <div id="canvas-wrapper">
                <canvas id="fabric-canvas"></canvas>
            </div>
        </div>

                <!-- Hidden storage for the currently active frame's skin path -->
                <input type="hidden" id="activeFrameImg-source" value="{{ $frames->first()->full_url ?? '' }}">
                <input type="hidden" id="initial-frame-config" value='@json($frames->first()->config ?? null)'>

                <!-- Scrollable Tools Area -->
                <div class="scroll-editor scrollbar-hide">
                    <!-- Toggles Section -->
                    <div class="toggle-bar">
                        <button class="toggle-btn" id="frame-toggle-btn" onclick="toggleFrameOverlays()">TEMPLATE</button>
                        <div style="position: relative;">
                            <button class="toggle-btn" onclick="openFrameColorPanel()" style="font-size: 7px; line-height: 1;">
                                FRAME<br>COLOR
                            </button>
                        </div>
                    </div>

                    <!-- Frame Color Panel (Inline) -->
                    <div id="frameColorPanel">
                        <div class="panel-header-simple" style="display: flex; justify-content: space-between; align-items: center;">
                            <span class="panel-title-simple">Select Frame Color</span>
                            <div style="display: flex; gap: 8px;">
                                <button class="icon-btn-small" id="undoColorBtn" onclick="undoFrameColor()" disabled>
                                    <i data-lucide="undo-2" style="width: 16px; height: 16px;"></i>
                                </button>
                                <button class="icon-btn-small" id="redoColorBtn" onclick="redoFrameColor()" disabled>
                                    <i data-lucide="redo-2" style="width: 16px; height: 16px;"></i>
                                </button>
                            </div>
                        </div>

                        <div class="layer-bubbles" id="layerBubbles">
                            <!-- Injected via JS -->
                        </div>

                         <div class="palette-grid">
                            <!-- Custom Picker -->
                            <div class="color-swatch custom-picker-btn" onclick="document.getElementById('customFrameColorInput').click()" style="background: linear-gradient(45deg, #f06, #f90, #ff0, #0f0, #0ff, #00f, #90f, #f06); border: none;">
                                <i data-lucide="plus" style="width: 20px; height: 20px; color: white;"></i>
                            </div>
                            <input type="color" id="customFrameColorInput" class="hidden" oninput="applyPaletteColor(this.value)">

                            <!-- Presets -->
                            <div class="color-swatch" style="background: #ffffff;" onclick="applyPaletteColor('#ffffff')"></div>
                            <div class="color-swatch" style="background: #1e293b;" onclick="applyPaletteColor('#1e293b')"></div>

                            <div class="color-swatch" style="background: #f97316;" onclick="applyPaletteColor('#f97316')"></div>
                            <div class="color-swatch" style="background: #eab308;" onclick="applyPaletteColor('#eab308')"></div>
                            <div class="color-swatch" style="background: #84cc16;" onclick="applyPaletteColor('#84cc16')"></div>
                            <div class="color-swatch" style="background: #22c55e;" onclick="applyPaletteColor('#22c55e')"></div>
                            <div class="color-swatch" style="background: #0ea5e9;" onclick="applyPaletteColor('#0ea5e9')"></div>
                            <div class="color-swatch" style="background: #3b82f6;" onclick="applyPaletteColor('#3b82f6')"></div>
                            <div class="color-swatch" style="background: #6366f1;" onclick="applyPaletteColor('#6366f1')"></div>
                            <div class="color-swatch" style="background: #d946ef;" onclick="applyPaletteColor('#d946ef')"></div>
                            <div class="color-swatch" style="background: #f43f5e;" onclick="applyPaletteColor('#f43f5e')"></div>
                        </div>

                        <div class="panel-actions">
                            <button class="btn-action btn-apply" onclick="confirmFrameColorPanel()">Apply</button>
                            <button class="btn-action btn-cancel" onclick="cancelFrameColorPanel()">Cancel</button>
                        </div>
                    </div>

                    <!-- Font Panel as a Drawer -->
                    <div id="fontPanel">
                        <div class="font-panel-header">
                            <span class="font-panel-title">Fonts</span>
                            <div class="font-close" onclick="toggleFontList()"><i data-lucide="x"></i></div>
                        </div>
                        <div class="font-option" style="font-family: 'Inter', sans-serif;" onclick="setFont('Inter')">Inter (Default)</div>
                        <div class="font-option" style="font-family: 'Roboto', sans-serif;" onclick="setFont('Roboto')">Roboto</div>
                        <div class="font-option" style="font-family: 'Poppins', sans-serif;" onclick="setFont('Poppins')">Poppins</div>
                        <div class="font-option" style="font-family: 'Montserrat', sans-serif;" onclick="setFont('Montserrat')">Montserrat</div>
                        <div class="font-option" style="font-family: 'Bebas Neue', sans-serif;" onclick="setFont('Bebas Neue')">Bebas Neue</div>
                        <div class="font-option" style="font-family: 'Pacifico', cursive;" onclick="setFont('Pacifico')">Pacifico</div>
                        <div class="font-option" style="font-family: 'Dancing Script', cursive;" onclick="setFont('Dancing Script')">Dancing Script</div>
                        <div class="font-option" style="font-family: 'Playfair Display', serif;" onclick="setFont('Playfair Display')">Playfair Display</div>
                        <div class="font-option" style="font-family: 'Oswald', sans-serif;" onclick="setFont('Oswald')">Oswald</div>
                        <div class="font-option" style="font-family: 'Lato', sans-serif;" onclick="setFont('Lato')">Lato</div>
                        <div class="font-option" style="font-family: 'Open Sans', sans-serif;" onclick="setFont('Open Sans')">Open Sans</div>
                        <div class="font-option" style="font-family: 'Raleway', sans-serif;" onclick="setFont('Raleway')">Raleway</div>
                        <div class="font-option" style="font-family: 'Abril Fatface', serif;" onclick="setFont('Abril Fatface')">Abril Fatface</div>
                        <div class="font-option" style="font-family: 'Comfortaa', cursive;" onclick="setFont('Comfortaa')">Comfortaa</div>
                        <div class="font-option" style="font-family: 'Righteous', cursive;" onclick="setFont('Righteous')">Righteous</div>
                        <div class="font-option" style="font-family: 'Varela Round', sans-serif;" onclick="setFont('Varela Round')">Varela Round</div>
                        <div class="font-option" style="font-family: 'Caveat', cursive;" onclick="setFont('Caveat')">Caveat</div>
                        <div class="font-option" style="font-family: 'Lobster', cursive;" onclick="setFont('Lobster')">Lobster</div>
                    </div>

                    <!-- Frame Selection Grid -->

                    <div class="frames-grid">
                        @php 
                            $basePosterUrl = request()->query('design') ?? asset('uploads/' . ($item_frames->first()->frame_image ?? $item->display_image)); 
                        @endphp
                        @forelse($frames as $index => $frame)
                            @php 
                                $logicUrl = $frame->full_url;
                                $thumbUrl = $frame->thumbnail_url ?? $frame->full_url;
                                $config = json_encode($frame->config ?? null); 
                            @endphp
                            <div class="frame-item {{ $index === 0 ? 'selected' : '' }}"
                                style="position: relative; background: #f1f5f9; cursor: pointer;"
                                data-config='@json($frame->config)'
                                data-category-id="{{ $frame->category_id ?? 'all' }}"
                                onclick="changeFrame('{{ $logicUrl }}', this)">
                                <!-- Background Poster in Thumbnail -->
                                <img src="{{ $basePosterUrl }}"
                                    style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: 1;">
                                <!-- Frame Overlay in Thumbnail -->
                                <img src="{{ $thumbUrl }}"
                                    style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: contain; object-position: bottom; z-index: 1;">
                            </div>
                        @empty
                            <div style="grid-column: span 3; padding: 20px; text-align: center; color: #94a3b8;">
                                <i data-lucide="image-off" style="width: 40px; height: 40px; margin: 0 auto 10px; opacity: 0.5;"></i>
                                <p style="font-size: 13px;">No overlays found in Admin.</p>
                                <p style="font-size: 11px; margin-top: 4px;">Upload ZIPs in Poster Maker or PNGs in Custom Frames.</p>
                            </div>
                        @endforelse
                    </div>



                    <!-- Toolbox Section -->
                    <div class="toolbox">
                        <div class="tool-item" onclick="document.getElementById('logoInput').click()">
                            <div class="tool-icon"><i data-lucide="image"></i></div>
                            <span class="tool-label">Add Logo</span>
                            <input type="file" id="logoInput" class="hidden" accept="image/*" onchange="uploadLogo(this)">
                        </div>
                        <div class="tool-item" onclick="addText()">
                            <div class="tool-icon"><i data-lucide="keyboard"></i></div>
                            <span class="tool-label">Add Text</span>
                        </div>
                        <div class="tool-item" onclick="addSticker()">
                            <div class="tool-icon"><i data-lucide="smile-plus"></i></div>
                            <span class="tool-label">Add Sticker</span>
                        </div>
                        <div class="tool-item" onclick="document.getElementById('colorInput').click()">
                            <div class="tool-icon"><i data-lucide="palette"></i></div>
                            <span class="tool-label">Color</span>
                            <input type="color" id="colorInput" class="hidden" oninput="applyUniversalColor(this.value)">
                        </div>
                        <div class="tool-item" onclick="toggleSizePanel()">
                            <div class="tool-icon"><i data-lucide="text-cursor-input"></i></div>
                            <span class="tool-label">Size</span>
                        </div>
                        <div class="tool-item" onclick="toggleLayersModal()">
                            <div class="tool-icon"><i data-lucide="layers"></i></div>
                            <span class="tool-label">Layers</span>
                        </div>
                    </div> <!-- toolbox -->
                </div> <!-- scroll-editor -->
            </div> <!-- editor-container -->

                <!-- Canva Contextual Bar -->
                <div id="contextualBar">
                     <!-- Sub-panel for Size -->
                     <div id="fontSizeControl" class="tool-sub-panel">
                         <div style="display:flex; justify-content:space-between; margin-bottom: 12px; align-items:center;">
                             <span style="font-size:14px; font-weight:800; color:#1e293b;">Font Size</span>
                             <div style="background:#7d2ae8; color:white; padding:4px 10px; border-radius:6px; font-size:12px; font-weight:800;">
                                <span id="fontSizeDisplay">24</span>px
                             </div>
                         </div>
                         <input type="range" id="fontSizeSlider" min="10" max="200" value="24"
                                style="width:100%; height:6px; appearance:none; background:#e2e8f0; border-radius:3px; outline:none;"
                                oninput="changeFontSize(this.value)">
                     </div>

                     <div id="imageAdjustControl" class="tool-sub-panel" style="display:none; width: 100%; max-width: 400px; padding: 15px;">
                         <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">
                             <label style="font-weight:700; color:#1a202c; font-size:15px;">Image Adjustment</label>
                             <button onclick="closeAllPanels()" style="border:none; background:none; cursor:pointer; color:#718096;"><i data-lucide="x" style="width:18px;"></i></button>
                         </div>
                         
                         <div style="margin-bottom:15px;">
                             <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                                 <label style="font-size:13px; font-weight:600; color:#4a5568;">Zoom</label>
                                 <span id="zoomVal" style="font-size:12px; color:#718096; font-family:monospace;">100%</span>
                             </div>
                             <input type="range" id="zoomSlider" min="100" max="300" value="100" style="width:100%;" oninput="updateImageAdjustment()">
                         </div>

                         <div style="margin-bottom:15px;">
                             <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                                 <label style="font-size:13px; font-weight:600; color:#4a5568;">Horizontal Position</label>
                                 <span id="posXVal" style="font-size:12px; color:#718096; font-family:monospace;">50%</span>
                             </div>
                             <input type="range" id="posXSlider" min="0" max="100" value="50" style="width:100%;" oninput="updateImageAdjustment()">
                         </div>

                         <div>
                             <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                                 <label style="font-size:13px; font-weight:600; color:#4a5568;">Vertical Position</label>
                                 <span id="posYVal" style="font-size:12px; color:#718096; font-family:monospace;">50%</span>
                             </div>
                             <input type="range" id="posYSlider" min="0" max="100" value="50" style="width:100%;" oninput="updateImageAdjustment()">
                         </div>
                     </div>


                     <div class="bar-scroll" style="justify-content: center;">
                          <div id="editTool" class="tool-btn" onclick="triggerEdit()" style="display:none;">
                              <div class="tool-btn-icon"><i data-lucide="edit-3"></i></div>
                              <span class="tool-btn-label">Edit</span>
                          </div>
                          <div id="fontTool" class="tool-btn" onclick="toggleFontList()" style="display:none;">
                              <div class="tool-btn-icon"><i data-lucide="type"></i></div>
                              <span class="tool-btn-label">Font</span>
                          </div>
                          <div id="sizeTool" class="tool-btn" onclick="toggleSizePanel()" style="display:none;">
                              <div class="tool-btn-icon"><i data-lucide="maximize"></i></div>
                              <span class="tool-btn-label">Size</span>
                          </div>
                          <div id="boldTool" class="tool-btn" onclick="toggleBold()" style="display:none;">
                              <div class="tool-btn-icon"><i data-lucide="bold"></i></div>
                              <span class="tool-btn-label">Bold</span>
                          </div>
                          <div id="italicTool" class="tool-btn" onclick="toggleItalic()" style="display:none;">
                              <div class="tool-btn-icon"><i data-lucide="italic"></i></div>
                              <span class="tool-btn-label">Italic</span>
                          </div>
                          <div id="attachTool" class="tool-btn" onclick="document.getElementById('attachInput').click()" style="display:none;">
                              <div class="tool-btn-icon"><i data-lucide="image-plus"></i></div>
                              <span class="tool-btn-label">Attach</span>
                              <input type="file" id="attachInput" class="hidden" accept="image/*" onchange="attachImage(this)">
                          </div>
                          <div id="detachTool" class="tool-btn" onclick="detachImage()" style="display:none;">
                              <div class="tool-btn-icon"><i data-lucide="image-minus"></i></div>
                              <span class="tool-btn-label">Detach</span>
                          </div>
                          <div id="adjustTool" class="tool-btn" onclick="toggleAdjustPanel()" style="display:none;">
                              <div class="tool-btn-icon"><i data-lucide="sliders"></i></div>
                              <span class="tool-btn-label">Adjust</span>
                          </div>
                          <div id="contextualColorTool" class="tool-btn" onclick="document.getElementById('colorInput').click()">
                              <div class="tool-btn-icon" style="border-bottom: 4px solid #7d2ae8;"><i data-lucide="palette"></i></div>
                              <span class="tool-btn-label">Color</span>
                          </div>
                          <div id="contextualLayersTool" class="tool-btn" onclick="toggleLayersModal()">
                              <div class="tool-btn-icon"><i data-lucide="layers"></i></div>
                              <span class="tool-btn-label">Layers</span>
                          </div>
                          <div id="deleteTool" class="tool-btn" onclick="removeActiveElement()">
                              <div class="tool-btn-icon" style="color:#ef4444;"><i data-lucide="trash-2"></i></div>
                              <span class="tool-btn-label" style="color:#ef4444;">Delete</span>
                          </div>
                     </div>
                </div>

                <!-- Layers Modal -->
                <div id="layersModal" onclick="this.style.display='none'">
                    <div class="layers-box" onclick="event.stopPropagation()">
                        <div class="modal-header">Select Layer</div>
                        <div class="modal-divider"></div>
                        <div id="layersContainer" style="overflow-y:auto; flex:1;">
                            <!-- Layers injected here -->
                        </div>
                        <div class="modal-footer">
                            <button class="modal-btn btn-cancel" onclick="document.getElementById('layersModal').style.display='none'">CLOSE</button>
                        </div>
                    </div>
                </div>

                <!-- Add Text Modal -->
                <div id="textModal">
                    <div class="modal-content-box">
                        <div class="modal-header">Add Text</div>
                        <div class="modal-divider"></div>
                        <div class="modal-body">
                            <textarea id="modalTextArea" placeholder="Add Text here"></textarea>
                        </div>
                        <div class="modal-footer">
                            <button class="modal-btn btn-cancel" onclick="closeTextModal()">CANCEL</button>
                            <button class="modal-btn btn-add" onclick="confirmAddText()">ADD</button>
                        </div>
                    </div>
                </div>

                <!-- Sticker Modal -->
                <div id="stickerModal" onclick="closeStickerModal(event)">
                    <div class="sticker-modal-content" onclick="event.stopPropagation()">
                        <div class="sticker-header">
                            <div class="sticker-cat-bar">
                                @foreach($sticker_categories as $index => $cat)
                                    <button class="sticker-cat-btn {{ $index === 0 ? 'active' : '' }}" 
                                            onclick="filterStickers('{{ $cat->id }}', this)">
                                        {{ $cat->name }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        <div class="sticker-body">
                            <div class="sticker-grid" id="stickerContainer">
                                <!-- Stickers will be injected here -->
                            </div>
                        </div>
                    </div>
                </div>
@endsection

@section('scripts')
    <script>
        // ═══════════════════════════════════════════════════════
        //  FABRIC.JS POST EDITOR
        //  All drag, resize, rotate, text editing, and export
        //  is powered by Fabric.js v5 — no custom handlers needed
        // ═══════════════════════════════════════════════════════

        // ── Design Data from Blade ──
        @php 
            $designUrl = request()->query('design') ?? asset('uploads/' . ($item_frames->first()->frame_image ?? $item->display_image));
        @endphp
        const DESIGN_URL = "{{ $designUrl }}";
        const BUSINESS = @json($business ?? null);

        // ── Canvas Setup ──
        let fCanvas = null;          // The Fabric.js canvas instance
        let activeObject = null;     // Currently selected Fabric object
        let isFrameHidden = false;
        let currentFrameConfig = null;

        // Track frame overlay objects separately so we can manage them
        let frameOverlayObjects = []; // Fabric Image objects from frame config
        let backgroundImage = null;   // The base poster background

        // Canvas resolution: use a fixed internal resolution for consistent export
        const CANVAS_W = 1080;
        const CANVAS_H = 1350; // Default 4:5 aspect

        let currentScale = 1; // Track the CSS scale factor for export

        function initCanvas() {
            const wrapper = document.getElementById('canvas-wrapper');
            
            fCanvas = new fabric.Canvas('fabric-canvas', {
                width: CANVAS_W,
                height: CANVAS_H,
                backgroundColor: '#ffffff',
                preserveObjectStacking: true,
                selection: true,
                stopContextMenu: true,
                fireRightClick: true,
                enableRetinaScaling: true,
            });

            // Scale canvas visually via CSS transform (NOT setZoom)
            scaleCanvasToFit();

            // Smart alignment guides
            initAlignmentGuides();

            // Events
            fCanvas.on('selection:created', (e) => onObjectSelected(e));
            fCanvas.on('selection:updated', (e) => onObjectSelected(e));
            fCanvas.on('selection:cleared', () => onSelectionCleared());
            fCanvas.on('object:modified', () => fCanvas.renderAll());

            // Load background poster
            loadBackgroundImage(DESIGN_URL);
        }

        function scaleCanvasToFit() {
            const wrapper = document.getElementById('canvas-wrapper');
            if (!wrapper || !fCanvas) return;
            const section = wrapper.parentElement;
            const maxW = section.offsetWidth - 32;
            const maxH = window.innerHeight * 0.55;
            const intW = fCanvas.internalW || CANVAS_W;
            const intH = fCanvas.internalH || CANVAS_H;
            const scale = Math.min(maxW / intW, maxH / intH, 1);
            currentScale = scale;

            // Use Fabric.js native zoom — renders crisp at any scale (no CSS blur)
            fCanvas.setZoom(scale);
            fCanvas.setWidth(intW * scale);
            fCanvas.setHeight(intH * scale);

            // Remove any leftover CSS transform
            wrapper.style.transform = 'none';
            wrapper.style.transformOrigin = '';
            wrapper.style.width = (intW * scale) + 'px';
            wrapper.style.height = (intH * scale) + 'px';

            // Set parent section height to match
            section.style.height = (intH * scale + 48) + 'px';
            section.style.overflow = 'hidden';
        }

        function loadBackgroundImage(url) {
            fabric.Image.fromURL(url, (img) => {
                if (!img) return;
                img.scaleToWidth(fCanvas.getWidth());
                img.scaleToHeight(fCanvas.getHeight());
                fCanvas.setBackgroundImage(img, fCanvas.renderAll.bind(fCanvas), {
                    originX: 'left',
                    originY: 'top',
                    scaleX: (fCanvas.getWidth()) / img.width,
                    scaleY: (fCanvas.getHeight()) / img.height,
                });
                backgroundImage = img;
            }, { crossOrigin: 'anonymous' });
        }

        // ── Smart Alignment Guides ──
        let alignGuidelines = [];
        const SNAP_THRESHOLD = 8;

        function initAlignmentGuides() {
            fCanvas.on('object:moving', function(e) {
                const obj = e.target;
                const canvasW = fCanvas.getWidth();
                const canvasH = fCanvas.getHeight();
                const centerX = canvasW / 2;
                const centerY = canvasH / 2;
                const objCenterX = obj.left + (obj.width * obj.scaleX) / 2;
                const objCenterY = obj.top + (obj.height * obj.scaleY) / 2;
                const objRight = obj.left + obj.width * obj.scaleX;
                const objBottom = obj.top + obj.height * obj.scaleY;

                clearGuideLines();

                // Center X snap
                if (Math.abs(objCenterX - centerX) < SNAP_THRESHOLD) {
                    obj.set('left', centerX - (obj.width * obj.scaleX) / 2);
                    drawGuideLine(centerX, 0, centerX, canvasH, '#d946ef');
                }
                // Center Y snap
                if (Math.abs(objCenterY - centerY) < SNAP_THRESHOLD) {
                    obj.set('top', centerY - (obj.height * obj.scaleY) / 2);
                    drawGuideLine(0, centerY, canvasW, centerY, '#d946ef');
                }
                // Left edge
                if (Math.abs(obj.left) < SNAP_THRESHOLD) {
                    obj.set('left', 0);
                }
                // Top edge
                if (Math.abs(obj.top) < SNAP_THRESHOLD) {
                    obj.set('top', 0);
                }
                // Right edge
                if (Math.abs(objRight - canvasW) < SNAP_THRESHOLD) {
                    obj.set('left', canvasW - obj.width * obj.scaleX);
                }
                // Bottom edge
                if (Math.abs(objBottom - canvasH) < SNAP_THRESHOLD) {
                    obj.set('top', canvasH - obj.height * obj.scaleY);
                }
            });

            fCanvas.on('object:moved', function() {
                clearGuideLines();
            });
        }

        function drawGuideLine(x1, y1, x2, y2, color) {
            const line = new fabric.Line([x1, y1, x2, y2], {
                stroke: color || '#d946ef',
                strokeWidth: 1.5,
                selectable: false,
                evented: false,
                strokeDashArray: [5, 5],
                _isGuideLine: true,
            });
            fCanvas.add(line);
            alignGuidelines.push(line);
        }

        function clearGuideLines() {
            alignGuidelines.forEach(line => fCanvas.remove(line));
            alignGuidelines = [];
        }

        // ── Selection Events ──
        function onObjectSelected(e) {
            const obj = e.selected ? e.selected[0] : (e.target || null);
            if (!obj) return;
            activeObject = obj;
            showContextualBar(obj);
        }

        function onSelectionCleared() {
            activeObject = null;
            hideContextualBar();
        }

        function showContextualBar(obj) {
            const bar = document.getElementById('contextualBar');
            if (bar) bar.style.display = 'flex';
            closeAllPanels();

            const isText = obj.type === 'textbox' || obj.type === 'i-text' || obj.type === 'text';
            const isFrame = obj._isFrameImage === true;
            const isPlaceholder = obj._isPlaceholder === true;

            // Tool visibility
            const editEl = document.getElementById('editTool');
            const fontEl = document.getElementById('fontTool');
            const sizeEl = document.getElementById('sizeTool');
            const boldEl = document.getElementById('boldTool');
            const italicEl = document.getElementById('italicTool');
            const attachEl = document.getElementById('attachTool');
            const detachEl = document.getElementById('detachTool');
            const adjustEl = document.getElementById('adjustTool');
            const colorTool = document.getElementById('contextualColorTool');
            const layersTool = document.getElementById('contextualLayersTool');
            const delTool = document.getElementById('deleteTool');

            if (editEl) editEl.style.display = isText ? 'flex' : 'none';
            if (fontEl) fontEl.style.display = isText ? 'flex' : 'none';
            if (sizeEl) sizeEl.style.display = isText ? 'flex' : 'none';
            if (boldEl) boldEl.style.display = isText ? 'flex' : 'none';
            if (italicEl) italicEl.style.display = isText ? 'flex' : 'none';
            
            if (attachEl) attachEl.style.display = isFrame ? 'flex' : 'none';
            if (detachEl) detachEl.style.display = (isFrame && !isPlaceholder) ? 'flex' : 'none';
            if (adjustEl) adjustEl.style.display = isFrame ? 'flex' : 'none';
            
            if (colorTool) colorTool.style.display = isFrame ? 'none' : 'flex';
            if (layersTool) layersTool.style.display = 'flex';
            if (delTool) delTool.style.display = 'flex';

            // Sync size slider
            if (isText) {
                const fs = obj.fontSize || 24;
                const fss = document.getElementById('fontSizeSlider');
                const fsd = document.getElementById('fontSizeDisplay');
                if (fss) fss.value = fs;
                if (fsd) fsd.innerText = Math.round(fs);
            }

            // Sync adjust sliders for frame images
            if (isFrame) {
                const zs = document.getElementById('zoomSlider');
                const pxs = document.getElementById('posXSlider');
                const pys = document.getElementById('posYSlider');
                if (zs) zs.value = obj._zoom || 100;
                if (pxs) pxs.value = obj._posX || 50;
                if (pys) pys.value = obj._posY || 50;
            }
        }

        function hideContextualBar() {
            const bar = document.getElementById('contextualBar');
            if (bar) bar.style.display = 'none';
            closeAllPanels();
        }

        // ── Text Functions ──
        let editingTextElement = null;

        function addText() {
            fCanvas.discardActiveObject();
            editingTextElement = null;
            const modal = document.getElementById('textModal');
            modal.querySelector('.modal-header').innerText = 'Add Text';
            modal.style.display = 'flex';
            const textArea = document.getElementById('modalTextArea');
            textArea.value = '';
            textArea.focus();
        }

        function closeTextModal() {
            document.getElementById('textModal').style.display = 'none';
        }

        function confirmAddText() {
            const txt = document.getElementById('modalTextArea').value;
            if (!txt) {
                closeTextModal();
                return;
            }

            if (editingTextElement) {
                editingTextElement.set('text', txt);
                fCanvas.renderAll();
                closeTextModal();
                return;
            }

            const canvasW = fCanvas.getWidth();
            const canvasH = fCanvas.getHeight();

            const textObj = new fabric.Textbox(txt, {
                left: canvasW / 2,
                top: canvasH / 2,
                originX: 'center',
                originY: 'center',
                fontSize: 48,
                fontWeight: '900',
                fontFamily: 'Inter',
                fill: '#000000',
                textAlign: 'center',
                width: canvasW * 0.6,
                editable: true,
                _objectType: 'text',
                _label: 'Custom Text',
            });

            fCanvas.add(textObj);
            fCanvas.setActiveObject(textObj);
            fCanvas.renderAll();
            closeTextModal();
        }

        function triggerEdit() {
            if (!activeObject) return;
            const isText = activeObject.type === 'textbox' || activeObject.type === 'i-text';
            if (!isText) return;
            // Enter Fabric's built-in editing mode
            activeObject.enterEditing();
            activeObject.selectAll();
            fCanvas.renderAll();
        }

        // ── Style Functions (Selection-Aware) ──
        // Helper: Check if there's an active text selection within a Textbox in editing mode
        function hasTextSelection(obj) {
            return obj && obj.isEditing && obj.selectionStart !== obj.selectionEnd;
        }

        function toggleBold() {
            if (!activeObject) return;
            if (activeObject.type === 'textbox' || activeObject.type === 'i-text') {
                if (hasTextSelection(activeObject)) {
                    const styles = activeObject.getSelectionStyles(activeObject.selectionStart, activeObject.selectionEnd);
                    const allBold = styles.every(s => s.fontWeight >= 700 || s.fontWeight === 'bold' || s.fontWeight === '900');
                    activeObject.setSelectionStyles({ fontWeight: allBold ? '400' : '900' });
                } else {
                    const isBold = activeObject.fontWeight === 'bold' || activeObject.fontWeight >= 700;
                    activeObject.set('fontWeight', isBold ? '400' : '900');
                }
                fCanvas.renderAll();
            }
        }

        function toggleItalic() {
            if (!activeObject) return;
            if (activeObject.type === 'textbox' || activeObject.type === 'i-text') {
                if (hasTextSelection(activeObject)) {
                    const styles = activeObject.getSelectionStyles(activeObject.selectionStart, activeObject.selectionEnd);
                    const allItalic = styles.every(s => s.fontStyle === 'italic');
                    activeObject.setSelectionStyles({ fontStyle: allItalic ? 'normal' : 'italic' });
                } else {
                    const isItalic = activeObject.fontStyle === 'italic';
                    activeObject.set('fontStyle', isItalic ? 'normal' : 'italic');
                }
                fCanvas.renderAll();
            }
        }

        function changeFontSize(val) {
            if (!activeObject) return;
            if (activeObject.type === 'textbox' || activeObject.type === 'i-text') {
                if (hasTextSelection(activeObject)) {
                    activeObject.setSelectionStyles({ fontSize: parseInt(val) });
                } else {
                    activeObject.set('fontSize', parseInt(val));
                }
                fCanvas.renderAll();
                document.getElementById('fontSizeDisplay').innerText = val;
            }
        }

        function setFont(font) {
            if (!activeObject) return;
            if (activeObject.type === 'textbox' || activeObject.type === 'i-text') {
                if (hasTextSelection(activeObject)) {
                    activeObject.setSelectionStyles({ fontFamily: font });
                } else {
                    activeObject.set('fontFamily', font);
                }
                fCanvas.renderAll();
                toggleFontList();
            }
        }

        function applyUniversalColor(val) {
            if (!activeObject) return;
            const isText = activeObject.type === 'textbox' || activeObject.type === 'i-text' || activeObject.type === 'text';
            if (isText) {
                if (hasTextSelection(activeObject)) {
                    activeObject.setSelectionStyles({ fill: val });
                } else {
                    activeObject.set('fill', val);
                }
            } else if (activeObject.type === 'image') {
                // Apply color filter to images
                activeObject.filters = [new fabric.Image.filters.BlendColor({
                    color: val,
                    mode: 'tint',
                    alpha: 1
                })];
                activeObject.applyFilters();
            }
            fCanvas.renderAll();
        }

        function toggleSizePanel() {
            const panel = document.getElementById('fontSizeControl');
            panel.style.display = (panel.style.display === 'block') ? 'none' : 'block';
        }

        // ── Sticker Logic ──
        const allStickers = @json($stickers);

        function addSticker() {
            fCanvas.discardActiveObject();
            document.getElementById('stickerModal').style.display = 'flex';
            const firstCat = document.querySelector('.sticker-cat-btn');
            if (firstCat) firstCat.click();
        }

        function closeStickerModal(e) {
            document.getElementById('stickerModal').style.display = 'none';
        }

        function filterStickers(catId, btn) {
            document.querySelectorAll('.sticker-cat-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const filtered = allStickers.filter(s => s.sticker_category_id == catId);
            const container = document.getElementById('stickerContainer');
            container.innerHTML = '';
            filtered.forEach(s => {
                const div = document.createElement('div');
                div.className = 'sticker-item';
                div.innerHTML = `<img src="{{ asset('uploads') }}/${s.image}" alt="sticker">`;
                div.onclick = () => selectSticker(`{{ asset('uploads') }}/${s.image}`);
                container.appendChild(div);
            });
        }

        function selectSticker(src) {
            const canvasW = fCanvas.getWidth();
            const canvasH = fCanvas.getHeight();
            
            fabric.Image.fromURL(src, (img) => {
                if (!img) return;
                const maxSize = canvasW * 0.25;
                const scale = maxSize / Math.max(img.width, img.height);
                img.set({
                    left: canvasW / 2,
                    top: canvasH / 2,
                    originX: 'center',
                    originY: 'center',
                    scaleX: scale,
                    scaleY: scale,
                    _objectType: 'sticker',
                    _label: 'Sticker',
                });
                fCanvas.add(img);
                fCanvas.setActiveObject(img);
                fCanvas.renderAll();
                closeStickerModal();
            }, { crossOrigin: 'anonymous' });
        }

        // ── Logo Upload ──
        function uploadLogo(input) {
            fCanvas.discardActiveObject();
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = e => {
                    const canvasW = fCanvas.getWidth();
                    const canvasH = fCanvas.getHeight();
                    
                    fabric.Image.fromURL(e.target.result, (img) => {
                        if (!img) return;
                        const maxSize = canvasW * 0.25;
                        const scale = maxSize / Math.max(img.width, img.height);
                        img.set({
                            left: canvasW / 2,
                            top: canvasH / 2,
                            originX: 'center',
                            originY: 'center',
                            scaleX: scale,
                            scaleY: scale,
                            _objectType: 'logo',
                            _label: 'Logo',
                        });
                        fCanvas.add(img);
                        fCanvas.setActiveObject(img);
                        fCanvas.renderAll();
                    });
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // ── Delete ──
        function removeActiveElement() {
            if (!activeObject) return;
            fCanvas.remove(activeObject);
            fCanvas.discardActiveObject();
            fCanvas.renderAll();
            activeObject = null;
        }

        // ── Layers ──
        function moveElement(dir) {
            if (!activeObject) return;
            if (dir === 'forward') {
                fCanvas.bringForward(activeObject);
            } else {
                fCanvas.sendBackwards(activeObject);
            }
            fCanvas.renderAll();
        }

        // ── Panels ──
        function toggleFontList() {
            const panel = document.getElementById('fontPanel');
            panel.style.display = panel.style.display === 'flex' ? 'none' : 'flex';
        }

        function closeAllPanels() {
            const panels = ['frameColorPanel', 'fontPanel', 'fontSizeControl', 'imageAdjustControl'];
            panels.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.style.display = 'none';
            });
            const framesGrid = document.querySelector('.frames-grid');
            if (framesGrid && framesGrid.style.display === 'none') {
                framesGrid.style.display = '';
            }
            document.querySelector('.editor-container').classList.remove('panel-open');
        }

        // ── Filter Logic ──
        function toggleFilterMenu() {
            fCanvas.discardActiveObject();
            fCanvas.renderAll();
            const menu = document.getElementById('filterMenu');
            menu.classList.toggle('active');
        }

        function filterFrames(catId, label, element) {
            document.querySelectorAll('.filter-option').forEach(el => el.classList.remove('selected'));
            element.classList.add('selected');
            document.getElementById('filterMenu').classList.remove('active');
            const items = document.querySelectorAll('.frame-item');
            items.forEach(item => {
                const itemCat = item.getAttribute('data-category-id');
                if (catId === 'all' || itemCat == catId || itemCat === 'all') {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        document.addEventListener('click', function(event) {
            const container = document.querySelector('.filter-container');
            if (container && !container.contains(event.target)) {
                 document.getElementById('filterMenu').classList.remove('active');
            }
        });

        // ── Frame Toggle ──
        function toggleFrameOverlays() {
            fCanvas.discardActiveObject();
            isFrameHidden = !isFrameHidden;
            const btn = document.getElementById('frame-toggle-btn');

            frameOverlayObjects.forEach(obj => {
                obj.set('visible', !isFrameHidden);
            });

            // Also toggle text elements from frame config
            fCanvas.getObjects().forEach(obj => {
                if (obj._isFrameLayer) {
                    obj.set('visible', !isFrameHidden);
                }
            });

            if (isFrameHidden) {
                if (btn) btn.classList.add('inactive');
            } else {
                if (btn) btn.classList.remove('inactive');
            }
            fCanvas.renderAll();
        }

        // ── Frame Change ──
        function changeFrame(url, element) {
            fCanvas.discardActiveObject();
            isFrameHidden = false;
            const btn = document.getElementById('frame-toggle-btn');
            if (btn) btn.classList.remove('inactive');

            const sourceInput = document.getElementById('activeFrameImg-source');
            if (sourceInput) sourceInput.value = url;

            if (element) {
                document.querySelectorAll('.frame-item').forEach(i => i.classList.remove('selected'));
                element.classList.add('selected');

                const configAttr = element.getAttribute('data-config');
                if (configAttr && configAttr !== 'null' && configAttr !== 'undefined' && configAttr !== '') {
                    try {
                        currentFrameConfig = JSON.parse(configAttr);
                        applyFrameConfig(currentFrameConfig);
                    } catch (e) {
                        console.error("Config parse error:", e);
                        currentFrameConfig = null;
                        applyFrameConfig(null);
                    }
                } else {
                    currentFrameConfig = null;
                    applyFrameConfig(null);
                }
            }
        }

        // ── Apply Frame Config (reimplemented for Fabric.js) ──
        async function applyFrameConfig(config) {
            // 1. Remove previous frame overlay objects
            frameOverlayObjects.forEach(obj => fCanvas.remove(obj));
            frameOverlayObjects = [];
            // Remove frame text layers too
            fCanvas.getObjects().filter(o => o._isFrameLayer).forEach(o => fCanvas.remove(o));

            if (!config || !config.layers) {
                // No config: show raw background at 4:5
                resizeCanvas(CANVAS_W, CANVAS_H);
                loadBackgroundImage(DESIGN_URL);
                // Make bg visible again
                if (backgroundImage) {
                    fCanvas.setBackgroundImage(backgroundImage, fCanvas.renderAll.bind(fCanvas));
                }
                fCanvas.renderAll();
                return;
            }

            try {
                // --- FLATTEN LAYERS ---
                // V4 Extractors generate nested groups (type: "group", children: []).
                function flattenLayersList(layers) {
                    let flat = [];
                    if (!layers || !Array.isArray(layers)) return flat;
                    layers.forEach(l => {
                        if (l.type === 'group' && Array.isArray(l.children)) {
                            flat = flat.concat(flattenLayersList(l.children));
                        } else {
                            flat.push(l);
                        }
                    });
                    return flat;
                }
                config.layers = flattenLayersList(config.layers);

                // 2. Determine design resolution
                let designW = (config.info && config.info.width) ? config.info.width : 0;
                let designH = (config.info && config.info.height) ? config.info.height : 0;

                if (designW === 0 || designH === 0) {
                    config.layers.forEach(l => {
                        const lw = l.width || l.w || 0;
                        const lh = l.height || l.h || 0;
                        const layerRight = (l.x || 0) + lw;
                        const layerBottom = (l.y || 0) + lh;
                        if (layerRight > designW) designW = layerRight;
                        if (layerBottom > designH) designH = layerBottom;
                    });
                }

                if (Math.abs(designW - designH) < 10) {
                    designW = designH = 1024;
                } else if (designW <= 0 || designH <= 0) {
                    designW = 1024; designH = 1024;
                }

                // 3. Resize canvas to match design ratio
                resizeCanvas(designW, designH);

                // Hide base poster (ZIP layers represent the full design)
                fCanvas.setBackgroundImage(null, fCanvas.renderAll.bind(fCanvas));
                fCanvas.backgroundColor = '#ffffff';

                // 4. Resolve asset paths
                const skinBase = document.getElementById('activeFrameImg-source').value;
                const skinDir = skinBase.substring(0, skinBase.lastIndexOf('/') + 1);
                const templateDir = skinDir.split('/skins/')[0] + '/';
                const fontsBase = templateDir + 'fonts/';

                // 5. Load Fonts
                const fontMap = {};
                const fontPromises = [];
                config.layers.forEach(l => {
                    if (l.font && !fontMap[l.font]) {
                        const internalName = `ZIPFONT_${l.font.replace(/[^a-zA-Z0-9]/g, '_')}_${new Date().getTime()}`;
                        fontMap[l.font] = internalName;
                        fontPromises.push(
                            loadFont(l.font, internalName, fontsBase).then(success => {
                                if (!success) fontMap[l.font] = l.font;
                            })
                        );
                    }
                });
                await Promise.all(fontPromises);
                await new Promise(r => setTimeout(r, 50));

                // 6. Sort and Render Layers
                const sortedLayers = [...config.layers].sort((a, b) => (a.z_index || 0) - (b.z_index || 0));
                const canvasW = designW;
                const canvasH = designH;

                for (let idx = 0; idx < sortedLayers.length; idx++) {
                    const layer = sortedLayers[idx];
                    const lname = (layer.name || '').toLowerCase();
                    const lw = layer.width || layer.w || 0;
                    const lh = layer.height || layer.h || 0;
                    const lx = layer.x || 0;
                    const ly = layer.y || 0;

                    if (lw === 0 || lh === 0) continue;

                    if (layer.type === 'image' || layer.type === 'shape') {
                        let src = layer.src;
                        let isAIMapped = false;

                        // Business logo substitution
                        @if($business ?? false)
                            if (lname.includes('logo') && "{{ $business->logo ?? '' }}" !== '') {
                                src = "{{ asset('uploads/' . ($business->logo ?? '')) }}";
                                isAIMapped = true; // Prevents the path resolver from messing with this absolute URL
                            }
                        @endif

                        if (!isAIMapped) {
                            if (src.includes('../skins/')) {
                                let parts = src.split('/');
                                src = templateDir + 'skins/' + parts[parts.length-2] + '/' + parts[parts.length-1];
                            } else {
                                src = skinDir + src.split('/').pop();
                            }
                        }
                        const cacheBuster = '?v=' + new Date().getTime();

                        await new Promise((resolve) => {
                            fabric.Image.fromURL(src + cacheBuster, (img) => {
                                if (!img) { resolve(); return; }

                                const isFrameSlot = lname.startsWith('image');
                                
                                let sX, sY, objLeft, objTop;
                                
                                if (isFrameSlot) {
                                    // Frame image slots: use 'cover' scaling to FILL the slot completely
                                    // This ensures the image takes up the entire predefined vertical and horizontal space.
                                    const coverScale = Math.max(lw / img.width, lh / img.height);
                                    sX = coverScale;
                                    sY = coverScale;
                                    // Center the overflow
                                    objLeft = lx - ((img.width * sX) - lw) / 2;
                                    objTop = ly - ((img.height * sY) - lh) / 2;
                                } else {
                                    // Decorative shapes/icons: use 'contain' to preserve native aspect ratio
                                    const containScale = Math.min(lw / img.width, lh / img.height);
                                    sX = containScale;
                                    sY = containScale;
                                    objLeft = lx + (lw - (img.width * sX)) / 2;
                                    objTop = ly + (lh - (img.height * sY)) / 2;
                                }

                                img.set({
                                    left: objLeft,
                                    top: objTop,
                                    scaleX: sX,
                                    scaleY: sY,
                                    selectable: true,
                                    evented: true,
                                    _isFrameLayer: true,
                                    _isFrameImage: isFrameSlot,
                                    _isPlaceholder: false,
                                    _label: layer.name || 'Component',
                                    _originalSrc: src + cacheBuster,
                                });

                                // ClipPath to keep rounded corners and hide overflow
                                if (isFrameSlot) {
                                    const rad = layer.radius || 20;
                                    img.set({
                                        clipPath: new fabric.Rect({
                                            width: lw / sX,
                                            height: lh / sY,
                                            rx: rad / sX,
                                            ry: rad / sY,
                                            left: -(lw / sX) / 2,
                                            top: -(lh / sY) / 2,
                                        }),
                                        // Keep strict lock states for frame slots because clipPath exposes ugly bounding boxes
                                        lockMovementX: false,
                                        lockMovementY: false,
                                        lockScalingX: false,
                                        lockScalingY: false,
                                        lockRotation: false,
                                        hasControls: true,
                                        // Store the slot bounds for the Adjust panel
                                        _slotLeft: lx,
                                        _slotTop: ly,
                                        _slotWidth: lw,
                                        _slotHeight: lh,
                                        _slotRadius: rad,
                                    });
                                }

                                fCanvas.add(img);
                                frameOverlayObjects.push(img);
                                resolve();
                            }, { crossOrigin: 'anonymous' });
                        });
                    } else if (layer.type === 'text' && layer.text) {
                        // Get business text replacement
                        let displayText = layer.text;
                        @if($business ?? false)
                            const bLow = lname;
                            if (bLow.includes('name') || bLow.includes('business_name')) displayText = "{{ $business->name ?? '' }}";
                            else if (bLow.includes('phone') || bLow.includes('mobile') || bLow.includes('contact') || bLow.includes('call')) displayText = "{{ $business->mobile_no ?? '' }}";
                            else if (bLow.includes('email') || bLow.includes('mail')) displayText = "{{ $business->email ?? '' }}";
                            else if (bLow.includes('website') || bLow.includes('web') || bLow.includes('url') || bLow.includes('www')) displayText = "{{ $business->website ?? '' }}";
                            else if (bLow.includes('address') || bLow.includes('location') || bLow.includes('addr')) displayText = "{{ $business->address ?? '' }}";
                        @endif

                        // Determine the primary template font to use as a smart fallback 
                        // instead of dropping to an ugly generic 'sans-serif' if the JSON omitted it.
                        let primaryFontFallback = 'sans-serif';
                        let fontCounts = {};
                        config.layers.forEach(l => {
                            if (l.type === 'text' && l.font) {
                                fontCounts[l.font] = (fontCounts[l.font] || 0) + 1;
                            }
                        });
                        let maxCount = 0;
                        for (let f in fontCounts) {
                            if (fontCounts[f] > maxCount && fontMap[f]) {
                                maxCount = fontCounts[f];
                                primaryFontFallback = fontMap[f];
                            }
                        }

                        // If layer.font is explicitly provided but not loaded, it falls back to layer.font. Otherwise, use primary template font.
                        let fFamily = layer.font ? (fontMap[layer.font] || layer.font) : primaryFontFallback;
                        
                        // Final safety: if it's literally requesting 'sans-serif', try to override it with the premium primary font
                        if (fFamily === 'sans-serif' && primaryFontFallback !== 'sans-serif') {
                            fFamily = primaryFontFallback;
                        }

                        const styleColor = layer.color ? layer.color.replace('0x', '#') : '#000000';
                        const styleFontInternal = fFamily;
                        let styleSize = layer.size || 20;

                        if (styleSize < 25) {
                            styleSize = Math.round(styleSize * 1.25);
                        }

                        const isBold = (layer.weight === 'bold' || layer.weight === 700 || layer.weight === '700');
                        const isItalic = (layer.font && layer.font.toLowerCase().includes('italic'));

                        const textObj = new fabric.Textbox(displayText, {
                            left: lx,
                            top: ly,
                            width: lw,
                            fontSize: styleSize,
                            fontFamily: styleFontInternal,
                            fill: styleColor,
                            fontWeight: isBold ? '700' : '400',
                            fontStyle: isItalic ? 'italic' : 'normal',
                            lineHeight: layer.line_height || 1.1,
                            textAlign: layer.justification || 'left',
                            editable: true,
                            _isFrameLayer: true,
                            _objectType: 'text',
                            _label: layer.name || 'Text',
                            objectCaching: false,
                        });

                        fCanvas.add(textObj);
                    }
                }

                fCanvas.renderAll();

            } catch (err) {
                console.error("Error in applyFrameConfig:", err);
            }
        }

        function resizeCanvas(w, h) {
            // Set the true canvas dimensions (full resolution)
            fCanvas.internalW = w;
            fCanvas.internalH = h;
            fCanvas.setDimensions({ width: w, height: h });
            // Re-apply CSS scaling
            scaleCanvasToFit();
        }

        async function loadFont(originalName, internalName, fontsBase) {
            if (!originalName || originalName === 'sans-serif') return false;
            // Strip any existing file extensions from the font name in case the JSON provides it
            let baseName = originalName;
            if (baseName.includes('.')) {
                baseName = baseName.substring(0, baseName.lastIndexOf('.'));
            }
            const formats = ['.ttf', '.otf', '.woff'];
            const ts = new Date().getTime();
            for (const ext of formats) {
                try {
                    const safeName = encodeURIComponent(baseName);
                    const fontUrl = `${fontsBase}${safeName}${ext}?v=${ts}`;
                    const font = new FontFace(internalName, `url("${fontUrl}")`);
                    const loaded = await font.load();
                    document.fonts.add(loaded);
                    return true;
                } catch (e) {
                    continue;
                }
            }
            return false;
        }

        // ── Export (Native Fabric.js — no html2canvas needed!) ──
        async function exportImage() {
            fCanvas.discardActiveObject();
            clearGuideLines();
            fCanvas.renderAll();

            // Wait for fonts
            if (document.fonts && document.fonts.ready) {
                await document.fonts.ready;
            }
            await new Promise(r => setTimeout(r, 200));

            // Export at full resolution (multiplier relative to current canvas size)
            const multiplier = 3; // Export at 3x for high quality
            const dataURL = fCanvas.toDataURL({
                format: 'png',
                quality: 1.0,
                multiplier: multiplier,
            });

            const link = document.createElement('a');
            link.download = 'festive_design_highres.png';
            link.href = dataURL;
            link.click();
        }

        // ── Image Attach/Detach (for frame photo slots) ──
        function attachImage(input) {
            if (!activeObject || !activeObject._isFrameImage) return;
            const file = input.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function(e) {
                fabric.Image.fromURL(e.target.result, (newImg) => {
                    if (!newImg) return;
                    // Replace the image content while keeping position/size
                    const oldLeft = activeObject.left;
                    const oldTop = activeObject.top;
                    const oldScaleX = activeObject.scaleX;
                    const oldScaleY = activeObject.scaleY;
                    const oldClipPath = activeObject.clipPath;
                    const oldW = activeObject.width * oldScaleX;
                    const oldH = activeObject.height * oldScaleY;

                    // Use strict slot dimensions instead of the potentially zoomed active object bounds
                    const slotW = activeObject._slotWidth || (activeObject.width * activeObject.scaleX);
                    const slotH = activeObject._slotHeight || (activeObject.height * activeObject.scaleY);
                    const slotL = activeObject._slotLeft !== undefined ? activeObject._slotLeft : oldLeft;
                    const slotT = activeObject._slotTop !== undefined ? activeObject._slotTop : oldTop;
                    const rad = activeObject._slotRadius || 0;

                    // Frame image slots: use 'cover' scaling to perfectly FILL the slot bounds
                    const coverScale = Math.max(slotW / newImg.width, slotH / newImg.height);
                    const sX = coverScale;
                    const sY = coverScale;
                    
                    // Center the overflow relative to the strict slot bounds
                    const centeredLeft = slotL - ((newImg.width * sX) - slotW) / 2;
                    const centeredTop = slotT - ((newImg.height * sY) - slotH) / 2;

                    // Generate a fresh clipPath calibrated to the new image's exact scale
                    const newClipPath = new fabric.Rect({
                        width: slotW / sX,
                        height: slotH / sY,
                        rx: rad / sX,
                        ry: rad / sY,
                        left: -(slotW / sX) / 2,
                        top: -(slotH / sY) / 2,
                    });

                    newImg.set({
                        left: centeredLeft,
                        top: centeredTop,
                        scaleX: sX,
                        scaleY: sY,
                        clipPath: newClipPath,
                        selectable: true,
                        evented: true,
                        _isFrameLayer: true,
                        _isFrameImage: true,
                        _isPlaceholder: false,
                        _label: activeObject._label || 'Component',
                        _originalSrc: e.target.result,
                        // Ensure lock states and metadata persist across replacements
                        lockMovementX: false,
                        lockMovementY: false,
                        lockScalingX: false,
                        lockScalingY: false,
                        lockRotation: false,
                        hasControls: true,
                        _slotLeft: activeObject._slotLeft,
                        _slotTop: activeObject._slotTop,
                        _slotWidth: activeObject._slotWidth,
                        _slotHeight: activeObject._slotHeight,
                        _slotRadius: activeObject._slotRadius
                    });

                    // Replace in canvas
                    const idx = fCanvas.getObjects().indexOf(activeObject);
                    fCanvas.remove(activeObject);
                    // Re-insert at same z-position
                    fCanvas.insertAt(newImg, idx);
                    
                    // Update overlay tracking
                    const oIdx = frameOverlayObjects.indexOf(activeObject);
                    if (oIdx >= 0) frameOverlayObjects[oIdx] = newImg;

                    fCanvas.setActiveObject(newImg);
                    activeObject = newImg;
                    fCanvas.renderAll();
                });
            };
            reader.readAsDataURL(file);
            input.value = '';
        }

        function detachImage() {
            if (!activeObject || !activeObject._isFrameImage) return;

            const origObj = activeObject;
            const slotW = origObj._slotWidth || (origObj.width * origObj.scaleX);
            const slotH = origObj._slotHeight || (origObj.height * origObj.scaleY);
            const slotL = origObj._slotLeft !== undefined ? origObj._slotLeft : origObj.left;
            const slotT = origObj._slotTop !== undefined ? origObj._slotTop : origObj.top;
            const rad = origObj._slotRadius || 0;
            const origLabel = origObj._label;

            // Replace the frame slot with a clean placeholder
            const placeholderSvg = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 400 400'><rect width='400' height='400' fill='%23e2e8f0'/><rect x='10' y='10' width='380' height='380' rx='8' fill='%23f1f5f9' stroke='%23cbd5e1' stroke-width='2' stroke-dasharray='12 6'/><g transform='translate(200,185)'><rect x='-40' y='-35' width='80' height='65' rx='8' fill='none' stroke='%2394a3b8' stroke-width='3'/><circle cx='-18' cy='-12' r='8' fill='%2394a3b8'/><path d='M-32 20 L-12 0 L0 12 L12 -5 L32 20Z' fill='%2394a3b8'/></g><text x='200' y='240' text-anchor='middle' font-family='sans-serif' font-size='18' font-weight='600' fill='%2394a3b8'>Tap to add image</text></svg>";
            const placeholderUrl = "data:image/svg+xml;charset=utf-8," + encodeURIComponent(placeholderSvg);
            fabric.Image.fromURL(placeholderUrl, (newImg) => {
                if (!newImg) return;

                // Exact stretch to fill the slot completely (no gaps)
                const sX = slotW / newImg.width, sY = slotH / newImg.height;

                const newClipPath = new fabric.Rect({
                    width: slotW / sX, height: slotH / sY,
                    rx: rad / sX, ry: rad / sY,
                    left: -(slotW / sX) / 2, top: -(slotH / sY) / 2,
                });

                newImg.set({
                    left: slotL, top: slotT,
                    scaleX: sX, scaleY: sY,
                    clipPath: newClipPath,
                    selectable: true, evented: true,
                    _isFrameLayer: true, _isFrameImage: true, _isPlaceholder: true,
                    _label: origLabel,
                    lockMovementX: false, lockMovementY: false,
                    lockScalingX: false, lockScalingY: false,
                    lockRotation: false, hasControls: true,
                    _slotLeft: slotL, _slotTop: slotT,
                    _slotWidth: slotW, _slotHeight: slotH, _slotRadius: rad,
                });

                const idx = fCanvas.getObjects().indexOf(origObj);
                fCanvas.remove(origObj);
                if (idx >= 0) fCanvas.insertAt(newImg, idx);
                else fCanvas.add(newImg);

                const oIdx = frameOverlayObjects.indexOf(origObj);
                if (oIdx >= 0) frameOverlayObjects[oIdx] = newImg;

                fCanvas.discardActiveObject();
                fCanvas.renderAll();
            }, { crossOrigin: 'anonymous' });
        }

        function toggleAdjustPanel() {
            const panel = document.getElementById('imageAdjustControl');
            panel.style.display = (panel.style.display === 'block') ? 'none' : 'block';
        }

        function updateImageAdjustment() {
            if (!activeObject || !activeObject._isFrameImage) return;

            const zoomVal = parseInt(document.getElementById('zoomSlider').value);
            const px = parseInt(document.getElementById('posXSlider').value);
            const py = parseInt(document.getElementById('posYSlider').value);

            document.getElementById('zoomVal').innerText = zoomVal + '%';
            document.getElementById('posXVal').innerText = px + '%';
            document.getElementById('posYVal').innerText = py + '%';

            activeObject._zoom = zoomVal;
            activeObject._posX = px;
            activeObject._posY = py;

            // Use stored slot bounds
            const slotL = activeObject._slotLeft || 0;
            const slotT = activeObject._slotTop || 0;
            const slotW = activeObject._slotWidth || 100;
            const slotH = activeObject._slotHeight || 100;
            const rad = activeObject._slotRadius || 20;

            // Base scale = cover scale (minimum to fill slot)
            if (!activeObject._baseScaleX) {
                activeObject._baseScaleX = activeObject.scaleX;
                activeObject._baseScaleY = activeObject.scaleY;
            }

            const zoomFactor = zoomVal / 100;
            const newSX = activeObject._baseScaleX * zoomFactor;
            const newSY = activeObject._baseScaleY * zoomFactor;

            // Calculate how much the image exceeds the slot at this zoom level
            const imgW = activeObject.width * newSX;
            const imgH = activeObject.height * newSY;
            const overflowX = Math.max(0, imgW - slotW);
            const overflowY = Math.max(0, imgH - slotH);

            // Position sliders (0-100) control where within the overflow the image sits
            // 50 = centered, 0 = left/top edge, 100 = right/bottom edge
            const offsetX = overflowX * (px / 100);
            const offsetY = overflowY * (py / 100);

            const newLeft = slotL - offsetX;
            const newTop = slotT - offsetY;

            // Update clipPath to always match the slot window  
            activeObject.set({
                left: newLeft,
                top: newTop,
                scaleX: newSX,
                scaleY: newSY,
                clipPath: new fabric.Rect({
                    width: slotW / newSX,
                    height: slotH / newSY,
                    rx: rad / newSX,
                    ry: rad / newSY,
                    left: -(slotW / newSX) / 2,
                    top: -(slotH / newSY) / 2,
                })
            });
            // Shift clipPath origin to match the slot center relative to image
            const clipCenterX = (slotL + slotW / 2 - newLeft) / newSX - activeObject.width / 2;
            const clipCenterY = (slotT + slotH / 2 - newTop) / newSY - activeObject.height / 2;
            activeObject.clipPath.set({ left: clipCenterX - (slotW / newSX) / 2, top: clipCenterY - (slotH / newSY) / 2 });

            fCanvas.renderAll();
        }

        // ── Frame Color Panel ──
        let selectedLayerIndex = 0;
        let initialLayerStates = [];
        let colorHistory = [];
        let historyIndex = -1;

        function openFrameColorPanel() {
            const panel = document.getElementById('frameColorPanel');
            if (frameOverlayObjects.length === 0) return;

            initialLayerStates = frameOverlayObjects.map(obj => ({
                src: obj._element ? obj._element.src : '',
                originalSrc: obj._originalSrc,
            }));

            if (panel.style.display === 'block') {
                closeFrameColorPanel();
                return;
            }

            const framesGrid = document.querySelector('.frames-grid');
            if (framesGrid) framesGrid.style.display = 'none';
            panel.style.display = 'block';
            renderLayerBubbles();
            colorHistory = [];
            historyIndex = -1;
            updateHistoryButtons();
        }

        function closeFrameColorPanel() {
            document.getElementById('frameColorPanel').style.display = 'none';
            const fg = document.querySelector('.frames-grid');
            if (fg) fg.style.display = '';
            document.querySelector('.editor-container').classList.remove('panel-open');
        }

        function cancelFrameColorPanel() {
            // TODO: Revert color changes using initialLayerStates
            closeFrameColorPanel();
        }

        function confirmFrameColorPanel() {
            closeFrameColorPanel();
        }

        function renderLayerBubbles() {
            const container = document.getElementById('layerBubbles');
            container.innerHTML = '';

            frameOverlayObjects.forEach((obj, idx) => {
                const bubble = document.createElement('div');
                bubble.className = `layer-bubble ${idx === selectedLayerIndex ? 'active' : ''}`;

                if (obj._element) {
                    const img = document.createElement('img');
                    img.src = obj._originalSrc || obj._element.src;
                    img.style.width = '100%';
                    img.style.height = '100%';
                    img.style.objectFit = 'cover';
                    bubble.appendChild(img);
                }

                bubble.onclick = () => {
                    selectedLayerIndex = idx;
                    renderLayerBubbles();
                };
                container.appendChild(bubble);
            });
        }

        function applyPaletteColor(color) {
            const obj = frameOverlayObjects[selectedLayerIndex];
            if (!obj) return;

            // Apply Fabric color filter
            obj.filters = [new fabric.Image.filters.BlendColor({
                color: color,
                mode: 'tint',
                alpha: 1
            })];
            obj.applyFilters();
            fCanvas.renderAll();

            // History
            addToHistory(selectedLayerIndex, { layerIndex: selectedLayerIndex, newColor: color });
            renderLayerBubbles();
        }

        function addToHistory(idx, actionObj) {
            if (historyIndex < colorHistory.length - 1) {
                colorHistory = colorHistory.slice(0, historyIndex + 1);
            }
            colorHistory.push(actionObj);
            historyIndex++;
            updateHistoryButtons();
        }

        function undoFrameColor() {
            if (historyIndex >= 0) {
                const action = colorHistory[historyIndex];
                const obj = frameOverlayObjects[action.layerIndex];
                if (obj) {
                    obj.filters = [];
                    obj.applyFilters();
                    fCanvas.renderAll();
                    renderLayerBubbles();
                }
                historyIndex--;
                updateHistoryButtons();
            }
        }

        function redoFrameColor() {
            if (historyIndex < colorHistory.length - 1) {
                historyIndex++;
                const action = colorHistory[historyIndex];
                const obj = frameOverlayObjects[action.layerIndex];
                if (obj) {
                    obj.filters = [new fabric.Image.filters.BlendColor({
                        color: action.newColor,
                        mode: 'tint',
                        alpha: 1
                    })];
                    obj.applyFilters();
                    fCanvas.renderAll();
                    renderLayerBubbles();
                }
                updateHistoryButtons();
            }
        }

        function updateHistoryButtons() {
            const ub = document.getElementById('undoColorBtn');
            const rb = document.getElementById('redoColorBtn');
            if (ub) ub.disabled = (historyIndex < 0);
            if (rb) rb.disabled = (historyIndex >= colorHistory.length - 1);
            if (window.lucide) window.lucide.createIcons();
        }

        // ── Layers Modal ──
        function toggleLayersModal() {
            fCanvas.discardActiveObject();
            fCanvas.renderAll();
            const modal = document.getElementById('layersModal');
            const container = document.getElementById('layersContainer');
            container.innerHTML = '';

            const objects = fCanvas.getObjects().filter(o => !o._isGuideLine);
            objects.forEach(obj => {
                let icon = 'type';
                let label = obj._label || obj.text || 'Component';
                
                if (obj.type === 'textbox' || obj.type === 'i-text') {
                    icon = 'type';
                    label = obj._label || (obj.text ? obj.text.substring(0, 20) : 'Text');
                } else if (obj._objectType === 'sticker') {
                    icon = 'smile';
                    label = 'Sticker';
                } else if (obj._objectType === 'logo') {
                    icon = 'image';
                    label = 'Logo';
                } else if (obj.type === 'image') {
                    icon = 'image';
                    label = obj._label || 'Image';
                }

                const item = document.createElement('div');
                item.className = 'layer-item';
                item.innerHTML = `
                    <i data-lucide="${icon}"></i>
                    <span class="layer-text">${label}</span>
                `;
                item.onclick = () => {
                    modal.style.display = 'none';
                    setTimeout(() => {
                        fCanvas.setActiveObject(obj);
                        fCanvas.renderAll();
                    }, 50);
                };
                container.appendChild(item);
            });

            modal.style.display = 'flex';
            if (window.lucide) window.lucide.createIcons();
        }

        // Global click listeners for panel management
        document.addEventListener('mousedown', function(e) {
            const panels = ['frameColorPanel', 'fontPanel', 'fontSizeControl'];
            const toggleButtons = ['.toggle-btn', '#editTool', '#fontTool', '#sizeTool'];

            let isClickInsidePanel = panels.some(id => {
                const el = document.getElementById(id);
                return el && el.contains(e.target);
            });

            let isClickOnToggle = toggleButtons.some(selector => {
                const el = document.querySelector(selector);
                return el && (el === e.target || el.contains(e.target));
            });

            const contextualBar = document.getElementById('contextualBar');
            const isInsideContextual = contextualBar && contextualBar.contains(e.target);

            if (!isClickInsidePanel && !isClickOnToggle && !isInsideContextual) {
                const wrapper = document.getElementById('canvas-wrapper');
                if (wrapper && !wrapper.contains(e.target)) {
                    closeAllPanels();
                }
            }
        });

        // ── Initialize ──
        lucide.createIcons();

        window.addEventListener('DOMContentLoaded', () => {
            initCanvas();

            // Load initial frame config
            const firstFrame = document.querySelector('.frame-item.selected');
            if (firstFrame) {
                const configAttr = firstFrame.getAttribute('data-config');
                if (configAttr && configAttr !== 'null') {
                    try {
                        currentFrameConfig = JSON.parse(configAttr);
                        setTimeout(() => {
                            applyFrameConfig(currentFrameConfig);
                        }, 300);
                    } catch(e) {
                        console.error("Initial config error:", e);
                    }
                }
            }
        });

        // Handle window resize
        window.addEventListener('resize', () => {
            if (fCanvas) {
                scaleCanvasToFit();
            }
        });
    </script>
@endsection

