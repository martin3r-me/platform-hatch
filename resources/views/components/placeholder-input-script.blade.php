{{-- Alpine-Komponente zum Baustein-Feld. @assets: einmalig im <head>, übersteht wire:navigate.
     Registrierung wie beim TinyMCE-Editor in platforms-events: sofort, falls Alpine
     schon läuft, sonst beim alpine:init. --}}
@assets
<style>
    .hatch-ph-editor:empty::before { content: attr(data-placeholder); color: var(--nx-faint); pointer-events: none; }
    .hatch-ph-chip {
        display: inline-flex; align-items: center; gap: .25rem;
        margin: 0 .125rem; padding: 0 .4rem; border-radius: 9999px;
        font-size: .75rem; line-height: 1.25rem; font-weight: 500; white-space: nowrap;
        vertical-align: baseline; cursor: default; user-select: all;
        color: var(--nx-info); background: rgba(25, 113, 194, .12);
    }
</style>
<script>
(function () {
    const TOKEN = /\{\{(\w+)\}\}/g;
    const ZWSP = '\u200B';

    function factory(cfg) {
        return {
            value: '',
            catalog: cfg.catalog || [],
            multiline: !!cfg.multiline,
            menuOpen: false,
            range: null,

            init() {
                this.$nextTick(() => this.renderFromValue());
                // Änderungen von außen (Livewire) übernehmen – aber nicht, während getippt wird.
                this.$watch('value', () => {
                    if (document.activeElement !== this.$refs.editor) this.renderFromValue();
                });
            },

            byKey(key) {
                return this.catalog.find((p) => p.key === key);
            },

            hasTokens() {
                return (this.value || '').replace(TOKEN, (m, k) => (this.byKey(k) ? '\u0000' : m)).includes('\u0000');
            },

            preview() {
                return (this.value || '').replace(TOKEN, (m, k) => this.byKey(k)?.example ?? m);
            },

            chip(key) {
                const p = this.byKey(key);
                const el = document.createElement('span');
                el.contentEditable = 'false';
                el.dataset.key = key;
                el.className = 'hatch-ph-chip';
                el.textContent = p.label;
                el.title = p.description + ' · heute: ' + p.example;
                return el;
            },

            renderFromValue() {
                const editor = this.$refs.editor;
                if (!editor) return;
                editor.textContent = '';
                const text = this.value || '';
                let last = 0;
                for (const m of text.matchAll(TOKEN)) {
                    if (!this.byKey(m[1])) continue;
                    this.appendText(editor, text.slice(last, m.index));
                    editor.appendChild(this.chip(m[1]));
                    last = m.index + m[0].length;
                }
                this.appendText(editor, text.slice(last));
            },

            appendText(editor, text) {
                if (!text) return;
                text.split('\n').forEach((line, i) => {
                    if (i > 0) editor.appendChild(document.createElement('br'));
                    if (line) editor.appendChild(document.createTextNode(line));
                });
            },

            serialize(node) {
                let out = '';
                node.childNodes.forEach((n) => {
                    if (n.nodeType === Node.TEXT_NODE) {
                        out += n.textContent;
                    } else if (n.dataset && n.dataset.key) {
                        out += '@{{' + n.dataset.key + '}}';
                    } else if (n.nodeName === 'BR') {
                        out += '\n';
                    } else {
                        // Browser verpacken neue Zeilen teils in <div>/<p>.
                        if (['DIV', 'P'].includes(n.nodeName) && out !== '' && !out.endsWith('\n')) out += '\n';
                        out += this.serialize(n);
                    }
                });
                return out;
            },

            onInput() {
                let v = this.serialize(this.$refs.editor).split(ZWSP).join('').replace(/\u00a0/g, ' ');
                if (this.multiline) {
                    v = v.replace(/\n$/, '');
                } else {
                    v = v.replace(/\n/g, ' ');
                }
                this.value = v;
                this.saveRange();
            },

            onKeydown(e) {
                if (e.key === 'Enter' && !this.multiline) e.preventDefault();
            },

            onPaste(e) {
                e.preventDefault();
                let text = (e.clipboardData || window.clipboardData).getData('text/plain') || '';
                if (!this.multiline) text = text.replace(/\s*\n\s*/g, ' ');
                document.execCommand('insertText', false, text);
            },

            onBlur() {
                // Getippte/eingefügte Platzhalter-Codes beim Verlassen in Bausteine verwandeln.
                const editor = this.$refs.editor;
                const hasRawToken = Array.from(editor.childNodes).some((n) => {
                    if (n.nodeType !== Node.TEXT_NODE) return false;
                    return Array.from(n.textContent.matchAll(TOKEN)).some((m) => this.byKey(m[1]));
                });
                if (hasRawToken) this.renderFromValue();
            },

            saveRange() {
                const sel = window.getSelection();
                if (sel && sel.rangeCount && this.$refs.editor.contains(sel.anchorNode)) {
                    this.range = sel.getRangeAt(0).cloneRange();
                }
            },

            insert(key) {
                const editor = this.$refs.editor;
                editor.focus();

                let range = this.range;
                if (!range || !editor.contains(range.startContainer)) {
                    range = document.createRange();
                    range.selectNodeContents(editor);
                    range.collapse(false);
                }

                range.deleteContents();
                const chip = this.chip(key);
                // Unsichtbares Zeichen dahinter, damit der Cursor nach dem Baustein
                // stehen und weitergetippt werden kann (sonst hängt er in Chrome/Safari).
                const spacer = document.createTextNode(ZWSP);
                range.insertNode(spacer);
                range.insertNode(chip);

                const after = document.createRange();
                after.setStartAfter(spacer);
                after.collapse(true);
                const sel = window.getSelection();
                sel.removeAllRanges();
                sel.addRange(after);
                this.range = after.cloneRange();

                this.menuOpen = false;
                this.onInput();
            },
        };
    }

    window.hatchPlaceholderInput = factory;

    function register() {
        if (!window.Alpine || typeof window.Alpine.data !== 'function') return false;
        if (window.Alpine.__hatchPlaceholderInput) return true;
        window.Alpine.data('hatchPlaceholderInput', factory);
        window.Alpine.__hatchPlaceholderInput = true;
        return true;
    }

    if (!register()) {
        document.addEventListener('alpine:init', register);
    }
})();
</script>
@endassets
