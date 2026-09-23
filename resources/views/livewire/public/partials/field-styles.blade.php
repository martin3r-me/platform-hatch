{{-- Styles der Eingabefelder (Public-Overview + Vorschau im Template-Editor). Ohne <style>-Tag, wird eingebunden. --}}
    .intake-input {
        width: 100%; padding: 12px 16px;
        background: white; border: 1px solid #d1d5db;
        border-radius: 12px; color: #111827; font-size: 14px;
        outline: none; transition: all 0.2s ease;
    }
    .intake-input::placeholder { color: #9ca3af; }
    .intake-input:focus {
        border-color: #7c3aed;
        box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
    }
    .intake-input:disabled { cursor: not-allowed; background: #f9fafb; }

    .intake-input-sm {
        padding: 8px 12px; font-size: 13px; border-radius: 10px;
    }

    .intake-option-card {
        width: 100%; display: flex; align-items: center; gap: 14px;
        padding: 12px 16px; border-radius: 12px;
        border: 1px solid #e5e7eb; background: white;
        text-align: left; transition: all 0.2s ease;
    }
    .intake-option-card:not(:disabled):hover { background: #f9fafb; border-color: #d1d5db; }
    .intake-option-active {
        background: rgba(124, 58, 237, 0.05) !important;
        border-color: rgba(124, 58, 237, 0.4) !important;
        box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.08);
    }
    .intake-bool-card {
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        gap: 8px; padding: 24px 20px; border-radius: 16px;
        border: 1px solid #e5e7eb; background: white;
        transition: all 0.2s ease;
    }
    .intake-bool-card:not(:disabled):hover { background: #f9fafb; border-color: #d1d5db; }

    .intake-btn-submit {
        padding: 10px 24px; background: #10b981; color: white;
        font-size: 14px; font-weight: 600; border-radius: 14px;
        transition: all 0.2s ease;
    }
    .intake-btn-submit:hover { background: #059669; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3); }
    .intake-btn-submit:disabled { opacity: 0.5; }

    /* Compact Table (Mo-Fr Wochenfeedback) */
    .intake-compact-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    .intake-compact-table th {
        font-size: 12px; font-weight: 600;
        color: #6b7280; text-transform: uppercase; letter-spacing: 0.04em;
        text-align: left; padding: 10px 12px;
        border-bottom: 1px solid #e5e7eb;
    }
    .intake-compact-table td {
        padding: 12px;
        border-bottom: 1px solid #f3f4f6;
        vertical-align: middle;
    }
    .intake-compact-table tr:last-child td { border-bottom: none; }
    .intake-compact-table .row-label {
        font-weight: 600; color: #374151;
        white-space: nowrap;
    }

    .intake-thumbs {
        display: inline-flex; gap: 8px;
    }
    .intake-thumb {
        width: 40px; height: 40px;
        border-radius: 12px; border: 1px solid #e5e7eb;
        background: white; display: flex; align-items: center; justify-content: center;
        transition: all 0.15s ease;
    }
    .intake-thumb:not(:disabled):hover { background: #f9fafb; }
    .intake-thumb.up-active {
        background: #10b981; border-color: #10b981; color: white;
    }
    .intake-thumb.down-active {
        background: #ef4444; border-color: #ef4444; color: white;
    }
