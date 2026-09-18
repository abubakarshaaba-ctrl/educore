@props(['target'])

<div class="edu-rich-toolbar" data-rich-toolbar data-target="{{ $target }}" role="toolbar" aria-label="Message formatting">
    <button type="button" data-rich-action="bold" title="Bold"><strong>B</strong></button>
    <button type="button" data-rich-action="italic" title="Italic"><em>I</em></button>
    <span class="edu-rich-sep" aria-hidden="true"></span>
    <button type="button" data-rich-action="h1" title="Heading 1">H1</button>
    <button type="button" data-rich-action="h2" title="Heading 2">H2</button>
    <button type="button" data-rich-action="h3" title="Heading 3">H3</button>
    <span class="edu-rich-sep" aria-hidden="true"></span>
    <button type="button" data-rich-action="bullet" title="Bulleted list">• List</button>
    <button type="button" data-rich-action="number" title="Numbered list">1. List</button>
    <button type="button" data-rich-action="link" title="Insert link">Link</button>
</div>
<div class="edu-rich-hint">Use H1/H2/H3 for section hierarchy. Formatting is preserved in EduCore web and mobile messages.</div>

@once
<style>
.edu-rich-toolbar{display:flex;flex-wrap:wrap;gap:5px;align-items:center;padding:7px 8px;border:1px solid var(--border,#CBD5E1);border-bottom:0;border-radius:8px 8px 0 0;background:#F8FAFC}
.edu-rich-toolbar+textarea,.edu-rich-toolbar~textarea.edu-rich-target{border-top-left-radius:0!important;border-top-right-radius:0!important}
.edu-rich-toolbar button{min-width:34px;height:30px;padding:0 8px;border:1px solid #CBD5E1;border-radius:6px;background:#fff;color:#0F172A;font:600 11.5px/1 inherit;cursor:pointer}
.edu-rich-toolbar button:hover,.edu-rich-toolbar button:focus-visible{border-color:#64748B;background:#F1F5F9;outline:none}
.edu-rich-sep{width:1px;height:20px;background:#CBD5E1;margin:0 2px}
.edu-rich-hint{font-size:10.5px;line-height:1.4;color:#64748B;margin-top:5px}
.edu-rich-text{line-height:1.6;overflow-wrap:anywhere}
.edu-rich-text h1,.edu-rich-text h2,.edu-rich-text h3{color:inherit;margin:0.65em 0 0.3em;line-height:1.25}
.edu-rich-text h1:first-child,.edu-rich-text h2:first-child,.edu-rich-text h3:first-child{margin-top:0}
.edu-rich-text h1{font-size:1.45em;font-weight:800}
.edu-rich-text h2{font-size:1.25em;font-weight:800}
.edu-rich-text h3{font-size:1.1em;font-weight:700}
.edu-rich-text p{margin:0.2em 0}
.edu-rich-text ul,.edu-rich-text ol{margin:0.35em 0;padding-left:1.4em}
.edu-rich-text li{margin:0.15em 0}
.edu-rich-text a{color:#2563EB;text-decoration:underline;text-underline-offset:2px}
.edu-rich-spacer{height:.55em}
@media(max-width:520px){.edu-rich-toolbar button{height:34px;min-width:38px}.edu-rich-sep{display:none}}
</style>
<script>
document.addEventListener('click', function (event) {
    const button = event.target.closest('[data-rich-action]');
    if (!button) return;
    const toolbar = button.closest('[data-rich-toolbar]');
    const textarea = toolbar ? document.getElementById(toolbar.dataset.target) : null;
    if (!textarea) return;

    event.preventDefault();
    textarea.focus();

    const start = textarea.selectionStart ?? textarea.value.length;
    const end = textarea.selectionEnd ?? start;
    const selected = textarea.value.slice(start, end);
    const action = button.dataset.richAction;

    const replace = (value, selectionStart, selectionEnd) => {
        textarea.setRangeText(value, start, end, 'end');
        textarea.selectionStart = start + selectionStart;
        textarea.selectionEnd = start + selectionEnd;
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
    };

    if (action === 'bold') {
        const inner = selected || 'bold text';
        replace('**' + inner + '**', 2, 2 + inner.length);
        return;
    }
    if (action === 'italic') {
        const inner = selected || 'italic text';
        replace('*' + inner + '*', 1, 1 + inner.length);
        return;
    }
    if (action === 'link') {
        const inner = selected || 'link text';
        const value = '[' + inner + '](https://)';
        replace(value, 1, 1 + inner.length);
        return;
    }

    const prefixByAction = { h1: '# ', h2: '## ', h3: '### ', bullet: '- ' };
    if (prefixByAction[action]) {
        const source = selected || (action.startsWith('h') ? 'Heading' : 'List item');
        const prefix = prefixByAction[action];
        const value = source.split('\n').map(line => prefix + line.replace(/^\s*(?:#{1,3}|[-*])\s+/, '')).join('\n');
        replace(value, prefix.length, value.length);
        return;
    }

    if (action === 'number') {
        const source = selected || 'List item';
        const value = source.split('\n').map((line, index) => (index + 1) + '. ' + line.replace(/^\s*\d+[.)]\s+/, '')).join('\n');
        replace(value, 3, value.length);
    }
});
</script>
@endonce
