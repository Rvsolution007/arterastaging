@extends('layouts.client')

@section('title', 'Select Frame')@section('styles')<style>
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
            /* Ensure it stacks above canvas and selection handles */
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
        }

        .next-button {
            display: flex;
            align-items: center;
            gap: 6px;
            background: #0f766e;
            /* Teal-700 */
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        /* Canvas Section - FIXED */
        .canvas-section {
            background-color: #f8fafc;
            /* Subtle contrast background */
            padding: 24px 16px;
            flex-shrink: 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        #capture-area {
            width: auto;
            height: auto;
            max-width: 100%;
            max-height: 58vh;
            /* Limit vertical space for Story/Tall layouts */
            background-color: #ffffff;
            /* Removed transition: aspect-ratio to avoid race conditions in JS calculations */
            overflow: visible;
            /* Allow selection handles to extend beyond canvas edges */
            position: relative;
            margin: 0 auto;
            border-radius: 12px;
            box-shadow: 0 10px 40px -10px rgba(0, 0, 0, 0.2);
            /* Professional shadow */
            border: 1px solid rgba(0, 0, 0, 0.05);
            opacity: 0;
            /* Hide initially for smooth fade-in */
            transition: opacity 0.2s ease;
            /* Snappier fade transition */
        }

        /* Clip poster and frame overlays to canvas bounds */
        .base-post-image,
        .frame-overlay-wrapper {
            overflow: hidden;
        }

        /* Smart Guides Container */
        .guide-container {
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 3000;
        }
        #capture-area .smart-guide { display: none; }

        .base-post-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .frame-overlay-wrapper {
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 10;
        }

        .frame-overlay-wrapper .layer-img {
            position: absolute;
            pointer-events: none;
            object-fit: contain;
        }

        /* Dynamic Overlays */
        .draggable {
            position: absolute;
            cursor: move;
            touch-action: none;
            user-select: none;
            z-index: 20;
            overflow: visible !important;
            /* Ensure selection handles are not clipped */
        }

        .info-capsule {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 9999px;
            padding: 3px 12px 3px 5px;
            display: flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            pointer-events: auto;
        }

        .info-icon-wrapper {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            flex-shrink: 0;
        }

        .bg-orange-500 {
            background-color: #f59e0b;
        }

        .bg-blue-500 {
            background-color: #2563eb;
        }

        .bg-red-500 {
            background-color: #dc2626;
        }

        .bg-green-500 {
            background-color: #16a34a;
        }

        .bg-emerald-500 {
            background-color: #059669;
        }

        #fontPanel {
            position: fixed;
            bottom: 0;
            left: 50% !important;
            transform: translateX(-50%);
            width: 100%;
            max-width: 448px;
            background: white;
            border-top-left-radius: 24px;
            border-top-right-radius: 24px;
            box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.2);
            z-index: 3500;
            padding: 16px;
            padding-bottom: calc(16px + env(safe-area-inset-bottom));
            display: none;
            max-height: 50vh;
            overflow-y: auto;
            animation: slideUpCenter 0.3s ease;
        }

        @keyframes slideUpCenter {
            from {
                transform: translateX(-50%) translateY(100%);
            }

            to {
                transform: translateX(-50%) translateY(0);
            }
        }

        .font-panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .font-panel-title {
            font-size: 18px;
            font-weight: 800;
            color: #1e293b;
        }

        .font-close {
            padding: 8px;
            color: #64748b;
            cursor: pointer;
        }

        .font-option {
            padding: 12px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 16px;
            cursor: pointer;
        }

        .font-option:last-child {
            border-bottom: none;
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
            /* Shown on selection */
            flex-direction: column;
            border-top-left-radius: 24px;
            border-top-right-radius: 24px;
            box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.15);
            animation: slideUpCenter 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from {
                transform: translateY(100%);
            }

            to {
                transform: translateY(0);
            }
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

        /* Sub-panels for tools */
        .tool-sub-panel {
            display: none;
            padding: 16px 20px;
            border-bottom: 1px solid #f1f5f9;
            background: #f8fafc;
        }

        /* Selection Visuals */
        .selection-overlay {
            position: absolute;
            inset: -2px;
            border: 1px solid #7d2ae8;
            pointer-events: none;
            display: none;
            z-index: 1000;
        }

        .node {
            position: absolute;
            background: #ffffff;
            border: 1px solid #7d2ae8;
            pointer-events: auto;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.15);
        }

        .node-corner {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .node-side-h {
            width: 12px;
            height: 5px;
            border-radius: 3px;
        }

        .node-side-v {
            width: 5px;
            height: 12px;
            border-radius: 3px;
        }

        .node-tl {
            top: -4.5px;
            left: -4.5px;
            cursor: nwse-resize;
        }

        .node-tr {
            top: -4.5px;
            right: -4.5px;
            cursor: nesw-resize;
        }

        .node-bl {
            bottom: -4.5px;
            left: -4.5px;
            cursor: nesw-resize;
        }

        .node-br {
            bottom: -4.5px;
            right: -4.5px;
            cursor: nwse-resize;
        }

        .node-tm {
            top: -3px;
            left: 50%;
            transform: translateX(-50%);
            cursor: ns-resize;
        }

        .node-bm {
            bottom: -3px;
            left: 50%;
            transform: translateX(-50%);
            cursor: ns-resize;
        }

        .node-ml {
            top: 50%;
            left: -3px;
            transform: translateY(-50%);
            cursor: ew-resize;
        }

        .node-mr {
            top: 50%;
            right: -3px;
            transform: translateY(-50%);
            cursor: ew-resize;
        }

        /* Rotation Handle */
        .rotation-row {
            position: absolute;
            bottom: -45px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: auto;
        }

        .rot-btn {
            width: 36px;
            height: 36px;
            background: white;
            border: 1.5px solid #e2e8f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            cursor: grab;
        }

        .rot-btn:active {
            cursor: grabbing;
        }

        .draggable.selected .selection-overlay {
            display: block;
        }

        /* Canva-style Dragging Effects */
        .draggable.dragging {
            cursor: grabbing !important;
            filter: drop-shadow(0 15px 25px rgba(0, 0, 0, 0.25));
            transition: transform 0.1s ease, filter 0.1s ease;
            z-index: 5000 !important;
        }

        .draggable.dragging > *:not(.selection-overlay) {
            transform: scale(1.05);
            transition: transform 0.1s ease;
        }

        /* Smart Alignment Guides */
        .smart-guide {
            position: absolute;
            background-color: #d946ef; /* Canva Purple */
            display: none;
            pointer-events: none;
            z-index: 4000;
        }
        #v-guide { width: 1.5px; top: 0; bottom: 0; left: 50%; transform: translateX(-50%); }
        #h-guide { height: 1.5px; left: 0; right: 0; top: 50%; transform: translateY(-50%); }

        /* Move Handle */
        .position-row {
            position: absolute;
            bottom: -88px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            pointer-events: auto;
            z-index: 1001;
        }

        .move-btn {
            width: 36px;
            height: 36px;
            background: #7d2ae8;
            color: white;
            border: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(125, 42, 232, 0.3);
            cursor: move;
            touch-action: none;
        }

        .move-btn:active {
            transform: scale(0.9);
        }

        /* Scrolling Editor Logic */
        .scroll-editor {
            flex: 1;
            overflow-y: auto;
            background-color: #ffffff;
            border-top-left-radius: 24px;
            border-top-right-radius: 24px;
            min-height: 300px;
            /* Ensure tools have space */
        }

        /* Quick Toggle Icons */
        .toggle-bar {
            display: flex;
            gap: 10px;
            padding: 16px;
            overflow-x: auto;
            border-bottom: 1px solid #f1f5f9;
            -webkit-overflow-scrolling: touch;
            /* Smooth scrolling for mobile */
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
            /* Teal-600 */
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
            /* Emerald green from screenshot */
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
            /* Slide from bottom feel */
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
            /* Center relative to flex parent */
            animation: slideUpCenterStack 0.3s ease-out;
        }

        @keyframes slideUpCenterStack {
            from {
                transform: translateY(100%);
            }

            to {
                transform: translateY(0);
            }
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

        /* ========== DRAG & SELECT SYSTEM (Mobile-First) ========== */
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
        #v-guide, #h-guide {
            position: absolute;
            background: #4f46e5;
            z-index: 9998;
            display: none;
            pointer-events: none;
        }
        #v-guide { width: 1px; top: 0; bottom: 0; left: 50%; }
        #h-guide { height: 1px; left: 0; right: 0; top: 50%; }
    </style>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Roboto:wght@400;700&family=Poppins:wght@400;700&family=Montserrat:wght@400;700&family=Bebas+Neue&family=Pacifico&family=Dancing+Script:wght@400;700&family=Playfair+Display:wght@400;700&family=Oswald:wght@400;700&family=Lato:wght@400;700&family=Open+Sans:wght@400;700&family=Raleway:wght@400;700&family=Abril+Fatface&family=Comfortaa:wght@400;700&family=Righteous&family=Varela+Round&family=Caveat:wght@400;700&family=Lobster&display=swap"
        rel="stylesheet">
    <style>
        #frameColorPanel {
            background: #ffffff;
            padding: 16px;
            display: none;
            /* Inline styles for scrolling context */
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

        .btn-cancel {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        /* Toggle Button Styling from screenshot */
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
            /* Center icon */
            gap: 0;
            /* No gap needed */
            background: #2e0ee6ff;
            /* Match download button */
            color: #ffffff;
            border: none;
            padding: 10px;
            /* Square sizing */
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
            /* Higher than header */
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
            from {
                opacity: 0;
                transform: translateY(-5px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
@endsection

@section('content')
    <div class="editor-container">
        <header class="app-header">
            <a href="{{ route('festival.details', $festival->id) }}" class="back-link">
                <i data-lucide="chevron-left"></i>
            </a>
            <h1 class="header-title">Select Frame</h1>

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
                                        {{ $cat->name }}</div>
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

            <!-- Fixed Preview Area -->
            <div class="canvas-section">
        <div id="capture-area">
            <!-- Smart Alignment Guides -->
            <div class="guide-container">
                <div id="v-guide" class="smart-guide"></div>
                <div id="h-guide" class="smart-guide"></div>
            </div>

            <!-- Background Artwork -->
                    @php 
                        $designUrl = request()->query('design') ?? asset('uploads/' . ($festival_posts->first()->frame_image ?? $festival->image));
                    @endphp
                    <img class="base-post-image" id="basePoster" src="{{ $designUrl }}" alt="Post">

                    <!-- Frame Overlay -->
                    <div class="frame-overlay-wrapper" id="frameOverlay">
                    </div>
                        <!-- Hidden storage for the currently active frame's skin path -->
                        <input type="hidden" id="activeFrameImg-source" value="{{ $frames->first()->full_url ?? '' }}">

                        <!-- Business Logo & Name (Draggable) -->
                        @if($business)
                            @if($business->logo)
                                <div id="logo-shell" class="draggable" style="left: 10%; top: 10%; width: 80px;">
                                    <img id="preview-logo" src="{{ asset('uploads/' . $business->logo) }}" alt="Logo" style="width: 100%; height: auto;">
                                </div>
                            @endif

                            <div id="preview-name" class="draggable text-element" style="left: 10%; bottom: 15%; color: #000000; font-weight: 800; font-size: 20px; text-shadow: 0px 0px 2px #fff;">
                                <span>{{ $business->name }}</span>
                            </div>

                            <div id="el-phone" class="draggable info-capsule" style="left: 10%; bottom: 8%;">
                                <div class="info-icon-wrapper bg-orange-500">
                                     <i data-lucide="smartphone" style="width: 14px; height: 14px;"></i>
                                </div>
                                <span style="font-size: 12px; font-weight: 700;">{{ $business->mobile_no }}</span>
                            </div>

                            <div id="el-email" class="draggable info-capsule" style="left: 10%; bottom: 3%; display: none;">
                                <div class="info-icon-wrapper bg-blue-500">
                                     <i data-lucide="mail" style="width: 14px; height: 14px;"></i>
                                </div>
                                <span style="font-size: 11px; font-weight: 600;">{{ $business->email }}</span>
                            </div>

                             <div id="el-address" class="draggable info-capsule" style="left: 50%; bottom: 8%; display: none;">
                                <div class="info-icon-wrapper bg-red-500">
                                     <i data-lucide="map-pin" style="width: 14px; height: 14px;"></i>
                                </div>
                                <span style="font-size: 11px; font-weight: 600;">{{ $business->address }}</span>
                            </div>

                            <div id="el-website" class="draggable info-capsule" style="left: 50%; bottom: 3%; display: none;">
                                <div class="info-icon-wrapper bg-green-500">
                                     <i data-lucide="globe" style="width: 14px; height: 14px;"></i>
                                </div>
                                <span style="font-size: 11px; font-weight: 600;">{{ $business->website }}</span>
                            </div>
                        @endif

                        <!-- Selection Handles Wrapper (Injected via JS) -->
                    </div>
                </div>

                <!-- Scrollable Tools Area -->
                <div class="scroll-editor scrollbar-hide">
                    <!-- Toggles Section -->
                    <div class="toggle-bar">
                        <button class="toggle-btn" onclick="toggleElement('preview-name', this)">NAME</button>
                        <button class="toggle-btn" onclick="toggleElement('logo-shell', this)">LOGO</button>
                        <button class="toggle-btn" onclick="toggleElement('el-phone', this)"><i
                                data-lucide="smartphone"></i></button>
                        <button class="toggle-btn" onclick="toggleElement('el-email', this)"><i data-lucide="mail"></i></button>
                        <button class="toggle-btn" onclick="toggleElement('el-address', this)"><i
                                data-lucide="map-pin"></i></button>
                        <button class="toggle-btn" onclick="toggleElement('el-website', this)"><i data-lucide="globe"></i></button>
                        <button class="toggle-btn" id="frame-toggle-btn" onclick="toggleFrameOverlays()">FRAME</button>
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
                            $basePosterUrl = request()->query('design') ?? asset('uploads/' . ($festival_posts->first()->frame_image ?? $festival->image)); 
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
                            <div class="tool-icon"><i data-lucide="type"></i></div>
                            <span class="tool-label">Text Color</span>
                            <input type="color" id="colorInput" class="hidden" oninput="changeColor(this.value)">
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

                     <div class="bar-scroll" style="justify-content: center;">
                          <!-- Edit Text -->
                          <div id="editTool" class="tool-btn" onclick="triggerEdit()">
                              <div class="tool-btn-icon"><i data-lucide="type"></i></div>
                              <span class="tool-btn-label">Edit</span>
                          </div>
                          <!-- Font -->
                          <div id="fontTool" class="tool-btn" onclick="toggleFontList()">
                              <div class="tool-btn-icon"><i data-lucide="a-large-small"></i></div>
                              <span class="tool-btn-label">Font</span>
                          </div>
                          <!-- Size -->
                          <div id="sizeTool" class="tool-btn" onclick="toggleSizePanel()">
                              <div class="tool-btn-icon"><i data-lucide="minimize-2"></i></div>
                              <span class="tool-btn-label">Size</span>
                          </div>
                          <!-- Bold -->
                          <div id="boldTool" class="tool-btn" onclick="toggleBold()">
                              <div class="tool-btn-icon" style="font-weight:900; font-size:18px;">B</div>
                              <span class="tool-btn-label">Bold</span>
                          </div>
                          <!-- Italic -->
                          <div id="italicTool" class="tool-btn" onclick="toggleItalic()">
                              <div class="tool-btn-icon" style="font-style:italic; font-size:18px;"><em>I</em></div>
                              <span class="tool-btn-label">Italic</span>
                          </div>
                          <!-- Color -->
                          <div id="contextualColorTool" class="tool-btn" onclick="document.getElementById('colorInput').click()">
                              <div class="tool-btn-icon" id="colorStroke" style="border-bottom: 4px solid #7d2ae8;"><i data-lucide="palette"></i></div>
                              <span class="tool-btn-label">Color</span>
                          </div>
                          <!-- Layers -->
                          <div id="layersTool" class="tool-btn" onclick="toggleLayersModal()">
                              <div class="tool-btn-icon"><i data-lucide="layers"></i></div>
                              <span class="tool-btn-label">Layers</span>
                          </div>
                          <!-- Delete -->
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
                </div>

                </div>
            </div>
@endsection

@section('scripts')
    <script>
        let activeElement = null;

        function toggleElement(id, btn) {
            const el = document.getElementById(id);
            if (el.style.display === 'none') {
                const displayVal = (id === 'preview-name') ? 'block' : 'flex';
                el.style.setProperty('display', displayVal, 'important');
                btn.classList.remove('inactive');
            } else {
                el.style.setProperty('display', 'none', 'important');
                btn.classList.add('inactive');
            }
        }

        function toggleWhatsApp(btn) {
            const container = document.getElementById('el-phone');
            const iconWrapper = container.querySelector('.info-icon-wrapper');
            const currentIcon = iconWrapper.querySelector('svg');

            if (btn.classList.contains('inactive')) {
                btn.classList.remove('inactive');
                iconWrapper.className = 'info-icon-wrapper bg-emerald-500';
                // Simple approach: replace icon html or use lucide
            } else {
                btn.classList.add('inactive');
                iconWrapper.className = 'info-icon-wrapper bg-orange-500';
            }
        }

        // Filter Logic
        function toggleFilterMenu() {
            clearActive();
            const menu = document.getElementById('filterMenu');
            menu.classList.toggle('active');
        }

        function filterFrames(catId, label, element) {
            // Update UI
            // document.getElementById('currentFilterLabel').innerText = label; // Removed label update
            document.querySelectorAll('.filter-option').forEach(el => el.classList.remove('selected'));
            element.classList.add('selected');

            // Hide Menu
            document.getElementById('filterMenu').classList.remove('active');

            // Filter Grid Items
            const items = document.querySelectorAll('.frame-item');
            let visibleCount = 0;

            items.forEach(item => {
                const itemCat = item.getAttribute('data-category-id');
                // Show if 'all' selected OR item matches category OR item matches 'all' (generic frames)
                if (catId === 'all' || itemCat == catId || itemCat === 'all') {
                    item.style.display = 'block';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });
        }

        // Close filter on outside click
        document.addEventListener('click', function(event) {
            const container = document.querySelector('.filter-container');
            if (container && !container.contains(event.target)) {
                 document.getElementById('filterMenu').classList.remove('active');
            }
        });

        let currentFrameConfig = null;
        let isFrameHidden = false;

        function toggleFrameOverlays() {
            clearActive();
            const area = document.getElementById('capture-area');
            const overlay = document.getElementById('frameOverlay');
            const businessIds = ['preview-name', 'logo-shell', 'el-phone', 'el-email', 'el-address', 'el-website'];
            const btn = document.getElementById('frame-toggle-btn');

            isFrameHidden = !isFrameHidden;

            if (isFrameHidden) {
                // HIDE EVERYTHING
                overlay.style.display = 'none';
                area.querySelectorAll('.frame-extra-layer').forEach(e => e.style.display = 'none');
                businessIds.forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.style.setProperty('display', 'none', 'important');
                });
                if(btn) btn.classList.add('inactive');
            } else {
                // SHOW EVERYTHING
                overlay.style.display = 'block';
                area.querySelectorAll('.frame-extra-layer').forEach(e => e.style.display = 'block');

                // Re-apply the last config to restore correct business visibility
                applyFrameConfig(currentFrameConfig);
                if(btn) btn.classList.remove('inactive');
            }
        }

        function clearFrame() {
            clearActive();
            currentFrameConfig = null;
            isFrameHidden = false;
            const btn = document.getElementById('frame-toggle-btn');
            if(btn) btn.classList.remove('inactive');

            const sourceInput = document.getElementById('activeFrameImg-source');
            if (sourceInput) sourceInput.value = '';
            document.querySelectorAll('.frame-item').forEach(i => i.classList.remove('selected'));
            applyFrameConfig(null);
        }

        function changeFrame(url, element) {
            clearActive();
            isFrameHidden = false; // Reset hidden state when picking a new frame
            const btn = document.getElementById('frame-toggle-btn');
            if(btn) btn.classList.remove('inactive');
            document.getElementById('frameOverlay').style.display = 'block';

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
            } else {
                clearFrame();
            }
        }

        async function applyFrameConfig(config) {
            const area = document.getElementById('capture-area');
            const overlay = document.getElementById('frameOverlay');

            // 1. Clear previous layers & hide all business elements
            overlay.innerHTML = ''; 
            const existingExtras = area.querySelectorAll('.frame-extra-layer');
            existingExtras.forEach(e => e.remove());

            const businessIds = ['preview-name', 'logo-shell', 'el-phone', 'el-email', 'el-address', 'el-website'];
            businessIds.forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.style.setProperty('display', 'none', 'important');
                }
            });

            if (!config || !config.layers) {
                // Return to default if no config (show basic info)
                area.style.aspectRatio = '4/5';
                businessIds.forEach(id => {
                    const el = document.getElementById(id);
                    if (el) {
                        // Restore capsule styling
                        if (id !== 'preview-name' && id !== 'logo-shell') {
                            el.classList.add('info-capsule');
                        }
                        el.style.cssText = ''; 

                        // Show/Hide based on defaults
                        const isPrimary = (id === 'preview-name' || id === 'el-phone');
                        const displayVal = isPrimary ? (id === 'preview-name' ? 'block' : 'flex') : 'none';
                        el.style.setProperty('display', displayVal, 'important');

                        const icon = el.querySelector('.info-icon-wrapper');
                        if (icon) icon.style.display = 'flex';

                        const span = el.querySelector('span');
                        if (span) span.style.cssText = ''; 
                    }
                });
                area.style.opacity = '1'; 
                return;
            }

            try {

            // 2. Determine base resolution from design
            // We look for largest layer bounds to find target aspect ratio
            let designW = 0, designH = 0;
            config.layers.forEach(l => {
                const layerRight = (l.x || 0) + (l.width || 0);
                const layerBottom = (l.y || 0) + (l.height || 0);
                if (layerRight > designW) designW = layerRight;
                if (layerBottom > designH) designH = layerBottom;
            });

            // Standardize base if close to common formats
            if (Math.abs(designW - designH) < 10) designW = designH = 1024; // Square
            else if (designW > 0 && designH > 0) {
                 // Keep calculated ratio
            } else {
                designW = 1024; designH = 1024;
            }

            // DYNAMIC ASPECT RATIO: Change canvas to match frame
            area.style.aspectRatio = `${designW} / ${designH}`;

            // GIVE BROWSER A MOMENT to update layout dimensions
            // Reduced delay for snappier feel
            await new Promise(r => setTimeout(r, 20));

            // Scale is relative to the width of the container
            const scale = area.clientWidth / designW || 1; // Fallback to 1 if clientWidth is 0
            const yOffset = 0; // Since canvas matches design, offset is zero

            // Ensure background poster covers the new ratio
            const basePoster = document.getElementById('basePoster');
            if (basePoster) {
                basePoster.style.width = '100%';
                basePoster.style.height = '100%';
                basePoster.style.objectFit = 'cover';
            }

            // 3. Resolve asset paths
            const skinBase = document.getElementById('activeFrameImg-source').value; 
            const skinDir = skinBase.substring(0, skinBase.lastIndexOf('/') + 1);
            const templateDir = skinDir.split('/skins/')[0] + '/';
            const fontsBase = templateDir + 'fonts/';

            // 4. Load Fonts with unique internal naming
            const fontMap = {};
            const fontPromises = [];
            config.layers.forEach(l => {
                if (l.font && !fontMap[l.font]) {
                    const internalName = `ZIPFONT_${l.font.replace(/[^a-zA-Z0-9]/g, '_')}_${new Date().getTime()}`;
                    fontMap[l.font] = internalName;
                    fontPromises.push(loadFont(l.font, internalName, fontsBase));
                }
            });
            await Promise.all(fontPromises);

            // Give browser a moment to register fonts
            await new Promise(r => setTimeout(r, 50));

            // 5. Render Layers with correct Z-Index (Preserve order)
            const matchedIds = new Set();
            config.layers.forEach((layer, idx) => {
                let elId = null;
                const lname = (layer.name || '').toLowerCase();
                const isText = layer.type === 'text';
                const isImg = layer.type === 'image';

                if (isText && (lname === 'name' || lname.includes('business_name'))) {
                    elId = 'preview-name';
                } else if (isImg && (lname === 'logo' || lname.includes('business_logo'))) {
                    elId = 'logo-shell';
                } else if (isText && (lname.includes('mobile') || lname.includes('phone') || lname.includes('contact') || lname.includes('call'))) {
                    elId = 'el-phone';
                } else if (isText && (lname.includes('email') || lname.includes('mail'))) {
                    elId = 'el-email';
                } else if (isText && (lname.includes('address') || lname.includes('location') || lname.includes('addr'))) {
                    elId = 'el-address';
                } else if (isText && (lname.includes('website') || lname.includes('web') || lname.includes('site') || lname.includes('url') || lname.includes('www'))) {
                    elId = 'el-website';
                }

                let el;
                if (elId) {
                    matchedIds.add(elId);
                    el = document.getElementById(elId);
                    if (!el) return;
                    el.classList.remove('info-capsule');
                    el.style.cssText = ''; 
                    el.style.setProperty('display', 'flex', 'important');
                    el.style.setProperty('align-items', 'center', 'important');
                    el.style.setProperty('gap', '0', 'important');
                    el.style.setProperty('padding', '0', 'important');
                    el.style.setProperty('background', 'transparent', 'important');
                    el.style.setProperty('border', 'none', 'important');
                    el.style.setProperty('backdrop-filter', 'none', 'important');
                    el.style.setProperty('box-shadow', 'none', 'important');

                    const icon = el.querySelector('.info-icon-wrapper');
                    if (icon) icon.style.setProperty('display', 'none', 'important');
                    const span = el.querySelector('span');
                    if (span) {
                        span.innerText = span.innerText.trim();
                        span.style.cssText = 'background:transparent !important; color:inherit !important; font-family:inherit !important; font-weight:inherit !important; font-size:inherit !important; line-height:1 !important; padding:0 !important; margin:0 !important; display:inline-block !important;';
                    }
                } else if (layer.type === 'image' && layer.name !== 'bg' && layer.name !== 'background') {
                    // Static frame image layer (no drag/select)
                    el = document.createElement('div');
                    el.className = 'icon-container frame-extra-layer';
                    el.setAttribute('data-label', layer.name || 'Component');

                    const img = document.createElement('img');
                    img.className = 'layer-img';
                    img.style.width = '100%';
                    img.style.height = '100%';
                    img.style.display = 'block';
                    img.style.objectFit = 'contain';

                    let src = layer.src;
                    if (src.includes('../skins/')) {
                         let parts = src.split('/');
                         src = templateDir + 'skins/' + parts[parts.length-2] + '/' + parts[parts.length-1];
                    } else {
                         src = skinDir + src.split('/').pop();
                    }
                    img.src = src;
                    img.setAttribute('data-original-src', src);
                    img.crossOrigin = "anonymous";

                    el.appendChild(img);
                    overlay.appendChild(el);
                } else if (layer.type === 'text' && layer.text && !elId) {
                     // Check if this static text looks like a placeholder we already handled
                     const txt = (layer.text || '').toLowerCase();
                     const isPlaceholder = config.layers.some(l => 
                        l !== layer && 
                        (l.name === 'email' || l.name === 'website' || l.name === 'mobile' || l.name === 'name') && 
                        (l.text || '').toLowerCase() === txt
                     );

                     if (!isPlaceholder) {
                        // Static frame text layer (no drag/select)
                        el = document.createElement('div');
                        el.className = 'frame-extra-layer text-element';
                        el.setAttribute('data-label', layer.name || 'Text');
                        el.innerText = layer.text;
                        area.appendChild(el);
                     }
                }

                if (el) {
                    const layerX = (layer.x || 0) * scale;
                    const layerY = ((layer.y || 0) * scale) + yOffset;
                    const layerW = (layer.width || 0) * scale;
                    const layerH = (layer.height || 0) * scale;

                    el.style.position = 'absolute';
                    el.style.left = layerX + 'px';
                    el.style.top = layerY + 'px';
                    el.style.width = layerW + 'px';
                    el.style.height = layerH + 'px';
                    el.style.zIndex = idx + 50; 
                    el.style.margin = '0';
                    el.style.padding = '0';

                    // Frame-extra-layers are static decorations — no interaction
                    // Business elements (elId set) are interactive — allow drag
                    const isFrameLayer = el.classList.contains('frame-extra-layer') && !elId;
                    el.style.pointerEvents = isFrameLayer ? 'none' : 'auto';

                    if (layer.type === 'text' || elId) {
                        const styleColor = layer.color ? layer.color.replace('0x', '#') : '#000000';
                        const styleFontInternal = fontMap[layer.font] || 'sans-serif';
                        const styleSize = (layer.size || 20) * scale;

                        const isBold = (layer.weight === 'bold' || layer.font?.toLowerCase().includes('bold'));
                        const isItalic = layer.font?.toLowerCase().includes('italic');

                        const applyStyles = (target) => {
                            target.style.setProperty('color', styleColor, 'important');
                            target.style.setProperty('font-family', `"${styleFontInternal}", sans-serif`, 'important');
                            target.style.setProperty('font-size', styleSize + 'px', 'important');
                            target.style.setProperty('font-weight', isBold ? '700' : '400', 'important');
                            target.style.setProperty('font-style', isItalic ? 'italic' : 'normal', 'important');
                            target.style.setProperty('line-height', '1.1', 'important');
                            target.style.setProperty('letter-spacing', '0', 'important');
                            target.style.setProperty('text-shadow', 'none', 'important');
                            target.style.setProperty('background', 'transparent', 'important');
                            target.style.setProperty('white-space', (elId === 'preview-name' || elId === 'el-phone' || elId === 'el-email' || elId === 'el-website') ? 'nowrap' : 'pre-wrap', 'important');
                            target.style.textAlign = layer.justification || 'left';
                        };

                        applyStyles(el);
                        const span = el.querySelector('span');
                        if (span) {
                            applyStyles(span);
                            span.style.display = 'inline-block';
                        }

                        el.style.setProperty('display', 'flex', 'important');
                        el.style.setProperty('align-items', 'center', 'important');
                        if (layer.justification === 'center') el.style.setProperty('justify-content', 'center', 'important');
                        else if (layer.justification === 'right') el.style.setProperty('justify-content', 'flex-end', 'important');
                        else el.style.setProperty('justify-content', 'flex-start', 'important');
                    }

                    if (elId === 'logo-shell') {
                        const img = el.querySelector('img');
                        if (img) {
                             img.style.width = '100%';
                             img.style.height = '100%';
                             img.style.objectFit = 'contain';
                        }
                    }

                    // Only attach drag/handles to business elements, NOT frame layers
                    if (elId) {
                        makeDraggable(el);
                        attachHandles(el);
                    }
                }
            });

            // FORCEFULLY SHOW primary business details if not matched by frame
            const primaryIds = ['preview-name', 'logo-shell', 'el-phone'];
            primaryIds.forEach(id => {
                if (!matchedIds.has(id)) {
                    const el = document.getElementById(id);
                    if (el) {
                        // Reset to default styling
                        if (id !== 'preview-name' && id !== 'logo-shell') {
                            el.classList.add('info-capsule');
                        }
                        el.style.cssText = ''; 
                        el.style.zIndex = "2000"; // Top of stack

                        const isPrimary = (id === 'preview-name' || id === 'el-phone');
                        const displayVal = isPrimary ? (id === 'preview-name' ? 'block' : 'flex') : 'none';
                        el.style.setProperty('display', displayVal, 'important');

                        // Restore default positions because cssText cleared them
                        if (id === 'preview-name') {
                            el.style.left = '10%';
                            el.style.bottom = '15%';
                        } else if (id === 'el-phone') {
                            el.style.left = '10%';
                            el.style.bottom = '8%';
                        } else if (id === 'logo-shell') {
                            el.style.left = '10%';
                            el.style.top = '10%';
                        }

                        const icon = el.querySelector('.info-icon-wrapper');
                        if (icon) icon.style.display = 'flex';

                        const span = el.querySelector('span');
                        if (span) span.style.cssText = '';

                        // Important: Make interactive
                        makeDraggable(el);
                        attachHandles(el);
                    }
                }
            });

            // Re-render lucide icons for any newly added elements with icons
            lucide.createIcons();

            } finally {
                // 6. FINALLY REVEAL the canvas once everything is positioned
                area.style.opacity = '1';
            }
        }

        async function loadFont(originalName, internalName, fontsBase) {
            if (!originalName || originalName === 'sans-serif') return;
            const formats = ['.ttf', '.otf', '.woff'];
            const ts = new Date().getTime();
            for (const ext of formats) {
                try {
                    // Use encodeURIComponent for filenames with spaces/dots
                    const safeName = encodeURIComponent(originalName);
                    const fontUrl = `${fontsBase}${safeName}${ext}?v=${ts}`;
                    const font = new FontFace(internalName, `url("${fontUrl}")`);
                    const loaded = await font.load();
                    document.fonts.add(loaded);
                    return;
                } catch (e) {
                    continue; 
                }
            }
        }

        function changeDesign(url, element) {
            clearActive();
            document.getElementById('basePoster').src = url;
            document.querySelectorAll('.frame-item').forEach(i => i.classList.remove('selected'));
            element.classList.add('selected');
        }

        // Legacy changeColor removed


        function changeBGColor(color) {
            clearActive();
            document.getElementById('capture-area').style.backgroundColor = color;
        }

        function toggleFontList() {
            const panel = document.getElementById('fontPanel');
            panel.style.display = panel.style.display === 'block' ? 'none' : 'block';
        }

        function selectFont(font) {
            if (activeElement && activeElement.classList.contains('text-element')) {
                activeElement.style.fontFamily = font;
            } else {
                alert('Please select a text element first');
            }
            toggleFontList();
        }

        let editingTextElement = null;

        function addText() {
            clearActive();
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
                editingTextElement.innerText = txt;
                closeTextModal();
                return;
            }

            const div = document.createElement('div');
            div.className = 'draggable text-element';
            div.style.left = '50%';
            div.style.top = '50%';
            div.style.fontSize = '24px';
            div.style.fontWeight = '900';
            div.style.color = '#000000';
            div.style.position = 'absolute';
            div.style.transform = 'translate(-50%, -50%)';
            div.innerText = txt;
            attachHandles(div);
            document.getElementById('capture-area').appendChild(div);
            if (window.lucide) window.lucide.createIcons();
            makeDraggable(div);
            setActive(div);
            closeTextModal();
        }

        function attachHandles(el) {
            if (el.querySelector('.selection-overlay')) return;

            const overlay = document.createElement('div');
            overlay.className = 'selection-overlay';

            // Corner Circles
            const corners = ['tl', 'tr', 'bl', 'br'];
            corners.forEach(pos => {
                const node = document.createElement('div');
                node.className = `node node-corner node-${pos}`;
                setupScaling(node, el, pos);
                overlay.appendChild(node);
            });

            // Side Pills (Only for width adjustment on stickers/containers)
            if (el.classList.contains('sticker-container') || el.classList.contains('logo-container')) {
                const sides = [['ml', 'side-v'], ['mr', 'side-v']];
                sides.forEach(([pos, type]) => {
                    const node = document.createElement('div');
                    node.className = `node node-${type} node-${pos}`;
                    setupScaling(node, el, pos);
                    overlay.appendChild(node);
                });
            }

            // Rotation Handle below
            const rotRow = document.createElement('div');
            rotRow.className = 'rotation-row';

            const rot = document.createElement('div');
            rot.className = 'rot-btn';
            rot.innerHTML = '<i data-lucide="rotate-cw" style="width:18px;height:18px;color:#475569;"></i>';
            setupRotation(rot, el);

            rotRow.appendChild(rot);
            overlay.appendChild(rotRow);

            overlay.appendChild(rotRow);

            // Move Handle below
            const moveRow = document.createElement('div');
            moveRow.className = 'position-row';

            const moveBtn = document.createElement('div');
            moveBtn.className = 'move-btn';
            moveBtn.innerHTML = '<i data-lucide="move" style="width:18px;height:18px;color:#ffffff;"></i>';

            // Explicitly attach drag behavior to the move handle
            function initMoveHandleDrag(handle, parentEl) {
                let mp3 = 0, mp4 = 0;

                function moveStart(e) {
                    e.stopPropagation();
                    // CRITICAL: Prevent default here blocks native HTML5 dragging on desktop
                    if (e.cancelable) e.preventDefault();

                    // Normalize parent position before dragging
                    const transform = parentEl.style.transform || '';
                    if (transform.includes('translate(-50%')) {
                        const par = parentEl.offsetParent || parentEl.parentElement;
                        const parRect = par.getBoundingClientRect();
                        const elRect = parentEl.getBoundingClientRect();
                        parentEl.style.left = (elRect.left - parRect.left) + 'px';
                        parentEl.style.top = (elRect.top - parRect.top) + 'px';
                        parentEl.style.transform = transform
                            .replace(/translate\(-50%\s*,\s*-50%\)/g, '')
                            .replace(/translate\(-50%\)/g, '')
                            .trim() || '';
                    }
                    parentEl.style.bottom = 'auto';
                    parentEl.style.right = 'auto';

                    setActive(parentEl);
                    mp3 = e.type.includes('touch') ? e.touches[0].clientX : e.clientX;
                    mp4 = e.type.includes('touch') ? e.touches[0].clientY : e.clientY;

                    function moveDragging(ev) {
                        if (ev.type === 'touchmove' && ev.cancelable) ev.preventDefault();
                        let cx = ev.type.includes('touch') ? ev.touches[0].clientX : ev.clientX;
                        let cy = ev.type.includes('touch') ? ev.touches[0].clientY : ev.clientY;
                        if (cx === undefined || cy === undefined) return;

                        if (!parentEl.classList.contains('dragging')) parentEl.classList.add('dragging');

                        const dx = mp3 - cx;
                        const dy = mp4 - cy;
                        mp3 = cx;
                        mp4 = cy;

                        parentEl.style.top = Math.round(parentEl.offsetTop - dy) + 'px';
                        parentEl.style.left = Math.round(parentEl.offsetLeft - dx) + 'px';
                        
                        if (typeof showGuides === 'function') showGuides(parentEl);
                        if (typeof updateDeletePosition === 'function') updateDeletePosition();
                    }

                    function moveEnd() {
                        parentEl.classList.remove('dragging');
                        if (typeof hideGuides === 'function') hideGuides();
                        document.removeEventListener('mousemove', moveDragging);
                        document.removeEventListener('touchmove', moveDragging);
                        document.removeEventListener('mouseup', moveEnd);
                        document.removeEventListener('touchend', moveEnd);
                    }

                    document.addEventListener('mousemove', moveDragging);
                    document.addEventListener('touchmove', moveDragging, { passive: false });
                    document.addEventListener('mouseup', moveEnd);
                    document.addEventListener('touchend', moveEnd);
                }

                handle.addEventListener('mousedown', moveStart);
                handle.addEventListener('touchstart', moveStart, { passive: false });
            }

            initMoveHandleDrag(moveBtn, el);
            moveRow.appendChild(moveBtn);
            overlay.appendChild(moveRow);

            el.appendChild(overlay);
            if (window.lucide) window.lucide.createIcons();
        }

        // Helper functions for Guides
        function showGuides(el) {
            const area = document.getElementById('capture-area');
            const vGuide = document.getElementById('v-guide');
            const hGuide = document.getElementById('h-guide');
            if (!area || !vGuide || !hGuide) return;

            const areaW = area.offsetWidth;
            const areaH = area.offsetHeight;
            const centerX = areaW / 2;
            const centerY = areaH / 2;
            
            const elCenterX = el.offsetLeft + (el.offsetWidth / 2);
            const elCenterY = el.offsetTop + (el.offsetHeight / 2);
            const thresh = 5;

            if (Math.abs(elCenterX - centerX) < thresh) {
                el.style.left = Math.round(centerX - el.offsetWidth / 2) + 'px';
                vGuide.style.display = 'block';
            } else vGuide.style.display = 'none';

            if (Math.abs(elCenterY - centerY) < thresh) {
                el.style.top = Math.round(centerY - el.offsetHeight / 2) + 'px';
                hGuide.style.display = 'block';
            } else hGuide.style.display = 'none';
        }

        function hideGuides() {
            const vGuide = document.getElementById('v-guide');
            const hGuide = document.getElementById('h-guide');
            if (vGuide) vGuide.style.display = 'none';
            if (hGuide) hGuide.style.display = 'none';
        }

        function setupRotation(handle, el) {
            handle.addEventListener('mousedown', startRotate);
            handle.addEventListener('touchstart', startRotate, { passive: false });

            function startRotate(e) {
                if (e.type === 'touchstart' && e.cancelable) e.preventDefault();
                e.stopPropagation();
                
                const rect = el.getBoundingClientRect();
                const centerX = rect.left + rect.width / 2;
                const centerY = rect.top + rect.height / 2;

                function doRotate(ev) {
                    if (ev.type === 'touchmove' && ev.cancelable) ev.preventDefault();
                    
                    const cx = (ev.clientX !== undefined) ? ev.clientX : (ev.touches && ev.touches[0] ? ev.touches[0].clientX : 0);
                    const cy = (ev.clientY !== undefined) ? ev.clientY : (ev.touches && ev.touches[0] ? ev.touches[0].clientY : 0);
                    
                    const angle = Math.atan2(cy - centerY, cx - centerX);
                    const degree = angle * (180 / Math.PI) + 90;
                    el.style.transform = `translate(-50%, -50%) rotate(${degree}deg)`;
                }

                function stopRotate() {
                    document.removeEventListener('mousemove', doRotate);
                    document.removeEventListener('touchmove', doRotate);
                    document.removeEventListener('mouseup', stopRotate);
                    document.removeEventListener('touchend', stopRotate);
                }
                
                document.addEventListener('mousemove', doRotate);
                document.addEventListener('touchmove', doRotate, { passive: false });
                document.addEventListener('mouseup', stopRotate);
                document.addEventListener('touchend', stopRotate);
            }
        }

        function setupScaling(handle, el, pos) {
            handle.addEventListener('mousedown', startScale);
            handle.addEventListener('touchstart', startScale, { passive: false });

            function startScale(e) {
                if (e.type === 'touchstart' && e.cancelable) e.preventDefault();
                e.stopPropagation();
                
                const startX = (e.clientX !== undefined) ? e.clientX : (e.touches && e.touches[0] ? e.touches[0].clientX : 0);
                const startY = (e.clientY !== undefined) ? e.clientY : (e.touches && e.touches[0] ? e.touches[0].clientY : 0);
                
                const startW = el.offsetWidth;
                const startH = el.offsetHeight;
                const startFontSize = parseFloat(window.getComputedStyle(el).fontSize);

                function doScale(ev) {
                    if (ev.type === 'touchmove' && ev.cancelable) ev.preventDefault();
                    
                    const cx = (ev.clientX !== undefined) ? ev.clientX : (ev.touches && ev.touches[0] ? ev.touches[0].clientX : 0);
                    const cy = (ev.clientY !== undefined) ? ev.clientY : (ev.touches && ev.touches[0] ? ev.touches[0].clientY : 0);

                    let delta;
                    if (pos === 'br' || pos === 'bl') delta = cy - startY;
                    else delta = startY - cy;

                    if (pos === 'tr' || pos === 'br') delta = cx - startX;
                    else delta = startX - cx;

                    // Proportional factor based on initial width
                    const scaleFactor = (startW + delta) / startW;
                    const newW = startW * scaleFactor;

                    if (newW < 20) return;

                    if (el.classList.contains('text-element')) {
                        el.style.fontSize = (startFontSize * scaleFactor) + 'px';
                    } else {
                        const ratio = startW / startH;
                        el.style.width = newW + 'px';
                        el.style.height = (newW / ratio) + 'px';
                    }
                }

                function stopScale() {
                    document.removeEventListener('mousemove', doScale);
                    document.removeEventListener('touchmove', doScale);
                    document.removeEventListener('mouseup', stopScale);
                    document.removeEventListener('touchend', stopScale);
                }
                
                document.addEventListener('mousemove', doScale);
                document.addEventListener('touchmove', doScale, { passive: false });
                document.addEventListener('mouseup', stopScale);
                document.addEventListener('touchend', stopScale);
            }
        }

        // Sticker Logic
        const allStickers = @json($stickers);

        function addSticker() {
            clearActive();
            document.getElementById('stickerModal').style.display = 'flex';
            // Show first category by default
            const firstCat = document.querySelector('.sticker-cat-btn');
            if (firstCat) firstCat.click();
        }

        function closeStickerModal(e) {
            document.getElementById('stickerModal').style.display = 'none';
        }

        function filterStickers(catId, btn) {
            // Update UI
            document.querySelectorAll('.sticker-cat-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            // Filter
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
            const container = document.createElement('div');
            container.className = 'draggable sticker-container';
            container.setAttribute('data-label', 'Sticker');
            container.style.width = '120px';
            container.style.left = '50%';
            container.style.top = '50%';
            container.style.position = 'absolute';
            container.style.transform = 'translate(-50%, -50%)';

            const img = document.createElement('img');
            img.src = src;
            img.style.width = '100%';
            img.style.display = 'block';

            container.appendChild(img);
            attachHandles(container);
            document.getElementById('capture-area').appendChild(container);
            if (window.lucide) window.lucide.createIcons();
            makeDraggable(container);
            setActive(container);
            closeStickerModal();
        }

        function uploadLogo(input) {
            clearActive();
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = e => {
                    const container = document.createElement('div');
                    container.className = 'draggable logo-container';
                    container.setAttribute('data-label', 'Logo');
                    container.style.width = '120px';
                    container.style.left = '50%';
                    container.style.top = '50%';
                    container.style.position = 'absolute';
                    container.style.transform = 'translate(-50%, -50%)';

                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.setAttribute('data-original-src', e.target.result);
                    img.style.width = '100%';
                    img.style.display = 'block';

                    container.appendChild(img);
                    attachHandles(container);
                    document.getElementById('capture-area').appendChild(container);
                    if (window.lucide) window.lucide.createIcons();
                    makeDraggable(container);
                    setActive(container);
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function clearActive() {
            if (activeElement) {
                activeElement.classList.remove('selected');
                activeElement = null;
            }
            const bar = document.getElementById('contextualBar');
            if (bar) bar.style.display = 'none';
            closeAllPanels();
        }

        function setActive(el) {
          try {
            if (!el) {
                clearActive();
                return;
            }
            if (activeElement && activeElement !== el) {
                activeElement.classList.remove('selected');
            }
            activeElement = el;
            activeElement.classList.add('selected');

            // Show Canva Bar
            const bar = document.getElementById('contextualBar');
            if (bar) bar.style.display = 'flex';

            // Auto close other tool panels when selecting a new element
            closeAllPanels();

            // Conditional visibility for icons vs others
            const isIcon = el.classList.contains('icon-container');
            const colorTool = document.getElementById('contextualColorTool');
            const layersToolEl = document.getElementById('layersTool');
            const delTool = document.getElementById('deleteTool');

            if (isIcon) {
                if (colorTool) colorTool.style.display = 'none';
                if (layersToolEl) layersToolEl.style.display = 'none';
            } else {
                if (colorTool) colorTool.style.display = 'flex';
                if (layersToolEl) layersToolEl.style.display = 'flex';
            }
            if (delTool) delTool.style.display = 'flex';

            const isText = el.classList.contains('text-element') || el.id === 'preview-name' || el.id === 'el-phone' || el.id === 'el-email' || el.id === 'el-address' || el.id === 'el-website';

            // Null-safe sub-panel hide
            const fontSizeCtl = document.getElementById('fontSizeControl');
            if (fontSizeCtl) fontSizeCtl.style.display = 'none';

            const editEl = document.getElementById('editTool');
            const fontEl = document.getElementById('fontTool');
            const sizeEl = document.getElementById('sizeTool');
            const boldEl = document.getElementById('boldTool');
            const italicEl = document.getElementById('italicTool');

            if (editEl) editEl.style.display = isText ? 'flex' : 'none';
            if (fontEl) fontEl.style.display = isText ? 'flex' : 'none';
            if (sizeEl) sizeEl.style.display = isText ? 'flex' : 'none';
            if (boldEl) boldEl.style.display = isText ? 'flex' : 'none';
            if (italicEl) italicEl.style.display = isText ? 'flex' : 'none';
            if (layersToolEl) layersToolEl.style.display = 'flex';
            if (delTool) delTool.style.display = 'flex';

            if (isText) {
                const fs = parseFloat(window.getComputedStyle(el).fontSize);
                const fss = document.getElementById('fontSizeSlider');
                const fsd = document.getElementById('fontSizeDisplay');
                if (fss) fss.value = fs;
                if (fsd) fsd.innerText = Math.round(fs);
            }

            updateToolbarUI();
          } catch(err) { console.warn('setActive error (non-fatal):', err); }
        }

        function toggleSizePanel() {
            const panel = document.getElementById('fontSizeControl');
            panel.style.display = (panel.style.display === 'block') ? 'none' : 'block';
        }

        function changeFontSize(val) {
            if (activeElement) {
                activeElement.style.setProperty('font-size', val + 'px', 'important');
                const span = activeElement.querySelector('span');
                if (span) span.style.setProperty('font-size', val + 'px', 'important');
                document.getElementById('fontSizeDisplay').innerText = val;
            }
        }

        function moveElement(dir) {
            if (!activeElement) return;
            const z = parseInt(window.getComputedStyle(activeElement).zIndex) || 100;
            activeElement.style.zIndex = dir === 'forward' ? z + 1 : Math.max(1, z - 1);
        }

        function triggerEdit() {
            if (activeElement) {
                editingTextElement = activeElement;
                const modal = document.getElementById('textModal');
                modal.querySelector('.modal-header').innerText = 'Edit Text';
                modal.style.display = 'flex';
                document.getElementById('modalTextArea').value = activeElement.innerText;
                document.getElementById('modalTextArea').focus();
                closeAllPanels();
            }
        }

        function toggleBold() {
            if (activeElement) {
                const isBold = activeElement.style.fontWeight === 'bold' || activeElement.style.fontWeight === '700' || activeElement.style.fontWeight === '900';
                activeElement.style.fontWeight = isBold ? '400' : '900';
            }
        }

        function toggleItalic() {
            if (activeElement) {
                const isItalic = activeElement.style.fontStyle === 'italic';
                activeElement.style.fontStyle = isItalic ? 'normal' : 'italic';
            }
        }

        function cycleAlign() {
            if (activeElement) {
                const current = activeElement.style.textAlign || 'left';
                const next = current === 'left' ? 'center' : (current === 'center' ? 'right' : 'left');
                activeElement.style.textAlign = next;

                const icon = document.getElementById('alignIconCanvas');
                icon.innerHTML = `<i data-lucide="align-${next}"></i>`;
                lucide.createIcons();
            }
        }

        function updateToolbarUI() {
            if (!activeElement) return;
            const color = activeElement.style.color || '#000000';
            const cs = document.getElementById('colorStroke');
            if (cs) cs.style.borderColor = color;
        }

        function removeActiveElement() {
            if (activeElement) {
                const permanentIds = ['logo-shell', 'preview-name', 'el-phone', 'el-email', 'el-address', 'el-website'];
                if (permanentIds.includes(activeElement.id)) {
                    activeElement.style.display = 'none';
                } else {
                    activeElement.remove();
                }
                clearActive();
            }
        }

        // Handle click outside to deselect
        document.getElementById('capture-area').addEventListener('mousedown', function(e) {
            // If clicking the canvas background itself (not a draggable)
            const isCanvasBackground = e.target.id === 'capture-area' || 
                                       e.target.id === 'basePoster' || 
                                       e.target.classList.contains('frame-overlay-wrapper') ||
                                       e.target.classList.contains('layer-img');
            if (isCanvasBackground) {
                clearActive();
            }
        });

        document.getElementById('capture-area').addEventListener('touchstart', function(e) {
            const isCanvasBackground = e.target.id === 'capture-area' || 
                                       e.target.id === 'basePoster' || 
                                       e.target.classList.contains('frame-overlay-wrapper') ||
                                       e.target.classList.contains('layer-img');
            if (isCanvasBackground) {
                clearActive();
            }
        }, { passive: true });

        function closeAllPanels() {
            const panels = ['frameColorPanel', 'fontPanel', 'fontSizeControl'];
            panels.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.style.display = 'none';
            });
            // If frame color panel was open, it might have hidden the grid
            const framesGrid = document.querySelector('.frames-grid');
            if (framesGrid && framesGrid.style.display === 'none') {
                framesGrid.style.display = '';
            }
            document.querySelector('.editor-container').classList.remove('panel-open');
        }

        // Global click listener for closing panels when clicking outside
        document.addEventListener('mousedown', function(e) {
            const panels = ['frameColorPanel', 'fontPanel', 'fontSizeControl'];
            const toggleButtons = ['.toggle-btn', '#editTool', '#fontTool', '#sizeTool'];

            // If clicking a panel or a toggle button, don't auto-close (let those handlers work)
            let isClickInsidePanel = panels.some(id => {
                const el = document.getElementById(id);
                return el && el.contains(e.target);
            });

            let isClickOnToggle = toggleButtons.some(selector => {
                const el = document.querySelector(selector);
                return el && (el === e.target || el.contains(e.target));
            });

            // If we didn't click a panel, a toggle button, or the contextual bar area
            const contextualBar = document.getElementById('contextualBar');
            const isInsideContextual = contextualBar && contextualBar.contains(e.target);

            if (!isClickInsidePanel && !isClickOnToggle && !isInsideContextual) {
                // If clicking outside and NOT on the capture area (since capture area has its own logic)
                const area = document.getElementById('capture-area');
                if (area && !area.contains(e.target)) {
                    closeAllPanels();
                }
            }
        });

        /**
         * ──────────────────────────────────────────────────────────────
         *  UNIVERSAL DRAG ENGINE  — Canva-quality free-form dragging
         *  ● Works on mouse AND touch (mobile-first)
         *  ● Resolves ALL positioning modes before drag starts:
         *      bottom → top, right → left, translate(-50%) → px
         *  ● Dead-zone threshold prevents unintentional micro-drags
         *  ● Smart alignment guides with magnetic snap
         *  ● Visual lift / shadow while dragging
         * ──────────────────────────────────────────────────────────────
         */
        function makeDraggable(el) {
            // Guard: prevent duplicate listener attachment
            if (el._dragAttached) return;
            el._dragAttached = true;

            el.addEventListener('mousedown', onPointerDown);
            el.addEventListener('touchstart', onPointerDown, { passive: false });

            /* ── Helpers ─────────────────────────────────────── */
            function getXY(e) {
                const t = (e.touches && e.touches[0]) || e;
                return { x: t.clientX, y: t.clientY };
            }

            /* ── Pointer Down ────────────────────────────────── */
            function onPointerDown(e) {
                // Skip if user is interacting with selection handles
                if (e.target.classList.contains('node') ||
                    e.target.classList.contains('rot-btn') ||
                    e.target.classList.contains('move-btn') ||
                    e.target.closest('.rotation-row') ||
                    e.target.closest('.position-row')) return;

                // Don't start drag if in inline editing mode
                if (el.classList.contains('inline-editing')) return;

                // Prevent scrolling on mobile
                if (e.type === 'touchstart' && e.cancelable) e.preventDefault();
                e.stopPropagation();

                // Select this element (wrapped in try-catch so a crash
                // in setActive can never prevent drag listeners from attaching)
                try { setActive(el); } catch(err) { console.warn('setActive error:', err); }

                // ── NORMALIZE POSITION ──────────────────────────
                // Convert ANY CSS position mode (%, bottom, right, translate)
                // into concrete top/left px BEFORE capturing the origin.
                const parent = el.offsetParent || el.parentElement;
                if (parent) {
                    const pRect = parent.getBoundingClientRect();
                    const eRect = el.getBoundingClientRect();
                    // Subtract parent's border so left/top are relative to padding-box
                    const borderL = parseInt(getComputedStyle(parent).borderLeftWidth) || 0;
                    const borderT = parseInt(getComputedStyle(parent).borderTopWidth)  || 0;
                    el.style.left   = (eRect.left - pRect.left - borderL) + 'px';
                    el.style.top    = (eRect.top  - pRect.top  - borderT) + 'px';
                    el.style.bottom = 'auto';
                    el.style.right  = 'auto';
                    // Strip centering translates but preserve rotation
                    const tf = el.style.transform || '';
                    if (tf.includes('translate')) {
                        el.style.transform = tf.replace(/translate\([^)]*\)/g, '').trim() || '';
                    }
                }

                // Capture origin AFTER normalization
                const ptr       = getXY(e);
                const startX    = ptr.x;
                const startY    = ptr.y;
                const originL   = el.offsetLeft;
                const originT   = el.offsetTop;
                let   dragging  = false;
                const DEAD_ZONE = 3;

                /* ── Pointer Move ─────────────────────────────── */
                function onMove(ev) {
                    if (ev.type === 'touchmove' && ev.cancelable) ev.preventDefault();
                    const p  = getXY(ev);
                    const dx = p.x - startX;
                    const dy = p.y - startY;

                    // Dead-zone: only start real drag after threshold
                    if (!dragging) {
                        if (Math.abs(dx) < DEAD_ZONE && Math.abs(dy) < DEAD_ZONE) return;
                        dragging = true;
                        el.classList.add('dragging');
                    }

                    let newL = originL + dx;
                    let newT = originT + dy;

                    // ── Smart Alignment Guides ──
                    const area   = document.getElementById('capture-area');
                    const vGuide = document.getElementById('v-guide');
                    const hGuide = document.getElementById('h-guide');
                    if (area && vGuide && hGuide) {
                        const aw = area.offsetWidth, ah = area.offsetHeight;
                        const mx = aw / 2, my = ah / 2;
                        const ex = newL + el.offsetWidth / 2;
                        const ey = newT + el.offsetHeight / 2;
                        const S  = 6;
                        // Center snap
                        if (Math.abs(ex - mx) < S) { newL = Math.round(mx - el.offsetWidth / 2);  vGuide.style.display = 'block'; } else { vGuide.style.display = 'none'; }
                        if (Math.abs(ey - my) < S) { newT = Math.round(my - el.offsetHeight / 2); hGuide.style.display = 'block'; } else { hGuide.style.display = 'none'; }
                        // Edge snap
                        if (Math.abs(newL) < S) newL = 0;
                        if (Math.abs(newT) < S) newT = 0;
                        if (Math.abs(newL + el.offsetWidth  - aw) < S) newL = aw - el.offsetWidth;
                        if (Math.abs(newT + el.offsetHeight - ah) < S) newT = ah - el.offsetHeight;
                    }

                    el.style.left = Math.round(newL) + 'px';
                    el.style.top  = Math.round(newT) + 'px';
                }

                /* ── Pointer Up ───────────────────────────────── */
                function onUp() {
                    el.classList.remove('dragging');
                    const vG = document.getElementById('v-guide');
                    const hG = document.getElementById('h-guide');
                    if (vG) vG.style.display = 'none';
                    if (hG) hG.style.display = 'none';
                    document.removeEventListener('mousemove',   onMove);
                    document.removeEventListener('touchmove',   onMove);
                    document.removeEventListener('mouseup',     onUp);
                    document.removeEventListener('touchend',    onUp);
                    document.removeEventListener('touchcancel', onUp);
                }

                // CRITICAL: These MUST always execute, regardless of setActive errors
                document.addEventListener('mousemove',   onMove);
                document.addEventListener('touchmove',   onMove, { passive: false });
                document.addEventListener('mouseup',     onUp);
                document.addEventListener('touchend',    onUp);
                document.addEventListener('touchcancel', onUp);
            }
        }

        document.querySelectorAll('.draggable').forEach(el => {
            makeDraggable(el);
            attachHandles(el);
        });

        async function exportImage() {
            const wasActive = activeElement;
            clearActive();

            // Wait for any pending fonts to load
            if (document.fonts && document.fonts.ready) {
                await document.fonts.ready;
            }

            // Give extra time for layout and images
            await new Promise(r => setTimeout(r, 500));

            const area = document.getElementById('capture-area');
            const rect = area.getBoundingClientRect();

            html2canvas(area, { 
                useCORS: true, 
                scale: 3, 
                logging: false,
                backgroundColor: null,
                scrollX: 0,
                scrollY: -window.scrollY,
                width: rect.width,
                height: rect.height,
                onclone: (doc) => {
                    const clonedArea = doc.getElementById('capture-area');
                    if (clonedArea) {
                        // CRITICAL: Remove editor-only UI styles for a clean export
                        clonedArea.style.borderRadius = '0';
                        clonedArea.style.boxShadow = 'none';
                        clonedArea.style.border = 'none';
                        // Ensure dimensions are exact
                        clonedArea.style.width = rect.width + 'px';
                        clonedArea.style.height = rect.height + 'px';
                    }

                    // Force hide selection UI
                    doc.querySelectorAll('.node, .selection-overlay, .rotation-row').forEach(n => n.style.display = 'none');

                    // Lock positions of all layers
                    const originalLayers = area.querySelectorAll('.frame-extra-layer, .draggable, #frameOverlay, #basePoster');
                    const clonedLayers = doc.querySelectorAll('.frame-extra-layer, .draggable, #frameOverlay, #basePoster');

                    clonedLayers.forEach((cloned, idx) => {
                        const original = originalLayers[idx];
                        if (original && cloned) {
                            const origRect = original.getBoundingClientRect();
                            const areaRect = rect;

                            cloned.style.position = 'absolute';
                            cloned.style.left = (origRect.left - areaRect.left) + 'px';
                            cloned.style.top = (origRect.top - areaRect.top) + 'px';
                            cloned.style.width = origRect.width + 'px';
                            cloned.style.height = origRect.height + 'px';
                            cloned.style.margin = '0';
                            cloned.style.transform = 'none';
                        }
                    });
                }
            }).then(canvas => {
                const link = document.createElement('a');
                link.download = 'festive_design_highres.png';
                // Higher quality PNG
                link.href = canvas.toDataURL('image/png', 1.0);
                link.click();
                if (wasActive) setActive(wasActive);
            }).catch(err => {
                console.error("Capture error:", err);
                if (wasActive) setActive(wasActive);
            });
        }

        function changeColor(val) {
            if (activeElement) {
                // Strictly for text elements now
                if (activeElement.classList.contains('text-element') || 
                    ['preview-name', 'el-phone', 'el-email', 'el-address', 'el-website'].includes(activeElement.id)) {

                    activeElement.style.setProperty('color', val, 'important');
                    const span = activeElement.querySelector('span');
                    if (span) span.style.setProperty('color', val, 'important');
                    updateToolbarUI();
                } else {
                     // Feedback for user
                     // alert('This tool is for Text Color only. Use the Frame Color option above for frames.');
                }
            }
        }

        function changeFrameColor(val) {
             // Legacy or unused now
        }

        let selectedLayerIndex = 0;
        let initialLayerStates = [];

        function openFrameColorPanel() {
            const panel = document.getElementById('frameColorPanel');
            const layers = document.querySelectorAll('#frameOverlay .layer-img');

            if (layers.length === 0) return;

            initialLayerStates = [];
            layers.forEach((img, idx) => {
                initialLayerStates.push({
                    src: img.src,
                    originalSrc: img.getAttribute('data-original-src'),
                    color: img.getAttribute('data-color')
                });
            });

            if (panel.style.display === 'block') {
                closeFrameColorPanel();
                return;
            }

            // Hide Frame Grid, Show Panel
            const framesGrid = document.querySelector('.frames-grid');
            if (framesGrid) framesGrid.style.display = 'none';

            panel.style.display = 'block';

            // Highlight result
            renderLayerBubbles();

            // Reset History on Open
            colorHistory = [];
            historyIndex = -1;
            updateHistoryButtons();
        }

        let colorHistory = [];
        let historyIndex = -1;

        function addToHistory(layerIndex, color) {
            // If we are in middle of history, cut off future
            if (historyIndex < colorHistory.length - 1) {
                colorHistory = colorHistory.slice(0, historyIndex + 1);
            }

            colorHistory.push({
                layerIndex: layerIndex,
                color: color
            });
            historyIndex++;
            updateHistoryButtons();
        }

        function undoFrameColor() {
            if (historyIndex >= 0) {
                // To undo, we need the state BEFORE this action.
                // But since we only track changes, undoing 'Red' means going back to... what?
                // Simplification: We need to snapshot state.
                // Better: Just apply the PREVIOUS color for that layer?
                // Actually, let's just reverse the action.
                // If history is [A, B, C]. Current is C.
                // Undo means go to B.

                // Wait, standard undo logic:
                // Current State is implicit. 
                // Let's go back one step in history stack.
                // BUT we need to know what the color WAS before.

                // Revised logic: Store {layerIndex, newColor, oldColor}
                // But getting old color is tricky if we don't store it.
                // Let's implement full state tracking or just "Reverse" logic.

                // Simple implementation: 
                // We will rely on `colorHistory` containing the state at step N.
                // We need initial state.
                const layers = document.querySelectorAll('#frameOverlay .layer-img');

                // Let's simply track the action: {layerIndex, prevColor, newColor}
                // When 'applyPaletteColor' is called, we record prevColor.

                const action = colorHistory[historyIndex];
                if (action) {
                     // Restore prevColor
                     const layer = layers[action.layerIndex];
                     if(layer) {
                         if(action.prevColor) {
                             recolorImage(layer, action.prevColor);
                         } else {
                             // Restore original image (no color)
                             const orig = layer.getAttribute('data-original-src');
                             layer.src = orig;
                             layer.removeAttribute('data-color');
                         }
                         renderLayerBubbles();
                     }
                     historyIndex--;
                     updateHistoryButtons();
                }
            }
        }

        function redoFrameColor() {
             if (historyIndex < colorHistory.length - 1) {
                 historyIndex++;
                 const action = colorHistory[historyIndex];
                 const layers = document.querySelectorAll('#frameOverlay .layer-img');
                 const layer = layers[action.layerIndex];
                 if(layer) {
                     recolorImage(layer, action.newColor);
                     renderLayerBubbles();
                 }
                 updateHistoryButtons();
             }
        }

        function updateHistoryButtons() {
            document.getElementById('undoColorBtn').disabled = (historyIndex < 0);
            document.getElementById('redoColorBtn').disabled = (historyIndex >= colorHistory.length - 1);
            if(window.lucide) window.lucide.createIcons();
        }

        function closeFrameColorPanel() {
            document.getElementById('frameColorPanel').style.display = 'none';
            // Restore Frame Grid
            const fg = document.querySelector('.frames-grid');
            if (fg) fg.style.display = '';
            const fh = document.querySelector('.frames-header');
            if (fh) fh.style.display = '';
            document.querySelector('.editor-container').classList.remove('panel-open');
        }

        function cancelFrameColorPanel() {
            // Revert changes
            const layers = document.querySelectorAll('#frameOverlay .layer-img');
            layers.forEach((img, idx) => {
                const state = initialLayerStates[idx];
                if (state) {
                    img.src = state.src;
                    if (state.color) {
                         img.setAttribute('data-color', state.color);
                    } else {
                         img.removeAttribute('data-color');
                    }
                }
            });
            closeFrameColorPanel();
        }

        function confirmFrameColorPanel() {
            closeFrameColorPanel();
        }

        function renderLayerBubbles() {
            const layers = document.querySelectorAll('#frameOverlay .layer-img');
            const container = document.getElementById('layerBubbles');
            container.innerHTML = '';

            layers.forEach((layer, idx) => {
                const bubble = document.createElement('div');
                bubble.className = `layer-bubble ${idx === selectedLayerIndex ? 'active' : ''}`;

                // Always show the image
                const img = document.createElement('img');
                img.src = layer.getAttribute('data-original-src') || layer.src;
                img.style.width = '100%';
                img.style.height = '100%';
                img.style.objectFit = 'cover';
                bubble.appendChild(img);

                const currentColor = layer.getAttribute('data-color');
                if (currentColor) {
                    // Show a small color indicator in the corner instead of changing full background
                    const indicator = document.createElement('div');
                    indicator.style.position = 'absolute';
                    indicator.style.bottom = '4px';
                    indicator.style.right = '4px';
                    indicator.style.width = '12px';
                    indicator.style.height = '12px';
                    indicator.style.borderRadius = '50%';
                    indicator.style.backgroundColor = currentColor;
                    indicator.style.border = '1.5px solid #fff';
                    indicator.style.boxShadow = '0 2px 4px rgba(0,0,0,0.2)';
                    bubble.appendChild(indicator);
                }

                bubble.onclick = () => {
                    selectedLayerIndex = idx;
                    renderLayerBubbles();
                };
                container.appendChild(bubble);
            });
        }

        function applyPaletteColor(color) {
            const layers = document.querySelectorAll('#frameOverlay .layer-img');
            const layer = layers[selectedLayerIndex];
            if (layer) {
                // Record History
                const prevColor = layer.getAttribute('data-color');

                // Only add if different
                if (prevColor !== color) {
                     addToHistory(selectedLayerIndex, {
                         layerIndex: selectedLayerIndex,
                         prevColor: prevColor, 
                         newColor: color
                     });

                     // Helper for history format
                     // The addToHistory function above was generic, let's inline the push here
                     // to match the {prev, new} structure
                }

                recolorImage(layer, color);
            }
        }

        // Override the simple addToHistory I wrote above to use the passed object
        function addToHistory(idx, actionObj) {
            if (historyIndex < colorHistory.length - 1) {
                colorHistory = colorHistory.slice(0, historyIndex + 1);
            }
            colorHistory.push(actionObj);
            historyIndex++;
            updateHistoryButtons();
        }

        // Legacy changeColor removed


        function changeBGColor(val) {
            if (activeElement) {
                activeElement.style.setProperty('background-color', val, 'important');
                activeElement.style.padding = '4px 8px';
                activeElement.style.borderRadius = '4px';
            }
        }

        function toggleFontList() {
            const panel = document.getElementById('fontPanel');
            panel.style.display = (panel.style.display === 'block') ? 'none' : 'block';
        }

        function setFont(font) {
            if (activeElement) {
                activeElement.style.setProperty('font-family', `"${font}", sans-serif`, 'important');
                const span = activeElement.querySelector('span');
                if (span) span.style.setProperty('font-family', `"${font}", sans-serif`, 'important');
                toggleFontList();
            }
        }

        function toggleLayersModal() {
            const modal = document.getElementById('layersModal');
            const container = document.getElementById('layersContainer');
            container.innerHTML = '';

            // ... (rest of logic handles in separate view blocks usually, but we are replacing a block)
            // Wait, I need to be careful not to cut off function logic not shown.
            // I should only replace the functions I am changing.
            // But they are interleaved. 
            // I will replace from changeColor down to setFont.
            // Wait, recolorImage is ABOVE changeBGColor in the file.
        }

        function recolorImage(img, color) {
            const originalSrc = img.getAttribute('data-original-src') || img.src;
            if (!img.getAttribute('data-original-src')) img.setAttribute('data-original-src', originalSrc);

            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            const image = new Image();
            image.crossOrigin = "anonymous";
            image.src = originalSrc;

            image.onload = () => {
                canvas.width = image.naturalWidth;
                canvas.height = image.naturalHeight;
                ctx.clearRect(0, 0, canvas.width, canvas.height); // Ensure clean
                ctx.drawImage(image, 0, 0);

                const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                const data = imageData.data;
                const len = data.length;
                let hasTransparency = false;

                // Thorough check for transparency
                for(let i = 3; i < len; i += 4) { // Check every pixel
                    if (data[i] < 250) {
                        hasTransparency = true;
                        break;
                    }
                }

                // If opaque (or mostly opaque), try to remove background
                if (!hasTransparency) {
                    // Detect background color from top-left pixel
                    const r0 = data[0], g0 = data[1], b0 = data[2];
                    const isWhiteish = (r0 > 230 && g0 > 230 && b0 > 230);

                    if (isWhiteish) {
                        for(let i = 0; i < len; i += 4) {
                            const r = data[i], g = data[i+1], b = data[i+2];
                            // Tolerance 230
                            if (r > 230 && g > 230 && b > 230) {
                                data[i+3] = 0; // Make transparent
                            }
                        }
                        ctx.putImageData(imageData, 0, 0);
                    }
                }

                ctx.globalCompositeOperation = 'source-in';
                ctx.fillStyle = color;
                ctx.fillRect(0, 0, canvas.width, canvas.height);

                img.src = canvas.toDataURL('image/png');
                img.setAttribute('data-color', color);

                // CRITICAL: Refresh bubbles row immediately after color is applied to attribute
                renderLayerBubbles();
            };
        }

        function toggleFontList() {
            const panel = document.getElementById('fontPanel');
            panel.style.display = (panel.style.display === 'block') ? 'none' : 'block';
        }

        function setFont(font) {
            if (activeElement) {
                activeElement.style.setProperty('font-family', `"${font}", sans-serif`, 'important');
                const span = activeElement.querySelector('span');
                if (span) span.style.setProperty('font-family', `"${font}", sans-serif`, 'important');
                toggleFontList();
            }
        }

        function toggleLayersModal() {
            clearActive();
            const modal = document.getElementById('layersModal');
            const container = document.getElementById('layersContainer');
            container.innerHTML = '';

            const draggables = document.querySelectorAll('.draggable');
            draggables.forEach(el => {
                // Determine icon and label
                let icon = 'type';
                let rawLabel = el.innerText.trim() || el.getAttribute('data-label') || 'Component';
                let label = rawLabel;

                const low = rawLabel.toLowerCase();
                const id = el.id.toLowerCase();

                if (id.includes('phone') || low.includes('phone') || low.includes('mobile') || low.includes('call') || low.includes('contact')) {
                    label = 'Call';
                } else if (id.includes('email') || low.includes('email') || low.includes('mail')) {
                    label = 'Email';
                } else if (id.includes('website') || low.includes('web') || low.includes('site') || low.includes('www') || low.includes('url')) {
                    label = 'Website';
                } else if (id.includes('address') || low.includes('address') || low.includes('location') || low.includes('addr')) {
                    label = 'Address';
                } else if (id.includes('name') || low.includes('name') || low.includes('business')) {
                    label = 'Business Name';
                } else if (low.includes('logo') || id.includes('logo')) {
                    label = 'Logo';
                } else if (low.includes('sticker') || el.classList.contains('sticker-container')) {
                    label = 'Sticker';
                }

                if (el.classList.contains('sticker-container')) {
                    icon = 'smile';
                } else if (el.classList.contains('logo-container') || el.id === 'logo-shell') {
                    icon = 'image';
                } else if (el.classList.contains('icon-container')) {
                    icon = 'image';
                    if (!label.toLowerCase().includes('icon')) label += ' Icon';
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
                        setActive(el);
                    }, 50);
                };
                container.appendChild(item);
            });

            modal.style.display = 'flex';
            if (window.lucide) window.lucide.createIcons();
        }

        lucide.createIcons();

        // Initialize with first selected frame config if exists
        window.addEventListener('DOMContentLoaded', () => {
            const firstFrame = document.querySelector('.frame-item.selected');
            if (firstFrame) {
                const configAttr = firstFrame.getAttribute('data-config');
                if (configAttr && configAttr !== 'null') {
                    try {
                        currentFrameConfig = JSON.parse(configAttr);
                        // Reduced delay for snappier initialization
                        setTimeout(() => {
                             applyFrameConfig(currentFrameConfig);
                        }, 200);
                    } catch(e) {
                        console.error("Initial config error:", e);
                    }
                }
            }
        });
    </script>
@endsection