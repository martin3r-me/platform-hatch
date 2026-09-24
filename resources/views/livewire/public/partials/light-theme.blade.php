{{--
    Helles, mobil-optimiertes Theme für die öffentlichen Umfrage-Seiten.
    Wird am Ende der jeweiligen <style>-Blöcke eingebunden und überschreibt das frühere dunkle Design.
--}}
    .intake-bg {
        background:
            radial-gradient(1200px 500px at 50% -10%, #e0e7ff 0%, rgba(224, 231, 255, 0) 70%),
            linear-gradient(180deg, #f5f6fb 0%, #f8fafc 100%);
    }
    .intake-header-glass {
        background: rgba(255, 255, 255, .85);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border-bottom: 1px solid #e5e7eb;
    }
    .intake-card {
        border: 1px solid #eceef3;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04), 0 10px 30px -12px rgba(15, 23, 42, .12);
    }
    .intake-shell-main { scrollbar-color: rgba(15, 23, 42, .15) transparent; }
    .intake-shell-main::-webkit-scrollbar-thumb { background: rgba(15, 23, 42, .15); }
    .intake-shell-main::-webkit-scrollbar-thumb:hover { background: rgba(15, 23, 42, .25); }
    .intake-progress { background: linear-gradient(90deg, #4f46e5, #6366f1, #6366f1); box-shadow: none; }
    .intake-progress-done { background: linear-gradient(90deg, #10b981, #34d399); box-shadow: none; }

    /* Mobile first: weniger Rand, 16px in Eingabefeldern (sonst zoomt iOS beim Antippen) */
    .intake-input { font-size: 16px; }
    @media (max-width: 640px) {
        /* unten kein Innenabstand: sonst scheint Inhalt unter der klebenden Leiste durch */
        .intake-shell-main { padding: 1rem .75rem 0; }
        .intake-shell-footer { padding: .5rem 1rem; }
        .intake-card { border-radius: 18px; }
    }

    .intake-btn-primary, .intake-btn-submit { white-space: nowrap; }

    /* Aktions-Leiste klebt mobil am unteren Rand – „Weiter“/„Abschließen“ immer erreichbar */
    @media (max-width: 767px) {
        .intake-sticky-actions {
            position: sticky; bottom: 0; z-index: 20;
            background: rgba(255, 255, 255, .96);
            backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
            border-top: 1px solid #eceef3;
            padding-top: .75rem !important;
            padding-bottom: calc(.75rem + env(safe-area-inset-bottom)) !important;
        }
    }
