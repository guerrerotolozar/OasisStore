@php
    $__oasisCatalog = @include(base_path('themes/oasis/sync-catalog.php'));
    \Illuminate\Support\Facades\View::share('oasisCatalog', $__oasisCatalog);
@endphp
<style>
    /* ==========================================================================
       1. VARIABLES DE COLOR NATIVAS (SIN DOBLE HSL() PARA COMPATIBILIDAD TOTAL)
       ========================================================================== */
    :root {
        /* Canales HSL puros para utilidades compiladas hsl(var(--color-*)) */
        --color-primary: 160, 84%, 32%;
        --color-secondary: 199, 89%, 40%;
        --color-neutral: 214, 28%, 84%;
        --color-base: 222, 47%, 11%;
        --color-muted: 215, 19%, 36%;
        --color-inverted: 0, 0%, 100%;
        --color-background: 210, 35%, 96%;
        --color-background-secondary: 0, 0%, 100%;

        /* Tokens Semánticos Directos Oasis (Modo Claro de Alto Contraste) */
        --oasis-bg: #f1f5f9;
        --oasis-surface: #ffffff;
        --oasis-card-bg: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        --oasis-voucher-bg: linear-gradient(165deg, #ffffff 0%, #f0fdf4 100%);
        --oasis-subcard-bg: #f8fafc;
        --oasis-input-bg: #ffffff;
        --oasis-text: #0f172a;
        --oasis-muted: #475569;
        --oasis-border: #cbd5e1;
        --oasis-border-strong: rgba(5, 150, 105, 0.42);
        --oasis-primary: #059669;
        --oasis-primary-soft: rgba(5, 150, 105, 0.12);
        --oasis-secondary: #0284c7;
        --oasis-secondary-soft: rgba(2, 132, 199, 0.12);
        --oasis-shadow: 0 12px 30px -10px rgba(15, 23, 42, 0.08);
    }

    .dark {
        /* Canales HSL puros para Modo Oscuro (Idéntico al Admin Dashboard) */
        --color-primary: 160, 84%, 42%;
        --color-secondary: 189, 94%, 46%;
        --color-neutral: 217, 30%, 18%;
        --color-base: 210, 40%, 98%;
        --color-muted: 215, 20%, 68%;
        --color-inverted: 222, 47%, 7%;
        --color-background: 222, 47%, 6%;
        --color-background-secondary: 222, 42%, 10%;

        /* Tokens Semánticos Directos Oasis (Modo Oscuro Bóveda Digital) */
        --oasis-bg: #090d16;
        --oasis-surface: #0f172a;
        --oasis-card-bg: linear-gradient(180deg, rgba(17, 28, 45, 0.96) 0%, rgba(10, 17, 30, 0.98) 100%);
        --oasis-voucher-bg: linear-gradient(165deg, rgba(16, 33, 52, 0.96) 0%, rgba(9, 17, 30, 0.99) 100%);
        --oasis-subcard-bg: rgba(7, 13, 24, 0.78);
        --oasis-input-bg: #070d18;
        --oasis-text: #f8fafc;
        --oasis-muted: #94a3b8;
        --oasis-border: rgba(148, 163, 184, 0.18);
        --oasis-border-strong: rgba(16, 185, 129, 0.38);
        --oasis-primary: #10b981;
        --oasis-primary-soft: rgba(16, 185, 129, 0.15);
        --oasis-secondary: #06b6d4;
        --oasis-secondary-soft: rgba(6, 182, 212, 0.15);
        --oasis-shadow: 0 20px 42px -15px rgba(2, 6, 23, 0.7);
    }

    /* ==========================================================================
       2. REFUERZO DE CLASES UTILITARIAS PARA CERO CHOQUE EN CLARO / OSCURO
       ========================================================================== */
    body {
        background-color: var(--oasis-bg) !important;
        color: var(--oasis-text) !important;
        background-image:
            radial-gradient(ellipse 80% 45% at 50% -10%, var(--oasis-primary-soft), transparent 70%);
        background-attachment: fixed;
    }

    .bg-background { background-color: var(--oasis-bg) !important; }
    .bg-background-secondary { background-color: var(--oasis-surface) !important; }
    .bg-background\/70, .bg-background\/80, .bg-background\/85 { background-color: var(--oasis-subcard-bg) !important; }
    .text-base { color: var(--oasis-text) !important; }
    .text-muted { color: var(--oasis-muted) !important; }
    .text-primary { color: var(--oasis-primary) !important; }
    .text-secondary { color: var(--oasis-secondary) !important; }
    .border-neutral, .border-neutral\/50, .border-neutral\/60 { border-color: var(--oasis-border) !important; }

    .bg-primary { background-color: var(--oasis-primary) !important; color: #ffffff !important; }
    .bg-primary\/5, .bg-primary\/10, .bg-primary\/15, .bg-primary\/20 { background-color: var(--oasis-primary-soft) !important; }
    .border-primary, .border-primary\/30, .border-primary\/35, .border-primary\/40 { border-color: var(--oasis-border-strong) !important; }
    .bg-secondary\/15, .bg-secondary\/20 { background-color: var(--oasis-secondary-soft) !important; }
    .border-secondary\/30 { border-color: rgba(6, 182, 212, 0.35) !important; }

    /* Visibilidad perfecta del logo blanco cuando el usuario activa Modo Claro */
    body:not(.dark) img[src*="logo-light"] {
        filter: invert(1) brightness(0.15) contrast(1.2);
    }

    /* Inputs, Selects y Textareas siempre legibles en ambos temas */
    input:not([type="checkbox"]):not([type="radio"]):not([type="file"]),
    select,
    textarea {
        background-color: var(--oasis-input-bg) !important;
        color: var(--oasis-text) !important;
        border-color: var(--oasis-border) !important;
    }

    input::placeholder,
    textarea::placeholder {
        color: var(--oasis-muted) !important;
        opacity: 0.75;
    }

    /* ==========================================================================
       3. COMPONENTES A MEDIDA OASIS BÓVEDA DIGITAL & TERMINAL P2P
       ========================================================================== */
    .oasis-split-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 1.75rem;
        align-items: start;
    }

    @media (min-width: 1024px) {
        .oasis-split-layout {
            grid-template-columns: minmax(0, 1.55fr) minmax(340px, 1fr);
            gap: 2rem;
        }
        .oasis-sticky-panel {
            position: sticky;
            top: 5.5rem;
        }
    }

    /* Tarjeta Principal de Terminal (Se adapta a Claro y Oscuro) */
    .oasis-terminal {
        background: var(--oasis-card-bg);
        color: var(--oasis-text);
        border: 1px solid var(--oasis-border);
        border-radius: 1.25rem;
        box-shadow:
            inset 0 1px 0 0 var(--oasis-border-strong),
            var(--oasis-shadow);
    }

    /* Voucher / Boleto Digital Derecho (Se adapta a Claro y Oscuro) */
    .oasis-voucher {
        background: var(--oasis-voucher-bg);
        color: var(--oasis-text);
        border: 1px solid var(--oasis-border-strong);
        border-radius: 1.25rem;
        box-shadow: var(--oasis-shadow);
        position: relative;
        overflow: hidden;
    }

    .oasis-voucher::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--oasis-primary), var(--oasis-secondary));
    }

    .oasis-voucher-divider {
        border-top: 1px dashed var(--oasis-border);
        margin: 1rem 0;
    }

    /* Barra de Progreso Pipeline P2P de 3 Pasos */
    .oasis-pipeline {
        display: grid;
        grid-template-columns: repeat(1, minmax(0, 1fr));
        gap: 0.75rem;
        background: var(--oasis-surface);
        border: 1px solid var(--oasis-border);
        border-radius: 1rem;
        padding: 0.85rem 1.1rem;
        box-shadow: var(--oasis-shadow);
    }

    @media (min-width: 640px) {
        .oasis-pipeline {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    .oasis-step {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        padding: 0.5rem 0.7rem;
        border-radius: 0.75rem;
        font-size: 0.78rem;
        color: var(--oasis-muted);
        border: 1px solid transparent;
    }

    .oasis-step-active {
        background: var(--oasis-primary-soft);
        border-color: var(--oasis-border-strong);
        color: var(--oasis-text);
        font-weight: 700;
    }

    .oasis-step-done {
        background: var(--oasis-secondary-soft);
        border-color: rgba(6, 182, 212, 0.3);
        color: var(--oasis-text);
    }

    .oasis-step-num {
        width: 1.5rem;
        height: 1.5rem;
        border-radius: 9999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        font-weight: 800;
        flex-shrink: 0;
        background: var(--oasis-subcard-bg);
        color: var(--oasis-muted);
        border: 1px solid var(--oasis-border);
    }

    .oasis-step-active .oasis-step-num {
        background: var(--oasis-primary);
        color: #ffffff;
        border-color: var(--oasis-primary);
    }

    .oasis-step-done .oasis-step-num {
        background: var(--oasis-secondary);
        color: #ffffff;
        border-color: var(--oasis-secondary);
    }

    /* Miniatura Compacta de Comprobante en Sala P2P */
    .oasis-receipt-card {
        display: inline-flex;
        flex-direction: column;
        gap: 0.5rem;
        background: var(--oasis-subcard-bg);
        border: 1px solid var(--oasis-border-strong);
        border-radius: 0.9rem;
        padding: 0.65rem;
        max-width: 260px;
        transition: transform 0.15s ease, border-color 0.15s ease;
    }

    .oasis-receipt-card:hover {
        border-color: var(--oasis-primary);
        transform: translateY(-1px);
    }

    .oasis-receipt-thumb {
        width: 100%;
        max-width: 240px;
        max-height: 175px;
        object-fit: cover;
        border-radius: 0.6rem;
        cursor: zoom-in;
        border: 1px solid var(--oasis-border);
        background: #050a14;
    }

    /* Modal Nativo Light-Dismiss para Comprobantes */
    dialog.oasis-lightbox {
        margin: auto;
        padding: 0;
        border: 1px solid var(--oasis-border-strong);
        border-radius: 1.25rem;
        background: var(--oasis-surface);
        color: var(--oasis-text);
        max-width: min(92vw, 860px);
        max-height: 90vh;
        box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.75);
        overflow: hidden;
    }

    dialog.oasis-lightbox::backdrop {
        background-color: rgba(2, 6, 23, 0.8);
        backdrop-filter: blur(6px);
    }

    /* Barra de Chat Directo P2P */
    .oasis-composer {
        background: var(--oasis-subcard-bg);
        border: 1px solid var(--oasis-border);
        border-radius: 1.15rem;
        padding: 0.85rem;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .oasis-composer:focus-within {
        border-color: var(--oasis-primary);
        box-shadow: 0 0 0 3px var(--oasis-primary-soft);
    }

    .oasis-composer textarea {
        width: 100%;
        background: transparent !important;
        border: 0 !important;
        color: var(--oasis-text) !important;
        font-size: 0.9rem;
        line-height: 1.45;
        resize: none;
        outline: none;
    }

    .oasis-mono-pill {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 0.72rem;
        letter-spacing: 0.04em;
    }
</style>
