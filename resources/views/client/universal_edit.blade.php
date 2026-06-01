@extends('layouts.client')

@section('title', 'Select Frame')
@section('styles')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Roboto:wght@400;700;900&family=Poppins:wght@400;700;900&family=Montserrat:wght@400;700;900&family=Bebas+Neue&family=Pacifico&family=Dancing+Script:wght@400;700&family=Playfair+Display:wght@400;700;900&family=Oswald:wght@400;700&family=Lato:wght@400;700;900&family=Open+Sans:wght@400;700;800&family=Raleway:wght@400;700;900&family=Abril+Fatface&family=Comfortaa:wght@400;700&family=Righteous&family=Varela+Round&family=Caveat:wght@400;700&family=Lobster&display=swap" rel="stylesheet">
    <style>
    /* CRITICAL: Disable parent scrolling */
    #main-content {
        overflow: hidden !important;
        padding-bottom: 0 !important;
        height: 100vh !important;
        height: 100dvh !important;
    }
    nav, #fab-container, #fab-backdrop { display: none !important; }

    .editor-container {
        display: flex;
        flex-direction: column;
        height: 100vh;
        height: 100dvh;
        background-color: #f1f5f9;
        overflow: hidden;
    }
    .editor-container.panel-open .scroll-editor { display: none !important; }
    .editor-container.panel-open .canvas-section {
        flex: 1; align-items: center; padding-bottom: 380px;
    }

    /* Header */
    .app-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 0 16px; height: 56px; background: #ffffff;
        border-bottom: 1px solid #f1f5f9; flex-shrink: 0; z-index: 2500;
    }
    .back-link { color: #334155; padding: 8px; margin-left: -8px; }
    .header-title { flex: 1; font-size: 18px; font-weight: 700; color: #1e293b; margin-left: 8px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 65%; }

    /* Canvas Section */
    .canvas-section {
        background-color: #f8fafc; padding: 12px 16px;
        flex-shrink: 0; display: flex; justify-content: center;
        overflow: hidden;
    }
    #canvas-wrapper {
        position: relative; border-radius: 12px;
        box-shadow: 0 10px 40px -10px rgba(0,0,0,0.2);
        border: 1px solid rgba(0,0,0,0.05);
        overflow: hidden;
        margin: 0 auto;
        touch-action: none !important;
    }
    .canvas-container, .upper-canvas, .lower-canvas {
        touch-action: none !important;
    }

    /* Scrolling Editor */
    .scroll-editor {
        flex: 1; overflow-y: auto; background-color: #ffffff;
        border-top-left-radius: 24px; border-top-right-radius: 24px;
        min-height: 260px;
    }

    /* Toggle Bar */
    .toggle-bar {
        display: flex; gap: 10px; padding: 10px 16px 8px;
        overflow-x: auto; -webkit-overflow-scrolling: touch;
    }
    .toggle-bar::-webkit-scrollbar { display: none; }
    .toggle-btn {
        min-width: 40px; height: 40px; padding: 0 10px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        background-color: #4f46e5; color: white; font-size: 9px; font-weight: 900;
        text-transform: uppercase; flex-shrink: 0; border: none; cursor: pointer;
        box-shadow: 0 4px 6px -1px rgba(79,70,229,0.2); transition: all 0.2s;
    }
    .toggle-btn i { width: 18px; height: 18px; }
    .toggle-btn.inactive { background-color: #f1f5f9; color: #94a3b8; box-shadow: none; }

    /* Frame Grid */
    .frames-grid {
        display: flex; gap: 12px; padding: 4px 16px 12px;
        overflow-x: auto; -webkit-overflow-scrolling: touch;
    }
    .frames-grid::-webkit-scrollbar { display: none; }
    .frame-item {
        width: 85px; height: 85px; min-width: 85px; border-radius: 12px;
        overflow: hidden; border: 2px solid #f1f5f9; background: #ffffff;
        cursor: pointer; flex-shrink: 0; transition: all 0.2s;
    }
    .frame-item.selected { border-color: #4f46e5; box-shadow: 0 0 0 2px rgba(79,70,229,0.1); }
    .frame-item img { width: 100%; height: 100%; object-fit: contain; padding: 4px; }

    /* Toolbox */
    .toolbox {
        display: flex; justify-content: space-around; padding: 12px 4px;
        padding-bottom: calc(20px + env(safe-area-inset-bottom));
        border-top: 1px solid #f1f5f9; background: white; z-index: 2500;
    }
    .tool-item { display: flex; flex-direction: column; align-items: center; gap: 5px; flex: 1; cursor: pointer; min-width: 0; }
    .tool-icon {
        width: 42px; height: 42px; border-radius: 12px; background-color: #f1f5f9;
        border: none; display: flex; align-items: center; justify-content: center;
        color: #1e293b; transition: all 0.2s;
    }
    .tool-item:active .tool-icon { transform: scale(0.95); background-color: #e2e8f0; }
    .tool-label { font-size: 9px; font-weight: 700; color: #334155; white-space: nowrap; text-align: center; }

    /* Modals */
    #textModal, #layersModal {
        position: fixed; inset: 0; background: rgba(0,0,0,0.5);
        z-index: 4000; display: none; align-items: center; justify-content: center; padding: 20px;
    }
    .modal-content-box {
        background: #fff; width: 100%; max-width: 320px; border-radius: 12px;
        overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
    }
    .modal-header { padding: 16px; text-align: center; font-weight: 700; font-size: 18px; color: #1e293b; }
    .modal-divider { height: 1px; background: #e2e8f0; margin: 0 16px; }
    .modal-body { padding: 20px 16px; }
    #modalTextArea { width: 100%; height: 100px; border: none; outline: none; font-size: 20px; color: #334155; resize: none; background: transparent; }
    .modal-footer { display: flex; gap: 12px; padding: 16px; }
    .modal-btn { flex: 1; padding: 12px; border-radius: 8px; font-weight: 700; border: none; cursor: pointer; text-transform: uppercase; }
    .btn-cancel { background: #e2e8f0; color: #1e293b; }
    .btn-add { background: #2e0ee6; color: white; }

    /* Sticker Modal */
    #stickerModal {
        position: fixed; inset: 0; background: rgba(0,0,0,0.5);
        z-index: 3000; display: none; align-items: flex-end; justify-content: center;
    }
    .sticker-modal-content {
        background: #fff; width: 100%; max-width: 448px;
        border-top-left-radius: 20px; border-top-right-radius: 20px;
        max-height: 80vh; display: flex; flex-direction: column; overflow: hidden;
        animation: slideUp 0.3s ease-out;
    }
    @keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
    .sticker-header { padding: 16px; border-bottom: 1px solid #f1f5f9; }
    .sticker-cat-bar { display: flex; gap: 12px; overflow-x: auto; padding-bottom: 4px; }
    .sticker-cat-bar::-webkit-scrollbar { display: none; }
    .sticker-cat-btn {
        padding: 8px 16px; background: #f8fafc; border: 1px solid #e2e8f0;
        border-radius: 8px; font-size: 14px; font-weight: 600; color: #475569;
        white-space: nowrap; cursor: pointer;
    }
    .sticker-cat-btn.active { background: #2e0ee6; color: white; border-color: #2e0ee6; }
    .sticker-body { flex: 1; overflow-y: auto; padding: 16px; }
    .sticker-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
    .sticker-item { aspect-ratio: 1; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: transform 0.2s; }
    .sticker-item:active { transform: scale(0.9); }
    .sticker-item img { max-width: 100%; max-height: 100%; object-fit: contain; }

    /* Layers Box */
    .layers-box {
        background: white; width: 100%; max-width: 448px; border-radius: 20px;
        overflow: hidden; display: flex; flex-direction: column; max-height: 70vh;
    }
    .layer-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-bottom: 1px solid #f1f5f9; cursor: pointer; }
    .layer-item:active { background: #f8fafc; }
    .layer-item i { color: #64748b; }
    .layer-text { flex: 1; font-size: 14px; font-weight: 600; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .hidden { display: none; }

    /* Floating Image Action Select Box */
    #imageActionSelect {
        position: absolute;
        z-index: 3500;
        display: none;
        min-width: 170px;
        background: #ffffff;
        border-radius: 14px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.18), 0 1.5px 6px rgba(0,0,0,0.08);
        overflow: hidden;
        animation: selectPopIn 0.22s cubic-bezier(0.16,1,0.3,1);
        border: 1px solid rgba(0,0,0,0.06);
    }
    @keyframes selectPopIn {
        from { opacity: 0; transform: translateY(6px) scale(0.96); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    #imageActionSelect .select-header {
        padding: 10px 14px 6px;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #94a3b8;
        border-bottom: 1px solid #f1f5f9;
    }
    #imageActionSelect .select-option {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 11px 14px;
        font-size: 13px;
        font-weight: 600;
        color: #334155;
        cursor: pointer;
        transition: background 0.15s, color 0.15s;
    }
    #imageActionSelect .select-option:hover {
        background: #f0f4ff;
        color: #4f46e5;
    }
    #imageActionSelect .select-option:active {
        background: #e0e7ff;
    }
    #imageActionSelect .select-option i {
        width: 18px;
        height: 18px;
        flex-shrink: 0;
        color: #64748b;
        transition: color 0.15s;
    }
    #imageActionSelect .select-option:hover i {
        color: #4f46e5;
    }
    #imageActionSelect .select-option.danger {
        color: #ef4444;
    }
    #imageActionSelect .select-option.danger i {
        color: #ef4444;
    }
    #imageActionSelect .select-option.danger:hover {
        background: #fef2f2;
        color: #dc2626;
    }
    #imageActionSelect .select-divider {
        height: 1px;
        background: #f1f5f9;
        margin: 0;
    }

    /* Contextual Bar */
    #contextualBar {
        position: fixed; bottom: 0; left: 50% !important; transform: translateX(-50%);
        width: 100%; max-width: 448px; background: #fff; z-index: 5000; display: none;
        padding-bottom: env(safe-area-inset-bottom); flex-direction: column;
        border-top-left-radius: 24px; border-top-right-radius: 24px;
        box-shadow: 0 -10px 40px rgba(0,0,0,0.15);
        animation: slideUpBar 0.3s cubic-bezier(0.16,1,0.3,1);
    }
    @keyframes slideUpBar {
        from { transform: translateX(-50%) translateY(100%); }
        to { transform: translateX(-50%) translateY(0); }
    }
    .bar-scroll { display: flex; gap: 12px; padding: 12px 16px; overflow-x: auto; -webkit-overflow-scrolling: touch; scroll-behavior: smooth; }
    .bar-scroll::-webkit-scrollbar { display: none; }
    .tool-btn { display: flex; flex-direction: column; align-items: center; gap: 4px; min-width: 56px; cursor: pointer; }
    .tool-btn-icon {
        width: 40px; height: 40px; border-radius: 10px; background: #f1f5f9;
        display: flex; align-items: center; justify-content: center; color: #475569; transition: all 0.2s;
    }
    .tool-btn:active .tool-btn-icon { transform: scale(0.9); background: #e2e8f0; }
    .tool-btn-label { font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
    .tool-sub-panel { display: none; padding: 16px 20px; border-bottom: 1px solid #f1f5f9; background: #f8fafc; }

    /* Frame Color Panel */
    #frameColorPanel { background: #fff; padding: 16px; display: none; margin-top: 10px; border-bottom: 1px solid #f1f5f9; }
    .panel-header-simple { text-align: left; margin-bottom: 16px; }
    .panel-title-simple { font-size: 14px; font-weight: 800; color: #1e293b; }
    .layer-bubbles { display: flex; gap: 12px; margin-bottom: 20px; overflow-x: auto; padding-bottom: 4px; }
    .layer-bubbles::-webkit-scrollbar { display: none; }
    .layer-bubble { width: 48px; height: 48px; border-radius: 12px; border: 2px solid #f1f5f9; cursor: pointer; flex-shrink: 0; overflow: hidden; position: relative; background: #f8fafc; transition: all 0.2s; }
    .layer-bubble.active { border-color: #4f46e5; box-shadow: 0 0 0 4px rgba(79,70,229,0.15); }
    .palette-grid { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 24px; }
    .color-swatch { width: 40px; height: 40px; border-radius: 50%; cursor: pointer; border: 1px solid rgba(0,0,0,0.05); transition: transform 0.1s; }
    .color-swatch:active { transform: scale(0.9); }
    .custom-picker-btn { background: #fff; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; }
    .panel-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .btn-action { height: 44px; border-radius: 8px; font-size: 13px; font-weight: 700; text-transform: uppercase; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: opacity 0.2s; }
    .btn-action:active { opacity: 0.8; }
    .btn-apply { background: #4f46e5; color: white; border: none; }
    .icon-btn-small { width: 32px; height: 32px; border-radius: 8px; border: 1px solid #e2e8f0; background: white; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #64748b; }
    .icon-btn-small:disabled { opacity: 0.5; cursor: not-allowed; }

    /* Filter */
    .filter-container { position: relative; margin-right: 12px; }
    .filter-btn { display: flex; align-items: center; justify-content: center; background: #2e0ee6ff; color: #fff; border: none; padding: 10px; border-radius: 6px; cursor: pointer; }
    .filter-dropdown {
        position: absolute; top: 100%; right: 0; margin-top: 8px; background: #fff;
        border: 1px solid #e2e8f0; border-radius: 12px;
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); width: 200px; z-index: 200;
        display: none; overflow: hidden; animation: fadeIn 0.15s ease-out;
    }
    .filter-dropdown.active { display: block; }
    .filter-option { display: flex; align-items: center; padding: 10px 16px; font-size: 14px; color: #475569; cursor: pointer; }
    .filter-option:hover { background: #f1f5f9; }
    .filter-option.selected { background: #f0fdfa; color: #0d9488; font-weight: 600; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }

    /* Font Panel */
    #fontPanel {
        position: fixed; bottom: 0; left: 50%; transform: translateX(-50%);
        width: 100%; max-width: 448px; background: #fff;
        border-top-left-radius: 24px; border-top-right-radius: 24px;
        z-index: 5000; display: none; flex-direction: column; max-height: 60vh;
        overflow-y: auto; padding: 0; padding-bottom: 20px;
        box-shadow: 0 -10px 40px rgba(0,0,0,0.15); animation: slideUpCentered 0.3s ease-out;
    }
    @keyframes slideUpCentered {
        from { transform: translate(-50%, 100%); }
        to { transform: translate(-50%, 0); }
    }
    .font-panel-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0px; padding: 20px 20px 10px 20px; position: sticky; top: 0; background: white; z-index: 10; }
    .font-panel-title { font-size: 18px; font-weight: 800; color: #1e293b; }
    .font-close { width: 36px; height: 36px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #64748b; }
    .font-option { padding: 14px 16px; margin: 4px 20px; font-size: 17px; color: #334155; border-radius: 12px; cursor: pointer; transition: all 0.2s; }
    .font-option:hover { background: #f1f5f9; color: #4f46e5; }
    .font-option:active { transform: scale(0.98); background: #e2e8f0; }

    /* Favorite Heart Animation */
    .favorite-btn {
        position: absolute;
        top: 6px;
        right: 6px;
        width: 24px;
        height: 24px;
        background: rgba(255, 255, 255, 0.8);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 10;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: transform 0.2s;
    }
    .favorite-btn:active {
        transform: scale(0.8);
    }
    .heart-icon {
        width: 14px;
        height: 14px;
        transition: fill 0.3s, stroke 0.3s;
    }
    .heart-icon.liked {
        fill: #ef4444;
        stroke: #ef4444;
        animation: heartPop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    @keyframes heartPop {
        0% { transform: scale(1); }
        50% { transform: scale(1.4); }
        100% { transform: scale(1); }
    }

    /* ═══ My Products Panel ═══ */
    #myProductsModal {
        position: fixed; inset: 0; background: rgba(0,0,0,0.5);
        z-index: 6000; display: none; align-items: flex-end; justify-content: center;
        backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);
    }
    .products-panel {
        background: #fff; width: 100%; max-width: 448px;
        border-top-left-radius: 24px; border-top-right-radius: 24px;
        max-height: 75vh; display: flex; flex-direction: column; overflow: hidden;
        animation: slideUp 0.3s cubic-bezier(0.16,1,0.3,1);
        box-shadow: 0 -12px 40px rgba(0,0,0,0.15);
    }
    .products-panel-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 18px 20px 12px; border-bottom: 1px solid #f1f5f9; flex-shrink: 0;
    }
    .products-panel-title {
        display: flex; align-items: center; gap: 10px;
        font-size: 17px; font-weight: 800; color: #1e293b;
    }
    .products-panel-title i { color: #4f46e5; }
    .products-panel-count {
        background: #eef2ff; color: #4f46e5; font-size: 11px; font-weight: 700;
        padding: 3px 9px; border-radius: 20px;
    }
    .products-panel-close {
        width: 34px; height: 34px; border-radius: 50%; background: #f1f5f9;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; color: #64748b; border: none; transition: all 0.2s;
    }
    .products-panel-close:active { transform: scale(0.9); background: #e2e8f0; }
    .products-search {
        padding: 10px 20px; flex-shrink: 0;
    }
    .products-search-input {
        width: 100%; border: 1.5px solid #e2e8f0; border-radius: 12px;
        padding: 10px 14px 10px 38px; font-size: 14px; color: #334155;
        background: #f8fafc url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'%3E%3C/circle%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'%3E%3C/line%3E%3C/svg%3E") 12px center no-repeat;
        outline: none; transition: all 0.2s;
    }
    .products-search-input:focus {
        border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.08);
        background-color: #fff;
    }
    .products-body {
        flex: 1; overflow-y: auto; padding: 4px 20px 20px;
        -webkit-overflow-scrolling: touch;
    }
    .products-body::-webkit-scrollbar { width: 4px; }
    .products-body::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    .product-group-label {
        font-size: 11px; font-weight: 800; color: #94a3b8;
        text-transform: uppercase; letter-spacing: 1px;
        margin: 16px 0 10px; padding-bottom: 6px;
        border-bottom: 1px solid #f1f5f9;
        display: flex; align-items: center; gap: 6px;
    }
    .product-group-label i { width: 14px; height: 14px; }
    .products-grid {
        display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;
    }
    .product-card {
        position: relative; border-radius: 14px; overflow: hidden;
        border: 2px solid #f1f5f9; background: #fafafa;
        cursor: pointer; transition: all 0.2s;
        aspect-ratio: 1;
    }
    .product-card:active { transform: scale(0.96); }
    .product-card.selected {
        border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79,70,229,0.15);
    }
    .product-card img {
        width: 100%; height: 100%; object-fit: contain; padding: 6px;
    }
    .product-card-name {
        position: absolute; bottom: 0; left: 0; right: 0;
        background: linear-gradient(transparent, rgba(0,0,0,0.7));
        color: white; font-size: 9px; font-weight: 700;
        padding: 14px 6px 5px; text-align: center;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .product-used-badge {
        position: absolute; top: 5px; right: 5px;
        background: #22c55e; color: white;
        width: 20px; height: 20px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 11px; font-weight: 900;
        box-shadow: 0 2px 6px rgba(34,197,94,0.4);
        animation: tagPop 0.3s ease;
    }
    .products-actions {
        display: flex; gap: 10px; padding: 14px 20px;
        border-top: 1px solid #f1f5f9; flex-shrink: 0;
        padding-bottom: calc(14px + env(safe-area-inset-bottom));
    }
    .products-action-btn {
        flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px;
        padding: 12px 16px; border-radius: 12px; font-size: 13px; font-weight: 700;
        cursor: pointer; border: none; transition: all 0.2s;
    }
    .products-action-btn:active { transform: scale(0.97); }
    .btn-full-image {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white; box-shadow: 0 4px 12px rgba(37,99,235,0.3);
    }
    .btn-cutout-image {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white; box-shadow: 0 4px 12px rgba(5,150,105,0.3);
    }
    .products-action-btn:disabled {
        opacity: 0.4; cursor: not-allowed; transform: none;
    }
    .products-empty {
        text-align: center; padding: 40px 20px;
    }
    .products-empty-icon {
        width: 64px; height: 64px; border-radius: 20px;
        background: linear-gradient(135deg, #eef2ff, #e0e7ff);
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 16px; color: #6366f1;
    }
    .products-empty h4 {
        font-size: 16px; font-weight: 700; color: #1e293b; margin: 0 0 6px;
    }
    .products-empty p {
        font-size: 13px; color: #94a3b8; margin: 0 0 20px; line-height: 1.5;
    }
    .products-empty-btn {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 10px 24px; border-radius: 10px;
        background: linear-gradient(135deg, #6366f1, #4f46e5);
        color: white; font-size: 14px; font-weight: 700;
        text-decoration: none; border: none; cursor: pointer;
        box-shadow: 0 4px 12px rgba(79,70,229,0.3);
    }
    .products-no-results {
        text-align: center; padding: 30px 10px; color: #94a3b8;
    }
    .products-no-results i { font-size: 28px; margin-bottom: 8px; opacity: 0.4; display: block; }
    .products-no-results p { font-size: 13px; font-weight: 600; margin: 0; }

    /* ═══ Image Type Badges & Filter ═══ */
    .image-type-badge {
        position: absolute; bottom: 3px; left: 3px; z-index: 5;
        font-size: 7px; font-weight: 800; text-transform: uppercase;
        letter-spacing: 0.3px; padding: 2px 5px; border-radius: 4px;
        line-height: 1.2; pointer-events: none;
    }
    .image-type-badge.badge-full {
        background: rgba(59,130,246,0.9); color: white;
    }
    .image-type-badge.badge-transparent {
        background: rgba(16,185,129,0.9); color: white;
    }
    .image-type-filter {
        display: flex; gap: 6px; padding: 0 16px 8px;
    }
    .image-type-filter-btn {
        padding: 4px 12px; border-radius: 20px; font-size: 11px;
        font-weight: 700; border: 1.5px solid #e2e8f0;
        background: #f8fafc; color: #64748b; cursor: pointer;
        transition: all 0.2s; white-space: nowrap;
    }
    .image-type-filter-btn.active {
        background: #4f46e5; color: white; border-color: #4f46e5;
    }
    .image-type-filter-btn:active { transform: scale(0.95); }
    </style>
    <!-- Fabric.js v5 — Canvas editor library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js"></script>
@endsection

@section('content')
    @php
        $hideBranding = ($type == 'post' || $type == 'custom');
        $imgName = $item_frames->first()->frame_image ?? ($item->display_image ?? '');
        $designUrl = request()->query('design') ?? ($imgName ? asset('uploads/' . $imgName) : '');

        // ══ SERVER-SIDE BRIGHTNESS DETECTION (PHP GD) ══
        // Analyze the bottom 30% of the template image to detect dark/light footer
        $phpTemplateIsDark = false;
        $phpBrightnessValue = 128;
        try {
            $imgPath = '';
            // Convert URL to local file path
            if ($designUrl) {
                $parsed = parse_url($designUrl);
                $urlPath = $parsed['path'] ?? '';
                // Clean the path to get the relative path to the public directory
                $urlPath = str_replace(['/Artera/public/', '/Artera/'], '/', $urlPath);
                $urlPath = ltrim($urlPath, '/');
                $imgPath = public_path($urlPath);
                
                // Fallback for XAMPP setups where public_path might not match URL perfectly
                if (!file_exists($imgPath)) {
                    $imgPath = base_path($urlPath);
                }
            }
            
            // Temporary debug file to verify path resolution
            @file_put_contents(storage_path('logs/theming_debug.log'), "URL: $designUrl\nPath: $imgPath\nExists: " . (file_exists($imgPath)?'Y':'N') . "\n", FILE_APPEND);
            
            if ($imgPath && file_exists($imgPath)) {
                $cacheKey = 'img_bright_' . md5($imgPath);
                
                $cachedResult = \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addDays(30), function() use ($imgPath) {
                    $res = ['isDark' => false, 'brightness' => 128];
                    $info = @getimagesize($imgPath);
                    if ($info) {
                        $mime = $info['mime'] ?? '';
                        $srcImg = null;
                        if ($mime === 'image/jpeg' || $mime === 'image/jpg') {
                            $srcImg = @imagecreatefromjpeg($imgPath);
                        } elseif ($mime === 'image/png') {
                            $srcImg = @imagecreatefrompng($imgPath);
                        } elseif ($mime === 'image/webp') {
                            $srcImg = @imagecreatefromwebp($imgPath);
                        }
                        if ($srcImg) {
                            $w = imagesx($srcImg);
                            $h = imagesy($srcImg);
                            // Sample the bottom 30% of the image
                            $sampleStartY = (int)($h * 0.7);
                            $totalR = 0; $totalG = 0; $totalB = 0; $count = 0;
                            // Sample every 5th pixel for speed
                            for ($y = $sampleStartY; $y < $h; $y += 5) {
                                for ($x = 0; $x < $w; $x += 5) {
                                    $rgb = imagecolorat($srcImg, $x, $y);
                                    $r = ($rgb >> 16) & 0xFF;
                                    $g = ($rgb >> 8) & 0xFF;
                                    $b = $rgb & 0xFF;
                                    $totalR += $r; $totalG += $g; $totalB += $b;
                                    $count++;
                                }
                            }
                            if ($count > 0) {
                                $avgR = $totalR / $count;
                                $avgG = $totalG / $count;
                                $avgB = $totalB / $count;
                                $res['brightness'] = round(($avgR * 299 + $avgG * 587 + $avgB * 114) / 1000);
                                $res['isDark'] = ($res['brightness'] < 128);
                            }
                            imagedestroy($srcImg);
                        }
                    }
                    return $res;
                });
                
                $phpTemplateIsDark = $cachedResult['isDark'];
                $phpBrightnessValue = $cachedResult['brightness'];
            }
        } catch (\Exception $e) {
            // Fallback: assume light template
            $phpTemplateIsDark = false;
            $phpBrightnessValue = 128;
        }
    @endphp

    <div class="editor-container">
        <header class="app-header">
            @if(request()->has('from_app'))
                <a href="javascript:void(0)" onclick="window.history.back()" class="back-link">
                    <i data-lucide="chevron-left"></i>
                </a>
            @elseif($type === 'business_custom_frame')
                <a href="{{ route('custom') }}" class="back-link">
                    <i data-lucide="chevron-left"></i>
                </a>
            @else
                <a href="{{ route('universal.details', ['type' => $type, 'id' => $id]) }}" class="back-link">
                    <i data-lucide="chevron-left"></i>
                </a>
            @endif
            <h1 class="header-title">{{ $item->display_name ?? 'Editor' }}</h1>
            <div style="display: flex; align-items: center;">
                @if(isset($poster_categories) && count($poster_categories) > 0)
                <div class="filter-container" style="margin-right: 8px;">
                    <button class="filter-btn" onclick="toggleFilterMenu()" title="Filter Frames">
                        <i data-lucide="layout-template" style="width: 20px; height: 20px;"></i>
                    </button>
                    <div class="filter-dropdown" id="filterMenu">
                        <div class="filter-option selected" onclick="filterFrames('all', 'All', this)">All Categories</div>
                        @foreach($poster_categories as $cat)
                            <div class="filter-option" onclick="filterFrames('{{ $cat->id }}', '{{ $cat->name }}', this)">{{ $cat->name }}</div>
                        @endforeach
                    </div>
                </div>
                @endif
                <div style="display: flex; gap: 8px; margin-right: 12px;">
                    <button class="icon-btn-small" id="canvasUndoBtn" onclick="undoCanvas()" disabled style="background:transparent; border:none; color:#1e293b; cursor:pointer;"><i data-lucide="undo-2" style="width:20px;height:20px;"></i></button>
                    <button class="icon-btn-small" id="canvasRedoBtn" onclick="redoCanvas()" disabled style="background:transparent; border:none; color:#1e293b; cursor:pointer;"><i data-lucide="redo-2" style="width:20px;height:20px;"></i></button>
                </div>
                <button class="next-button" onclick="exportImage()"
                    style="background-color: #2e0ee6ff; color: white; padding: 8px 20px; border-radius: 6px; font-weight: 600; font-size: 14px;">
                    Download
                </button>
            </div>
        </header>

        <!-- Fabric.js Canvas -->
        <div class="canvas-section" style="position: relative;">
            <div id="canvas-wrapper">
                <canvas id="fabric-canvas"></canvas>
            </div>
            <!-- Processing Overlay -->
            <div id="processingOverlay" style="display: none; position: absolute; inset: 0; background: rgba(255,255,255,0.7); backdrop-filter: blur(8px); webkit-backdrop-filter: blur(8px); z-index: 1000; align-items: center; justify-content: center; flex-direction: column; border-radius: 2rem;">
                <div style="width: 45px; height: 45px; border: 5px solid #f1f5f9; border-top: 5px solid #2e0ee6ff; border-radius: 50%; animation: spin 1s cubic-bezier(0.4, 0, 0.2, 1) infinite; box-shadow: 0 4px 15px rgba(46, 14, 230, 0.2);"></div>
                <div style="margin-top: 16px; font-weight: 800; color: #2e0ee6ff; font-size: 15px; letter-spacing: -0.01em; text-transform: uppercase;">Removing Background</div>
                <div style="font-size: 11px; color: #64748b; font-weight: 600; margin-top: 4px;">Using On-Device AI</div>
                <style>@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>
            </div>
        </div>

        <input type="hidden" id="activeFrameImg-source" value="{{ $frames->first()->full_url ?? '' }}">

        <!-- Scrollable Tools -->
        <div class="scroll-editor scrollbar-hide">
            <!-- Toggle Bar -->
            <div class="toggle-bar">
                @if(!$hideBranding)
                    <button class="toggle-btn" id="toggle-name" onclick="toggleBusinessElement('name', this)">NAME</button>
                    <button class="toggle-btn" id="toggle-logo" onclick="toggleBusinessElement('logo', this)">LOGO</button>
                    <button class="toggle-btn" id="toggle-phone" onclick="toggleBusinessElement('phone', this)"><i data-lucide="smartphone"></i></button>
                    <button class="toggle-btn" id="toggle-email" onclick="toggleBusinessElement('email', this)"><i data-lucide="mail"></i></button>
                    <button class="toggle-btn" id="toggle-address" onclick="toggleBusinessElement('address', this)"><i data-lucide="map-pin"></i></button>
                    <button class="toggle-btn" id="toggle-website" onclick="toggleBusinessElement('website', this)"><i data-lucide="globe"></i></button>
                @endif
                <button class="toggle-btn" id="frame-toggle-btn" onclick="toggleFramePanel()">{{ $hideBranding ? 'TEMPLATE' : 'FRAME' }}</button>
                <div style="position: relative;">
                    <button class="toggle-btn" onclick="openFrameColorPanel()" style="font-size: 7px; line-height: 1;">FRAME<br>COLOR</button>
                </div>
            </div>

            <!-- Frame Color Panel -->
            <div id="frameColorPanel">
                <div class="panel-header-simple" style="display: flex; justify-content: space-between; align-items: center;">
                    <span class="panel-title-simple">Select Frame Color</span>
                    <div style="display: flex; gap: 8px;">
                        <button class="icon-btn-small" id="undoColorBtn" onclick="undoFrameColor()" disabled><i data-lucide="undo-2" style="width:16px;height:16px;"></i></button>
                        <button class="icon-btn-small" id="redoColorBtn" onclick="redoFrameColor()" disabled><i data-lucide="redo-2" style="width:16px;height:16px;"></i></button>
                    </div>
                </div>
                <div class="layer-bubbles" id="layerBubbles"></div>
                <div class="palette-grid">
                    <div class="color-swatch custom-picker-btn" onclick="document.getElementById('customFrameColorInput').click()" style="background: linear-gradient(45deg, #f06, #f90, #ff0, #0f0, #0ff, #00f, #90f, #f06); border: none;">
                        <i data-lucide="plus" style="width:20px;height:20px;color:white;"></i>
                    </div>
                    <input type="color" id="customFrameColorInput" class="hidden" oninput="applyPaletteColor(this.value)">
                    <div class="color-swatch" style="background:#ffffff;" onclick="applyPaletteColor('#ffffff')"></div>
                    <div class="color-swatch" style="background:#1e293b;" onclick="applyPaletteColor('#1e293b')"></div>
                    <div class="color-swatch" style="background:#f97316;" onclick="applyPaletteColor('#f97316')"></div>
                    <div class="color-swatch" style="background:#eab308;" onclick="applyPaletteColor('#eab308')"></div>
                    <div class="color-swatch" style="background:#84cc16;" onclick="applyPaletteColor('#84cc16')"></div>
                    <div class="color-swatch" style="background:#22c55e;" onclick="applyPaletteColor('#22c55e')"></div>
                    <div class="color-swatch" style="background:#0ea5e9;" onclick="applyPaletteColor('#0ea5e9')"></div>
                    <div class="color-swatch" style="background:#3b82f6;" onclick="applyPaletteColor('#3b82f6')"></div>
                    <div class="color-swatch" style="background:#6366f1;" onclick="applyPaletteColor('#6366f1')"></div>
                    <div class="color-swatch" style="background:#d946ef;" onclick="applyPaletteColor('#d946ef')"></div>
                    <div class="color-swatch" style="background:#f43f5e;" onclick="applyPaletteColor('#f43f5e')"></div>
                </div>
                <div class="panel-actions">
                    <button class="btn-action btn-apply" onclick="confirmFrameColorPanel()">Apply</button>
                    <button class="btn-action btn-cancel" onclick="cancelFrameColorPanel()">Cancel</button>
                </div>
            </div>

            <!-- Font Panel -->
            <div id="fontPanel">
                <div class="font-panel-header">
                    <span class="font-panel-title">Fonts</span>
                    <div class="font-close" onclick="toggleFontList()"><i data-lucide="x"></i></div>
                </div>
                <div class="font-option" style="font-family:'Inter',sans-serif;" onclick="setFont('Inter')">Inter (Default)</div>
                <div class="font-option" style="font-family:'Roboto',sans-serif;" onclick="setFont('Roboto')">Roboto</div>
                <div class="font-option" style="font-family:'Poppins',sans-serif;" onclick="setFont('Poppins')">Poppins</div>
                <div class="font-option" style="font-family:'Montserrat',sans-serif;" onclick="setFont('Montserrat')">Montserrat</div>
                <div class="font-option" style="font-family:'Bebas Neue',sans-serif;" onclick="setFont('Bebas Neue')">Bebas Neue</div>
                <div class="font-option" style="font-family:'Pacifico',cursive;" onclick="setFont('Pacifico')">Pacifico</div>
                <div class="font-option" style="font-family:'Dancing Script',cursive;" onclick="setFont('Dancing Script')">Dancing Script</div>
                <div class="font-option" style="font-family:'Playfair Display',serif;" onclick="setFont('Playfair Display')">Playfair Display</div>
                <div class="font-option" style="font-family:'Oswald',sans-serif;" onclick="setFont('Oswald')">Oswald</div>
                <div class="font-option" style="font-family:'Lato',sans-serif;" onclick="setFont('Lato')">Lato</div>
                <div class="font-option" style="font-family:'Open Sans',sans-serif;" onclick="setFont('Open Sans')">Open Sans</div>
                <div class="font-option" style="font-family:'Raleway',sans-serif;" onclick="setFont('Raleway')">Raleway</div>
                <div class="font-option" style="font-family:'Abril Fatface',serif;" onclick="setFont('Abril Fatface')">Abril Fatface</div>
                <div class="font-option" style="font-family:'Comfortaa',cursive;" onclick="setFont('Comfortaa')">Comfortaa</div>
                <div class="font-option" style="font-family:'Righteous',cursive;" onclick="setFont('Righteous')">Righteous</div>
                <div class="font-option" style="font-family:'Varela Round',sans-serif;" onclick="setFont('Varela Round')">Varela Round</div>
                <div class="font-option" style="font-family:'Caveat',cursive;" onclick="setFont('Caveat')">Caveat</div>
                <div class="font-option" style="font-family:'Lobster',cursive;" onclick="setFont('Lobster')">Lobster</div>
            </div>

            <!-- Frame Panel -->
            <div id="framePanel" style="display: block; padding-top: 10px;">
                <div class="panel-header-simple" style="display: flex; justify-content: space-between; align-items: center; padding: 0 16px 10px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="panel-title-simple" style="font-size: 14px; font-weight: 700; color: #1e293b;">Select Frame</span>
                        <select id="editorThemeFilter" onchange="applyFrameFilters()" style="padding: 2px 6px; font-size: 12px; border: 1px solid #e2e8f0; border-radius: 4px; outline: none; background: #fff;">
                            <option value="all">All Themes</option>
                            <option value="dark">Dark Theme</option>
                            <option value="light">Light Theme</option>
                        </select>
                    </div>
                </div>
                <!-- Image Type Filter Removed as per request -->
                <div class="frames-grid">
                    @php $basePosterUrl = $designUrl; @endphp
                    @forelse($frames as $index => $frame)
                        @php
                            $logicUrl = $frame->full_url;
                            $thumbUrl = $frame->thumbnail_url ?? $frame->full_url;
                            $fConfig = null;
                            if (isset($frame->config)) {
                                $fConfig = $frame->config;
                            } elseif (isset($frame->json_rules)) {
                                $fConfig = is_string($frame->json_rules) ? json_decode($frame->json_rules) : $frame->json_rules;
                            }
                        @endphp
                        @php $frameImgType = $frame->image_type ?? 'full'; @endphp
                        <div class="frame-item {{ $index === 0 ? 'selected' : '' }}"
                            style="position: relative; background: #f1f5f9; cursor: pointer; overflow: hidden;"
                            data-config="{{ json_encode($fConfig) }}"
                            data-category-id="{{ $frame->category_id ?? 'all' }}"
                            data-theme="{{ $frame->theme ?? 'all' }}"
                            data-image-type="{{ $frameImgType }}"
                            data-req-address="{{ $frame->req_address ?? 0 }}"
                            data-req-email="{{ $frame->req_email ?? 0 }}"
                            data-req-phone="{{ $frame->req_phone ?? 0 }}"
                            data-req-website="{{ $frame->req_website ?? 0 }}"
                            data-skins-dir="{{ $fConfig ? dirname($logicUrl) : '' }}"
                            onclick="changeFrame('{{ $logicUrl }}', this)">
                            <img src="{{ $thumbUrl }}" style="position:absolute;inset:0;width:100%;height:100%;object-fit:contain;object-position:bottom;z-index:1;">
                            <span class="image-type-badge badge-{{ $frameImgType }}">{{ $frameImgType === 'transparent' ? '✂️ Cutout' : '🖼️ Full' }}</span>
                            <div class="favorite-btn" onclick="toggleFavoriteFrame(event, '{{ $frame->id }}', this)">
                                <i data-lucide="heart" class="heart-icon {{ in_array($frame->id, $favoriteFrames ?? []) ? 'liked' : 'text-gray-400' }}"></i>
                            </div>
                        </div>
                    @empty
                        <div style="padding: 20px; text-align: center; color: #94a3b8;">
                            <p style="font-size: 13px;">No frames found.</p>
                        </div>
                    @endforelse
                </div>
                <div style="padding: 10px 16px; text-align: center;">
                    <button onclick="toggleFrameOverlays()" class="btn-action" style="width: 100%; background: #f1f5f9; color: #4f46e5; border-radius: 8px;">Toggle Frame Visibility</button>
                </div>
            </div>

            <!-- Toolbox -->
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
                <div class="tool-item" onclick="openMyProducts()">
                    <div class="tool-icon" style="{{ ($hasProducts ?? false) ? 'background: linear-gradient(135deg, #eef2ff, #e0e7ff); color: #4f46e5;' : '' }}">
                        <i data-lucide="shopping-bag"></i>
                    </div>
                    <span class="tool-label">Products</span>
                </div>
                <div class="tool-item" onclick="addSticker()">
                    <div class="tool-icon"><i data-lucide="smile-plus"></i></div>
                    <span class="tool-label">Sticker</span>
                </div>
                <div class="tool-item" onclick="toggleLayersModal()">
                    <div class="tool-icon"><i data-lucide="layers"></i></div>
                    <span class="tool-label">Layers</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Image Action Select Box -->
    <!-- Image Action Popup Removed (Actions moved to Contextual Bar below) -->

    <!-- Contextual Bar -->
    <div id="contextualBar">
        <div id="fontSizeControl" class="tool-sub-panel">
            <div style="display:flex;justify-content:space-between;margin-bottom:12px;align-items:center;">
                <span style="font-size:14px;font-weight:800;color:#1e293b;">Font Size</span>
                <div style="background:#7d2ae8;color:white;padding:4px 10px;border-radius:6px;font-size:12px;font-weight:800;"><span id="fontSizeDisplay">24</span>px</div>
            </div>
            <input type="range" id="fontSizeSlider" min="10" max="200" value="24" style="width:100%;height:6px;appearance:none;background:#e2e8f0;border-radius:3px;outline:none;" oninput="changeFontSize(this.value)">
        </div>

        <div id="nudgeControl" class="tool-sub-panel" style="display:none; flex-direction:column; align-items:center;">
            <div style="font-size:14px;font-weight:800;color:#1e293b;margin-bottom:12px;">Nudge Position</div>
            <div style="display:grid; grid-template-columns: 48px 48px 48px; grid-template-rows: 48px 48px; gap:8px;">
                <div style="grid-column: 2; grid-row: 1;">
                    <button onclick="nudgeObject('up')" style="width:100%;height:100%;background:#f1f5f9;border:none;border-radius:8px;display:flex;align-items:center;justify-content:center;cursor:pointer;"><i data-lucide="arrow-up" style="width:20px;height:20px;color:#475569;"></i></button>
                </div>
                <div style="grid-column: 1; grid-row: 2;">
                    <button onclick="nudgeObject('left')" style="width:100%;height:100%;background:#f1f5f9;border:none;border-radius:8px;display:flex;align-items:center;justify-content:center;cursor:pointer;"><i data-lucide="arrow-left" style="width:20px;height:20px;color:#475569;"></i></button>
                </div>
                <div style="grid-column: 2; grid-row: 2;">
                    <button onclick="nudgeObject('down')" style="width:100%;height:100%;background:#f1f5f9;border:none;border-radius:8px;display:flex;align-items:center;justify-content:center;cursor:pointer;"><i data-lucide="arrow-down" style="width:20px;height:20px;color:#475569;"></i></button>
                </div>
                <div style="grid-column: 3; grid-row: 2;">
                    <button onclick="nudgeObject('right')" style="width:100%;height:100%;background:#f1f5f9;border:none;border-radius:8px;display:flex;align-items:center;justify-content:center;cursor:pointer;"><i data-lucide="arrow-right" style="width:20px;height:20px;color:#475569;"></i></button>
                </div>
            </div>
        </div>

        <div style="position:relative; display:flex; align-items:center; width:100%; max-width:100%; overflow:hidden;">
            <button id="scrollLeftBtn" onclick="scrollContextualBar(-1)" style="position:absolute; left:0; z-index:10; background:linear-gradient(to right, white 60%, transparent); border:none; padding:10px 15px 10px 5px; cursor:pointer; color:#475569; display:none; height:100%;">
                <i data-lucide="chevron-left"></i>
            </button>
            <div id="contextualBarScroll" class="bar-scroll" style="flex:1;" onscroll="updateContextualScrollArrows()">
            <div id="editTool" class="tool-btn" onclick="triggerEdit()" style="display:none;"><div class="tool-btn-icon"><i data-lucide="edit-3"></i></div><span class="tool-btn-label">Edit</span></div>
            <div id="nudgeTool" class="tool-btn" onclick="toggleNudgePanel()"><div class="tool-btn-icon"><i data-lucide="move"></i></div><span class="tool-btn-label">Nudge</span></div>
            <div id="fontTool" class="tool-btn" onclick="toggleFontList()" style="display:none;"><div class="tool-btn-icon"><i data-lucide="type"></i></div><span class="tool-btn-label">Font</span></div>
            <div id="sizeTool" class="tool-btn" onclick="toggleSizePanel()" style="display:none;"><div class="tool-btn-icon"><i data-lucide="maximize"></i></div><span class="tool-btn-label">Size</span></div>
            <div id="boldTool" class="tool-btn" onclick="toggleBold()" style="display:none;"><div class="tool-btn-icon"><i data-lucide="bold"></i></div><span class="tool-btn-label">Bold</span></div>
            <div id="italicTool" class="tool-btn" onclick="toggleItalic()" style="display:none;"><div class="tool-btn-icon"><i data-lucide="italic"></i></div><span class="tool-btn-label">Italic</span></div>
            <div id="attachTool" class="tool-btn" onclick="openReplaceSelectionModal()" style="display:none;"><div class="tool-btn-icon"><i data-lucide="image-plus"></i></div><span class="tool-btn-label">Replace</span><input type="file" id="attachInput" class="hidden" accept="image/*" onchange="attachImage(this)"></div>
            <div id="detachTool" class="tool-btn" onclick="detachImage()" style="display:none;"><div class="tool-btn-icon"><i data-lucide="image-minus"></i></div><span class="tool-btn-label">Detach</span></div>
            <div id="removeBgTool" class="tool-btn" onclick="removeBackgroundFromActiveObject()" style="display:none;"><div class="tool-btn-icon"><i data-lucide="scissors"></i></div><span class="tool-btn-label" id="removeBgLabel">Remove BG</span></div>
            <div id="contextualColorTool" class="tool-btn" onclick="document.getElementById('colorInput').click()"><div class="tool-btn-icon" style="border-bottom:4px solid #7d2ae8;"><i data-lucide="palette"></i></div><span class="tool-btn-label">Color</span><input type="color" id="colorInput" class="hidden" oninput="changeColor(this.value)"></div>
            <div id="contextualLayersTool" class="tool-btn" onclick="toggleLayersModal()"><div class="tool-btn-icon"><i data-lucide="layers"></i></div><span class="tool-btn-label">Layers</span></div>
            <div id="deleteTool" class="tool-btn" onclick="removeActiveElement()"><div class="tool-btn-icon" style="color:#ef4444;"><i data-lucide="trash-2"></i></div><span class="tool-btn-label" style="color:#ef4444;">Delete</span></div>
            </div>
            <button id="scrollRightBtn" onclick="scrollContextualBar(1)" style="position:absolute; right:0; z-index:10; background:linear-gradient(to left, white 60%, transparent); border:none; padding:10px 5px 10px 15px; cursor:pointer; color:#475569; display:none; height:100%;">
                <i data-lucide="chevron-right"></i>
            </button>
        </div>
    </div>

    <!-- Modals -->
    <div id="replaceSelectionModal" style="position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:6000;display:none;align-items:flex-end;justify-content:center;backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px);" onclick="closeReplaceSelectionModal()">
        <div class="products-panel" style="animation:slideUp 0.3s cubic-bezier(0.16,1,0.3,1);padding:24px 20px;border-top-left-radius:24px;border-top-right-radius:24px;background:#fff;width:100%;max-width:448px;" onclick="event.stopPropagation()">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                <h3 style="margin:0;font-size:18px;font-weight:800;color:#1e293b;">Replace Image</h3>
                <button onclick="closeReplaceSelectionModal()" style="background:#f1f5f9;border:none;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#64748b;cursor:pointer;transition:all 0.2s;"><i data-lucide="x" style="width:18px;height:18px;"></i></button>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <button onclick="triggerDeviceUpload()" style="display:flex;flex-direction:column;align-items:center;gap:12px;padding:24px 16px;background:#f8fafc;border:2px solid #e2e8f0;border-radius:16px;cursor:pointer;transition:all 0.2s;" onmouseover="this.style.borderColor='#4f46e5'" onmouseout="this.style.borderColor='#e2e8f0'">
                    <div style="width:48px;height:48px;background:#eef2ff;color:#4f46e5;border-radius:50%;display:flex;align-items:center;justify-content:center;"><i data-lucide="upload" style="width:24px;height:24px;"></i></div>
                    <span style="font-size:14px;font-weight:700;color:#334155;">Upload Photo</span>
                </button>
                <button onclick="triggerProductSelection()" style="display:flex;flex-direction:column;align-items:center;gap:12px;padding:24px 16px;background:#f8fafc;border:2px solid #e2e8f0;border-radius:16px;cursor:pointer;transition:all 0.2s;" onmouseover="this.style.borderColor='#4f46e5'" onmouseout="this.style.borderColor='#e2e8f0'">
                    <div style="width:48px;height:48px;background:#eef2ff;color:#4f46e5;border-radius:50%;display:flex;align-items:center;justify-content:center;"><i data-lucide="shopping-bag" style="width:24px;height:24px;"></i></div>
                    <span style="font-size:14px;font-weight:700;color:#334155;">My Products</span>
                </button>
            </div>
        </div>
    </div>

    <div id="layersModal" onclick="this.style.display='none'">
        <div class="layers-box" onclick="event.stopPropagation()">
            <div class="modal-header">Select Layer</div><div class="modal-divider"></div>
            <div id="layersContainer" style="overflow-y:auto;flex:1;"></div>
            <div class="modal-footer"><button class="modal-btn btn-cancel" onclick="document.getElementById('layersModal').style.display='none'">CLOSE</button></div>
        </div>
    </div>
    <div id="textModal">
        <div class="modal-content-box">
            <div class="modal-header">Add Text</div><div class="modal-divider"></div>
            <div class="modal-body"><textarea id="modalTextArea" placeholder="Add Text here"></textarea></div>
            <div class="modal-footer">
                <button class="modal-btn btn-cancel" onclick="closeTextModal()">CANCEL</button>
                <button class="modal-btn btn-add" onclick="confirmAddText()">ADD</button>
            </div>
        </div>
    </div>
    <div id="stickerModal" onclick="closeStickerModal(event)">
        <div class="sticker-modal-content" onclick="event.stopPropagation()">
            <div class="sticker-header"><div class="sticker-cat-bar">
                @foreach($sticker_categories as $index => $cat)
                    <button class="sticker-cat-btn {{ $index === 0 ? 'active' : '' }}" onclick="filterStickers('{{ $cat->id }}', this)">{{ $cat->name }}</button>
                @endforeach
            </div></div>
            <div class="sticker-body"><div class="sticker-grid" id="stickerContainer"></div></div>
        </div>
    </div>

    <!-- ═══ My Products Panel (Canva-Style Bottom Sheet) ═══ -->
    <div id="myProductsModal" onclick="closeMyProducts(event)">
        <div class="products-panel" onclick="event.stopPropagation()">
            <div class="products-panel-header">
                <div class="products-panel-title">
                    <i data-lucide="shopping-bag"></i>
                    My Products
                    <span class="products-panel-count" id="productsCount">0</span>
                </div>
                <button class="products-panel-close" onclick="closeMyProducts()"><i data-lucide="x"></i></button>
            </div>
            <div class="products-search">
                <input type="text" class="products-search-input" id="productsSearchInput"
                    placeholder="Search products..." oninput="filterProducts(this.value)">
            </div>
            <div class="products-body" id="productsBody">
                <div style="text-align:center;padding:40px 0;color:#94a3b8;">
                    <div style="width:32px;height:32px;border:3px solid #e2e8f0;border-top:3px solid #4f46e5;border-radius:50%;animation:spin 0.8s linear infinite;margin:0 auto;"></div>
                    <p style="margin-top:12px;font-size:13px;font-weight:600;">Loading products...</p>
                </div>
            </div>
            <div class="products-actions" id="productsActions" style="display:none;">
                <button class="products-action-btn btn-full-image" id="btnFullImage" disabled onclick="insertProductImage('full')">
                    <i data-lucide="image" style="width:16px;height:16px;"></i> Full Image
                </button>
                <button class="products-action-btn btn-cutout-image" id="btnCutoutImage" disabled onclick="insertProductImage('transparent')">
                    <i data-lucide="scissors" style="width:16px;height:16px;"></i> Cutout
                </button>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
// ═══════════════════════════════════════════════════════
//  FABRIC.JS UNIVERSAL EDITOR
//  Drag, resize, rotate, text editing, export
//  Powered by Fabric.js v5
// ═══════════════════════════════════════════════════════

var DESIGN_URL = "{{ $designUrl }}";
var BUSINESS = @json($business ?? null);
if (BUSINESS) {
    if (typeof BUSINESS.extra_emails === 'string') BUSINESS.extra_emails = JSON.parse(BUSINESS.extra_emails || '[]');
    if (typeof BUSINESS.extra_mobile_numbers === 'string') BUSINESS.extra_mobile_numbers = JSON.parse(BUSINESS.extra_mobile_numbers || '[]');
    if (typeof BUSINESS.extra_websites === 'string') BUSINESS.extra_websites = JSON.parse(BUSINESS.extra_websites || '[]');
    if (typeof BUSINESS.extra_addresses === 'string') BUSINESS.extra_addresses = JSON.parse(BUSINESS.extra_addresses || '[]');
}
var HIDE_BRANDING = {{ $hideBranding ? 'true' : 'false' }};
var EDITOR_TYPE = "{{ $type }}";
var SUBCATEGORY_IMAGE = "{{ (isset($item) && $type == 'post' && rtrim(get_class($item), '\\') == 'App\Models\GeneralPost' && $item->business_sub_category) ? asset('uploads/' . $item->business_sub_category->image_1) : '' }}";
var POST_AI_DATA = @json(
    (isset($item) && $item instanceof \App\Models\GeneralPost && $item->ai_generated_content)
        ? json_decode($item->ai_generated_content, true)
        : null
);

var fCanvas = null;
var activeObject = null;
var isFrameHidden = false;
var globalBaseImageElement = null;
var extractedTemplateAccentColor = null;
// SERVER-SIDE DETECTED (PHP GD) — 100% reliable, no CORS/canvas issues
var templateIsDark = {{ $phpTemplateIsDark ? 'true' : 'false' }};
var phpBrightnessValue = {{ $phpBrightnessValue }};



// --- DYNAMIC THEMING HELPERS ---
function getContrastColor(hexColor) {
    if (!hexColor) return '#000000';
    let hex = hexColor.replace('#', '');
    if (hex.length === 3) hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
    if (hex.length !== 6) return '#000000';
    let r = parseInt(hex.substr(0, 2), 16);
    let g = parseInt(hex.substr(2, 2), 16);
    let b = parseInt(hex.substr(4, 2), 16);
    let yiq = ((r * 299) + (g * 587) + (b * 114)) / 1000;
    return (yiq >= 128) ? '#000000' : '#FFFFFF';
}

function extractDominantColor(imgElement) {
    let canvas = document.createElement('canvas');
    let ctx = canvas.getContext('2d');
    canvas.width = imgElement.naturalWidth || imgElement.width;
    canvas.height = imgElement.naturalHeight || imgElement.height;
    if(canvas.width === 0 || canvas.height === 0) return null;
    ctx.drawImage(imgElement, 0, 0);
    try {
        let imageData = ctx.getImageData(0, 0, canvas.width, canvas.height).data;
        let colorCounts = {};
        let maxCount = 0;
        let dominantRgb = null;
        // Sample every 50th pixel for performance
        for (let i = 0; i < imageData.length; i += 4 * 50) {
            let r = imageData[i], g = imageData[i+1], b = imageData[i+2], a = imageData[i+3];
            if (a < 128) continue; // Skip transparent
            // Skip pure grays/whites/blacks to find a vibrant accent
            let maxC = Math.max(r,g,b), minC = Math.min(r,g,b);
            let diff = maxC - minC;
            if (diff < 15) continue; // Too gray
            if (maxC < 30 || maxC > 240) continue; // Too dark or too bright

            // Quantize colors (group nearby colors)
            let qR = Math.round(r / 20) * 20;
            let qG = Math.round(g / 20) * 20;
            let qB = Math.round(b / 20) * 20;
            let rgb = `${qR},${qG},${qB}`;
            colorCounts[rgb] = (colorCounts[rgb] || 0) + 1;
            if (colorCounts[rgb] > maxCount) {
                maxCount = colorCounts[rgb];
                dominantRgb = {r: qR, g: qG, b: qB};
            }
        }
        if (!dominantRgb) return null;
        
        let toHex = (c) => {
            let hex = c.toString(16);
            return hex.length == 1 ? "0" + hex : hex;
        };
        return "#" + toHex(dominantRgb.r) + toHex(dominantRgb.g) + toHex(dominantRgb.b);
    } catch (e) {
        console.warn("Could not extract dominant color", e);
        return null;
    }
}

// Calculate overall average brightness of the template image (0=black, 255=white)
function getImageBrightness(imgElement) {
    try {
        let canvas = document.createElement('canvas');
        let ctx = canvas.getContext('2d');
        // Use small size for speed (100x100 is enough for brightness)
        canvas.width = 100;
        canvas.height = 100;
        ctx.drawImage(imgElement, 0, 0, 100, 100);
        let imageData = ctx.getImageData(0, 0, 100, 100).data;
        let rSum = 0, gSum = 0, bSum = 0, count = 0;
        for (let i = 0; i < imageData.length; i += 4 * 4) { // Sample every 4th pixel
            if (imageData[i+3] < 128) continue;
            rSum += imageData[i];
            gSum += imageData[i+1];
            bSum += imageData[i+2];
            count++;
        }
        if (count === 0) return 128; // neutral fallback
        let avgR = rSum / count, avgG = gSum / count, avgB = bSum / count;
        // YIQ brightness formula
        return ((avgR * 299) + (avgG * 587) + (avgB * 114)) / 1000;
    } catch(e) {
        console.warn('getImageBrightness failed:', e);
        return 128; // neutral fallback
    }
}

function tintImageObject(fabricImg, hexColor) {
    try {
        let imgEl = fabricImg.getElement();
        let w = imgEl ? (imgEl.naturalWidth || imgEl.width) : 0;
        let h = imgEl ? (imgEl.naturalHeight || imgEl.height) : 0;
        
        if (!imgEl || w === 0 || h === 0) return;
        
        let canvas = document.createElement('canvas');
        canvas.width = w;
        canvas.height = h;
        let ctx = canvas.getContext('2d');
        
        // Draw original
        ctx.drawImage(imgEl, 0, 0, w, h);
        
        // Apply tint
        ctx.globalCompositeOperation = 'source-in';
        ctx.fillStyle = hexColor;
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        // Update fabric image
        fabricImg.setSrc(canvas.toDataURL('image/png'), () => {
            if (fabricImg.canvas) fabricImg.canvas.renderAll();
        });
    } catch(e) {
        console.warn('Tinting failed', e);
    }
}

function isPixelTransparent(fabricImg, canvasX, canvasY) {
    try {
        let imgEl = fabricImg.getElement();
        let w = imgEl ? (imgEl.naturalWidth || imgEl.width) : 0;
        let h = imgEl ? (imgEl.naturalHeight || imgEl.height) : 0;
        
        if (!imgEl || w === 0 || h === 0) return true;
        
        // Map canvas coordinates to image coordinates
        let localX = (canvasX - fabricImg.left) / fabricImg.scaleX;
        let localY = (canvasY - fabricImg.top) / fabricImg.scaleY;
        
        if (localX < 0 || localY < 0 || localX >= w || localY >= h) return true;
        
        let canvas = document.createElement('canvas');
        canvas.width = 1;
        canvas.height = 1;
        let ctx = canvas.getContext('2d');
        // scale correctly
        ctx.drawImage(imgEl, -localX, -localY, w, h);
        
        let alpha = ctx.getImageData(0, 0, 1, 1).data[3];
        return alpha < 128;
    } catch(e) {
        return false; // Assume not transparent if CORS blocks it
    }
}

function getRegionAverageColor(baseImgElement, x, y, width, height, canvasW, canvasH) {
    if (!baseImgElement) return null;
    let canvas = document.createElement('canvas');
    let ctx = canvas.getContext('2d');
    canvas.width = canvasW || 1080;
    canvas.height = canvasH || 1080;
    if(canvas.width === 0 || canvas.height === 0 || !width || !height) return null;
    
    // Draw the image exactly as it is scaled on the fabric canvas
    ctx.drawImage(baseImgElement, 0, 0, canvas.width, canvas.height);
    
    // Safety clamp
    x = Math.max(0, x);
    y = Math.max(0, y);
    width = Math.min(canvas.width - x, width);
    height = Math.min(canvas.height - y, height);
    if (width <= 0 || height <= 0) return null;

    try {
        let imageData = ctx.getImageData(x, y, width, height).data;
        let rSum = 0, gSum = 0, bSum = 0, count = 0;
        for (let i = 0; i < imageData.length; i += 4 * 5) { // Sample every 5th pixel
            if (imageData[i+3] < 128) continue;
            rSum += imageData[i];
            gSum += imageData[i+1];
            bSum += imageData[i+2];
            count++;
        }
        if (count === 0) return null;
        let r = Math.round(rSum / count);
        let g = Math.round(gSum / count);
        let b = Math.round(bSum / count);
        let toHex = (c) => { let hex = c.toString(16); return hex.length == 1 ? "0" + hex : hex; };
        return "#" + toHex(r) + toHex(g) + toHex(b);
    } catch(e) {
        console.warn("getRegionAverageColor CORS/Canvas error:", e);
        return null;
    }
}
// -------------------------------
var currentFrameConfig = null;
var frameOverlayObjects = [];
var frameImageObjects = [];
var businessObjects = {}; // { name, logo, phone, email, address, website }

// ── Global Canvas Undo/Redo ──
var globalCanvasHistory = [];
var globalHistoryStep = -1;
var isHistoryTracking = false;
var isUndoRedoProcessing = false;

function saveCanvasState() {
    if (!isHistoryTracking || isUndoRedoProcessing || !fCanvas) return;
    
    // Discard any state ahead of the current step
    if (globalHistoryStep < globalCanvasHistory.length - 1) {
        globalCanvasHistory = globalCanvasHistory.slice(0, globalHistoryStep + 1);
    }
    
    // Take snapshot
    const json = JSON.stringify(fCanvas.toJSON(['_isFrameLayer', '_isFrameImage', '_isPlaceholder', '_label', '_originalSrc', '_maskSrc', '_slotLeft', '_slotTop', '_slotWidth', '_slotHeight', '_slotRadius', '_objectType', '_businessKey']));
    globalCanvasHistory.push(json);
    
    // Limit to 10 undo steps (11 states total including base)
    if (globalCanvasHistory.length > 11) {
        globalCanvasHistory.shift();
    }
    
    globalHistoryStep = globalCanvasHistory.length - 1;
    updateCanvasHistoryButtons();
}

function updateCanvasHistoryButtons() {
    const u = document.getElementById('canvasUndoBtn');
    const r = document.getElementById('canvasRedoBtn');
    if (u) u.disabled = globalHistoryStep <= 0;
    if (r) r.disabled = globalHistoryStep >= globalCanvasHistory.length - 1;
    if (window.lucide) window.lucide.createIcons();
}

function undoCanvas() {
    if (globalHistoryStep > 0) {
        isUndoRedoProcessing = true;
        globalHistoryStep--;
        fCanvas.loadFromJSON(globalCanvasHistory[globalHistoryStep], function() {
            fCanvas.renderAll();
            isUndoRedoProcessing = false;
            updateCanvasHistoryButtons();
        });
    }
}

function redoCanvas() {
    if (globalHistoryStep < globalCanvasHistory.length - 1) {
        isUndoRedoProcessing = true;
        globalHistoryStep++;
        fCanvas.loadFromJSON(globalCanvasHistory[globalHistoryStep], function() {
            fCanvas.renderAll();
            isUndoRedoProcessing = false;
            updateCanvasHistoryButtons();
        });
    }
}

var CANVAS_W = 1080;
var CANVAS_H = 1080;

// ── Canvas Init ──
var currentZoom = 1;

function initCanvas() {
    fCanvas = new fabric.Canvas('fabric-canvas', {
        width: CANVAS_W,
        height: CANVAS_H,
        backgroundColor: '#ffffff',
        preserveObjectStacking: true,
        selection: true,
        enableRetinaScaling: true,
    });

    // Scale canvas to fit the screen using Fabric's native zoom
    fitCanvasToScreen();

    initAlignmentGuides();

    fCanvas.on('selection:created', (e) => onObjectSelected(e));
    fCanvas.on('selection:updated', (e) => onObjectSelected(e));
    fCanvas.on('selection:cleared', () => onSelectionCleared());
    fCanvas.on('object:moving', () => updateImageActionPosition());
    fCanvas.on('object:scaling', () => updateImageActionPosition());

    // ── WORKAROUND: Fabric.js findTarget misses objects when canvas has zoom + iframe embed ──
    // Manual fallback using containsPoint which correctly handles viewport-transformed coordinates.
    fCanvas.on('mouse:down', function(opt) {
        if (!opt.target) {
            const ptr = fCanvas.getPointer(opt.e, true);
            const objects = fCanvas.getObjects();
            for (let i = objects.length - 1; i >= 0; i--) {
                const obj = objects[i];
                if (obj.selectable && obj.evented && obj.visible && obj.containsPoint(ptr)) {
                    fCanvas.setActiveObject(obj);
                    fCanvas.requestRenderAll();
                    break;
                }
            }
        }
    });
    // ── END WORKAROUND ──

    // Undo/Redo tracking hooks
    fCanvas.on('object:modified', saveCanvasState);
    fCanvas.on('object:added', saveCanvasState);
    fCanvas.on('object:removed', saveCanvasState);

    // For business_custom_frame: ZIP template provides its own BG layer via JSON config,
    // so skip loading a separate background image to prevent double-rendering.
    if (EDITOR_TYPE !== 'business_custom_frame') {
        loadBackgroundImage(DESIGN_URL);
    }

    // Add business info elements
    if (!HIDE_BRANDING && BUSINESS) {
        addBusinessElements();
    }
}

function fitCanvasToScreen() {
    const wrapper = document.getElementById('canvas-wrapper');
    if (!wrapper || !fCanvas) return;
    const section = wrapper.parentElement;
    const maxW = section.offsetWidth - 32;
    const maxH = window.innerHeight * 0.50;
    const intW = fCanvas.internalW || CANVAS_W;
    const intH = fCanvas.internalH || CANVAS_H;
    const scale = Math.min(maxW / intW, maxH / intH, 1);
    currentZoom = scale;

    // Fabric.js native zoom — this correctly remaps mouse coordinates
    fCanvas.setZoom(scale);
    // Set the physical canvas size to the scaled dimensions
    fCanvas.setWidth(intW * scale);
    fCanvas.setHeight(intH * scale);
    if (fCanvas.calcOffset) fCanvas.calcOffset();
}

function autoFilterCategories(width, height) {
    let ratio = width / height;
    let detectedCat = "square";
    if (Math.abs(ratio - 1) <= 0.1) detectedCat = "square";
    else if (Math.abs(ratio - 0.8) <= 0.1) detectedCat = "portrait";
    else if (Math.abs(ratio - 0.5625) <= 0.1) detectedCat = "story";
    else if (Math.abs(ratio - 1.777) <= 0.1) detectedCat = "landscape";

    let matchedOption = null;
    document.querySelectorAll('.filter-option').forEach(el => {
        let catName = el.innerText.trim().toLowerCase();
        if (catName !== 'all categories') {
            if (catName === detectedCat) {
                el.style.display = 'block';
                matchedOption = el;
            } else {
                el.style.display = 'none';
            }
        } else {
            el.style.display = 'none'; // hide 'All Categories'
        }
    });
    
    if (matchedOption) {
         let onclickStr = matchedOption.getAttribute('onclick');
         let match = onclickStr ? onclickStr.match(/filterFrames\('([^']+)',\s*'([^']+)'/) : null;
         if(match) filterFrames(match[1], match[2], matchedOption);
    }
}

function loadBackgroundImage(url) {
    if (!url || url.trim() === '') {
        // For custom posts without a base poster
        resizeCanvas(1080, 1080);
        return;
    }

    fabric.Image.fromURL(url, (img) => {
        if (!img) return;
        
        // 2. Keep Canvas Square as requested (1080x1080)
        let newW = 1080;
        let newH = 1080;
        resizeCanvas(newW, newH);

        // 3. Lock Frame Categories
        autoFilterCategories(img.width, img.height);

        // 4. Set Background - using cover to ensure it fits the square without stretching
        let scale = Math.max(newW / img.width, newH / img.height);
        fCanvas.setBackgroundImage(img, fCanvas.renderAll.bind(fCanvas), {
            originX: 'center', originY: 'center',
            left: newW / 2, top: newH / 2,
            scaleX: scale,
            scaleY: scale,
        });
    }, { crossOrigin: 'anonymous' });
}

// ── Resize Canvas (for frame configs with different aspect ratios) ──
function resizeCanvas(w, h) {
    fCanvas.internalW = w;
    fCanvas.internalH = h;
    const wrapper = document.getElementById('canvas-wrapper');
    const section = wrapper.parentElement;
    const maxW = section.offsetWidth - 32;
    const maxH = window.innerHeight * 0.50;
    const scale = Math.min(maxW / w, maxH / h, 1);
    currentZoom = scale;
    fCanvas.setZoom(scale);
    fCanvas.setWidth(w * scale);
    fCanvas.setHeight(h * scale);
    if (fCanvas.calcOffset) fCanvas.calcOffset();
}

// ── Business Elements ──
function addBusinessElements() {
    if (!BUSINESS) return;
    const cW = CANVAS_W, cH = CANVAS_H;

    // Logo
    if (BUSINESS.logo) {
        fabric.Image.fromURL("{{ asset('uploads') }}/" + BUSINESS.logo, (img) => {
            if (!img) return;
            const scale = 120 / Math.max(img.width, img.height);
            img.set({ left: cW * 0.08, top: cH * 0.08, scaleX: scale, scaleY: scale, _objectType: 'logo', _label: 'Logo', _businessKey: 'logo' });
            
            let handledByFrame = false;
            if (fCanvas) {
                fCanvas.getObjects().forEach(o => {
                    if (o._isFrameLayer && o._businessKey === 'logo') {
                        handledByFrame = true;
                    }
                });
            }
            const btn = document.getElementById('toggle-logo');
            if (handledByFrame || (btn && btn.classList.contains('inactive'))) {
                img.set('visible', false);
            }
            
            fCanvas.add(img);
            businessObjects.logo = img;
            fCanvas.renderAll();
        }, { crossOrigin: 'anonymous' });
    }

    // Name - hidden by default
    const nameObj = new fabric.Textbox("{{ $business->name ?? '' }}", {
        left: cW * 0.08, top: cH * 0.82, width: cW * 0.6,
        fontSize: 36, fontWeight: '900', fontFamily: 'Inter', fill: '#000000',
        visible: false, editable: true, _objectType: 'text', _label: 'Business Name', _businessKey: 'name',
    });
    fCanvas.add(nameObj);
    businessObjects.name = nameObj;

    // Phone - hidden by default
    const phoneObj = new fabric.Textbox("{{ $business->mobile_no ?? '' }}", {
        left: cW * 0.08, top: cH * 0.88, width: cW * 0.4,
        fontSize: 22, fontWeight: '700', fontFamily: 'Inter', fill: '#000000',
        visible: false, editable: true, _objectType: 'text', _label: 'Phone', _businessKey: 'phone',
    });
    fCanvas.add(phoneObj);
    businessObjects.phone = phoneObj;

    // Email — hidden by default
    const emailObj = new fabric.Textbox("{{ $business->email ?? '' }}", {
        left: cW * 0.08, top: cH * 0.92, width: cW * 0.5,
        fontSize: 20, fontWeight: '600', fontFamily: 'Inter', fill: '#000000',
        visible: false, editable: true, _objectType: 'text', _label: 'Email', _businessKey: 'email',
    });
    fCanvas.add(emailObj);
    businessObjects.email = emailObj;

    // Address — hidden by default
    const addrObj = new fabric.Textbox("{{ $business->address ?? '' }}", {
        left: cW * 0.5, top: cH * 0.88, width: cW * 0.45,
        fontSize: 20, fontWeight: '600', fontFamily: 'Inter', fill: '#000000',
        visible: false, editable: true, _objectType: 'text', _label: 'Address', _businessKey: 'address',
    });
    fCanvas.add(addrObj);
    businessObjects.address = addrObj;

    // Website — hidden by default
    const webObj = new fabric.Textbox("{{ $business->website ?? '' }}", {
        left: cW * 0.5, top: cH * 0.92, width: cW * 0.45,
        fontSize: 20, fontWeight: '600', fontFamily: 'Inter', fill: '#000000',
        visible: false, editable: true, _objectType: 'text', _label: 'Website', _businessKey: 'website',
    });
    fCanvas.add(webObj);
    businessObjects.website = webObj;

    fCanvas.renderAll();
}

function toggleBusinessElement(key, btn) {
    const isCurrentlyInactive = btn.classList.contains('inactive');
    const newState = isCurrentlyInactive; // if inactive, we want to show it
    
    if (newState) {
        btn.classList.remove('inactive');
    } else {
        btn.classList.add('inactive');
    }

    let handledByFrame = false;
    fCanvas.getObjects().forEach(o => {
        if (o._isFrameLayer && o._businessKey === key) {
            o.set('visible', newState);
            handledByFrame = true;
        }
    });

    if (businessObjects[key]) {
        if (handledByFrame) {
            businessObjects[key].set('visible', false);
        } else {
            businessObjects[key].set('visible', newState);
        }
        if (!newState && fCanvas.getActiveObject() === businessObjects[key]) {
            fCanvas.discardActiveObject();
        }
    }

    fCanvas.renderAll();
}

// ── Alignment Guides ──
var alignGuidelines = [];
var SNAP_THRESHOLD = 8;

// Helper: get internal (unzoomed) canvas dimensions
function getInternalSize() {
    const z = fCanvas.getZoom() || 1;
    return { w: fCanvas.getWidth() / z, h: fCanvas.getHeight() / z };
}

function initAlignmentGuides() {
    fCanvas.on('object:moving', function(e) {
        const obj = e.target;
        const { w: cW, h: cH } = getInternalSize();
        const cx = cW / 2, cy = cH / 2;
        const ox = obj.left + (obj.width * obj.scaleX) / 2;
        const oy = obj.top + (obj.height * obj.scaleY) / 2;
        clearGuideLines();
        if (Math.abs(ox - cx) < SNAP_THRESHOLD) { obj.set('left', cx - (obj.width * obj.scaleX) / 2); drawGuideLine(cx, 0, cx, cH); }
        if (Math.abs(oy - cy) < SNAP_THRESHOLD) { obj.set('top', cy - (obj.height * obj.scaleY) / 2); drawGuideLine(0, cy, cW, cy); }
        if (Math.abs(obj.left) < SNAP_THRESHOLD) obj.set('left', 0);
        if (Math.abs(obj.top) < SNAP_THRESHOLD) obj.set('top', 0);
    });
    fCanvas.on('object:moved', clearGuideLines);
}

function drawGuideLine(x1, y1, x2, y2) {
    const line = new fabric.Line([x1, y1, x2, y2], { stroke: '#d946ef', strokeWidth: 2, selectable: false, evented: false, strokeDashArray: [5,5], _isGuideLine: true });
    fCanvas.add(line); alignGuidelines.push(line);
}
function clearGuideLines() { alignGuidelines.forEach(l => fCanvas.remove(l)); alignGuidelines = []; }

// ── Selection Events ──
function onObjectSelected(e) {
    activeObject = e.selected ? e.selected[0] : (e.target || null);
    if (!activeObject) return;
    
    // Always use the unified bottom contextual bar
    showContextualBar(activeObject);
}
function onSelectionCleared() { activeObject = null; hideContextualBar(); }

// Old Floating Image Action Box Removed in favor of unified bottom Contextual Bar

// Reposition contextual tools if needed (not required for bottom bar, kept for future use)
function updateImageActionPosition() {}

// Image Action handlers mapped from bottom bar now
function handleImageAction(action) {
    switch (action) {
        case 'replace': document.getElementById('attachInput').click(); break;
        case 'detach': detachImage(); break;
        case 'remove_bg': removeBackgroundFromActiveObject(); break;
        case 'delete': removeActiveElement(); break;
    }
}

function scrollContextualBar(dir) {
    const el = document.getElementById('contextualBarScroll');
    if(el) el.scrollBy({ left: dir * 150, behavior: 'smooth' });
}
function updateContextualScrollArrows() {
    const el = document.getElementById('contextualBarScroll');
    const lBtn = document.getElementById('scrollLeftBtn');
    const rBtn = document.getElementById('scrollRightBtn');
    if(el && lBtn && rBtn) {
        if(el.scrollWidth > el.clientWidth) {
            lBtn.style.display = el.scrollLeft > 0 ? 'block' : 'none';
            rBtn.style.display = Math.ceil(el.scrollLeft) < (el.scrollWidth - el.clientWidth) ? 'block' : 'none';
        } else {
            lBtn.style.display = 'none';
            rBtn.style.display = 'none';
        }
    }
}

function showContextualBar(obj) {
    const bar = document.getElementById('contextualBar');
    if (bar) {
        bar.style.display = 'flex';
        setTimeout(updateContextualScrollArrows, 50);
    }
    closeAllPanels();
    
    const isText = obj.type === 'textbox' || obj.type === 'i-text';
    const isImage = obj.type === 'image';
    const isShapeOrIcon = obj.type === 'path' || obj.type === 'rect' || obj.type === 'circle' || obj.type === 'polygon' || obj.type === 'group' || obj.type === 'line';
    
    // Every image should be able to be replaced or detached/removed-bg (unless it's a fixed system element)
    const canReplaceDetach = isImage; 
    const isRemovableBg = isImage;
    const canColor = isText || isShapeOrIcon || (isImage && obj._isFrameImage !== true);
    
    const d = (id, show) => { const el = document.getElementById(id); if (el) el.style.display = show ? 'flex' : 'none'; };
    
    d('editTool', isText); 
    d('fontTool', isText); 
    d('sizeTool', isText || isShapeOrIcon || isImage); 
    d('boldTool', isText); 
    d('italicTool', isText);
    
    d('attachTool', canReplaceDetach); 
    d('detachTool', canReplaceDetach);
    d('removeBgTool', isRemovableBg);
    
    d('contextualColorTool', canColor); 
    d('contextualLayersTool', true); 
    d('deleteTool', true);
    if (isText) {
        const fss = document.getElementById('fontSizeSlider'); const fsd = document.getElementById('fontSizeDisplay');
        if (fss) fss.value = obj.fontSize || 24; if (fsd) fsd.innerText = Math.round(obj.fontSize || 24);
    }
}
function hideContextualBar() { const bar = document.getElementById('contextualBar'); if (bar) bar.style.display = 'none'; closeAllPanels(); }

// ── Replace Image Modal Actions ──
function openReplaceSelectionModal() {
    document.getElementById('replaceSelectionModal').style.display = 'flex';
    if (window.lucide) window.lucide.createIcons();
}
function closeReplaceSelectionModal() {
    document.getElementById('replaceSelectionModal').style.display = 'none';
}
function triggerDeviceUpload() {
    closeReplaceSelectionModal();
    document.getElementById('attachInput').click();
}
function triggerProductSelection() {
    closeReplaceSelectionModal();
    openMyProducts();
}

// ── Background Removal (Smart: Photoroom API → Client-side Fallback) ──
var _bgRemovalModule = null;

async function removeBackgroundFromActiveObject() {
    if (!activeObject || activeObject.type !== 'image') return;

    // CRITICAL: Save reference NOW before any async work changes it
    const targetObj = activeObject;

    const overlay = document.getElementById('processingOverlay');
    if (overlay) overlay.style.display = 'flex';
    const label = document.getElementById('removeBgLabel');
    if (label) label.innerText = "Working...";

    // Get image data from the canvas object at FULL resolution
    const dataURL = targetObj.toDataURL({ format: 'png', quality: 1, multiplier: 2 });

    let resultImageURL = null;

    // ── Step 1: Try Photoroom paid API first ──
    try {
        if (label) label.innerText = "Removing BG (AI)...";
        console.log("🔄 Attempting Photoroom API...");

        const response = await fetch('{{ route("remove-background") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ image_base64: dataURL })
        });

        const data = await response.json();

        if (data.success && !data.fallback && data.image) {
            console.log("✅ Photoroom API success! Remaining limit:", data.remaining_limit);
            resultImageURL = data.image;
        } else {
            console.log("⚠️ Photoroom API returned fallback:", data.message);
            // Will proceed to client-side fallback below
        }
    } catch (apiErr) {
        console.warn("⚠️ Photoroom API call failed, falling back to client-side:", apiErr.message);
    }

    // ── Step 2: Fallback to client-side @imgly if API didn't work ──
    if (!resultImageURL) {
        if (window.FlutterBridge) {
            // Mobile app: Require Rewarded Ad to use the free offline BG removal!
            if (label) label.innerText = "Ad Required";
            const callbackId = 'bg_remove_' + Date.now();
            window.onRewardedAdWatched = async function(id) {
                if (id === callbackId) {
                    await performLocalBgRemoval(dataURL, label, overlay, targetObj);
                }
            };
            window.FlutterBridge.postMessage('showRewardedAd:' + callbackId);
            return; // Stop here, wait for ad to finish
        } else {
            // Web: Just perform local BG removal directly
            await performLocalBgRemoval(dataURL, label, overlay, targetObj);
            return;
        }
    } else {
        // API succeeded, replace image directly
        replaceBgImage(resultImageURL, targetObj, overlay, label);
    }
}

async function performLocalBgRemoval(dataURL, label, overlay, targetObj) {
    try {
        if (overlay) overlay.style.display = 'flex';
        if (label) label.innerText = "Removing BG (Local)...";
        console.log("🔄 Using client-side @imgly background removal...");

        if (!_bgRemovalModule) {
            console.log("Loading AI Background Removal module...");
            _bgRemovalModule = await import('https://cdn.jsdelivr.net/npm/@imgly/background-removal@1.5.1/+esm');
            console.log("✅ AI module loaded successfully.");
        }

        const removeBg = _bgRemovalModule.default || _bgRemovalModule.removeBackground;
        if (!removeBg) throw new Error("removeBackground function not found in module");

        const blob = await removeBg(dataURL, {
            model: 'isnet',
            output: { format: 'image/png', quality: 1 },
        });
        const resultImageURL = URL.createObjectURL(blob);
        console.log("✅ Client-side background removal success.");
        
        replaceBgImage(resultImageURL, targetObj, overlay, label);
    } catch (localErr) {
        console.error("❌ Client-side background removal also failed:", localErr);
        alert("Background removal failed: " + localErr.message);
        if (overlay) overlay.style.display = 'none';
        if (label) label.innerText = "Remove BG";
    }
}

function replaceBgImage(resultImageURL, targetObj, overlay, label) {
    fabric.Image.fromURL(resultImageURL, (newImg) => {
        if (!newImg) {
            alert("Background removal failed: could not create new image.");
            if (overlay) overlay.style.display = 'none';
            if (label) label.innerText = "Remove BG";
            return;
        }
        newImg.set({
            left: targetObj.left,
            top: targetObj.top,
            scaleX: targetObj.scaleX,
            scaleY: targetObj.scaleY,
            angle: targetObj.angle,
            originX: targetObj.originX,
            originY: targetObj.originY,
            _objectType: targetObj._objectType,
            _label: targetObj._label,
            _businessKey: targetObj._businessKey
        });
        const idx = fCanvas.getObjects().indexOf(targetObj);
        fCanvas.remove(targetObj);
        fCanvas.insertAt(newImg, idx >= 0 ? idx : fCanvas.getObjects().length, false);

        if (targetObj._businessKey && businessObjects[targetObj._businessKey]) {
            businessObjects[targetObj._businessKey] = newImg;
        }

        fCanvas.setActiveObject(newImg);
        fCanvas.renderAll();

        if (overlay) overlay.style.display = 'none';
        if (label) label.innerText = "Remove BG";
    }, { crossOrigin: 'anonymous' });
}

// ── Text ──
function addText() { fCanvas.discardActiveObject(); document.getElementById('textModal').style.display = 'flex'; document.getElementById('modalTextArea').value = ''; }
function closeTextModal() { document.getElementById('textModal').style.display = 'none'; }
function confirmAddText() {
    const txt = document.getElementById('modalTextArea').value;
    if (!txt) { closeTextModal(); return; }
    const { w: cW, h: cH } = getInternalSize();
    const t = new fabric.Textbox(txt, {
        left: cW / 2, top: cH / 2, originX: 'center', originY: 'center',
        fontSize: 48, fontWeight: '900', fontFamily: 'Inter', fill: '#000000',
        textAlign: 'center', width: cW * 0.6, editable: true, _objectType: 'text', _label: 'Custom Text',
    });
    fCanvas.add(t); fCanvas.setActiveObject(t); fCanvas.renderAll(); closeTextModal();
}
function triggerEdit() { if (activeObject && (activeObject.type === 'textbox' || activeObject.type === 'i-text')) { activeObject.enterEditing(); activeObject.selectAll(); fCanvas.renderAll(); } }

// ── Style (Selection-Aware) ──
// Helper: Check if there's an active text selection within a Textbox in editing mode
function hasTextSelection(obj) {
    return obj && obj.isEditing && obj.selectionStart !== obj.selectionEnd;
}

function toggleBold() {
    if (!activeObject || activeObject.type !== 'textbox') return;
    if (hasTextSelection(activeObject)) {
        const styles = activeObject.getSelectionStyles(activeObject.selectionStart, activeObject.selectionEnd);
        const allBold = styles.every(s => s.fontWeight >= 700 || s.fontWeight === 'bold' || s.fontWeight === '900');
        activeObject.setSelectionStyles({ fontWeight: allBold ? '400' : '900' });
    } else {
        activeObject.set('fontWeight', (activeObject.fontWeight >= 700) ? '400' : '900');
    }
    fCanvas.renderAll();
}
function toggleItalic() {
    if (!activeObject || activeObject.type !== 'textbox') return;
    if (hasTextSelection(activeObject)) {
        const styles = activeObject.getSelectionStyles(activeObject.selectionStart, activeObject.selectionEnd);
        const allItalic = styles.every(s => s.fontStyle === 'italic');
        activeObject.setSelectionStyles({ fontStyle: allItalic ? 'normal' : 'italic' });
    } else {
        activeObject.set('fontStyle', activeObject.fontStyle === 'italic' ? 'normal' : 'italic');
    }
    fCanvas.renderAll();
}
function changeFontSize(v) {
    if (!activeObject || activeObject.type !== 'textbox') return;
    if (hasTextSelection(activeObject)) {
        activeObject.setSelectionStyles({ fontSize: parseInt(v) });
    } else {
        activeObject.set('fontSize', parseInt(v));
    }
    fCanvas.renderAll();
    document.getElementById('fontSizeDisplay').innerText = v;
}
function setFont(f) {
    if (!activeObject || activeObject.type !== 'textbox') return;
    if (hasTextSelection(activeObject)) {
        activeObject.setSelectionStyles({ fontFamily: f });
    } else {
        activeObject.set('fontFamily', f);
    }
    fCanvas.renderAll();
    toggleFontList();
}
function changeColor(v) {
    if (!activeObject) return;
    if (activeObject.type === 'textbox' || activeObject.type === 'i-text') {
        if (hasTextSelection(activeObject)) {
            activeObject.setSelectionStyles({ fill: v });
        } else {
            activeObject.set('fill', v);
        }
    } else if (activeObject.type === 'path' || activeObject.type === 'rect' || activeObject.type === 'circle' || activeObject.type === 'polygon' || activeObject.type === 'line') {
        if (activeObject.fill) activeObject.set('fill', v);
        if (activeObject.stroke) activeObject.set('stroke', v);
    } else if (activeObject.type === 'group') {
        activeObject.getObjects().forEach(function(obj) {
            if (obj.fill) obj.set('fill', v);
            if (obj.stroke) obj.set('stroke', v);
        });
    } else if (activeObject.type === 'image') {
        // Handle images (e.g., PSD shape layers, icons) by applying a BlendColor filter
        activeObject.filters = activeObject.filters || [];
        // Remove existing BlendColor filters first
        activeObject.filters = activeObject.filters.filter(f => f && f.type !== 'BlendColor');
        // Add new Tint filter
        activeObject.filters.push(new fabric.Image.filters.BlendColor({
            color: v,
            mode: 'tint',
            alpha: 1
        }));
        activeObject.applyFilters();
    }
    fCanvas.renderAll();
    
    if (typeof saveCanvasState === 'function' && typeof isHistoryTracking !== 'undefined' && isHistoryTracking) {
        saveCanvasState();
    }
}
function toggleSizePanel() { const p = document.getElementById('fontSizeControl'); p.style.display = p.style.display === 'block' ? 'none' : 'block'; }
function toggleNudgePanel() { 
    const p = document.getElementById('nudgeControl'); 
    p.style.display = p.style.display === 'flex' ? 'none' : 'flex';
}
function nudgeObject(direction) {
    if (!activeObject) return;
    const step = 2; // move by 2 pixels per click
    if (direction === 'up') activeObject.set('top', activeObject.top - step);
    else if (direction === 'down') activeObject.set('top', activeObject.top + step);
    else if (direction === 'left') activeObject.set('left', activeObject.left - step);
    else if (direction === 'right') activeObject.set('left', activeObject.left + step);
    activeObject.setCoords();
    fCanvas.renderAll();
    if (typeof saveCanvasState === 'function' && typeof isHistoryTracking !== 'undefined' && isHistoryTracking) {
        saveCanvasState();
    }
}

// ── Sticker ──
var allStickers = @json($stickers);
function addSticker() { fCanvas.discardActiveObject(); document.getElementById('stickerModal').style.display = 'flex'; const f = document.querySelector('.sticker-cat-btn'); if (f) f.click(); }
function closeStickerModal() { document.getElementById('stickerModal').style.display = 'none'; }
function filterStickers(catId, btn) {
    document.querySelectorAll('.sticker-cat-btn').forEach(b => b.classList.remove('active')); btn.classList.add('active');
    const filtered = allStickers.filter(s => s.sticker_category_id == catId);
    const c = document.getElementById('stickerContainer'); c.innerHTML = '';
    filtered.forEach(s => { const d = document.createElement('div'); d.className = 'sticker-item'; d.innerHTML = `<img src="{{ asset('uploads') }}/${s.image}">`; d.onclick = () => selectSticker(`{{ asset('uploads') }}/${s.image}`); c.appendChild(d); });
}
function selectSticker(src) {
    const { w: cW, h: cH } = getInternalSize();
    fabric.Image.fromURL(src, (img) => {
        if (!img) return;
        const scale = (cW * 0.25) / Math.max(img.width, img.height);
        img.set({ left: cW/2, top: cH/2, originX: 'center', originY: 'center', scaleX: scale, scaleY: scale, _objectType: 'sticker', _label: 'Sticker' });
        fCanvas.add(img); fCanvas.setActiveObject(img); fCanvas.renderAll(); closeStickerModal();
    }, { crossOrigin: 'anonymous' });
}

// ── Logo Upload ──
function uploadLogo(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        const { w: cW, h: cH } = getInternalSize();
        fabric.Image.fromURL(e.target.result, (img) => {
            if (!img) return;
            const scale = (cW * 0.25) / Math.max(img.width, img.height);
            img.set({ left: cW/2, top: cH/2, originX: 'center', originY: 'center', scaleX: scale, scaleY: scale, _objectType: 'logo', _label: 'Logo' });
            fCanvas.add(img); fCanvas.setActiveObject(img); fCanvas.renderAll();
        });
    };
    reader.readAsDataURL(input.files[0]);
}

// ── Delete ──
function removeActiveElement() {
    if (!activeObject) return;
    // If it's a business element, just hide instead of removing
    if (activeObject._businessKey) {
        activeObject.set('visible', false);
        const btn = document.getElementById('toggle-' + activeObject._businessKey);
        if (btn) btn.classList.add('inactive');
    } else {
        fCanvas.remove(activeObject);
    }
    fCanvas.discardActiveObject(); fCanvas.renderAll(); activeObject = null;
}

// ── Panels ──
function toggleFontList() { const p = document.getElementById('fontPanel'); p.style.display = p.style.display === 'flex' ? 'none' : 'flex'; }
function closeAllPanels() {
    ['framePanel','frameColorPanel','fontPanel','fontSizeControl','nudgeControl'].forEach(id => { const el = document.getElementById(id); if (el) el.style.display = 'none'; });
}
function toggleFilterMenu() { fCanvas.discardActiveObject(); fCanvas.renderAll(); document.getElementById('filterMenu').classList.toggle('active'); }
function filterFrames(catId, label, el) {
    document.querySelectorAll('.filter-option').forEach(e => e.classList.remove('selected')); el.classList.add('selected');
    document.getElementById('filterMenu').classList.remove('active');
    document.querySelectorAll('.frame-item').forEach(item => { const c = item.getAttribute('data-category-id'); item.style.display = (catId === 'all' || c == catId || c === 'all') ? 'block' : 'none'; });
}
document.addEventListener('click', function(e) { const c = document.querySelector('.filter-container'); if (c && !c.contains(e.target)) document.getElementById('filterMenu').classList.remove('active'); });

// ── Frame Toggle ──
function toggleFramePanel() {
    const p = document.getElementById('framePanel');
    const btn = document.getElementById('frame-toggle-btn');
    const isPanelOpen = p.style.display === 'block';

    if (isPanelOpen) {
        // Panel is open — toggle frame visibility on canvas
        isFrameHidden = !isFrameHidden;
        frameOverlayObjects.forEach(o => o.set('visible', !isFrameHidden));
        fCanvas.getObjects().forEach(o => { if (o._isFrameLayer) o.set('visible', !isFrameHidden); });
        fCanvas.renderAll();
        if (btn) {
            if (isFrameHidden) {
                btn.style.backgroundColor = ''; btn.style.color = '';
                btn.classList.add('inactive');
            } else {
                btn.classList.remove('inactive');
                btn.style.backgroundColor = '#4f46e5'; btn.style.color = 'white';
            }
        }
    } else {
        // Panel is closed — open it
        closeAllPanels();
        p.style.display = 'block';
        if (window.lucide) window.lucide.createIcons();
        if (btn) { btn.classList.remove('inactive'); btn.style.backgroundColor = '#4f46e5'; btn.style.color = 'white'; }
    }
}

function toggleFrameOverlays() {
    fCanvas.discardActiveObject(); isFrameHidden = !isFrameHidden;
    frameOverlayObjects.forEach(o => o.set('visible', !isFrameHidden));
    fCanvas.getObjects().forEach(o => { if (o._isFrameLayer) o.set('visible', !isFrameHidden); });
    fCanvas.renderAll();
    const btn = document.getElementById('frame-toggle-btn');
    if (btn) {
        if (isFrameHidden) {
            btn.style.backgroundColor = ''; btn.style.color = '';
            btn.classList.add('inactive');
        } else {
            btn.classList.remove('inactive');
            btn.style.backgroundColor = '#4f46e5'; btn.style.color = 'white';
        }
    }
}

// ── Frame Change ──
// ── Frame Filters ──
function applyFrameFilters() {
    const filter = document.getElementById('editorThemeFilter').value;
    
    // Calculate user's available requirements
    let availAddress = BUSINESS && BUSINESS.address ? 1 + (BUSINESS.extra_addresses ? BUSINESS.extra_addresses.length : 0) : 0;
    let availEmail = BUSINESS && BUSINESS.email ? 1 + (BUSINESS.extra_emails ? BUSINESS.extra_emails.length : 0) : 0;
    let availPhone = BUSINESS && BUSINESS.mobile_no ? 1 + (BUSINESS.extra_mobile_numbers ? BUSINESS.extra_mobile_numbers.length : 0) : 0;
    let availWebsite = BUSINESS && BUSINESS.website ? 1 + (BUSINESS.extra_websites ? BUSINESS.extra_websites.length : 0) : 0;

    if (BUSINESS && BUSINESS.hidden_frame_fields) {
        let hidden = BUSINESS.hidden_frame_fields;
        if (typeof hidden === 'string') {
            try { hidden = JSON.parse(hidden); } catch(e) { hidden = {}; }
        }
        if (hidden.addresses) availAddress -= hidden.addresses.length;
        if (hidden.emails) availEmail -= hidden.emails.length;
        if (hidden.mobile_numbers) availPhone -= hidden.mobile_numbers.length;
        if (hidden.websites) availWebsite -= hidden.websites.length;
    }

    const frameItems = document.querySelectorAll('.frame-item');
    let firstVisible = null;
    
    frameItems.forEach(item => {
        let show = true;
        
        // Theme filter
        const theme = item.getAttribute('data-theme');
        if (filter !== 'all' && theme !== 'all' && theme !== filter) {
            show = false;
        }
        
        // Requirements filter
        const reqAddress = parseInt(item.getAttribute('data-req-address') || '0');
        const reqEmail = parseInt(item.getAttribute('data-req-email') || '0');
        const reqPhone = parseInt(item.getAttribute('data-req-phone') || '0');
        const reqWebsite = parseInt(item.getAttribute('data-req-website') || '0');
        
        if (availAddress !== reqAddress || availEmail !== reqEmail || availPhone !== reqPhone || availWebsite !== reqWebsite) {
            show = false;
        }
        
        item.style.display = show ? 'block' : 'none';
        
        if (show && !firstVisible) {
            firstVisible = item;
        }
    });
}

function changeFrame(url, element) {
    fCanvas.discardActiveObject(); isFrameHidden = false;
    const btn = document.getElementById('frame-toggle-btn'); if (btn) btn.classList.remove('inactive');
    const si = document.getElementById('activeFrameImg-source'); if (si) si.value = url;
    if (element) {
        document.querySelectorAll('.frame-item').forEach(i => i.classList.remove('selected')); element.classList.add('selected');
        const ca = element.getAttribute('data-config');
        
        let isBase = false;
        
        console.log('[FRAME] changeFrame called. URL:', url);
        console.log('[FRAME] data-config value:', ca ? (ca.substring(0, 100) + '...') : 'NULL/EMPTY');
        console.log('[FRAME] Rendering mode:', (!ca || ca === 'null' || ca === 'undefined' || ca === '') ? '🖼️ PNG OVERLAY (no JSON config)' : '📦 ZIP/JSON CONFIG (full layer rendering)');

        // ── KEY FIX: For JSON-based PosterMaker frames, we MUST use applyFrameConfig to render the layers.
        // For simple PNG frames (CustomFrame, BusinessFrame), they won't have a config, so we load them as PNG overlays.
        if (!ca || ca === 'null' || ca === 'undefined' || ca === '') {
            // Remove old overlay layers
            frameOverlayObjects.forEach(o => fCanvas.remove(o)); frameOverlayObjects = [];
            fCanvas.getObjects().filter(o => o._isFrameLayer).forEach(o => fCanvas.remove(o));
            
            // For simple PNG frames, we must re-enable the raw business objects because the PNG 
            // frame provides no text natively, and Custom Posts hide them by default.
            if (typeof businessObjects !== 'undefined') {
                for (const key in businessObjects) {
                    if (businessObjects[key]) {
                        businessObjects[key].set('visible', true);
                        const btn = document.getElementById('toggle-' + key);
                        if (btn) btn.classList.remove('inactive');
                    }
                }
            }
            
            // Load the frame PNG as a simple overlay — preserve aspect ratio, position at bottom
            if (url) {
                const iW = fCanvas.internalW || CANVAS_W;
                const iH = fCanvas.internalH || CANVAS_H;
                fabric.Image.fromURL(url + '?v=' + Date.now(), (img) => {
                    if (!img) { fCanvas.renderAll(); return; }
                    // Scale to canvas width, preserve aspect ratio
                    const scale = iW / img.width;
                    const scaledH = img.height * scale;
                    img.set({
                        left: 0,
                        top: iH - scaledH, // Position at bottom
                        scaleX: scale,
                        scaleY: scale,
                        selectable: false, evented: false,
                        _isFrameLayer: true, _label: 'Frame Overlay',
                    });
                    fCanvas.add(img); frameOverlayObjects.push(img);
                    fCanvas.renderAll();
                }, { crossOrigin: 'anonymous' });
            } else {
                fCanvas.renderAll();
            }
            return; // Don't proceed to applyFrameConfig
        }

        if (ca && ca !== 'null' && ca !== 'undefined' && ca !== '') {
            try { currentFrameConfig = JSON.parse(ca); applyFrameConfig(currentFrameConfig, isBase); } catch(e) { currentFrameConfig = null; applyFrameConfig(null, isBase); }
        } else { currentFrameConfig = null; applyFrameConfig(null, isBase); }
    }
}

// ── Apply Frame Config ──
async function applyFrameConfig(config, isBaseTemplate = false) {
    if (!isBaseTemplate) {
        // Remove old overlay frame layers
        frameOverlayObjects.forEach(o => fCanvas.remove(o)); frameOverlayObjects = [];
        fCanvas.getObjects().filter(o => o._isFrameLayer).forEach(o => fCanvas.remove(o));
    }

    const hideBaseOnCustom = (EDITOR_TYPE === 'post' || EDITOR_TYPE === 'custom' || EDITOR_TYPE === 'business_custom_frame');

    if (!config || !config.layers) {
        // No config — this is a simple PNG overlay frame
        
        if (EDITOR_TYPE !== 'business_custom_frame') {
            // For festival/category: reset canvas and show base poster
            resizeCanvas(CANVAS_W, CANVAS_H);
            loadBackgroundImage(DESIGN_URL);
        }
        // For business_custom_frame: DON'T touch the base template, just overlay on top

        // Load the frame image as overlay on top
        const sourceInput = document.getElementById('activeFrameImg-source');
        if (sourceInput && sourceInput.value) {
            await new Promise(resolve => {
                const iW = fCanvas.internalW || CANVAS_W;
                const iH = fCanvas.internalH || CANVAS_H;
                fabric.Image.fromURL(sourceInput.value + '?v=' + Date.now(), (img) => {
                    if (!img) { resolve(); return; }
                    
                    const scale = iW / img.width;
                    const scaledH = img.height * scale;

                    img.set({
                        left: 0, 
                        top: iH - scaledH, // Position at bottom
                        scaleX: scale, 
                        scaleY: scale, // Preserve aspect ratio
                        selectable: false, evented: false,
                        _isFrameLayer: true, _label: 'Frame Overlay',
                    });
                    fCanvas.add(img); frameOverlayObjects.push(img); resolve();
                }, { crossOrigin: 'anonymous' });
            });
        }
        // Don't renderAll here - let applyFrameConfig handle the final render
        return;
    }

    try {
        // FREEZE canvas rendering — nothing will visually update until we call renderAll() at the very end.
        // This prevents the "flash of unstyled content" where users see partial/raw layouts for 2 seconds.
        const prevRenderOnAdd = fCanvas.renderOnAddRemove;
        fCanvas.renderOnAddRemove = false;

        // Determine design resolution
        let dW = (config.info && config.info.width) || 0;
        let dH = (config.info && config.info.height) || 0;
        if (!dW || !dH) {
            config.layers.forEach(l => {
                const r = (l.x||0) + (l.width||l.w||0), b = (l.y||0) + (l.height||l.h||0);
                if (r > dW) dW = r; if (b > dH) dH = b;
            });
        }
        if (dW <= 0 || dH <= 0) { dW = 1024; dH = 1024; }

        if (isBaseTemplate) {
            autoFilterCategories(dW, dH);
        }

        // Detect overlay mode: when user is applying a frame ON TOP of existing content.
        const isOverlay = !isBaseTemplate;
        isFrameHidden = false;



        if (isOverlay) {
            // Keep the base template canvas as-is, use its dimensions
            dW = fCanvas.internalW || CANVAS_W;
            dH = fCanvas.internalH || CANVAS_H;
        } else if (hideBaseOnCustom) {
            resizeCanvas(dW, dH);
            fCanvas.setBackgroundImage(null, () => {}); // No intermediate render
            fCanvas.backgroundColor = '#ffffff';
        } else {
            // Load base poster as background for festival/category
            resizeCanvas(dW, dH);
            await new Promise(resolve => {
                fabric.Image.fromURL(DESIGN_URL, (img) => {
                    if (!img) { resolve(); return; }
                    
                    // --- DYNAMIC THEMING ---
                    globalBaseImageElement = img.getElement();
                    extractedTemplateAccentColor = extractDominantColor(globalBaseImageElement) || '#1e88e5';
                    let brightness = getImageBrightness(globalBaseImageElement);
                    templateIsDark = (brightness < 128);
                    console.log("[THEMING] Accent:", extractedTemplateAccentColor, "| Brightness:", Math.round(brightness), "| isDark:", templateIsDark);
                    // -----------------------

                    fCanvas.setBackgroundImage(img, () => { resolve(); }, {
                        originX: 'left', originY: 'top',
                        scaleX: dW / img.width, scaleY: dH / img.height,
                    });
                }, { crossOrigin: 'anonymous' });
            });
        }

        // Resolve asset paths
        const skinBase = document.getElementById('activeFrameImg-source').value;
        const skinDir = skinBase.substring(0, skinBase.lastIndexOf('/') + 1);
        const templateDir = skinDir.split('/skins/')[0] + '/';
        const fontsBase = templateDir + 'fonts/';

        const frameProvides = { logo: false, name: false, phone: false, email: false, address: false, website: false };
        config.layers.forEach(l => {
            const ln = (l.id || l.name || '').toLowerCase();
            if (l.type === 'image' && ln.includes('logo')) frameProvides.logo = true;
            if (l.type === 'text') {
                if (ln.includes('name') || ln.includes('business_name')) frameProvides.name = true;
                if (ln.includes('phone') || ln.includes('mobile') || ln.includes('contact') || ln.includes('call')) frameProvides.phone = true;
                if (ln.includes('email') || ln.includes('mail')) frameProvides.email = true;
                if (ln.includes('website') || ln.includes('web') || ln.includes('url')) frameProvides.website = true;
                if (ln.includes('address') || ln.includes('location')) frameProvides.address = true;
            }
        });

        for (const key in frameProvides) {
            const btn = document.getElementById('toggle-' + key);
            
            // USER REQUEST: Always hide the floating fallback texts by default. 
            // Only show frame-provided text slots.
            if (businessObjects[key]) businessObjects[key].set('visible', false);
            if (frameProvides[key]) {
                if (btn) btn.classList.remove('inactive');
            } else {
                if (btn) btn.classList.add('inactive');
            }
        }

        // Load AI image mappings (for subcategory/AI-generated posts)
        const aiConfig = {!! $item->ai_generated_content ?? 'null' !!};
        const imageMappings = (aiConfig && aiConfig._image_mappings) ? aiConfig._image_mappings : null;

        // Load fonts
        const fontMap = {};
        const fontPromises = [];
        config.layers.forEach(l => {
            let fontKey = l.font_name || l.font;
            if (fontKey && !fontMap[fontKey]) {
                const iName = `ZF_${fontKey.replace(/[^a-zA-Z0-9]/g,'_')}_${Date.now()}`;
                fontMap[fontKey] = iName;
                fontPromises.push(loadFont(fontKey, iName, fontsBase).then(ok => { if (!ok) fontMap[fontKey] = fontKey; }));
            }
        });
        await Promise.all(fontPromises);
        await new Promise(r => setTimeout(r, 50));

        // Sort layers by z_index for correct visual stacking (Fabric.js uses object order)
        const sortedLayers = [...config.layers].sort((a, b) => (a.z_index || 0) - (b.z_index || 0));

        // Render layers
        let textGroups = {}; // Used for Grouped Auto-Scaling
        let iconsToProcess = []; // Used for post-processing icon colors to match text
        
        // When overlaying, calculate scale factor to map frame dimensions onto the base canvas
        let overlayScaleX = 1, overlayScaleY = 1;
        if (isOverlay) {
            // Calculate the frame's native dimensions from its layers (since info may lack width/height)
            let framNativeW = (config.info && config.info.width) || 0;
            let framNativeH = (config.info && config.info.height) || 0;
            if (!framNativeW || !framNativeH) {
                config.layers.forEach(l => {
                    const r = (l.x||0) + (l.width||l.w||0), b = (l.y||0) + (l.height||l.h||0);
                    if (r > framNativeW) framNativeW = r;
                    if (b > framNativeH) framNativeH = b;
                });
            }
            if (framNativeW > 0 && framNativeH > 0) {
                overlayScaleX = dW / framNativeW;
                overlayScaleY = dH / framNativeH;
            }
        }

        for (const layer of sortedLayers) {
            const lname = (layer.name || layer.id || '').toLowerCase();
            const layerOrigName = layer.name || layer.id || '';
            // Apply overlay scaling to positions and sizes
            const lw = ((layer.width || layer.w || 0)) * overlayScaleX;
            const lh = ((layer.height || layer.h || 0)) * overlayScaleY;
            const lx = ((layer.x || 0)) * overlayScaleX;
            const ly = ((layer.y || 0)) * overlayScaleY;
            if (!lw || !lh) continue;

            // Allow the 'bg' layer to render for overlay frames (like PosterMaker) because the 'bg' layer IS the transparent frame border graphic!
            if (isBaseTemplate && (!hideBaseOnCustom || isOverlay) && layer.type === 'image' && (lname === 'bg' || lname === 'background')) {
                continue;
            }

            // ── OVERLAY MODE FILTERS ──
            if (isOverlay) {
                // If the user applies an overlay frame on top of their custom design, 
                // ONLY skip image slots (user photo placeholders) since the custom post IS the main image.
                if (layer.type === 'image' && lname.startsWith('image')) {
                    continue;
                }
            }

            if (layer.type === 'image') {
                let src = layer.src;
                let isAIMapped = false;
                let isFrameSlot = lname.startsWith('image'); // e.g. "image1", "Image 1"

                // When overlaying frame on custom template, skip image slots entirely
                if (isOverlay && isFrameSlot) {
                    continue;
                }

                // Check if there's an AI image mapping for this layer
                if (imageMappings && isFrameSlot) {
                    let mappedSrc = null;
                    const cleanLName = lname.replace(/[\s\-_]/g, '').toLowerCase();

                    if (imageMappings[lname]) mappedSrc = imageMappings[lname];
                    if (!mappedSrc) {
                        for (let key in imageMappings) {
                            if (key.replace(/[\s\-_]/g, '').toLowerCase() === cleanLName) {
                                mappedSrc = imageMappings[key]; break;
                            }
                        }
                    }
                    if (!mappedSrc && (cleanLName === 'image1' || cleanLName === 'mainimage')) {
                        mappedSrc = imageMappings['image1'] || imageMappings['main_image'] || imageMappings['image 1'];
                    }

                    if (mappedSrc) {
                        isAIMapped = true;
                        const uploadBase = "{{ asset('uploads') }}/";
                        if (!mappedSrc.startsWith('http') && !mappedSrc.startsWith('/') && !mappedSrc.startsWith('data:')) {
                            mappedSrc = uploadBase + mappedSrc;
                        }
                        src = mappedSrc;
                        console.log("AI Mapped layer " + lname + " → " + src);
                    }
                }

                // If it's an image slot with no AI mapping, use the base poster (design URL) or subcategory image
                if (!isAIMapped && isFrameSlot) {
                    if (SUBCATEGORY_IMAGE !== '') {
                        src = SUBCATEGORY_IMAGE;
                        isAIMapped = true;
                    } else if (DESIGN_URL !== '' && EDITOR_TYPE !== 'business_custom_frame' && EDITOR_TYPE !== 'custom' && EDITOR_TYPE !== 'post') {
                        // Only inject DESIGN_URL into slots for festival/category types
                        // For custom posts, DESIGN_URL is the thumbnail preview of the template itself,
                        // so injecting it into a slot causes the "duplicate JPG" bug!
                        src = DESIGN_URL;
                        isAIMapped = true;
                    }
                    // We DO NOT use a transparent pixel fallback here anymore. 
                    // If no image is mapped, it will naturally fall through and load the 
                    // original ZIP placeholder (e.g. image.png)
                    // that original image file acts as the alpha mask for future replacements!
                }

                // Business logo substitution
                @if($business ?? false)
                    let isBusinessLogo = lname.includes('logo') && !lname.includes('email') && !lname.includes('call') && !lname.includes('phone') && !lname.includes('web');
                    if (isBusinessLogo && "{{ $business->logo ?? '' }}" !== '') {
                        src = "{{ asset('uploads/' . ($business->logo ?? '')) }}";
                        isAIMapped = true; // Prevents the path resolver from messing with this absolute URL
                    }
                @endif

                // Resolve template-relative paths for non-mapped template component images
                if (!isAIMapped) {
                    // Always use the robust skinDir (based on ZIP name) instead of trusting the folder name in JSON
                    src = skinDir + src.split('/').pop();
                }

                await Promise.race([
                    new Promise(resolve => {
                        const imgUrl = isAIMapped ? src : (src + '?v=' + Date.now());
                        
                        // Log attempt to load
                        console.log("Attempting to load image layer: " + lname + " from " + src);
                        
                        fabric.Image.fromURL(imgUrl, (img, isError) => {
                            if (!img || isError) { 
                                console.error("Failed to load image layer: " + lname, imgUrl);
                                resolve(); 
                                return; 
                            }
                            
                            // Prevent CORS tainting for local or external assets
                            let imgEl = img.getElement();
                            if (imgEl && !imgEl.crossOrigin) {
                                imgEl.crossOrigin = "Anonymous";
                            }

                            let sX, sY, objLeft, objTop;
                            
                            if (isFrameSlot) {
                                const coverScale = Math.max(lw / img.width, lh / img.height);
                                sX = coverScale;
                                sY = coverScale;
                                objLeft = lx - ((img.width * sX) - lw) / 2;
                                objTop = ly - ((img.height * sY) - lh) / 2;
                            } else {
                                const containScale = Math.min(lw / img.width, lh / img.height);
                                sX = containScale;
                                sY = containScale;
                                objLeft = lx + (lw - (img.width * sX)) / 2;
                                objTop = ly + (lh - (img.height * sY)) / 2;
                            }



                            // --- DYNAMIC THEMING FOR SHAPES AND ICONS ---
                            if (isOverlay) {
                                let isContactIcon = false;
                                let textKey = null;
                                let nLow = lname.toLowerCase();
                                
                                if (nLow.includes('phone') || nLow.includes('call') || nLow.includes('mobile') || nLow.includes('contact') || nLow.includes('whatsapp') || nLow.includes('tel') || nLow.includes('ph')) {
                                    isContactIcon = true; textKey = 'phone';
                                } else if (nLow.includes('email') || nLow.includes('mail')) {
                                    isContactIcon = true; textKey = 'email';
                                } else if (nLow.includes('website') || nLow.includes('web') || nLow.includes('url')) {
                                    isContactIcon = true; textKey = 'website';
                                } else if (nLow.includes('address') || nLow.includes('location')) {
                                    isContactIcon = true; textKey = 'address';
                                } else if (nLow.includes('icon') || nLow.includes('facebook') || nLow.includes('instagram') || nLow.includes('twitter') || nLow.includes('youtube') || nLow.includes('social') || nLow.includes('linkedin')) {
                                    isContactIcon = true; textKey = 'social';
                                }

                                // Only the full-canvas bg/background layer should be non-selectable
                                // (it covers everything and would block all clicks).
                                // Other shapes (footer bars, colored strips) should remain selectable.
                                let isBgLayer = (nLow === 'bg' || nLow === 'background');
                                if (isBgLayer) {
                                    img._isDecorativeShape = true;
                                }
                                                    
                                if (isContactIcon && !nLow.includes('logo') && !isFrameSlot) {
                                    iconsToProcess.push({ img: img, layer: layer, lx: lx, ly: ly, lw: lw, lh: lh, textKey: textKey, lname: lname });
                                }
                            }
                            // ----------------------------------
                            // ----------------------------------

                            const rad = layer.radius || 0;
                            const isRotated = (layer.angle && layer.angle !== 0);

                            img.set({
                                left: isRotated ? (lx + lw / 2) : objLeft, 
                                top: isRotated ? (ly + lh / 2) : objTop,
                                originX: isRotated ? 'center' : 'left',
                                originY: isRotated ? 'center' : 'top',
                                angle: layer.angle || 0,
                                scaleX: sX, scaleY: sY,
                                selectable: true, evented: true,
                                _isFrameLayer: isOverlay, _isFrameImage: isFrameSlot,
                                _isPlaceholder: false, _label: layerOrigName || 'Component', _originalSrc: src,
                                _businessKey: (lname.toLowerCase().includes('logo') && !lname.toLowerCase().includes('email') && !lname.toLowerCase().includes('call') && !lname.toLowerCase().includes('phone') && !lname.toLowerCase().includes('web')) ? 'logo' : null,
                            });
                            
                            if (isFrameSlot) {
                                img.set({
                                    lockMovementX: false, lockMovementY: false,
                                    lockScalingX: false, lockScalingY: false, lockRotation: false,
                                    hasControls: true,
                                    _slotLeft: objLeft, _slotTop: objTop, _slotWidth: lw, _slotHeight: lh, _slotRadius: rad,
                                    _maskSrc: src
                                });
                            } else {
                                img.set({
                                    lockMovementX: false, lockMovementY: false,
                                    lockScalingX: false, lockScalingY: false, lockRotation: false,
                                    hasControls: true
                                });
                            }

                            // Decorative shapes (footer bars, background strips) must NOT block
                            // clicks on text/icons that sit on top of them.
                            // This MUST be set AFTER the main img.set() to avoid being overridden.
                            if (img._isDecorativeShape) {
                                img.set({ selectable: false, evented: false, hasControls: false });
                            }

                            const idx = config.layers.indexOf(layer);
                            fCanvas.insertAt(img, isBaseTemplate ? idx : (fCanvas.getObjects().length));
                            
                            if (isBaseTemplate && isFrameSlot) {
                                frameImageObjects.push(img);
                            } else if (!isBaseTemplate) {
                                frameOverlayObjects.push(img);
                            }
                            
                            resolve();
                        }, { crossOrigin: 'anonymous' });
                    }),
                    new Promise(resolve => setTimeout(() => { console.warn('Image load timeout for layer: ' + lname); resolve(); }, 5000))
                ]);
            } else if (layer.type === 'text') {
                // Text rendering: check AI content first, then business info, then template default
                let displayText = layer.text || '';

                // 1. AI-generated content (highest priority for post/custom types)
                if (aiConfig && aiConfig[layerOrigName] !== undefined && !layerOrigName.startsWith('_')) {
                    let aiText = aiConfig[layerOrigName];
                    if (Array.isArray(aiText)) aiText = aiText.join(' ');
                    if (typeof aiText === 'string') {
                        aiText = aiText.replace(/\\n/g, '\n');
                        displayText = aiText;
                    }
                }

                let textBKey = null;
                const bLow = lname.toLowerCase();
                if (bLow.includes('name') || bLow.includes('business_name')) textBKey = 'name';
                else if (bLow.includes('phone') || bLow.includes('mobile') || bLow.includes('contact') || bLow.includes('call') || bLow.includes('whatsapp') || bLow.includes('number') || bLow.includes('tel') || bLow.includes('ph')) textBKey = 'phone';
                else if (bLow.includes('email') || bLow.includes('mail')) textBKey = 'email';
                else if (bLow.includes('website') || bLow.includes('web') || bLow.includes('url')) textBKey = 'website';
                else if (bLow.includes('address') || bLow.includes('location')) textBKey = 'address';

                // 2. Business info substitution (overrides AI for business fields)
                @if($business ?? false)
                    if (textBKey === 'name' && "{{ $business->name ?? '' }}" !== "") displayText = "{{ $business->name }}";
                    else if (textBKey === 'phone' && "{{ $business->mobile_no ?? '' }}" !== "") displayText = "{{ $business->mobile_no }}";
                    else if (textBKey === 'email' && "{{ $business->email ?? '' }}" !== "") displayText = "{{ $business->email }}";
                    else if (textBKey === 'website' && "{{ $business->website ?? '' }}" !== "") displayText = "{{ $business->website }}";
                    else if (textBKey === 'address' && "{{ $business->address ?? '' }}" !== "") displayText = "{{ $business->address }}";
                @endif

                if (!displayText) continue;

                let rawColor = layer.font_color || layer.color;
                let color = rawColor ? String(rawColor).replace('0x','#') : '#000000';
                
                // --- DYNAMIC THEMING FOR TEXT ---
                // Uses pre-computed template brightness (calculated at load time)
                // No canvas pixel reading at runtime = no CORS issues, guaranteed to work
                if (isOverlay) {
                    // Smart detection: Only apply dynamic text color if the text does NOT sit on top of a decorative shape.
                    // If the text is on top of a decorative shape, we trust the JSON's original color choice for that shape.
                    let overlapsShape = false;
                    let shapeColor = null;
                    frameOverlayObjects.forEach(obj => {
                        // Check colored shape layers (footer bars, strips, etc.) — NOT bg, NOT text
                        const objLabel = (obj._label || '').toLowerCase();
                        const isBg = (objLabel === 'bg' || objLabel === 'background');
                        const isTextObj = (obj.type === 'textbox' || obj.type === 'i-text' || obj.type === 'text');
                        if (!isBg && !isTextObj && obj.type === 'image') {
                            let sl = obj.left;
                            let st = obj.top;
                            let sr = sl + (obj.width * obj.scaleX);
                            let sb = st + (obj.height * obj.scaleY);
                            let textCenterX = lx + (lw / 2);
                            let textCenterY = ly + (lh / 2);
                            if (textCenterX >= sl && textCenterX <= sr && textCenterY >= st && textCenterY <= sb) {
                                overlapsShape = true;
                            }
                        }
                    });

                    if (!overlapsShape) {
                        if (templateIsDark) {
                            color = '#FFFFFF';
                        } else {
                            color = '#000000';
                        }
                    }
                    console.log('[THEMING] Text "' + lname + '" → color=' + color + ' (templateIsDark=' + templateIsDark + ', overlapsShape=' + overlapsShape + ')');
                }
                // --------------------------------
                // Determine the primary template font to use as a smart fallback 
                // instead of dropping to an ugly generic 'sans-serif' if the JSON omitted it.
                let primaryFontFallback = 'sans-serif';
                let fontCounts = {};
                config.layers.forEach(l => {
                    let lf = l.font_name || l.font;
                    if (l.type === 'text' && lf) {
                        fontCounts[lf] = (fontCounts[lf] || 0) + 1;
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
                let requestedFont = layer.font_name || layer.font;
                let fFamily = requestedFont ? (fontMap[requestedFont] || requestedFont) : primaryFontFallback;
                
                // Final safety: if it's literally requesting 'sans-serif', try to override it with the premium primary font
                if (fFamily === 'sans-serif' && primaryFontFallback !== 'sans-serif') {
                    fFamily = primaryFontFallback;
                }
                
                let isBold = (layer.weight === 'bold' || layer.weight === 700 || layer.weight === '700');
                const isItalic = (requestedFont && requestedFont.toLowerCase().includes('italic'));

                let finalWeight = isBold ? '700' : (layer.weight || '400');
                let finalStyle = isItalic ? 'italic' : 'normal';

                // CRITICAL FIX: Dynamically loaded fonts from TTF files (ZF_ prefix) already have the 
                // bold/italic style baked directly into their glyphs. If we request '700' or 'italic', 
                // the canvas FontFace matcher will fail to find it (since it was registered as normal) 
                // and will fall back to generic Arial.
                if (fFamily && fFamily.startsWith('ZF_')) {
                    finalWeight = 'normal';
                    finalStyle = 'normal';
                }

                if (layer.uppercase) {
                    displayText = displayText.toUpperCase();
                }

                // Adobe Standard Point-to-Pixel Conversion
                // Formula: pixel_size = point_size × (document_ppi / 72)
                // This is the EXACT same formula Photoshop uses internally.
                let rawFontPt = layer.font_size || layer.size || 20;
                let docPPI = (config.info && config.info.ppi) || 72;
                let origSize = rawFontPt * (docPPI / 72) * overlayScaleY;

                let fShadow = null;
                if (layer.shadow) {
                    fShadow = new fabric.Shadow({
                        color: layer.shadow.color || 'rgba(0,0,0,0.5)',
                        blur: layer.shadow.blur || 5,
                        offsetX: layer.shadow.offsetX || 2,
                        offsetY: layer.shadow.offsetY || 2
                    });
                }

                const isRotatedText = (layer.angle && layer.angle !== 0);
                
                let tOriginX = 'left';
                let tLeft = lx;
                if (isRotatedText) {
                    tOriginX = 'center';
                    tLeft = lx + lw / 2;
                } else {
                    if (layer.justification === 'right') {
                        tOriginX = 'right';
                        tLeft = lx + lw;
                    } else if (layer.justification === 'center') {
                        tOriginX = 'center';
                        tLeft = lx + lw / 2;
                    }
                }

                let tOriginY = isRotatedText ? 'center' : 'top';
                let tTop = isRotatedText ? (ly + lh / 2) : ly;

                // Center the address vertically to accommodate 1 or 2 lines perfectly
                if (textBKey === 'address' && !isRotatedText) {
                    tOriginY = 'center';
                    tTop = ly + (lh / 2);
                }

                // Create text at ORIGINAL Photoshop font size
                const t = new fabric.Textbox(displayText, {
                    left: tLeft, 
                    top: tTop,
                    originX: tOriginX,
                    originY: tOriginY,
                    angle: layer.angle || 0,
                    fontSize: origSize,
                    fontFamily: fFamily, fill: color,
                    fontWeight: finalWeight,
                    fontStyle: finalStyle,
                    lineHeight: layer.line_height || 1.1,
                    textAlign: layer.justification || 'left',
                    charSpacing: layer.char_spacing || 0,
                    shadow: fShadow,
                    width: lw,
                    editable: true, _isFrameLayer: isOverlay, _objectType: 'text',
                    _label: layerOrigName || 'Text',
                    _businessKey: textBKey,
                    objectCaching: false,
                });

                // --- PREPARE FOR GROUPED AUTO-SCALING ---
                let baseName = (layerOrigName || 'text').replace(/[\d\s_]+$/, '').toLowerCase();
                if (!baseName) baseName = 'text';

                // We group by baseName AND original font size to prevent lazy layer naming 
                // (e.g. all layers named "Text 1", "Text 2") from shrinking headings to match list items.
                let groupKey = baseName + '_' + Math.round(origSize);

                let maxUpscalePx = 1 * (docPPI / 72) * overlayScaleY;
                if (!textGroups[groupKey]) textGroups[groupKey] = [];
                textGroups[groupKey].push({
                    t: t,
                    lh: lh,
                    lw: lw,
                    maxFontSize: origSize + maxUpscalePx,
                    origSize: origSize,
                    minFontSize: Math.max(8, origSize * 0.4)
                });

                fCanvas.add(t);
                if (!isBaseTemplate) {
                    frameOverlayObjects.push(t);
                }

            }
        }

        // --- GROUPED AUTO-SCALING (CANVA-STYLE) ---
        console.log('[DIAG] Starting grouped auto-scaling. Groups:', Object.keys(textGroups));
        for (let baseName in textGroups) {
            let group = textGroups[baseName];
            if (group.length === 0) continue;
            
            let requiredFontSizes = [];
            
            // 1. Simulate shrinking/upscaling for each item in the group
            for (let item of group) {
                let currentSize = item.origSize;
                
                // Define safe margins (10% vertical padding, 5% horizontal padding)
                let safeHeight = item.lh * 0.90;
                let safeWidth = item.lw * 0.95;

                // Auto-upscale slightly if text is smaller than safe box
                if (item.t.height <= safeHeight) {
                    while (currentSize < item.maxFontSize) {
                        currentSize += 0.5;
                        item.t.set('fontSize', currentSize);
                        item.t.initDimensions();
                        let textW = item.t.calcTextWidth();
                        if (item.t.height > safeHeight || textW > safeWidth) {
                            currentSize -= 0.5;
                            item.t.set('fontSize', currentSize);
                            item.t.initDimensions();
                            break;
                        }
                    }
                }

                let needed = false;
                if (item.lh && (item.t.height > safeHeight || item.t.calcTextWidth() > safeWidth)) {
                    needed = true;
                    while ((item.t.height > safeHeight || item.t.calcTextWidth() > safeWidth) && currentSize > item.minFontSize) {
                        currentSize -= 1;
                        item.t.set('fontSize', currentSize);
                        item.t.initDimensions();
                    }
                }
                console.log('[DIAG] Group:', baseName, 'Layer:', item.t._label, 'origSize:', item.origSize, 'finalSize:', currentSize, 'lh:', item.lh, 'actualH:', Math.round(item.t.height), 'needed:', needed);
                requiredFontSizes.push(currentSize);
            }
            
            // 2. Find the lowest common denominator font size
            let groupMinSize = Math.min(...requiredFontSizes);
            console.log('[DIAG] Group:', baseName, 'groupMinSize:', groupMinSize, 'sizes:', requiredFontSizes);
            
            // 3. Apply the uniform size to all items in the group
            for (let item of group) {
                if (item.t.fontSize !== groupMinSize) {
                    item.t.set('fontSize', groupMinSize);
                    item.t.initDimensions();
                }
            }
        }

        // --- POST-PROCESS ICON COLORS ---
        // We do this after all images AND text are loaded into frameOverlayObjects
        if (iconsToProcess && iconsToProcess.length > 0) {
            console.log('[THEMING] Processing ' + iconsToProcess.length + ' contact icons for dynamic color matching...');
            iconsToProcess.forEach(iconData => {
                let matchingTextLayer = null;
                if (iconData.textKey && iconData.textKey !== 'social') {
                    matchingTextLayer = frameOverlayObjects.find(obj => obj._objectType === 'text' && obj._businessKey === iconData.textKey);
                }

                let targetColor = null;

                if (matchingTextLayer) {
                    // Match the icon color EXACTLY to what the text color ended up being
                    targetColor = matchingTextLayer.fill;
                } else {
                    // Social icons or no matching text found
                    let iconOverlapsShape = false;
                    let iconCenterX = iconData.lx + (iconData.lw / 2);
                    let iconCenterY = iconData.ly + (iconData.lh / 2);
                    
                    frameOverlayObjects.forEach(obj => {
                        const objLabel = (obj._label || '').toLowerCase();
                        const isBg = (objLabel === 'bg' || objLabel === 'background');
                        const isTextObj = (obj.type === 'textbox' || obj.type === 'i-text' || obj.type === 'text');
                        if (!isBg && !isTextObj && obj.type === 'image') {
                            let sl = obj.left;
                            let st = obj.top;
                            let sr = sl + (obj.width * obj.scaleX);
                            let sb = st + (obj.height * obj.scaleY);
                            if (iconCenterX >= sl && iconCenterX <= sr && iconCenterY >= st && iconCenterY <= sb) {
                                iconOverlapsShape = true;
                            }
                        }
                    });

                    if (!iconOverlapsShape) {
                        targetColor = templateIsDark ? '#FFFFFF' : '#000000';
                    } else {
                        let layerColor = iconData.layer.color || iconData.layer.fill;
                        if (layerColor) targetColor = String(layerColor).replace('0x', '#');
                    }
                }

                if (targetColor) {
                    console.log('[THEMING] Icon "' + iconData.lname + '" → matched color=' + targetColor);
                    iconData.img.filters.push(new fabric.Image.filters.BlendColor({
                        color: targetColor,
                        mode: 'tint',
                        alpha: 1
                    }));
                    iconData.img.applyFilters();
                }
            });
        }

        // Check if frame has a logo and hide base logo if needed
        let handledByFrame = false;
        fCanvas.getObjects().forEach(o => {
            if (o._isFrameLayer && o._businessKey === 'logo') {
                handledByFrame = true;
            }
        });
        if (businessObjects && businessObjects.logo) {
            const btn = document.getElementById('toggle-logo');
            if (handledByFrame || (btn && btn.classList.contains('inactive'))) {
                businessObjects.logo.set('visible', false);
            } else {
                businessObjects.logo.set('visible', true);
            }
        }

        // UNFREEZE canvas and render everything in one perfect frame
        console.log('[DIAG] Final renderAll. isBaseTemplate:', isBaseTemplate, 'Total objects:', fCanvas.getObjects().length);
        fCanvas.renderOnAddRemove = prevRenderOnAdd;
        fCanvas.renderAll();
        
        // Recalculate canvas offsets — critical for iframe embeds (Flutter WebView)
        if (fCanvas.calcOffset) fCanvas.calcOffset();
        
        // Debug: Log selection state of all objects
        fCanvas.getObjects().forEach((obj, i) => {
            console.log('[DIAG] Object #' + i + ':', obj._label || obj.type, '| selectable:', obj.selectable, '| evented:', obj.evented, '| visible:', obj.visible, '| _isDecorativeShape:', !!obj._isDecorativeShape);
        });
        
        // Start history tracking if this is the base template
        if (isBaseTemplate) {
            isHistoryTracking = true;
            saveCanvasState();
        }
    } catch(err) {
        // Restore rendering even on error
        fCanvas.renderOnAddRemove = true;
        console.error("applyFrameConfig error:", err);
    }
}

async function loadFont(name, iName, base) {
    if (!name || name === 'sans-serif') return false;
    let baseName = name;
    if (baseName.includes('.')) {
        baseName = baseName.substring(0, baseName.lastIndexOf('.'));
    }

    let failedUrls = [];

    for (const ext of ['.ttf','.otf','.woff']) {
        let fontUrl = `${base}${encodeURIComponent(baseName)}${ext}?v=${Date.now()}`;
        try { 
            const f = new FontFace(iName, `url("${fontUrl}")`); 
            const l = await f.load(); 
            document.fonts.add(l); 
            return true; 
        } catch(e) { 
            failedUrls.push({ url: fontUrl, error: e.toString() });
            continue; 
        }
    }
    
    // DEBUG: Show font loading errors on screen
    let debugDiv = document.getElementById('font-debug-panel');
    if (!debugDiv) {
        debugDiv = document.createElement('div');
        debugDiv.id = 'font-debug-panel';
        debugDiv.style.position = 'fixed';
        debugDiv.style.top = '10px';
        debugDiv.style.left = '10px';
        debugDiv.style.right = '10px';
        debugDiv.style.backgroundColor = 'rgba(255,0,0,0.8)';
        debugDiv.style.color = 'white';
        debugDiv.style.padding = '10px';
        debugDiv.style.zIndex = '99999';
        debugDiv.style.fontSize = '12px';
        debugDiv.style.maxHeight = '200px';
        debugDiv.style.overflow = 'auto';
        document.body.appendChild(debugDiv);
    }
    debugDiv.innerHTML += `<p><strong>Font Failed:</strong> ${baseName}<br><small>${failedUrls.map(f => f.url + ': ' + f.error).join('<br>')}</small></p>`;

    return false;
}

// ── Export ──
function trackWebDownload(type, id, thumbnail) {
    fetch('{{ route("api.track-activity") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            userId: '{{ auth()->id() }}',
            action: 'download_template',
            item_type: type,
            item_id: id,
            platform: 'Web',
            downloaded_image: thumbnail
        })
    }).catch(err => console.error('Error tracking download:', err));
}

async function exportImage() {
    fCanvas.discardActiveObject(); clearGuideLines(); fCanvas.renderAll();
    if (document.fonts && document.fonts.ready) await document.fonts.ready;
    await new Promise(r => setTimeout(r, 200));
    const multiplier = 3 / currentZoom; // Export at ~3x design resolution regardless of display zoom
    const dataURL = fCanvas.toDataURL({ format: 'png', quality: 1.0, multiplier: multiplier });
    
    if (window.FlutterBridge) {
        window.FlutterBridge.postMessage('export:' + dataURL);
    } else {
        const link = document.createElement('a'); link.download = 'design_highres.png'; link.href = dataURL; link.click();
    }

    // Generate a lightweight, small JPEG thumbnail to track on the server
    let thumbnailDataURL = null;
    try {
        const maxDim = Math.max(fCanvas.width, fCanvas.height);
        const thumbMultiplier = (maxDim > 0) ? (250 / maxDim) : 0.2;
        thumbnailDataURL = fCanvas.toDataURL({ 
            format: 'jpeg', 
            quality: 0.5, 
            multiplier: thumbMultiplier 
        });
    } catch (e) {
        console.error("Error generating thumbnail:", e);
    }

    trackWebDownload('{{ $type }}', '{{ $id }}', thumbnailDataURL);
}

// ── Image Attach/Detach ──
function attachImage(input) {
    if (!activeObject || activeObject.type !== 'image' || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        replaceActiveImageWithUrl(e.target.result);
    };
    reader.readAsDataURL(input.files[0]); input.value = '';
}

function replaceActiveImageWithUrl(imageUrl, productId = null, mode = null) {
    if (!activeObject || activeObject.type !== 'image') return;
    const isFrameSlot = activeObject._isFrameImage === true && activeObject._slotWidth !== undefined;
    
    fabric.Image.fromURL(imageUrl, (newImg) => {
        if (!newImg) return;
        
        if (!isFrameSlot) {
            // Generic image replacement (e.g. Logos or standard shapes)
            newImg.set({
                left: activeObject.left, top: activeObject.top,
                scaleX: (activeObject.width * activeObject.scaleX) / newImg.width,
                scaleY: (activeObject.height * activeObject.scaleY) / newImg.height,
                angle: activeObject.angle, originX: activeObject.originX, originY: activeObject.originY,
                clipPath: activeObject.clipPath, selectable: true, evented: true,
                _isFrameLayer: activeObject._isFrameLayer, _isFrameImage: activeObject._isFrameImage,
                _label: activeObject._label, _businessKey: activeObject._businessKey,
                _originalSrc: imageUrl
            });
            if (productId) newImg.set({ _objectType: 'product_image', _productId: productId, _imageMode: mode });
            const idx = fCanvas.getObjects().indexOf(activeObject);
            fCanvas.remove(activeObject); 
            fCanvas.insertAt(newImg, idx >= 0 ? idx : fCanvas.getObjects().length, false);
            if (activeObject._businessKey && businessObjects[activeObject._businessKey]) businessObjects[activeObject._businessKey] = newImg;
            fCanvas.setActiveObject(newImg); activeObject = newImg; fCanvas.renderAll();
            saveCanvasState();
            if (productId) trackProductSelection(productId, imageUrl, mode);
            return;
        }

        // Frame image slots: use 'cover' scaling to perfectly FILL the slot bounds
        const slotW = activeObject._slotWidth;
        const slotH = activeObject._slotHeight;
        const slotL = activeObject._slotLeft;
        const slotT = activeObject._slotTop;
        const rad = activeObject._slotRadius || 0;
        const maskSrc = activeObject._maskSrc || activeObject._originalSrc;
        
        const coverScale = Math.max(slotW / newImg.width, slotH / newImg.height);
        const sX = coverScale;
        const sY = coverScale;
        
        // Center the overflow relative to the strict slot bounds
        const centeredLeft = slotL - ((newImg.width * sX) - slotW) / 2;
        const centeredTop = slotT - ((newImg.height * sY) - slotH) / 2;

        // HTML5 Canvas compositing strategy (Canva-style robust alpha masking)
        if (maskSrc && maskSrc.trim() !== '') {
            const usrImg = new Image();
            usrImg.crossOrigin = "anonymous";
            usrImg.onload = () => {
                const mskImg = new Image();
                mskImg.crossOrigin = "anonymous";
                mskImg.onload = () => {
                    // To prevent squishing, the mask MUST preserve its native aspect ratio.
                    // We scale the mask so it 'covers' the slot bounds (just like applyFrameConfig does)
                    const maskCoverScale = Math.max(slotW / mskImg.width, slotH / mskImg.height);
                    const finalW = mskImg.width * maskCoverScale;
                    const finalH = mskImg.height * maskCoverScale;

                    const offCanvas = document.createElement('canvas');
                    offCanvas.width = finalW;
                    offCanvas.height = finalH;
                    const ctx = offCanvas.getContext('2d');

                    // Calculate 'cover' scaling for the user's image to fill this new exact canvas size
                    const usrCoverScale = Math.max(finalW / usrImg.width, finalH / usrImg.height);
                    const drawW = usrImg.width * usrCoverScale;
                    const drawH = usrImg.height * usrCoverScale;
                    // Center the user image overflow
                    const drawX = (finalW - drawW) / 2;
                    const drawY = (finalH - drawH) / 2;

                    // Draw user image
                    ctx.drawImage(usrImg, drawX, drawY, drawW, drawH);

                    // Apply the alpha mask using destination-in (matching the canvas exact size)
                    ctx.globalCompositeOperation = 'destination-in';
                    ctx.drawImage(mskImg, 0, 0, finalW, finalH);

                    // Fabric image needs to be shifted because finalW/finalH might be larger than slotW/slotH
                    const finalLeft = slotL - ((finalW - slotW) / 2);
                    const finalTop = slotT - ((finalH - slotH) / 2);

                    // Generate a perfectly cropped Fabric image
                    fabric.Image.fromURL(offCanvas.toDataURL('image/png'), (finalImg) => {
                        finalImg.set({
                            left: finalLeft,
                            top: finalTop,
                            scaleX: 1, // Canvas is already exact size
                            scaleY: 1,
                            selectable: true,
                            evented: true,
                            _isFrameLayer: true,
                            _isFrameImage: true,
                            _isPlaceholder: false,
                            _label: activeObject._label,
                            _originalSrc: imageUrl,
                            _maskSrc: maskSrc,
                            lockMovementX: false,
                            lockMovementY: false,
                            lockScalingX: false,
                            lockScalingY: false,
                            lockRotation: false,
                            hasControls: true,
                            _slotLeft: slotL,
                            _slotTop: slotT,
                            _slotWidth: slotW,
                            _slotHeight: slotH,
                            _slotRadius: rad
                        });

                        if (productId) finalImg.set({ _objectType: 'product_image', _productId: productId, _imageMode: mode });

                        const idx = fCanvas.getObjects().indexOf(activeObject);
                        fCanvas.remove(activeObject); fCanvas.insertAt(finalImg, idx);
                        const oIdx = frameOverlayObjects.indexOf(activeObject); if (oIdx >= 0) frameOverlayObjects[oIdx] = finalImg;
                        fCanvas.setActiveObject(finalImg); activeObject = finalImg; fCanvas.renderAll();
                        saveCanvasState();
                        if (productId) trackProductSelection(productId, imageUrl, mode);
                    });
                };
                mskImg.onerror = () => {
                    fallbackToRectClipPath(newImg, slotW, slotH, sX, sY, rad, centeredLeft, centeredTop, imageUrl, maskSrc, productId, mode);
                };
                mskImg.src = maskSrc;
            };
            usrImg.onerror = () => {
                fallbackToRectClipPath(newImg, slotW, slotH, sX, sY, rad, centeredLeft, centeredTop, imageUrl, maskSrc, productId, mode);
            };
            usrImg.src = imageUrl;
        } else {
            fallbackToRectClipPath(newImg, slotW, slotH, sX, sY, rad, centeredLeft, centeredTop, imageUrl, maskSrc, productId, mode);
        }
    }, { crossOrigin: 'anonymous' });
}

function fallbackToRectClipPath(newImg, slotW, slotH, sX, sY, rad, centeredLeft, centeredTop, origSrc, maskSrc, productId = null, mode = null) {
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
        _label: activeObject._label,
        _originalSrc: origSrc,
        _maskSrc: maskSrc,
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

    if (productId) {
        newImg.set({ _objectType: 'product_image', _productId: productId, _imageMode: mode });
    }

    const idx = fCanvas.getObjects().indexOf(activeObject);
    fCanvas.remove(activeObject); fCanvas.insertAt(newImg, idx);
    const oIdx = frameOverlayObjects.indexOf(activeObject); if (oIdx >= 0) frameOverlayObjects[oIdx] = newImg;
    fCanvas.setActiveObject(newImg); activeObject = newImg; fCanvas.renderAll();
    saveCanvasState();
    if (productId) trackProductSelection(productId, origSrc, mode);
}
function detachImage() {
    if (!activeObject || activeObject.type !== 'image') return;

    // For non-frame images: remove any clipPath and show full original size
    if (!activeObject._isFrameImage || !activeObject._slotWidth) {
        activeObject.set({ clipPath: null });
        activeObject.setCoords();
        fCanvas.renderAll();
        return;
    }

    const origObj = activeObject;
    const slotW = origObj._slotWidth || (origObj.width * origObj.scaleX);
    const slotH = origObj._slotHeight || (origObj.height * origObj.scaleY);
    const slotL = origObj._slotLeft !== undefined ? origObj._slotLeft : origObj.left;
    const slotT = origObj._slotTop !== undefined ? origObj._slotTop : origObj.top;
    const rad = origObj._slotRadius || 0;
    const origLabel = origObj._label;

    const origSrc = origObj._originalSrc || origObj.getSrc();

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

        // NOW ALSO SPAWN THE ORIGINAL IMAGE AS A FREE-FLOATING LAYER
        fabric.Image.fromURL(origSrc, (detachedImg) => {
            if (!detachedImg) return;
            
            // Calculate a scale so it isn't massive. Fit within canvas width.
            const cvsW = fCanvas.internalW || CANVAS_W;
            let detScale = 1;
            if (detachedImg.width > cvsW * 0.8) {
                detScale = (cvsW * 0.8) / detachedImg.width;
            }

            detachedImg.set({
                left: slotL + 20, // Offset slightly from the slot
                top: slotT + 20,
                scaleX: detScale,
                scaleY: detScale,
                selectable: true, evented: true,
                _isFrameLayer: true, _isFrameImage: false, // Standard floating image now
                _label: 'Detached Image',
                lockMovementX: false, lockMovementY: false,
                lockScalingX: false, lockScalingY: false, lockRotation: false,
                hasControls: true
            });

            fCanvas.add(detachedImg);
            fCanvas.setActiveObject(detachedImg);
            fCanvas.renderAll();
        }, { crossOrigin: 'anonymous' });

    }, { crossOrigin: 'anonymous' });
}

// ── Frame Color ──
var selectedLayerIndex = 0, colorHistory = [], historyIndex = -1;
function openFrameColorPanel() {
    if (frameOverlayObjects.length === 0) return;
    const p = document.getElementById('frameColorPanel');
    if (p.style.display === 'block') { closeFrameColorPanel(); return; }
    closeAllPanels();
    p.style.display = 'block'; renderLayerBubbles(); colorHistory = []; historyIndex = -1; updateHistoryButtons();
}
function closeFrameColorPanel() { closeAllPanels(); }
function cancelFrameColorPanel() { closeFrameColorPanel(); }
function confirmFrameColorPanel() { closeFrameColorPanel(); }
function renderLayerBubbles() {
    const c = document.getElementById('layerBubbles'); c.innerHTML = '';
    frameOverlayObjects.forEach((obj, idx) => {
        const b = document.createElement('div'); b.className = `layer-bubble ${idx === selectedLayerIndex ? 'active' : ''}`;
        if (obj._element) { const img = document.createElement('img'); img.src = obj._originalSrc || obj._element.src; img.style.cssText = 'width:100%;height:100%;object-fit:cover;'; b.appendChild(img); }
        b.onclick = () => { selectedLayerIndex = idx; renderLayerBubbles(); }; c.appendChild(b);
    });
}
function applyPaletteColor(color) {
    const obj = frameOverlayObjects[selectedLayerIndex]; if (!obj) return;
    obj.filters = [new fabric.Image.filters.BlendColor({ color: color, mode: 'tint', alpha: 1 })];
    obj.applyFilters(); fCanvas.renderAll();
    if (historyIndex < colorHistory.length - 1) colorHistory = colorHistory.slice(0, historyIndex + 1);
    colorHistory.push({ layerIndex: selectedLayerIndex, newColor: color }); historyIndex++; updateHistoryButtons(); renderLayerBubbles();
}
function undoFrameColor() { if (historyIndex >= 0) { const a = colorHistory[historyIndex]; const o = frameOverlayObjects[a.layerIndex]; if (o) { o.filters = []; o.applyFilters(); fCanvas.renderAll(); renderLayerBubbles(); } historyIndex--; updateHistoryButtons(); } }
function redoFrameColor() { if (historyIndex < colorHistory.length - 1) { historyIndex++; const a = colorHistory[historyIndex]; const o = frameOverlayObjects[a.layerIndex]; if (o) { o.filters = [new fabric.Image.filters.BlendColor({ color: a.newColor, mode: 'tint', alpha: 1 })]; o.applyFilters(); fCanvas.renderAll(); renderLayerBubbles(); } updateHistoryButtons(); } }
function updateHistoryButtons() { const u = document.getElementById('undoColorBtn'), r = document.getElementById('redoColorBtn'); if (u) u.disabled = historyIndex < 0; if (r) r.disabled = historyIndex >= colorHistory.length - 1; if (window.lucide) window.lucide.createIcons(); }

// ── Layers Modal ──
function toggleLayersModal() {
    fCanvas.discardActiveObject(); fCanvas.renderAll();
    const modal = document.getElementById('layersModal'), c = document.getElementById('layersContainer'); c.innerHTML = '';
    fCanvas.getObjects().filter(o => !o._isGuideLine).forEach(obj => {
        let icon = 'type', label = obj._label || obj.text || 'Component';
        if (obj.type === 'textbox') { icon = 'type'; label = obj._label || (obj.text ? obj.text.substring(0,20) : 'Text'); }
        else if (obj._objectType === 'sticker') { icon = 'smile'; } else if (obj._objectType === 'logo' || obj.type === 'image') { icon = 'image'; label = obj._label || 'Image'; }
        const item = document.createElement('div'); item.className = 'layer-item';
        item.innerHTML = `<i data-lucide="${icon}"></i><span class="layer-text">${label}</span>`;
        item.onclick = () => { modal.style.display = 'none'; setTimeout(() => { fCanvas.setActiveObject(obj); fCanvas.renderAll(); }, 50); };
        c.appendChild(item);
    });
    modal.style.display = 'flex'; if (window.lucide) window.lucide.createIcons();
}

// ── Frame Thumbnail Renderer ──
function renderFrameThumbnails() {
    document.querySelectorAll('.frame-item[data-config]').forEach(item => {
        const configStr = item.getAttribute('data-config');
        const skinsDir = item.getAttribute('data-skins-dir');
        if (!configStr || configStr === 'null' || !skinsDir) return;

        let config;
        try { config = JSON.parse(configStr); } catch(e) { return; }
        if (!config || !config.layers || !config.info) return;

        const designW = config.info.width || 1080;
        const designH = config.info.height || 1080;
        const thumbW = item.offsetWidth || 80;
        const thumbH = item.offsetHeight || 80;
        const scaleDown = Math.min(thumbW / designW, thumbH / designH);

        // Derive fonts dir from skins dir (go up from /skins/DesignName to /fonts)
        const fontsDir = skinsDir.split('/skins/')[0] + '/fonts';

        // Load fonts first, then render
        const fontsToLoad = [];
        config.layers.forEach(layer => {
            if (layer.type === 'text' && layer.font) {
                const fontName = layer.font;
                if (!fontsToLoad.includes(fontName)) {
                    fontsToLoad.push(fontName);
                    try {
                        const fontUrl = `${fontsDir}/${encodeURIComponent(fontName)}.ttf`;
                        const fontFace = new FontFace(fontName, `url(${fontUrl})`);
                        document.fonts.add(fontFace);
                        fontFace.load().catch(() => {});
                    } catch(e) {}
                }
            }
        });

        // Wait for fonts then render
        document.fonts.ready.then(() => {
            const overlay = document.createElement('div');
            overlay.style.cssText = `position:absolute;top:0;left:0;width:${designW}px;height:${designH}px;transform:scale(${scaleDown});transform-origin:top left;overflow:hidden;pointer-events:none;z-index:10;`;

            config.layers.forEach(layer => {
                if (layer.type === 'image') {
                    const lname = (layer.name || '').toLowerCase();
                    const isFrameSlot = lname.startsWith('image');

                    const el = document.createElement('div');
                    el.style.cssText = `position:absolute;left:${layer.x}px;top:${layer.y}px;width:${layer.w || 0}px;height:${layer.h || 0}px;z-index:${layer.z_index || 0};overflow:hidden;pointer-events:none;`;

                    const img = document.createElement('img');
                    img.style.width = '100%';
                    img.style.height = '100%';

                    let imgSrc = '';
                    let isProduct = false;

                    if (isFrameSlot) {
                        isProduct = true;
                        // Mirror Fabric.js logic: check AI _image_mappings first
                        const mappings = (POST_AI_DATA && POST_AI_DATA._image_mappings) ? POST_AI_DATA._image_mappings : null;
                        let mappedSrc = null;
                        if (mappings) {
                            const cleanLName = lname.replace(/[\s\-_]/g, '').toLowerCase();
                            mappedSrc = mappings[lname] || null;
                            if (!mappedSrc) {
                                for (let key in mappings) {
                                    if (key.replace(/[\s\-_]/g, '').toLowerCase() === cleanLName) {
                                        mappedSrc = mappings[key]; break;
                                    }
                                }
                            }
                            if (!mappedSrc && (cleanLName === 'image1' || cleanLName === 'mainimage')) {
                                mappedSrc = mappings['image1'] || mappings['main_image'] || mappings['image 1'];
                            }
                        }

                        if (mappedSrc) {
                            const uploadsDir = "{{ asset('uploads') }}/";
                            if (!mappedSrc.startsWith('http') && !mappedSrc.startsWith('/') && !mappedSrc.startsWith('data:')) {
                                mappedSrc = uploadsDir + mappedSrc;
                            }
                            imgSrc = mappedSrc;
                        } else if (SUBCATEGORY_IMAGE) {
                            imgSrc = SUBCATEGORY_IMAGE;
                        } else if (DESIGN_URL !== '' && EDITOR_TYPE !== 'business_custom_frame' && EDITOR_TYPE !== 'custom' && EDITOR_TYPE !== 'post') {
                            imgSrc = DESIGN_URL;
                        } else {
                            let src = layer.src;
                            if (src.includes('../skins/')) src = src.split('/').pop();
                            imgSrc = `${skinsDir}/${src}`;
                        }
                    } else {
                        let src = layer.src;
                        if (src.includes('../skins/')) src = src.split('/').pop();
                        imgSrc = `${skinsDir}/${src}`;
                    }

                    img.src = imgSrc;
                    img.style.objectFit = isProduct ? 'cover' : 'contain';
                    if (isProduct) el.style.borderRadius = ((layer.radius || 0)) + 'px';

                    el.appendChild(img);
                    overlay.appendChild(el);
                } else if (layer.type === 'text') {
                    const text = (POST_AI_DATA && POST_AI_DATA[layer.name]) ? POST_AI_DATA[layer.name] : (layer.text || '');
                    const el = document.createElement('div');
                    el.innerText = text.replace(/\\n/g, '\n');
                    const fontSize = layer.size || 20;
                    const color = (layer.color || '#000').replace('0x', '#');
                    const isBold = (layer.weight === 'bold' || (layer.font && layer.font.toLowerCase().includes('bold')));
                    el.style.cssText = `position:absolute;left:${layer.x}px;top:${layer.y}px;width:${layer.w || 100}px;height:${layer.h || 30}px;z-index:${layer.z_index || 0};color:${color};font-size:${fontSize}px;font-family:'${layer.font || 'sans-serif'}',sans-serif;font-weight:${isBold ? '700' : (layer.weight || '400')};text-align:${layer.justification || 'left'};line-height:${layer.line_height || 1.1};overflow:hidden;white-space:pre-wrap;overflow-wrap:break-word;`;
                    if (layer.uppercase) el.style.textTransform = 'uppercase';
                    if (layer.char_spacing) el.style.letterSpacing = ((layer.char_spacing / 1000) * fontSize) + 'px';
                    if (layer.shadow) {
                        el.style.textShadow = `${layer.shadow.offsetX || 0}px ${layer.shadow.offsetY || 0}px ${layer.shadow.blur || 0}px ${layer.shadow.color || 'rgba(0,0,0,0.5)'}`;
                    }
                    overlay.appendChild(el);
                }
            });

            item.appendChild(overlay);
        });
    });
}

// ── Init ──
lucide.createIcons();
window.addEventListener('DOMContentLoaded', () => {
    initCanvas();
    applyFrameFilters();
    renderFrameThumbnails();

    @php
        $baseConfigObj = null;
        if (isset($post_template) && $post_template) {
            if (isset($post_template->config)) {
                $baseConfigObj = $post_template->config;
            } elseif (isset($post_template->json_rules)) {
                $baseConfigObj = is_string($post_template->json_rules) ? json_decode($post_template->json_rules) : $post_template->json_rules;
            }
        }
    @endphp
    const applyFirstFrameOverlay = () => {
        const firstFrame = document.querySelector('.frame-item[style*="display: block"]') || document.querySelector('.frame-item');
        if (firstFrame) {
            const frameUrl = firstFrame.getAttribute('onclick');
            const urlMatch = frameUrl ? frameUrl.match(/changeFrame\('([^']+)'/) : null;
            
            setTimeout(() => {
                try {
                    if (urlMatch) {
                        changeFrame(urlMatch[1], firstFrame);
                    } else {
                        firstFrame.click();
                    }
                } catch(e) {}
                
                setTimeout(() => {
                    isHistoryTracking = true; 
                    saveCanvasState();
                }, 1000);
            }, 300);
        } else {
            isHistoryTracking = true; saveCanvasState();
        }
    };

    const baseConfig = @json($baseConfigObj);
    if (baseConfig) {
        try {
            const si = document.getElementById('activeFrameImg-source');
            if (si) si.value = '{{ isset($post_template) && $post_template ? $post_template->full_url : "" }}';
            if (EDITOR_TYPE === 'business_custom_frame') {
                fCanvas.clear();
                fCanvas.backgroundColor = '#ffffff';
            }

            console.time('[DIAG] Total init time');
            console.time('[DIAG] Base config render');

            setTimeout(() => { 
                applyFrameConfig(baseConfig, true).then(() => {
                    console.timeEnd('[DIAG] Base config render');
                    console.log('[DIAG] Base config done. Objects on canvas:', fCanvas.getObjects().length);
                    
                    // Chain overlay immediately after base
                    console.time('[DIAG] Frame overlay');
                    const firstFrame = document.querySelector('.frame-item.selected');
                    if (firstFrame) {
                        const frameUrl = firstFrame.getAttribute('onclick');
                        const urlMatch = frameUrl ? frameUrl.match(/changeFrame\('([^']+)'/) : null;
                        try {
                            if (urlMatch && typeof changeFrame === 'function') {
                                changeFrame(urlMatch[1], firstFrame);
                            } else {
                                firstFrame.click();
                            }
                        } catch(e) { console.error('[DIAG] Frame overlay error:', e); }
                        console.timeEnd('[DIAG] Frame overlay');
                    }
                    console.timeEnd('[DIAG] Total init time');
                    
                    setTimeout(() => {
                        isHistoryTracking = true;
                        saveCanvasState();
                    }, 1500);
                }).catch((err) => {
                    console.error('[DIAG] Base config error:', err);
                }); 
            }, 300);
        } catch(e) {
            console.error('[DIAG] Init error:', e);
        }
    } else {
        // For festival/category: still wait a moment for canvas init
        setTimeout(() => applyFirstFrameOverlay(), 500);
    }
});
window.addEventListener('resize', () => { if (fCanvas) { const { w, h } = getInternalSize(); resizeCanvas(w, h); } });

// ── Favorite Frame Logic ──
function toggleFavoriteFrame(e, frameId, btnElement) {
    e.stopPropagation(); // Prevent changing frame
    const icon = btnElement.querySelector('.heart-icon');
    const isLiked = icon.classList.contains('liked');
    
    // Optimistic UI update
    if (isLiked) {
        icon.classList.remove('liked');
        icon.classList.add('text-gray-400');
    } else {
        icon.classList.add('liked');
        icon.classList.remove('text-gray-400');
    }

    fetch('{{ route("toggle.favorite.frame") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ frame_id: frameId })
    }).then(res => res.json()).catch(err => console.error('Error toggling favorite:', err));
}

// ── Frame Filtering Logic ──
function getActiveCount(fieldList, hiddenObj, key) {
    if (!fieldList) return 0;
    let total = fieldList.length;
    let hiddenList = (hiddenObj && hiddenObj[key]) ? hiddenObj[key] : [];
    // Count active items
    let active = 0;
    fieldList.forEach(item => {
        if (!hiddenList.includes(item)) active++;
    });
    return active;
}

var currentImageTypeFilter = 'all';

function setImageTypeFilter(type, btn) {
    currentImageTypeFilter = type;
    document.querySelectorAll('.image-type-filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    applyFrameFilters();
}

function applyFrameFilters() {
    const themeFilter = document.getElementById('editorThemeFilter').value;
    const categoryId = window.currentFrameCategoryFilter || 'all';
    const imageTypeFilter = currentImageTypeFilter || 'all';

    // Calculate active requirements from BUSINESS
    let activeAddress = BUSINESS && BUSINESS.address ? 1 : 0;
    let activeEmail = BUSINESS && BUSINESS.email ? 1 : 0;
    let activePhone = BUSINESS && BUSINESS.mobile_no ? 1 : 0;
    let activeWebsite = BUSINESS && BUSINESS.website ? 1 : 0;

    let hiddenFields = BUSINESS && BUSINESS.hidden_frame_fields ? BUSINESS.hidden_frame_fields : {};

    if (BUSINESS && BUSINESS.extra_addresses) activeAddress += getActiveCount(BUSINESS.extra_addresses, hiddenFields, 'addresses');
    if (BUSINESS && BUSINESS.extra_emails) activeEmail += getActiveCount(BUSINESS.extra_emails, hiddenFields, 'emails');
    if (BUSINESS && BUSINESS.extra_mobile_numbers) activePhone += getActiveCount(BUSINESS.extra_mobile_numbers, hiddenFields, 'mobile_numbers');
    if (BUSINESS && BUSINESS.extra_websites) activeWebsite += getActiveCount(BUSINESS.extra_websites, hiddenFields, 'websites');

    const frames = document.querySelectorAll('.frame-item');
    frames.forEach(frame => {
        const frameTheme = frame.getAttribute('data-theme') || 'all';
        const frameCat = frame.getAttribute('data-category-id') || 'all';
        const frameImageType = frame.getAttribute('data-image-type') || 'full';
        const reqAddress = parseInt(frame.getAttribute('data-req-address')) || 0;
        const reqEmail = parseInt(frame.getAttribute('data-req-email')) || 0;
        const reqPhone = parseInt(frame.getAttribute('data-req-phone')) || 0;
        const reqWebsite = parseInt(frame.getAttribute('data-req-website')) || 0;

        let matchTheme = (themeFilter === 'all' || frameTheme === 'all' || frameTheme === themeFilter);
        let matchCat = (categoryId === 'all' || frameCat === 'all' || frameCat === categoryId);
        let matchImageType = (imageTypeFilter === 'all' || frameImageType === imageTypeFilter);
        
        let matchReq = true;
        if (reqAddress > 0 && activeAddress < reqAddress) matchReq = false;
        if (reqEmail > 0 && activeEmail < reqEmail) matchReq = false;
        if (reqPhone > 0 && activePhone < reqPhone) matchReq = false;
        if (reqWebsite > 0 && activeWebsite < reqWebsite) matchReq = false;

        if (matchTheme && matchCat && matchReq && matchImageType) {
            frame.style.display = 'block';
        } else {
            frame.style.display = 'none';
        }
    });
}

// Call on load
document.addEventListener('DOMContentLoaded', () => {
    applyFrameFilters();
});

// ═══════════════════════════════════════════════════════
//  MY PRODUCTS PANEL — Canva-Style Product Image Picker
// ═══════════════════════════════════════════════════════

var HAS_PRODUCTS = {{ ($hasProducts ?? false) ? 'true' : 'false' }};
var myProductsData = null; // Cached API response
var selectedProductId = null;
var selectedProductUrl = null;
var selectedProductName = null;

function openMyProducts() {
    const modal = document.getElementById('myProductsModal');
    modal.style.display = 'flex';
    document.getElementById('productsSearchInput').value = '';

    // Reset selection
    selectedProductId = null;
    selectedProductUrl = null;
    updateProductActionButtons();

    // Load products (use cache if available)
    if (myProductsData) {
        renderProducts(myProductsData);
    } else {
        loadMyProducts();
    }

    if (window.lucide) window.lucide.createIcons();
}

function closeMyProducts(e) {
    if (e && e.target !== document.getElementById('myProductsModal')) return;
    document.getElementById('myProductsModal').style.display = 'none';
}

async function loadMyProducts() {
    const body = document.getElementById('productsBody');
    body.innerHTML = `
        <div style="text-align:center;padding:40px 0;color:#94a3b8;">
            <div style="width:32px;height:32px;border:3px solid #e2e8f0;border-top:3px solid #4f46e5;border-radius:50%;animation:spin 0.8s linear infinite;margin:0 auto;"></div>
            <p style="margin-top:12px;font-size:13px;font-weight:600;">Loading products...</p>
        </div>`;

    try {
        // Get current template ID for used-tracking
        const currentFrame = document.querySelector('.frame-item.selected');
        let templateId = '';
        if (currentFrame) {
            templateId = currentFrame.getAttribute('data-config') ? 
                (currentFrame.querySelector('img')?.src || '') : '';
        }

        const response = await fetch(`{{ route('my.product.images') }}?template_id=${encodeURIComponent(templateId)}`);
        const data = await response.json();

        if (data.success) {
            myProductsData = data;
            renderProducts(data);
        } else {
            body.innerHTML = '<div class="products-empty"><p>Could not load products</p></div>';
        }
    } catch (err) {
        console.error('Products load error:', err);
        body.innerHTML = '<div class="products-empty"><p>Connection error. Please try again.</p></div>';
    }
}

function renderProducts(data) {
    const body = document.getElementById('productsBody');
    const countEl = document.getElementById('productsCount');
    const actionsEl = document.getElementById('productsActions');

    countEl.textContent = data.total_count || 0;

    // No products → empty state
    if (!data.has_products || data.total_count === 0) {
        actionsEl.style.display = 'none';
        body.innerHTML = `
            <div class="products-empty">
                <div class="products-empty-icon">
                    <i data-lucide="package" style="width:28px;height:28px;"></i>
                </div>
                <h4>No products added yet</h4>
                <p>Add your products to quickly insert<br>them into your designs</p>
                <a href="{{ route('products') }}" class="products-empty-btn">
                    <i data-lucide="plus" style="width:16px;height:16px;"></i>
                    Add Products
                </a>
            </div>`;
        if (window.lucide) window.lucide.createIcons();
        return;
    }

    // Has products → show grid grouped
    actionsEl.style.display = 'flex';
    let html = '';

    const groups = data.groups || {};
    const groupNames = Object.keys(groups);

    groupNames.forEach(groupName => {
        const products = groups[groupName];
        if (!products || products.length === 0) return;

        html += `<div class="product-group" data-group="${groupName}">`;
        if (groupNames.length > 1 || groupName !== 'All Products') {
            html += `<div class="product-group-label">
                <i data-lucide="tag"></i> ${groupName} 
                <span style="font-weight:600;color:#cbd5e1;">(${products.length})</span>
            </div>`;
        }
        html += `<div class="products-grid">`;

        products.forEach(p => {
            const usedBadge = p.is_used 
                ? `<div class="product-used-badge">✓</div>` 
                : '';
            
            const imageHtml = p.has_image 
                ? `<img src="${p.image_url}" alt="${p.name}" loading="lazy">` 
                : `<div style="width:100%;aspect-ratio:1;display:flex;align-items:center;justify-content:center;background:#f8fafc;border-radius:10px;"><i data-lucide="image-off" style="color:#cbd5e1;width:24px;height:24px;"></i></div>`;

            const clickAction = p.has_image ? `onclick="selectProduct(this)"` : `onclick="alert('Please add an image to this product from the Products page first.')"`;
            const opacityStyle = p.has_image ? '' : 'opacity: 0.6; cursor: not-allowed;';

            html += `
                <div class="product-card" data-product-id="${p.id}" data-product-name="${p.name}" 
                     data-product-url="${p.image_url || ''}" ${clickAction} style="${opacityStyle}">
                    ${imageHtml}
                    <div class="product-card-name">${p.name}</div>
                    ${usedBadge}
                </div>`;
        });

        html += `</div></div>`;
    });

    body.innerHTML = html;
    if (window.lucide) window.lucide.createIcons();
}

function selectProduct(card) {
    // Deselect previous
    document.querySelectorAll('.product-card.selected').forEach(c => c.classList.remove('selected'));
    
    // Select this one
    card.classList.add('selected');
    selectedProductId = card.getAttribute('data-product-id');
    selectedProductUrl = card.getAttribute('data-product-url');
    selectedProductName = card.getAttribute('data-product-name');

    updateProductActionButtons();
}

function updateProductActionButtons() {
    const btnFull = document.getElementById('btnFullImage');
    const btnCutout = document.getElementById('btnCutoutImage');

    if (selectedProductId) {
        btnFull.disabled = false;
        btnCutout.disabled = false;
    } else {
        btnFull.disabled = true;
        btnCutout.disabled = true;
    }
}

async function insertProductImage(mode) {
    if (!selectedProductUrl || !fCanvas) return;

    const imageUrl = selectedProductUrl;
    const productId = selectedProductId;

    // Close the panel
    document.getElementById('myProductsModal').style.display = 'none';

    if (mode === 'transparent') {
        // Use the existing background removal feature if available
        if (typeof removeBackgroundFromUrl === 'function') {
            // Custom function for BG removal
            const processedUrl = await processBackgroundRemoval(imageUrl);
            addProductImageToCanvas(processedUrl || imageUrl, productId, mode);
        } else {
            // Fallback: try server-side removal
            try {
                const overlay = document.getElementById('processingOverlay');
                if (overlay) overlay.style.display = 'flex';

                const resp = await fetch('{{ route("remove-background") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ image_url: imageUrl })
                });
                const result = await resp.json();

                if (overlay) overlay.style.display = 'none';

                if (result.success && result.processed_url) {
                    addProductImageToCanvas(result.processed_url, productId, mode);
                } else {
                    // If BG removal fails, still insert as full image
                    addProductImageToCanvas(imageUrl, productId, 'full');
                }
            } catch (err) {
                const overlay = document.getElementById('processingOverlay');
                if (overlay) overlay.style.display = 'none';
                console.error('BG removal failed:', err);
                addProductImageToCanvas(imageUrl, productId, 'full');
            }
        }
    } else {
        // Full image — direct insert
        addProductImageToCanvas(imageUrl, productId, mode);
    }
}

function addProductImageToCanvas(imageUrl, productId, mode) {
    if (activeObject && activeObject.type === 'image') {
        replaceActiveImageWithUrl(imageUrl, productId, mode);
        markProductAsUsed(productId);
        return;
    }

    fabric.Image.fromURL(imageUrl, function(img) {
        if (!img) return;

        // Scale image to fit nicely in canvas (max 50% of canvas width)
        const maxWidth = CANVAS_W * 0.5;
        const maxHeight = CANVAS_H * 0.5;
        const scale = Math.min(maxWidth / img.width, maxHeight / img.height, 1);

        img.set({
            left: CANVAS_W / 2,
            top: CANVAS_H / 2,
            originX: 'center',
            originY: 'center',
            scaleX: scale,
            scaleY: scale,
            _objectType: 'product_image',
            _productId: productId,
            _imageMode: mode,
        });

        fCanvas.add(img);
        fCanvas.setActiveObject(img);
        fCanvas.renderAll();
        saveCanvasState();

        // Track this selection
        trackProductSelection(productId, imageUrl, mode);

        // Mark as used in cached data
        markProductAsUsed(productId);

    }, { crossOrigin: 'anonymous' });
}

function trackProductSelection(productId, imageUrl, mode) {
    const currentFrame = document.querySelector('.frame-item.selected');
    let templateId = currentFrame ? (currentFrame.getAttribute('data-config') || '') : '';

    fetch('{{ route("save.product.selection") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            product_id: productId,
            template_id: templateId,
            image_url: imageUrl,
            image_mode: mode
        })
    }).catch(err => console.log('Track selection error:', err));
}

function markProductAsUsed(productId) {
    if (!myProductsData || !myProductsData.groups) return;
    
    // Update cached data
    Object.values(myProductsData.groups).forEach(products => {
        products.forEach(p => {
            if (String(p.id) === String(productId)) {
                p.is_used = true;
            }
        });
    });
}

// ── Product Search (Client-Side, Debounced) ──
var productSearchTimer = null;

function filterProducts(query) {
    clearTimeout(productSearchTimer);
    productSearchTimer = setTimeout(() => {
        doFilterProducts(query);
    }, 200);
}

function doFilterProducts(query) {
    const q = (query || '').toLowerCase().trim();
    const groups = document.querySelectorAll('.product-group');
    let anyVisible = false;

    groups.forEach(group => {
        const cards = group.querySelectorAll('.product-card');
        let groupHasVisible = false;

        cards.forEach(card => {
            const name = (card.getAttribute('data-product-name') || '').toLowerCase();
            const groupName = (group.getAttribute('data-group') || '').toLowerCase();
            
            if (!q || name.includes(q) || groupName.includes(q)) {
                card.style.display = '';
                groupHasVisible = true;
                anyVisible = true;
            } else {
                card.style.display = 'none';
            }
        });

        group.style.display = groupHasVisible ? '' : 'none';
    });

    // Show "no results" if nothing matches
    let noResultsEl = document.getElementById('productsNoResults');
    if (!anyVisible && q) {
        if (!noResultsEl) {
            noResultsEl = document.createElement('div');
            noResultsEl.id = 'productsNoResults';
            noResultsEl.className = 'products-no-results';
            noResultsEl.innerHTML = `
                <i data-lucide="search-x"></i>
                <p>No products matching "${q}"</p>`;
            document.getElementById('productsBody').appendChild(noResultsEl);
            if (window.lucide) window.lucide.createIcons();
        } else {
            noResultsEl.querySelector('p').textContent = `No products matching "${q}"`;
            noResultsEl.style.display = '';
        }
    } else if (noResultsEl) {
        noResultsEl.style.display = 'none';
    }
}
</script>
<script>
// ── INDEPENDENT Frame Auto-Apply — DISABLED ──
// No longer needed: frame overlay is now chained to base config via promises.
// Keeping this code commented out for reference.
/*
(function() {
    let applied = false;
    function tryAutoApplyFrame() {
        if (applied) return;
        const firstFrame = document.querySelector('.frame-item.selected');
        if (!firstFrame) return;
        if (typeof fCanvas === 'undefined' || !fCanvas) return;
        if (fCanvas.getObjects().length === 0) return;
        applied = true;
        console.log('[AutoFrame] Auto-clicking first selected frame.');
        try {
            const frameUrl = firstFrame.getAttribute('onclick');
            const urlMatch = frameUrl ? frameUrl.match(/changeFrame\('([^']+)'/) : null;
            if (urlMatch && typeof changeFrame === 'function') {
                changeFrame(urlMatch[1], firstFrame);
            } else {
                firstFrame.click();
            }
        } catch(e) { console.error('[AutoFrame] Error:', e); }
    }
    const interval = setInterval(() => {
        tryAutoApplyFrame();
        if (applied) clearInterval(interval);
    }, 500);
    setTimeout(() => clearInterval(interval), 15000);
})();
*/
</script>
@endsection