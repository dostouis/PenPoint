<?php
// public/index.php
// PenPoint - Smart Vending for Everyday School Essentials
// Theme: Japanese Retro Stationery Kiosk (English-First with Retro Kanji Accent Details)
// Mobile Calibrated for 390x844 & Desktop Kiosks

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/ProductModel.php';
require_once __DIR__ . '/../models/MachineModel.php';

$productModel = new ProductModel();
$products = $productModel->getAllProducts();
$machine_id = $_GET['machine'] ?? DEFAULT_MACHINE_ID;

// Compute dynamic category counts for chips
$totalCount = count($products);
$categoryCounts = [
    'Paper' => 0,
    'Pens' => 0,
    'Pencils' => 0,
    'Examination' => 0
];
foreach ($products as $p) {
    $cat = $p['category'] ?? '';
    foreach (array_keys($categoryCounts) as $k) {
        if (stripos($cat, $k) !== false) {
            $categoryCounts[$k]++;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#181c24">
    <title><?= APP_NAME ?> — Japanese Retro Stationery Kiosk</title>
    <!-- Retro Typography & Display Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Zen+Maru+Gothic:wght@500;700;900&family=Noto+Sans+JP:wght@400;600;700;900&family=Dela+Gothic+One&family=DM+Mono:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- html2canvas for Receipt Download -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        :root {
            --retro-bg: #151821;
            --retro-chassis: #212634;
            --retro-panel: #2a3142;
            --retro-cream: #fbf7ee;
            --retro-cream-muted: #e6dfcf;
            --retro-ivory: #fffef9;
            --retro-red: #d63031;
            --retro-red-glow: rgba(214, 48, 49, 0.45);
            --retro-red-deep: #b71516;
            --retro-matcha: #2e7d32;
            --retro-matcha-light: #4caf50;
            --retro-amber: #e67e22;
            --retro-mustard: #f39c12;
            --retro-blue: #0984e3;
            --retro-border: #3d465c;
            --retro-border-cream: rgba(251, 247, 238, 0.15);
            --retro-metal-edge: #4a546c;
            --retro-text-light: #fbf7ee;
            --retro-text-muted: #9ba6be;
            --retro-text-dark: #2d3436;
            --font-display: 'Dela Gothic One', sans-serif;
            --font-body: 'Zen Maru Gothic', 'Noto Sans JP', sans-serif;
            --font-mono: 'DM Mono', monospace;
            --radius-box: 14px;
            --radius-btn: 10px;
            --radius-pill: 9999px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        /* Custom Themed Scrollbar matching Retro Machine Chassis */
        html {
            scrollbar-width: thin;
            scrollbar-color: #3d465c #151821;
        }

        ::-webkit-scrollbar {
            width: 7px;
            height: 7px;
        }

        ::-webkit-scrollbar-track {
            background: #151821;
            border-left: 1px solid #232938;
        }

        ::-webkit-scrollbar-thumb {
            background: #3a445b;
            border-radius: var(--radius-pill);
            border: 1px solid #1c2230;
            transition: background 0.2s;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #505e7e;
        }

        ::-webkit-scrollbar-thumb:active {
            background: var(--retro-mustard);
        }

        ::selection {
            background: var(--retro-mustard);
            color: #12151b;
        }

        body {
            font-family: var(--font-body);
            background-color: var(--retro-bg);
            color: var(--retro-text-light);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            background-image: 
                radial-gradient(circle at 50% 0%, #252c3c 0%, transparent 60%),
                radial-gradient(circle at 10% 100%, #1c212c 0%, transparent 40%);
        }

        /* Subtle retro halftone print texture overlay */
        body::after {
            content: "";
            position: fixed;
            inset: 0;
            background-image: radial-gradient(rgba(255, 255, 255, 0.04) 1px, transparent 0);
            background-size: 8px 8px;
            pointer-events: none;
            z-index: 998;
        }

        /* -------------------------------------------------------------
           Header: Retro Showa Machine Crown & Signboard
        ------------------------------------------------------------- */
        header {
            position: sticky;
            top: 0;
            z-index: 60;
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            background: rgba(33, 38, 52, 0.94);
            border-bottom: 3px solid #11141c;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.5);
            padding: 10px 16px;
        }

        .header-inner {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .retro-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: inherit;
        }

        .retro-stationery-sign {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, var(--retro-red), var(--retro-red-deep));
            border: 2px solid #ffffff;
            box-shadow: 0 3px 0 #7e0c0d, 0 6px 12px rgba(0, 0, 0, 0.4);
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            flex-shrink: 0;
        }

        .sign-kanji {
            font-family: 'Dela Gothic One', sans-serif;
            font-size: 0.95rem;
            color: #ffffff;
            line-height: 1;
            text-shadow: 0 1px 2px rgba(0,0,0,0.4);
        }

        .sign-sub-tag {
            font-size: 0.5rem;
            font-family: var(--font-mono);
            letter-spacing: 0.08em;
            color: #ffcccc;
            margin-top: 1px;
            text-transform: uppercase;
        }

        .brand-titles {
            display: flex;
            flex-direction: column;
        }

        .brand-main-title {
            font-family: var(--font-display);
            font-size: 1.35rem;
            letter-spacing: 0.02em;
            color: var(--retro-cream);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            line-height: 1;
        }

        .brand-jp-badge {
            font-size: 0.65rem;
            font-family: var(--font-body);
            font-weight: 900;
            color: #12151b;
            background: var(--retro-mustard);
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 3px 8px;
            border-radius: 5px;
            box-shadow: 0 1px 0 rgba(0,0,0,0.25);
            letter-spacing: 0.04em;
            transform: translateY(-2px);
        }

        .brand-sub-strip {
            font-size: 0.65rem;
            color: var(--retro-text-muted);
            font-family: var(--font-mono);
            letter-spacing: 0.08em;
            margin-top: 2px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Retro Speaker / Audio SFX Toggle Button */
        .retro-audio-toggle {
            display: flex;
            align-items: center;
            gap: 6px;
            background: #181d28;
            border: 2px solid #364157;
            box-shadow: 0 2px 0 #11141c;
            color: var(--retro-cream);
            padding: 5px 10px;
            border-radius: var(--radius-btn);
            font-family: var(--font-mono);
            font-size: 0.7rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .retro-audio-toggle:hover {
            background: #242c3d;
            border-color: #576582;
            color: #ffeaa7;
        }

        .retro-audio-toggle:active {
            transform: translateY(2px);
            box-shadow: none;
        }

        .retro-audio-toggle.muted {
            opacity: 0.6;
            color: var(--retro-text-muted);
            border-color: #2b3345;
        }

        /* Retro "ONLINE" Glowing Machine Lamp */
        .machine-lamp {
            display: flex;
            align-items: center;
            gap: 7px;
            background: #151821;
            border: 2px solid #333a4d;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.6), 0 2px 0 #2c3344;
            padding: 5px 12px;
            border-radius: var(--radius-pill);
            font-family: var(--font-mono);
            font-size: 0.72rem;
            font-weight: 700;
            color: #4ade80;
        }

        .lamp-bulb {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: #22c55e;
            box-shadow: 0 0 10px #22c55e, 0 0 3px #ffffff;
            animation: retroFlicker 3s infinite alternate;
        }

        @keyframes retroFlicker {
            0%, 93%, 100% { opacity: 1; }
            94% { opacity: 0.4; }
            96% { opacity: 0.8; }
            98% { opacity: 0.3; }
        }

        .pass-ticket-btn {
            background: var(--retro-panel);
            border: 2px solid var(--retro-border);
            color: var(--retro-cream);
            padding: 6px 12px;
            border-radius: 8px;
            box-shadow: 0 2px 0 #151821;
            font-family: var(--font-body);
            font-weight: 700;
            font-size: 0.78rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s ease;
        }

        .pass-ticket-btn:active {
            transform: translateY(2px);
            box-shadow: 0 0 0 transparent;
        }

        /* -------------------------------------------------------------
           Stationery Shop Marquee / Ticker
        ------------------------------------------------------------- */
        .retro-ticker-strip {
            background: #11141c;
            border-bottom: 2px solid #282f40;
            padding: 6px 16px;
            font-family: var(--font-mono);
            font-size: 0.7rem;
            color: var(--retro-text-muted);
            display: flex;
            align-items: center;
            justify-content: space-between;
            overflow: hidden;
        }

        .ticker-red-tag {
            color: #ff7675;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* -------------------------------------------------------------
           Hero Machine Showcase
        ------------------------------------------------------------- */
        .retro-hero-wrapper {
            max-width: 1200px;
            margin: 14px auto 8px;
            padding: 0 16px;
            width: 100%;
        }

        .retro-vending-frame {
            background: linear-gradient(180deg, #283042 0%, #1e2432 100%);
            border: 2px solid #3d465c;
            border-radius: 16px;
            padding: 16px 20px;
            box-shadow: 0 8px 0 #12151b, 0 16px 30px rgba(0, 0, 0, 0.6);
            position: relative;
        }

        .retro-hero-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 6px;
        }

        .retro-hero-title {
            font-family: var(--font-display);
            font-size: 1.35rem;
            color: var(--retro-cream);
            letter-spacing: 0.02em;
            line-height: 1.25;
        }

        .retro-hero-tag {
            background: linear-gradient(135deg, var(--retro-red), var(--retro-red-deep));
            color: #ffffff;
            font-family: 'Dela Gothic One', sans-serif;
            font-size: 0.68rem;
            padding: 3px 9px;
            border-radius: 6px;
            box-shadow: 0 2px 0 #6d0a0b;
            letter-spacing: 0.05em;
            white-space: nowrap;
        }

        .retro-hero-desc {
            font-size: 0.82rem;
            color: var(--retro-cream-muted);
            line-height: 1.5;
            max-width: 660px;
            margin-bottom: 14px;
        }

        /* 4-Step Diagram Styled as Japanese Retro Instruction Cards */
        .retro-steps-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            border-top: 2px dashed #3a4358;
            padding-top: 12px;
        }

        .retro-step-card {
            background: #181d27;
            border: 1px solid #333b4e;
            border-radius: 8px;
            padding: 7px 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .step-kanji-num {
            width: 22px;
            height: 22px;
            background: var(--retro-mustard);
            color: #12151b;
            border-radius: 50%;
            font-family: 'Dela Gothic One', sans-serif;
            font-size: 0.68rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 1px 0 rgba(0,0,0,0.3);
        }

        .step-info-col {
            display: flex;
            flex-direction: column;
        }

        .step-label-jp {
            font-size: 0.6rem;
            font-weight: 800;
            color: var(--retro-mustard);
            line-height: 1;
            font-family: var(--font-mono);
            letter-spacing: 0.05em;
        }

        .step-label-en {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--retro-cream);
            margin-top: 2px;
        }

        /* -------------------------------------------------------------
           Category Slider: Smooth Horizontal Scroll Bar
           (No page jumping, retro cassette/switch styled chips)
        ------------------------------------------------------------- */
        .category-slider-section {
            max-width: 1200px;
            margin: 10px auto 4px;
            padding: 0 16px;
            width: 100%;
        }

        .category-slider-box {
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative;
        }

        .retro-slider-track {
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            gap: 8px;
            overflow-x: scroll;
            overflow-y: hidden;
            scroll-behavior: smooth;
            padding: 4px 2px 8px;
            scrollbar-width: none;
            -webkit-overflow-scrolling: touch;
            touch-action: pan-x;
            cursor: grab;
            user-select: none;
            -webkit-user-select: none;
            width: 100%;
        }

        .retro-slider-track.dragging {
            cursor: grabbing;
            scroll-behavior: auto;
        }

        .retro-slider-track::-webkit-scrollbar {
            display: none;
        }

        .retro-nav-arrow {
            width: 34px;
            height: 34px;
            background: #232938;
            border: 2px solid #3c465d;
            box-shadow: 0 3px 0 #12151b;
            border-radius: 8px;
            color: var(--retro-cream);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            flex-shrink: 0;
            transition: all 0.12s ease;
        }

        .retro-nav-arrow:active {
            transform: translateY(2px);
            box-shadow: 0 1px 0 #12151b;
        }

        /* Retro Cassette Chip */
        .retro-chip {
            padding: 7px 15px;
            border-radius: 8px;
            background: #1c212e;
            border: 2px solid #323b4f;
            box-shadow: 0 3px 0 #12151b;
            color: var(--retro-cream-muted);
            font-size: 0.78rem;
            font-weight: 700;
            font-family: var(--font-body);
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.15s cubic-bezier(0.16, 1, 0.3, 1);
            user-select: none;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .retro-chip-indicator {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #576574;
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.5);
            transition: all 0.2s;
        }

        .retro-chip:hover:not(.active) {
            background: #283042;
            color: var(--retro-cream);
            border-color: #44516c;
        }

        .retro-chip.active {
            background: var(--retro-cream);
            color: #12151b;
            border-color: #ffffff;
            box-shadow: 0 3px 0 #95a5a6, 0 4px 10px rgba(0, 0, 0, 0.3);
            font-weight: 900;
            transform: translateY(-1px);
        }

        .retro-chip.active .retro-chip-indicator {
            background: var(--retro-red);
            box-shadow: 0 0 6px var(--retro-red);
        }

        /* -------------------------------------------------------------
           Main Supplies Bay Grid
        ------------------------------------------------------------- */
        main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 8px 16px 60px;
            flex: 1;
            width: 100%;
        }

        .inventory-strip-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 16px 0 14px;
            padding: 0 4px;
            gap: 12px;
        }

        .inventory-title-group {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .inventory-title-main {
            font-family: var(--font-body);
            font-weight: 900;
            font-size: 1.15rem;
            color: var(--retro-cream);
            letter-spacing: -0.01em;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .inventory-count-tag {
            font-family: var(--font-mono);
            font-size: 0.7rem;
            background: rgba(243, 156, 18, 0.12);
            border: 1px solid rgba(243, 156, 18, 0.4);
            color: var(--retro-mustard);
            padding: 3px 9px;
            border-radius: var(--radius-pill);
            font-weight: 700;
            white-space: nowrap;
            letter-spacing: 0.04em;
        }

        .inventory-machine-tag {
            font-size: 0.72rem;
            color: var(--retro-text-muted);
            font-family: var(--font-mono);
            white-space: nowrap;
            background: #181d27;
            border: 1px solid #333a4c;
            padding: 3px 9px;
            border-radius: 6px;
        }

        .vending-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(265px, 1fr));
            gap: 16px;
        }

        /* Vending Bay Slot Card */
        .vending-slot-card {
            background: #232938;
            border: 2px solid #3a445b;
            border-radius: var(--radius-box);
            box-shadow: 0 6px 0 #13161f, 0 12px 24px rgba(0, 0, 0, 0.5);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            position: relative;
            transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s ease;
        }

        .vending-slot-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 9px 0 #13161f, 0 16px 28px rgba(0, 0, 0, 0.6);
            border-color: #556382;
        }

        /* Slot Plate Header */
        .slot-plate-header {
            background: #1a1f2b;
            border-bottom: 2px solid #2d3648;
            padding: 7px 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-family: var(--font-mono);
            font-size: 0.68rem;
        }

        .slot-identifier {
            display: flex;
            align-items: center;
            gap: 6px;
            color: var(--retro-cream);
            font-weight: 700;
        }

        .slot-num-badge {
            background: #11141c;
            border: 1px solid #364055;
            color: #74b9ff;
            padding: 1px 7px;
            border-radius: 3px;
            font-weight: 800;
        }

        .slot-badge-ribbon {
            background: linear-gradient(135deg, #e67e22, #d35400);
            color: #ffffff;
            font-size: 0.62rem;
            font-weight: 900;
            padding: 2px 7px;
            border-radius: 4px;
            box-shadow: 0 1px 0 rgba(0,0,0,0.3);
            letter-spacing: 0.04em;
        }

        /* Display Window with Genuine Product Photo */
        .showcase-window {
            height: 180px;
            background: radial-gradient(circle at center, #ffffff 15%, #ede7db 100%);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
            border-bottom: 2px solid #161a24;
            box-shadow: inset 0 4px 10px rgba(0, 0, 0, 0.15);
        }

        /* Glass reflection stripe */
        .showcase-window::after {
            content: "";
            position: absolute;
            top: -40px;
            right: -30px;
            width: 90px;
            height: 260px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.35) 0%, transparent 60%);
            transform: rotate(25deg);
            pointer-events: none;
        }

        .stationery-photo {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 8px 12px rgba(45, 52, 54, 0.3));
            transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 2;
        }

        .vending-slot-card:hover .stationery-photo {
            transform: scale(1.06);
        }

        /* Item Info Section */
        .slot-details-body {
            padding: 14px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .item-category-tag {
            font-size: 0.68rem;
            font-weight: 900;
            color: var(--retro-mustard);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 4px;
            font-family: var(--font-body);
        }

        .item-name-title {
            font-family: var(--font-body);
            font-size: 1.05rem;
            font-weight: 900;
            line-height: 1.3;
            color: var(--retro-cream);
            margin-bottom: 5px;
        }

        .item-eng-desc {
            font-size: 0.78rem;
            color: var(--retro-cream-muted);
            line-height: 1.45;
            margin-bottom: 12px;
            flex: 1;
        }

        /* Stock Counter Dial */
        .stock-meter-panel {
            background: #181d27;
            border: 1px solid #2e374a;
            border-radius: 6px;
            padding: 6px 10px;
            margin-bottom: 12px;
        }

        .meter-label-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-family: var(--font-mono);
            font-size: 0.68rem;
            color: var(--retro-text-muted);
            margin-bottom: 4px;
        }

        .stock-lamp-tag {
            font-weight: 700;
        }

        .meter-bar-track {
            height: 5px;
            background: #11141c;
            border-radius: var(--radius-pill);
            overflow: hidden;
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.6);
        }

        .meter-bar-fill {
            height: 100%;
            border-radius: var(--radius-pill);
            transform-origin: left center;
        }

        .meter-bar-fill.normal {
            background: linear-gradient(90deg, #27ae60, #2ecc71);
        }

        .meter-bar-fill.low {
            background: linear-gradient(90deg, #e67e22, #f39c12);
        }

        .meter-bar-fill.empty {
            background: #c0392b;
        }

        /* Slot Bottom Action Plate */
        .slot-action-plate {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 10px;
            border-top: 2px dashed #30394c;
        }

        .price-badge-block {
            display: flex;
            flex-direction: column;
        }

        .price-kanji-label {
            font-size: 0.62rem;
            font-weight: 700;
            color: var(--retro-text-muted);
            font-family: var(--font-mono);
        }

        .price-digits {
            font-size: 1.3rem;
            font-weight: 900;
            color: #ffeaa7;
            font-family: var(--font-mono);
            letter-spacing: -0.02em;
            text-shadow: 0 1px 4px rgba(0,0,0,0.5);
        }

        /* Tactile Push Button */
        .btn-retro-push {
            background: linear-gradient(180deg, #ff4757 0%, var(--retro-red) 100%);
            border: 2px solid #ffffff;
            color: #ffffff;
            box-shadow: 0 4px 0 #7e0c0d, 0 6px 12px rgba(0, 0, 0, 0.4);
            border-radius: var(--radius-btn);
            padding: 8px 16px;
            font-family: 'Dela Gothic One', sans-serif;
            font-size: 0.8rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.1s ease;
            letter-spacing: 0.04em;
        }

        .btn-retro-push:hover {
            filter: brightness(1.08);
        }

        .btn-retro-push:active {
            transform: translateY(3px);
            box-shadow: 0 1px 0 #7e0c0d;
        }

        .btn-retro-push.disabled {
            background: #485460;
            border-color: #718093;
            box-shadow: 0 2px 0 #2f3542;
            color: #a4b0be;
            cursor: not-allowed;
            pointer-events: none;
            transform: none;
        }

        /* -------------------------------------------------------------
           Retro Checkout Modal
        ------------------------------------------------------------- */
        .modal-retro-scrim {
            position: fixed;
            inset: 0;
            background: rgba(10, 13, 19, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            z-index: 100;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
            overflow-y: auto;
        }

        .modal-retro-scrim.open {
            display: flex;
        }

        .retro-checkout-chassis {
            background: #212634;
            border: 2px solid #424d66;
            border-radius: 18px;
            width: 100%;
            max-width: 390px;
            padding: 20px 18px 18px;
            box-shadow: 0 8px 0 #10131a, 0 20px 45px rgba(0, 0, 0, 0.8);
            position: relative;
            animation: retroPop 0.22s cubic-bezier(0.16, 1, 0.3, 1);
            max-height: 94vh;
            overflow-y: auto;
            margin: auto;
        }

        @keyframes retroPop {
            0% { transform: scale(0.94); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        .retro-close-circle {
            position: absolute;
            top: 14px;
            right: 14px;
            background: #181d27;
            border: 1px solid #3d475c;
            color: var(--retro-cream-muted);
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.12s;
            z-index: 5;
        }

        .retro-close-circle:hover {
            color: #ffffff;
            border-color: #74b9ff;
            background: #242c3d;
        }

        .retro-close-circle:active {
            transform: scale(0.92);
        }

        .checkout-dialog-head {
            text-align: center;
            padding: 0 24px;
            margin-bottom: 12px;
        }

        .checkout-slot-stamp {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: var(--retro-mustard);
            color: #12151b;
            font-family: var(--font-mono);
            font-weight: 900;
            font-size: 0.65rem;
            padding: 2px 8px;
            border-radius: 4px;
            box-shadow: 0 2px 0 rgba(0,0,0,0.25);
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .checkout-item-heading {
            font-family: var(--font-body);
            font-weight: 800;
            font-size: 1.15rem;
            color: var(--retro-cream);
            margin-top: 6px;
            line-height: 1.3;
        }

        .checkout-price-dial {
            font-size: 1.5rem;
            font-weight: 900;
            font-family: var(--font-mono);
            color: #ffeaa7;
            text-shadow: 0 2px 4px rgba(0,0,0,0.6);
            margin-top: 2px;
            line-height: 1;
        }

        /* Product Usage & Academic Guide Box */
        .product-guide-box {
            background: rgba(9, 132, 227, 0.08);
            border: 1px solid rgba(116, 185, 255, 0.35);
            border-radius: 9px;
            padding: 9px 12px;
            margin: 8px 0 10px;
            text-align: left;
        }

        .guide-label-row {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.68rem;
            font-family: var(--font-mono);
            font-weight: 700;
            color: #74b9ff;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 3px;
        }

        .guide-usage-txt {
            font-size: 0.76rem;
            color: var(--retro-cream);
            line-height: 1.4;
            font-weight: 600;
        }

        .guide-specs-txt {
            font-size: 0.68rem;
            color: var(--retro-cream-muted);
            font-family: var(--font-mono);
            margin-top: 3px;
        }

        /* Retro Quantity Stepper Bar */
        .retro-qty-panel {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #181d27;
            border: 1px solid #363f54;
            border-radius: 10px;
            padding: 8px 12px;
            margin: 10px 0 12px;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.4);
        }

        .qty-label-group {
            display: flex;
            flex-direction: column;
            text-align: left;
        }

        .qty-header-txt {
            font-size: 0.65rem;
            font-family: var(--font-mono);
            font-weight: 700;
            color: var(--retro-cream-muted);
            letter-spacing: 0.05em;
        }

        .qty-unit-txt {
            font-size: 0.72rem;
            color: #74b9ff;
            font-weight: 800;
            font-family: var(--font-mono);
        }

        .qty-counter-housing {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-qty-step {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: #252b3b;
            border: 2px solid #4a5570;
            color: var(--retro-cream);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 0 #12151b;
            transition: all 0.1s;
        }

        .btn-qty-step:active {
            transform: translateY(2px);
            box-shadow: none;
        }

        .btn-qty-step:disabled {
            opacity: 0.35;
            cursor: not-allowed;
            pointer-events: none;
        }

        .qty-digits-display {
            font-family: var(--font-mono);
            font-weight: 900;
            font-size: 1.25rem;
            color: #ffeaa7;
            min-width: 24px;
            text-align: center;
        }

        /* QR Ph Banner Bar */
        .qrph-banner-strip {
            background: #181d27;
            border: 1px solid #333d52;
            border-radius: 8px;
            padding: 7px 10px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .qrph-pill {
            background: #e74c3c;
            color: #ffffff;
            font-weight: 900;
            font-family: var(--font-display);
            font-size: 0.68rem;
            padding: 2px 6px;
            border-radius: 4px;
            letter-spacing: 0.02em;
        }

        /* QR Ticket Frame */
        .qr-ticket-box {
            background: #ffffff;
            padding: 10px;
            border-radius: 12px;
            border: 2px dashed #b2bec3;
            width: 175px;
            height: 175px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 16px rgba(0,0,0,0.35);
        }

        .qr-img-canvas {
            width: 100%;
            height: 100%;
            display: block;
            border-radius: 4px;
        }

        .checkout-retro-notes {
            text-align: center;
            font-size: 0.72rem;
            color: var(--retro-cream-muted);
            line-height: 1.4;
            margin: 10px 0 12px;
        }

        /* Push to Pay / Simulate Dispense Button */
        .btn-confirm-checkout {
            width: 100%;
            background: linear-gradient(180deg, #2ecc71 0%, #27ae60 100%);
            border: 2px solid #ffffff;
            color: #ffffff;
            box-shadow: 0 3px 0 #1b663e, 0 6px 14px rgba(0, 0, 0, 0.4);
            border-radius: 9px;
            padding: 11px;
            font-family: 'Dela Gothic One', sans-serif;
            font-size: 0.88rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            transition: all 0.12s ease;
            letter-spacing: 0.02em;
        }

        .btn-confirm-checkout:active {
            transform: translateY(2px);
            box-shadow: 0 1px 0 #1b663e;
        }

        .btn-simulate-fail {
            width: 100%;
            background: rgba(231, 76, 60, 0.08);
            border: 1px dashed rgba(231, 76, 60, 0.45);
            color: #ff7675;
            border-radius: 7px;
            padding: 6px;
            font-size: 0.68rem;
            font-family: var(--font-mono);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            transition: all 0.15s;
        }

        .btn-simulate-fail:hover {
            background: rgba(231, 76, 60, 0.16);
            border-color: #ff7675;
        }

        /* -------------------------------------------------------------
           IR Sensor Telemetry Drop Screen
        ------------------------------------------------------------- */
        .retro-drop-scrim {
            position: fixed;
            inset: 0;
            background: rgba(9, 11, 16, 0.94);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            z-index: 150;
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px;
            text-align: center;
        }

        .retro-drop-scrim.active {
            display: flex;
        }

        .chute-icon-casing {
            width: 105px;
            height: 105px;
            border-radius: 50%;
            background: #1f2533;
            border: 3px solid var(--retro-mustard);
            box-shadow: 0 0 20px rgba(243, 156, 18, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
            color: var(--retro-mustard);
            position: relative;
            overflow: hidden;
        }

        /* Mechanical Drop Chute Container */
        .chute-physics-stage {
            width: 100%;
            max-width: 320px;
            height: 110px;
            background: #131720;
            border: 2px solid #2f384d;
            border-radius: 12px;
            position: relative;
            overflow: hidden;
            margin: 0 auto 14px;
            box-shadow: inset 0 4px 12px rgba(0,0,0,0.7);
        }

        .chute-ir-beam {
            position: absolute;
            left: 0;
            right: 0;
            top: 72px;
            height: 2px;
            background: rgba(231, 76, 60, 0.4);
            box-shadow: 0 0 8px rgba(231, 76, 60, 0.6);
            transition: all 0.2s ease;
        }

        .chute-ir-beam.beam-tripped {
            background: #2ecc71;
            box-shadow: 0 0 16px #2ecc71, 0 0 4px #ffffff;
            height: 3px;
        }

        .chute-ir-beam-label {
            position: absolute;
            right: 8px;
            top: 54px;
            font-size: 0.58rem;
            font-family: var(--font-mono);
            color: #ff7675;
            letter-spacing: 0.05em;
            transition: color 0.2s;
        }

        .chute-ir-beam-label.beam-tripped {
            color: #2ecc71;
            font-weight: 900;
        }

        .chute-item-payload {
            position: absolute;
            top: -60px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 50px;
            object-fit: contain;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.5));
            transition: none;
        }

        .chute-item-payload.falling {
            animation: chuteFall 0.85s cubic-bezier(0.55, 0.055, 0.675, 0.19) forwards,
                       chuteBounce 0.45s 0.85s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }

        @keyframes chuteFall {
            0% { top: -60px; transform: translateX(-50%) rotate(0deg); }
            100% { top: 56px; transform: translateX(-50%) rotate(6deg); }
        }

        @keyframes chuteBounce {
            0% { transform: translateX(-50%) translateY(0) rotate(6deg); }
            40% { transform: translateX(-50%) translateY(-14px) rotate(-3deg); }
            70% { transform: translateX(-50%) translateY(0) rotate(2deg); }
            85% { transform: translateX(-50%) translateY(-4px) rotate(0deg); }
            100% { transform: translateX(-50%) translateY(0) rotate(0deg); }
        }

        /* Vintage Paper e-Receipt Printout */
        .retro-receipt-ticket {
            background: #fffdf7;
            color: #12151b;
            border: 2px dashed #b2bec3;
            border-radius: 8px;
            padding: 12px 14px;
            max-width: 330px;
            width: 100%;
            margin: 12px auto 0;
            text-align: left;
            font-family: var(--font-mono);
            box-shadow: 0 8px 24px rgba(0,0,0,0.6);
            transform-origin: top center;
            animation: receiptSlideDown 0.45s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes receiptSlideDown {
            0% { opacity: 0; transform: translateY(-16px) scale(0.96); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }

        .receipt-header-row {
            border-bottom: 1px dashed #7f8c8d;
            padding-bottom: 8px;
            margin-bottom: 8px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }

        .receipt-brand-stamp {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--retro-red), var(--retro-red-deep));
            border: 2px solid #ffffff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.25);
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin-bottom: 2px;
        }

        .receipt-brand-stamp .sign-kanji {
            font-family: 'Dela Gothic One', sans-serif;
            font-size: 0.8rem;
            color: #ffffff;
            line-height: 1;
            text-shadow: 0 1px 2px rgba(0,0,0,0.4);
        }

        .receipt-brand-stamp .sign-sub-tag {
            font-size: 0.45rem;
            font-family: var(--font-mono);
            letter-spacing: 0.08em;
            color: #ffcccc;
            margin-top: 1px;
            text-transform: uppercase;
        }

        .receipt-title {
            font-family: 'Dela Gothic One', sans-serif;
            font-size: 0.95rem;
            color: var(--retro-red-deep);
            letter-spacing: 0.05em;
            line-height: 1.1;
        }

        .receipt-line {
            display: flex;
            justify-content: space-between;
            font-size: 0.72rem;
            margin-bottom: 4px;
        }

        .receipt-total-line {
            border-top: 1px solid #2d3436;
            margin-top: 6px;
            padding-top: 6px;
            font-weight: 900;
            font-size: 0.85rem;
            display: flex;
            justify-content: space-between;
        }

        .btn-receipt-download {
            width: 100%;
            background: #181d28;
            border: 1.5px solid #364157;
            color: #74b9ff;
            border-radius: 6px;
            padding: 7px;
            font-size: 0.72rem;
            font-family: var(--font-mono);
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 8px;
            transition: all 0.15s ease;
        }

        .btn-receipt-download:hover {
            background: #242c3d;
            border-color: #74b9ff;
            color: #ffffff;
        }

        .btn-receipt-download:active {
            transform: scale(0.98);
        }

        .drop-en-title {
            font-family: var(--font-display);
            font-size: 1.55rem;
            color: var(--retro-cream);
            margin-bottom: 6px;
        }

        .drop-log-box {
            font-family: var(--font-mono);
            font-size: 0.85rem;
            color: var(--retro-cream-muted);
            max-width: 380px;
            line-height: 1.5;
            background: #161b24;
            border: 1px solid #2d3648;
            padding: 10px 14px;
            border-radius: 8px;
            margin-top: 8px;
        }

        .tray-takeout-panel {
            margin-top: 22px;
            display: none;
        }

        .btn-claim-item {
            background: linear-gradient(180deg, #2ecc71 0%, #27ae60 100%);
            border: 2px solid #ffffff;
            color: #ffffff;
            box-shadow: 0 4px 0 #1b663e, 0 8px 18px rgba(0,0,0,0.4);
            padding: 12px 28px;
            border-radius: 10px;
            font-family: 'Dela Gothic One', sans-serif;
            font-size: 0.95rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.12s;
        }

        .btn-claim-item:active {
            transform: translateY(3px);
            box-shadow: 0 1px 0 #1b663e;
        }

        /* Footer */
        footer {
            border-top: 3px solid #141720;
            background: #1b202c;
            padding: 24px 16px;
            text-align: center;
            font-size: 0.78rem;
            color: var(--retro-text-muted);
            margin-top: auto;
        }

        .project-team-stamps {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 8px;
            margin: 12px 0;
        }

        .team-hanko-stamp {
            background: #141822;
            border: 1px solid #333d52;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 0.72rem;
            color: var(--retro-cream);
            font-family: var(--font-body);
            font-weight: 700;
        }

        /* -------------------------------------------------------------
           Mobile Responsive (iPhone 390x844 & Compact Displays)
        ------------------------------------------------------------- */
        @media (max-width: 480px) {
            header {
                padding: 8px 10px;
            }

            .header-inner {
                gap: 8px;
            }

            .retro-brand {
                gap: 8px;
                min-width: 0;
                flex-shrink: 1;
            }

            .retro-stationery-sign {
                width: 36px;
                height: 36px;
            }

            .sign-kanji {
                font-size: 0.82rem;
            }

            .brand-main-title {
                font-size: 1.05rem;
                gap: 6px;
                white-space: nowrap;
            }

            .brand-jp-badge {
                font-size: 0.58rem;
                padding: 2px 6px;
            }

            .brand-sub-strip {
                font-size: 0.58rem;
                letter-spacing: 0.04em;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .header-actions {
                gap: 6px;
                flex-shrink: 0;
            }

            .retro-audio-toggle {
                padding: 5px 8px;
                font-size: 0.65rem;
                gap: 4px;
            }

            .machine-lamp {
                padding: 4px 8px;
                font-size: 0.65rem;
                gap: 5px;
            }

            .retro-ticker-strip {
                padding: 5px 10px;
                font-size: 0.64rem;
                gap: 8px;
            }

            .pass-ticket-btn span {
                display: none;
            }

            .pass-ticket-btn {
                padding: 6px 9px;
            }

            .retro-hero-wrapper {
                margin: 8px auto 4px;
                padding: 0 12px;
            }

            .retro-vending-frame {
                padding: 14px 14px 12px;
                border-radius: 14px;
            }

            .retro-hero-title {
                font-size: 1.18rem;
            }

            .retro-hero-desc {
                font-size: 0.78rem;
                margin-bottom: 10px;
            }

            .retro-steps-row {
                grid-template-columns: 1fr 1fr;
                gap: 6px;
                padding-top: 10px;
            }

            .retro-step-card {
                padding: 6px 8px;
            }

            .category-slider-section {
                margin: 6px auto 2px;
                padding: 0 8px;
            }

            .retro-slider-track {
                gap: 6px;
                padding: 4px 2px 6px;
            }

            .retro-chip {
                padding: 6px 12px;
                font-size: 0.75rem;
            }

            .retro-nav-arrow {
                width: 28px;
                height: 28px;
            }

            main {
                padding: 4px 12px 40px;
            }

            .inventory-strip-header {
                margin: 12px 0 10px;
            }

            .inventory-title-main {
                font-size: 1.05rem;
                gap: 6px;
            }

            .inventory-count-tag {
                font-size: 0.65rem;
                padding: 2px 7px;
            }

            .inventory-machine-tag {
                font-size: 0.68rem;
                padding: 2px 7px;
            }

            .vending-grid {
                grid-template-columns: 1fr;
                gap: 14px;
            }

            .showcase-window {
                height: 160px;
            }

            .slot-details-body {
                padding: 12px;
            }

            .item-name-title {
                font-size: 1rem;
            }

            .price-digits {
                font-size: 1.2rem;
            }

            .btn-retro-push {
                padding: 8px 14px;
                font-size: 0.78rem;
            }

            .modal-retro-scrim {
                padding: 16px 12px;
                align-items: center;
            }

            .retro-checkout-chassis {
                max-width: 360px;
                border-radius: 16px;
                padding: 18px 14px 16px;
                max-height: 92vh;
            }

            .qr-ticket-box {
                width: 155px;
                height: 155px;
                padding: 8px;
            }
        }
    </style>
</head>
<body>

    <!-- Retro Header & Signboard -->
    <header>
        <div class="header-inner">
            <a href="index.php" class="retro-brand">
                <div class="retro-stationery-sign">
                    <span class="sign-kanji">文具</span>
                    <span class="sign-sub-tag">PEN</span>
                </div>
                <div class="brand-titles">
                    <div class="brand-main-title">
                        <span><?= APP_NAME ?></span>
                        <span class="brand-jp-badge">SMART KIOSK</span>
                    </div>
                    <div class="brand-sub-strip">SMART STATIONERY VENDING KIOSK</div>
                </div>
            </a>
            <div class="header-actions">
                <button class="retro-audio-toggle" id="audioToggleBtn" onclick="toggleAudio()" title="Sound Effects">
                    <i data-lucide="volume-2" id="audioIcon" style="width: 14px; height: 14px;"></i>
                    <span id="audioLabel">SFX ON</span>
                </button>
                <div class="machine-lamp">
                    <div class="lamp-bulb"></div>
                    <span>ONLINE</span>
                </div>
            </div>
        </div>
    </header>

    <!-- Retro Stationery News Ticker -->
    <div class="retro-ticker-strip">
        <div class="ticker-red-tag">
            <i data-lucide="bell" style="width: 12px; height: 12px;"></i>
            Quiz & Exam Essentials • Instant Cashless Vending
        </div>
        <div style="font-family: var(--font-mono);">
            MACHINE: <?= htmlspecialchars($machine_id) ?> • IR DROP VERIFIED
        </div>
    </div>

    <!-- Retro Machine Guide Panel -->
    <div class="retro-hero-wrapper">
        <div class="retro-vending-frame">
            <div class="retro-hero-header">
                <h2 class="retro-hero-title">Need school supplies before your quiz?</h2>
                <div class="retro-hero-tag">INSTANT VEND</div>
            </div>
            <p class="retro-hero-desc">
                Don't run to the distant bookstore! Tap your essential supply, scan GCash or Maya QR, and the mechanical motor dispenses your item verified by dual IR drop sensors.
            </p>
            <div class="retro-steps-row">
                <div class="retro-step-card">
                    <div class="step-kanji-num">1</div>
                    <div class="step-info-col">
                        <span class="step-label-jp">STEP 01</span>
                        <span class="step-label-en">Select Item</span>
                    </div>
                </div>
                <div class="retro-step-card">
                    <div class="step-kanji-num">2</div>
                    <div class="step-info-col">
                        <span class="step-label-jp">STEP 02</span>
                        <span class="step-label-en">Scan QR</span>
                    </div>
                </div>
                <div class="retro-step-card">
                    <div class="step-kanji-num">3</div>
                    <div class="step-info-col">
                        <span class="step-label-jp">STEP 03</span>
                        <span class="step-label-en">Motor Dispenses</span>
                    </div>
                </div>
                <div class="retro-step-card">
                    <div class="step-kanji-num">4</div>
                    <div class="step-info-col">
                        <span class="step-label-jp">STEP 04</span>
                        <span class="step-label-en">IR Confirmed</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Slider Bar (Smooth Horizontal Scroll, Zero Page Jumping) -->
    <div class="category-slider-section">
        <div class="category-slider-box">
            <button class="retro-nav-arrow" onclick="slideCategory(-1)" aria-label="Slide Left">
                <i data-lucide="chevron-left" style="width: 16px; height: 16px;"></i>
            </button>
            <div class="retro-slider-track" id="categorySlider">
                <button class="retro-chip active" onclick="filterCategory('All', this)">
                    <span class="retro-chip-indicator"></span> All Items (<?= $totalCount ?>)
                </button>
                <button class="retro-chip" onclick="filterCategory('Paper', this)">
                    <span class="retro-chip-indicator"></span> Paper Supplies (<?= $categoryCounts['Paper'] ?>)
                </button>
                <button class="retro-chip" onclick="filterCategory('Pens', this)">
                    <span class="retro-chip-indicator"></span> Pens & Inks (<?= $categoryCounts['Pens'] ?>)
                </button>
                <button class="retro-chip" onclick="filterCategory('Pencils', this)">
                    <span class="retro-chip-indicator"></span> Exam Pencils (<?= $categoryCounts['Pencils'] ?>)
                </button>
                <button class="retro-chip" onclick="filterCategory('Examination', this)">
                    <span class="retro-chip-indicator"></span> Blue Books (<?= $categoryCounts['Examination'] ?>)
                </button>
            </div>
            <button class="retro-nav-arrow" onclick="slideCategory(1)" aria-label="Slide Right">
                <i data-lucide="chevron-right" style="width: 16px; height: 16px;"></i>
            </button>
        </div>
    </div>

    <!-- Supplies Catalog Grid -->
    <main>
        <div class="inventory-strip-header">
            <div class="inventory-title-group">
                <h3 class="inventory-title-main">
                    <span>Available Vending Slots</span>
                    <span class="inventory-count-tag" id="activeSlotsCountTag">6 SLOTS ACTIVE</span>
                </h3>
            </div>
            <div class="inventory-machine-tag">
                <?= htmlspecialchars($machine_id) ?> • 4 TIERS
            </div>
        </div>

        <div class="vending-grid" id="productsGrid">
            <?php foreach ($products as $p): ?>
                <?php 
                    $isLowStock = ($p['stock'] <= $p['low_stock_threshold'] && $p['stock'] > 0);
                    $isEmpty = ($p['stock'] <= 0);
                    $stockPercent = min(100, round(($p['stock'] / $p['max_capacity']) * 100));
                    $tierNum = $p['tier_number'] ?? ($p['slot_number'] <= 3 ? $p['slot_number'] : 4);
                    $mech = $p['mechanism_type'] ?? ($p['slot_number'] <= 3 ? 'roller_feed' : 'paddle_push');
                    $isRoller = ($mech === 'roller_feed');
                ?>
                <div class="vending-slot-card" data-category="<?= htmlspecialchars($p['category']) ?>" data-slot="<?= $p['slot_number'] ?>">
                    <div class="slot-plate-header">
                        <div class="slot-identifier">
                            <span class="slot-num-badge">SLOT <?= $p['slot_number'] ?></span>
                            <span>TIER <?= $tierNum ?></span>
                        </div>
                        <div class="slot-badge-ribbon" style="background: rgba(46, 204, 113, 0.15); border-color: #2ecc71; color: #2ecc71;">
                            <i data-lucide="check-circle" style="width: 11px; height: 11px; display: inline; vertical-align: middle;"></i>
                            Ready
                        </div>
                    </div>

                    <!-- Showcase Chamber Window with Genuine Product Photo -->
                    <div class="showcase-window">
                        <img src="<?= htmlspecialchars($p['image_url']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" class="stationery-photo" loading="lazy">
                    </div>

                    <div class="slot-details-body">
                        <div class="item-category-tag"><?= htmlspecialchars($p['category']) ?> • TIER <?= $tierNum ?></div>
                        <h4 class="item-name-title"><?= htmlspecialchars($p['name']) ?></h4>
                        <p class="item-eng-desc"><?= htmlspecialchars($p['description']) ?></p>

                        <!-- Stock Dial -->
                        <div class="stock-meter-panel">
                            <div class="meter-label-row">
                                <span>STOCK STATUS</span>
                                <span class="stock-lamp-tag" style="color: <?= $isEmpty ? '#e74c3c' : ($isLowStock ? '#f39c12' : '#2ecc71') ?>;">
                                    <?= $isEmpty ? 'SOLD OUT' : ($p['stock'] . ' units left') ?>
                                </span>
                            </div>
                            <div class="meter-bar-track">
                                <div class="meter-bar-fill <?= $isEmpty ? 'empty' : ($isLowStock ? 'low' : 'normal') ?>" style="width: <?= $stockPercent ?>%;"></div>
                            </div>
                        </div>

                        <!-- Price & Push Button -->
                        <div class="slot-action-plate">
                            <div class="price-badge-block">
                                <span class="price-kanji-label">PRICE</span>
                                <span class="price-digits"><?= CURRENCY_SYMBOL ?><?= number_format($p['price'], 2) ?></span>
                            </div>

                            <button class="btn-retro-push <?= $isEmpty ? 'disabled' : '' ?>" 
                                    onclick="openCheckout(<?= htmlspecialchars(json_encode($p)) ?>)">
                                <i data-lucide="arrow-down-circle" style="width: 15px; height: 15px;"></i>
                                <?= $isEmpty ? 'Sold Out' : 'PUSH / VEND' ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- Cashless QR Ph Checkout Modal -->
    <div class="modal-retro-scrim" id="checkoutModal">
        <div class="retro-checkout-chassis">
            <button class="retro-close-circle" onclick="closeCheckout()" aria-label="Close Modal">
                <i data-lucide="x" style="width: 16px; height: 16px;"></i>
            </button>

            <div class="checkout-dialog-head">
                <div class="checkout-slot-stamp" id="modalSlot">SLOT 01 • TIER 1</div>
                <h3 class="checkout-item-heading" id="modalItemName">Pilot G-Tec C4</h3>
                <div class="checkout-price-dial" id="modalPrice">₱45.00</div>
            </div>

            <!-- Product Purpose & Academic Recommendation -->
            <div class="product-guide-box" id="modalGuideBox">
                <div class="guide-label-row">
                    <i data-lucide="compass" style="width: 12px; height: 12px;"></i>
                    <span>Recommended Academic Use</span>
                </div>
                <div class="guide-usage-txt" id="modalUsage">
                    Major term papers, legal deeds, thesis documentation & contracts.
                </div>
                <div class="guide-specs-txt" id="modalSpecs">
                    8.5 x 13 in • Substance 20 • Pack of 5 Sheets
                </div>
            </div>

            <!-- Quantity Selector & Total Price Calculation -->
            <div class="retro-qty-panel">
                <div class="qty-label-group">
                    <span class="qty-header-txt">SELECT QUANTITY</span>
                    <span class="qty-unit-txt" id="qtyUnitLabel">₱45.00 each • max in stock</span>
                </div>
                <div class="qty-counter-housing">
                    <button type="button" class="btn-qty-step" id="qtyMinusBtn" onclick="changeQuantity(-1)" aria-label="Decrease quantity">
                        <i data-lucide="minus" style="width: 14px; height: 14px;"></i>
                    </button>
                    <span class="qty-digits-display" id="modalQtyDisplay">1</span>
                    <button type="button" class="btn-qty-step" id="qtyPlusBtn" onclick="changeQuantity(1)" aria-label="Increase quantity">
                        <i data-lucide="plus" style="width: 14px; height: 14px;"></i>
                    </button>
                </div>
            </div>

            <!-- QR Ph Universal Header -->
            <div class="qrph-banner-strip">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span class="qrph-pill">QR Ph</span>
                    <span style="font-size: 0.74rem; color: var(--retro-cream); font-weight: 700;">Universal QR (GCash • Maya • Banks)</span>
                </div>
                <span style="font-size: 0.65rem; font-family: var(--font-mono); color: #74b9ff;">PayMongo</span>
            </div>

            <!-- Dynamic QR Ph Ticket -->
            <div class="qr-ticket-box">
                <img id="qrImage" src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' fill='%23f8fafc'/><text x='50' y='55' font-size='10' text-anchor='middle' fill='%2364748b' font-family='sans-serif'>QR Ph...</text></svg>" alt="Payment QR" class="qr-img-canvas">
            </div>

            <div class="checkout-retro-notes">
                Scan with GCash, Maya, ShopeePay, or any banking app.<br>
                Single-use dynamic QR code • Expires in 30 mins.
            </div>

            <!-- Simulation Controls -->
            <div style="display: flex; flex-direction: column; gap: 8px; width: 100%;">
                <button class="btn-confirm-checkout" id="simulatePayBtn" onclick="executeDispense(false)">
                    <i data-lucide="check-circle-2" style="width: 17px; height: 17px;"></i>
                    Simulate Payment & Dispense
                </button>
                <button type="button" class="btn-simulate-fail" onclick="executeDispense(true)">
                    <i data-lucide="alert-triangle" style="width: 12px; height: 12px;"></i>
                    Simulate Jam / Sensor Drop Timeout & Auto-Refund
                </button>
            </div>
        </div>
    </div>

    <!-- IR Sensor Telemetry Verification Overlay with Animated Drop Chute & e-Receipt -->
    <div class="retro-drop-scrim" id="dispenseOverlay">
        <!-- Mechanical Chute Simulation Container -->
        <div class="chute-physics-stage" id="chuteStage">
            <span class="chute-ir-beam-label" id="beamStatusLabel">IR BEAM READY</span>
            <div class="chute-ir-beam" id="chuteBeam"></div>
            <img src="" id="chuteFallingImg" class="chute-item-payload" alt="Dropping Supply">
        </div>

        <h2 class="drop-en-title" id="dispenseTitle">Dispensing Supply...</h2>
        <div class="drop-log-box" id="dispenseMsg">
            Actuating motor on Slot #<span id="activeSlotNum">01</span>.<br>Awaiting optical IR break-beam sensor verification...
        </div>

        <!-- Animated Paper e-Receipt Printout -->
        <div class="retro-receipt-ticket" id="receiptPrintout" style="display: none;">
            <div class="receipt-header-row">
                <div class="receipt-brand-stamp">
                    <span class="sign-kanji">文具</span>
                    <span class="sign-sub-tag">PEN</span>
                </div>
                <div class="receipt-title">PENPOINT e-RECEIPT</div>
                <div style="font-size: 0.65rem; color: #636e72;">UNIVERSITY OF MAKATI • KIOSK #01</div>
            </div>
            <div class="receipt-line">
                <span>DATE/TIME:</span>
                <span id="receiptDateTime">2026-10-22 14:55</span>
            </div>
            <div class="receipt-line">
                <span>REF NO:</span>
                <span id="receiptRef">TXN-00000000</span>
            </div>
            <div class="receipt-line">
                <span>ITEM:</span>
                <span id="receiptItemName" style="font-weight: 700;">Pilot G-Tec C4</span>
            </div>
            <div class="receipt-line">
                <span>QTY:</span>
                <span id="receiptQty">1</span>
            </div>
            <div class="receipt-line">
                <span>PAY METHOD:</span>
                <span>QR Ph (PayMongo)</span>
            </div>
            <div class="receipt-line">
                <span>IR STATUS:</span>
                <span style="color: #27ae60; font-weight: 800;">VERIFIED (FALL DETECTED)</span>
            </div>
            <div class="receipt-total-line">
                <span>TOTAL PAID:</span>
                <span id="receiptTotalAmount">₱45.00</span>
            </div>
            <button type="button" class="btn-receipt-download" id="downloadReceiptBtn" onclick="downloadReceiptImage()">
                <i data-lucide="download" style="width: 14px; height: 14px;"></i>
                <span id="downloadBtnText">Save e-Receipt (PNG)</span>
            </button>
        </div>

        <div class="tray-takeout-panel" id="dispenseDoneAction">
            <button class="btn-claim-item" id="claimItemBtn" onclick="finishOrder()">
                <i data-lucide="package-check" style="width: 18px; height: 18px;"></i>
                Item Confirmed! Claim from Collection Tray
            </button>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div style="font-family: var(--font-display); font-size: 1rem; color: var(--retro-cream); margin-bottom: 4px;">
            <?= APP_NAME ?> • SMART STATIONERY VENDING KIOSK
        </div>
        <div>4-Tier Smart Academic Supplies System • Group SkiDB (IV-CINS)</div>
        <div class="project-team-stamps">
            <?php foreach (PROJECT_MEMBERS as $member): ?>
                <span class="team-hanko-stamp"><?= htmlspecialchars($member) ?></span>
            <?php endforeach; ?>
        </div>
        <div style="color: #636e72; font-size: 0.72rem; font-family: var(--font-mono); margin-top: 10px;">
            ESP32 MICROCONTROLLER • 4-TIER BREAK-BEAM IR DROP DETECTION • QR Ph PAYMONGO • © 2026
        </div>
    </footer>

    <script>
        lucide.createIcons();

        let currentProduct = null;
        let selectedPayMethod = 'QR_PH';
        let selectedQuantity = 1;
        let soundEnabled = true;

        // -------------------------------------------------------------
        // Retro Web Audio API Synthesizer (Zero External Audio Files)
        // Authentic Showa mechanical relay, click, rotary tick, and drop chimes
        // -------------------------------------------------------------
        let audioCtx = null;

        function getAudioContext() {
            if (!audioCtx) {
                const AudioContextClass = window.AudioContext || window.webkitAudioContext;
                if (AudioContextClass) audioCtx = new AudioContextClass();
            }
            if (audioCtx && audioCtx.state === 'suspended') {
                audioCtx.resume();
            }
            return audioCtx;
        }

        function toggleAudio() {
            soundEnabled = !soundEnabled;
            const btn = document.getElementById('audioToggleBtn');
            const icon = document.getElementById('audioIcon');
            const label = document.getElementById('audioLabel');

            if (soundEnabled) {
                btn.classList.remove('muted');
                label.innerText = 'SFX ON';
                icon.setAttribute('data-lucide', 'volume-2');
                playRetroSound('click');
            } else {
                btn.classList.add('muted');
                label.innerText = 'MUTED';
                icon.setAttribute('data-lucide', 'volume-x');
            }
            lucide.createIcons();
        }

        function playRetroSound(type) {
            if (!soundEnabled) return;
            try {
                const ctx = getAudioContext();
                if (!ctx) return;
                const now = ctx.currentTime;

                if (type === 'click') {
                    // Tactile mechanical switch click
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'triangle';
                    osc.frequency.setValueAtTime(140, now);
                    osc.frequency.exponentialRampToValueAtTime(40, now + 0.04);
                    gain.gain.setValueAtTime(0.4, now);
                    gain.gain.exponentialRampToValueAtTime(0.01, now + 0.04);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(now);
                    osc.stop(now + 0.04);
                } else if (type === 'step') {
                    // Rotary tick for stepper
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(520, now);
                    osc.frequency.exponentialRampToValueAtTime(880, now + 0.035);
                    gain.gain.setValueAtTime(0.25, now);
                    gain.gain.exponentialRampToValueAtTime(0.01, now + 0.035);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(now);
                    osc.stop(now + 0.035);
                } else if (type === 'drop') {
                    // Metallic coin/supply landing clink & IR chime
                    const osc1 = ctx.createOscillator();
                    const osc2 = ctx.createOscillator();
                    const gain = ctx.createGain();

                    osc1.type = 'sine';
                    osc1.frequency.setValueAtTime(987.77, now); // B5
                    osc1.frequency.setValueAtTime(1318.51, now + 0.09); // E6

                    osc2.type = 'triangle';
                    osc2.frequency.setValueAtTime(659.25, now);
                    osc2.frequency.setValueAtTime(880.00, now + 0.09);

                    gain.gain.setValueAtTime(0.3, now);
                    gain.gain.exponentialRampToValueAtTime(0.01, now + 0.45);

                    osc1.connect(gain);
                    osc2.connect(gain);
                    gain.connect(ctx.destination);

                    osc1.start(now);
                    osc2.start(now);
                    osc1.stop(now + 0.45);
                    osc2.stop(now + 0.45);

                    // Gentle haptic pulse on supported mobile devices
                    if (navigator.vibrate) navigator.vibrate([40, 60, 80]);
                } else if (type === 'fail') {
                    // Error buzz
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sawtooth';
                    osc.frequency.setValueAtTime(120, now);
                    osc.frequency.setValueAtTime(90, now + 0.12);
                    gain.gain.setValueAtTime(0.35, now);
                    gain.gain.exponentialRampToValueAtTime(0.01, now + 0.28);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(now);
                    osc.stop(now + 0.28);
                }
            } catch(e) {}
        }

        // -------------------------------------------------------------
        // Category Slider: Smooth touch swipe & drag horizontal physics
        // -------------------------------------------------------------
        const catSlider = document.getElementById('categorySlider');
        let isDown = false;
        let startX = 0;
        let scrollLeft = 0;
        let isDragging = false;

        function slideCategory(dir) {
            playRetroSound('click');
            if (catSlider) {
                catSlider.scrollBy({ left: dir * 140, behavior: 'smooth' });
            }
        }

        if (catSlider) {
            catSlider.addEventListener('mousedown', (e) => {
                isDown = true;
                isDragging = false;
                catSlider.classList.add('dragging');
                startX = e.pageX - catSlider.offsetLeft;
                scrollLeft = catSlider.scrollLeft;
            });

            window.addEventListener('mouseup', () => {
                isDown = false;
                if (catSlider) catSlider.classList.remove('dragging');
            });

            catSlider.addEventListener('mousemove', (e) => {
                if (!isDown) return;
                e.preventDefault();
                const x = e.pageX - catSlider.offsetLeft;
                const walk = (x - startX) * 1.5;
                if (Math.abs(walk) > 4) isDragging = true;
                catSlider.scrollLeft = scrollLeft - walk;
            });
        }

        function filterCategory(cat, el) {
            if (isDragging) {
                isDragging = false;
                return;
            }
            playRetroSound('click');

            document.querySelectorAll('.retro-chip').forEach(btn => btn.classList.remove('active'));
            el.classList.add('active');

            if (catSlider) {
                const trackRect = catSlider.getBoundingClientRect();
                const elRect = el.getBoundingClientRect();
                const targetScroll = catSlider.scrollLeft + (elRect.left - trackRect.left) - (catSlider.clientWidth / 2) + (el.clientWidth / 2);
                catSlider.scrollTo({ left: targetScroll, behavior: 'smooth' });
            }

            const cards = document.querySelectorAll('.vending-slot-card');
            let visibleCount = 0;
            cards.forEach(card => {
                const itemCat = card.dataset.category || '';
                if (cat === 'All' || itemCat.toLowerCase().includes(cat.toLowerCase())) {
                    card.style.display = 'flex';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            const countTag = document.getElementById('activeSlotsCountTag');
            if (countTag) {
                countTag.innerText = cat === 'All' ? '6 SLOTS ACTIVE' : `${visibleCount} IN ${cat.toUpperCase()}`;
            }
        }

        // -------------------------------------------------------------
        // Checkout & Telemetry Handling
        // -------------------------------------------------------------
        function openCheckout(product) {
            playRetroSound('click');
            currentProduct = product;
            selectedQuantity = 1;
            const slotStr = String(product.slot_number).padStart(2, '0');
            const tierStr = product.tier_number || (product.slot_number <= 3 ? product.slot_number : 4);
            document.getElementById('modalSlot').innerText = `SLOT ${slotStr} • TIER ${tierStr}`;
            document.getElementById('modalItemName').innerText = product.name;
            
            // Populate Academic Guide details
            const defaultUsage = {
                1: 'Legal deeds, contracts, term papers & major project case studies.',
                2: 'Departmental reports, thesis documentation, syllabus handouts & research.',
                3: 'Daily class essays, quick quizzes, laboratory worksheets & exercises.',
                4: 'Clean essay exams, blue book writing, accounting ledgers & official signing.',
                5: 'Standardized exams, midterms, finals, OMR bubble sheets & drafting.',
                6: 'Official university prelims, midterms, finals & formal exam booklets.'
            };
            const defaultSpecs = {
                1: '8.5 x 13 in (Long) • Substance 20 (75 GSM) • Pack of 5 Sheets',
                2: '210 x 297 mm (ISO A4) • 80 GSM High Opacity • Pack of 5 Sheets',
                3: '8.5 x 11 in (Letter) • Substance 20 • Pack of 5 Sheets',
                4: '0.4mm Needle Point • Smudge-proof Japanese Bio-Polymer Ink',
                5: 'Grade 2B High-Density Graphite • Break-resistant Core',
                6: 'Official UMak Lined Leaflet • Pack of 2 Examination Booklets'
            };

            const usageText = product.best_used_for || defaultUsage[product.slot_number] || 'General academic coursework & classroom requirements.';
            const specsText = product.specs || defaultSpecs[product.slot_number] || `${product.category} • Slot #${product.slot_number}`;

            document.getElementById('modalUsage').innerText = usageText;
            document.getElementById('modalSpecs').innerText = specsText;

            updatePriceAndQtyView();
            document.getElementById('checkoutModal').classList.add('open');
            lucide.createIcons();
        }

        function changeQuantity(delta) {
            if (!currentProduct) return;
            const maxAllowed = Math.min(10, currentProduct.stock);
            const newQty = selectedQuantity + delta;
            if (newQty >= 1 && newQty <= maxAllowed) {
                playRetroSound('step');
                selectedQuantity = newQty;
                updatePriceAndQtyView();
            }
        }

        function updatePriceAndQtyView() {
            if (!currentProduct) return;
            const unitPrice = Number(currentProduct.price);
            const total = unitPrice * selectedQuantity;
            const maxAllowed = Math.min(10, currentProduct.stock);

            document.getElementById('modalQtyDisplay').innerText = selectedQuantity;
            document.getElementById('modalPrice').innerText = `<?= CURRENCY_SYMBOL ?>` + total.toFixed(2);
            document.getElementById('qtyUnitLabel').innerText = `<?= CURRENCY_SYMBOL ?>${unitPrice.toFixed(2)} each • ${currentProduct.stock} in stock`;

            // Update minus/plus button states
            document.getElementById('qtyMinusBtn').disabled = (selectedQuantity <= 1);
            document.getElementById('qtyPlusBtn').disabled = (selectedQuantity >= maxAllowed);

            // Re-generate dynamic QR Ph payload with updated total
            generateQR(currentProduct.slot_number, total);
        }

        function closeCheckout() {
            playRetroSound('click');
            document.getElementById('checkoutModal').classList.remove('open');
        }

        function generateQR(slot, price) {
            const payload = encodeURIComponent(`00020101021226600016PH.PAYMONGO.QR0111PENPOINT_${slot}5204599953036085405${Number(price).toFixed(2)}5802PH5912PENPOINT_VEND6006MAKATI6304`);
            document.getElementById('qrImage').src = `https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=${payload}`;
        }

        async function executeDispense(simulateFail = false) {
            if (!currentProduct) return;
            playRetroSound('click');
            closeCheckout();

            const overlay = document.getElementById('dispenseOverlay');
            const title = document.getElementById('dispenseTitle');
            const msg = document.getElementById('dispenseMsg');
            const actionBtn = document.getElementById('dispenseDoneAction');
            const claimBtn = document.getElementById('claimItemBtn');
            const activeSlotNum = document.getElementById('activeSlotNum');
            const chuteBeam = document.getElementById('chuteBeam');
            const beamLabel = document.getElementById('beamStatusLabel');
            const fallingImg = document.getElementById('chuteFallingImg');
            const receiptBox = document.getElementById('receiptPrintout');

            const slotNum = currentProduct.slot_number;
            const slotStr = String(slotNum).padStart(2, '0');
            const tierNum = currentProduct.tier_number || (slotNum <= 3 ? slotNum : 4);
            const isRoller = (currentProduct.mechanism_type === 'roller_feed' || slotNum <= 3);
            const qty = selectedQuantity;

            // Reset chute animation state
            fallingImg.src = currentProduct.image_url;
            fallingImg.className = 'chute-item-payload';
            chuteBeam.classList.remove('beam-tripped');
            beamLabel.classList.remove('beam-tripped');
            beamLabel.innerText = 'IR BEAM ARMED';
            receiptBox.style.display = 'none';

            activeSlotNum.innerText = slotStr;
            title.innerText = `Dispensing ${qty}x ${currentProduct.name}...`;
            
            msg.innerHTML = `Dispensing supply from Slot #${slotStr}...<br><span style="color: #f39c12;">Awaiting optical drop verification...</span>`;
            actionBtn.style.display = "none";
            overlay.classList.add('active');
            lucide.createIcons();

            try {
                const failParam = simulateFail ? '&simulate_failure=1' : '';
                const resp = await fetch(`../api/dispense.php?action=dispense&slot=${slotNum}&quantity=${qty}&payment_method=QR_PH${failParam}`);
                const data = await resp.json();

                if (data.success) {
                    // Trigger physical chute falling animation
                    setTimeout(() => {
                        fallingImg.classList.add('falling');

                        // Beam break moment (at 0.85s when product hits bottom chute)
                        setTimeout(() => {
                            chuteBeam.classList.add('beam-tripped');
                            beamLabel.classList.add('beam-tripped');
                            beamLabel.innerText = 'IR BEAM TRIPPED 🟢';
                            playRetroSound('drop');

                            title.innerText = "Dispense Verified!";
                            msg.innerHTML = `<strong>Break-Beam IR Sensor Triggered!</strong><br>${qty}x ${currentProduct.name} fell from Tier ${tierNum} into collection tray.`;

                            // Populate and print e-receipt
                            const now = new Date();
                            const dtString = now.toLocaleDateString() + ' ' + now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                            document.getElementById('receiptDateTime').innerText = dtString;
                            document.getElementById('receiptRef').innerText = data.reference_no || ('TXN-' + Math.floor(10000000 + Math.random() * 90000000));
                            document.getElementById('receiptItemName').innerText = currentProduct.name;
                            document.getElementById('receiptQty').innerText = qty;
                            document.getElementById('receiptTotalAmount').innerText = `<?= CURRENCY_SYMBOL ?>` + Number(data.total_amount).toFixed(2);
                            receiptBox.style.display = 'block';

                            claimBtn.innerHTML = `<i data-lucide="package-check" style="width: 18px; height: 18px;"></i> ${qty} Item(s) Confirmed! Claim from Tray`;
                            claimBtn.style.background = "var(--retro-red)";
                            actionBtn.style.display = "block";
                            lucide.createIcons();
                        }, 850);
                    }, 600);
                } else if (data.status === 'REFUND_PENDING') {
                    // Sensor timeout / Jam
                    setTimeout(() => {
                        playRetroSound('fail');
                        msg.innerHTML = `<span style="color: #e67e22;">Sensor timeout on Tier ${tierNum}! Attempting 1x reverse & retry cycle...</span>`;
                        setTimeout(() => {
                            playRetroSound('fail');
                            title.innerText = "Dispense Failed — Refund Queued";
                            msg.innerHTML = `<strong>Break-Beam IR Drop Timeout</strong><br>Drop not confirmed after reverse/retry.<br><span style="color: #ff7675; font-size: 0.8rem;">Status: REFUND_PENDING (<?= CURRENCY_SYMBOL ?>${Number(data.total_amount).toFixed(2)})</span><br><span style="color: #95a5a6; font-size: 0.74rem;">PayMongo Ref: ${data.reference_no} • Notified Admin Dashboard</span>`;
                            beamLabel.innerText = 'IR BEAM TIMEOUT ⚠️';
                            claimBtn.innerHTML = `<i data-lucide="rotate-ccw" style="width: 18px; height: 18px;"></i> Acknowledge & Return`;
                            claimBtn.style.background = "#e74c3c";
                            actionBtn.style.display = "block";
                            lucide.createIcons();
                        }, 1400);
                    }, 1000);
                } else {
                    playRetroSound('fail');
                    title.innerText = "Dispense Error";
                    msg.innerText = data.error || "Unable to complete dispense cycle.";
                    claimBtn.innerHTML = `Close`;
                    actionBtn.style.display = "block";
                    lucide.createIcons();
                }
            } catch (err) {
                playRetroSound('fail');
                title.innerText = "Connection Timeout";
                msg.innerText = "Could not communicate with the vending machine microcontroller.";
                actionBtn.style.display = "block";
            }
        }

        async function downloadReceiptImage() {
            playRetroSound('click');
            const receipt = document.getElementById('receiptPrintout');
            const downloadBtn = document.getElementById('downloadReceiptBtn');
            const btnText = document.getElementById('downloadBtnText');

            if (!receipt || typeof html2canvas === 'undefined') return;

            // Temporarily hide the download button so it doesn't appear on the captured image
            downloadBtn.style.display = 'none';
            btnText.innerText = 'Capturing...';

            try {
                const canvas = await html2canvas(receipt, {
                    scale: 2, // High resolution for crisp phone displays
                    backgroundColor: '#fffdf7',
                    useCORS: true
                });

                // Restore download button
                downloadBtn.style.display = 'flex';
                btnText.innerText = 'Saved!';
                setTimeout(() => { btnText.innerText = 'Save e-Receipt (PNG)'; }, 2500);

                // Trigger direct file download
                const ref = document.getElementById('receiptRef')?.innerText || 'RECEIPT';
                const link = document.createElement('a');
                link.download = `PenPoint_${ref}.png`;
                link.href = canvas.toDataURL('image/png');
                link.click();
            } catch (err) {
                console.error("Receipt capture error:", err);
                downloadBtn.style.display = 'flex';
                btnText.innerText = 'Save e-Receipt (PNG)';
            }
        }

        function finishOrder() {
            playRetroSound('click');
            document.getElementById('dispenseOverlay').classList.remove('active');
            window.location.reload();
        }
    </script>
    <style>
        @keyframes retroSpin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</body>
</html>