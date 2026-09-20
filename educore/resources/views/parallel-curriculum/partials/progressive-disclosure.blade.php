<script>
(function () {
    const safeRead = (key) => {
        try { return window.localStorage.getItem(key); } catch (e) { return null; }
    };
    const safeWrite = (key, value) => {
        try { window.localStorage.setItem(key, value); } catch (e) {}
    };

    const normaliseFieldName = (value) => String(value || '')
        .replace(/\[([^\]]*)\]/g, '.$1')
        .replace(/^\.+|\.+$/g, '');

    const validationAlerts = Array.from(
        document.querySelectorAll('[data-parallel-validation-error]')
    );

    const validationTargets = validationAlerts
        .map((alert) => String(alert.dataset.parallelValidationTarget || '').trim())
        .filter(Boolean);

    const validationFields = validationAlerts.flatMap((alert) => {
        const raw = alert.dataset.parallelValidationFields || '[]';
        try {
            const parsed = JSON.parse(raw);
            return Array.isArray(parsed)
                ? parsed.map(normaliseFieldName).filter(Boolean)
                : [];
        } catch (e) {
            return [];
        }
    });

    const itemContainsValidationError = (item) => {
        if (validationTargets.length > 0) {
            return !!item.id && validationTargets.includes(item.id);
        }

        if (validationFields.length === 0) return false;

        const fieldNames = Array.from(item.querySelectorAll('[name]'))
            .map((element) => normaliseFieldName(element.getAttribute('name')))
            .filter(Boolean);

        return validationFields.some((errorField) =>
            fieldNames.some((fieldName) =>
                errorField === fieldName
                || errorField.startsWith(fieldName + '.')
                || fieldName.startsWith(errorField + '.')
            )
        );
    };

    document.querySelectorAll('[data-collapsible-root]').forEach((root) => {
        const storageKey = root.dataset.storageKey || 'parallel-curriculum';
        const items = Array.from(root.querySelectorAll('[data-collapsible-item]'));

        const applyState = (item, collapsed, persist = true) => {
            const body = item.querySelector(':scope > .pc-body, :scope > .body');
            const toggle = item.querySelector(':scope > .pc-head .pc-collapse-toggle, :scope > .head .pc-collapse-toggle');
            if (!body || !toggle) return;

            item.classList.toggle('is-collapsed', collapsed);
            body.hidden = collapsed;
            toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            toggle.textContent = collapsed ? 'Open' : 'Collapse';

            if (persist && item.dataset.collapseId) {
                safeWrite(storageKey + ':' + item.dataset.collapseId, collapsed ? '1' : '0');
            }
        };

        items.forEach((item, index) => {
            const head = item.querySelector(':scope > .pc-head, :scope > .head');
            const body = item.querySelector(':scope > .pc-body, :scope > .body');
            if (!head || !body || head.querySelector('.pc-collapse-toggle')) return;

            const collapseId = item.id || ('section-' + (index + 1));
            item.dataset.collapseId = collapseId;
            head.classList.add('pc-collapsible-head');

            if (!body.id) body.id = storageKey + '-' + collapseId + '-body';

            const toggle = document.createElement('button');
            toggle.type = 'button';
            toggle.className = 'pc-collapse-toggle';
            toggle.setAttribute('aria-controls', body.id);
            head.appendChild(toggle);

            const hashTarget = window.location.hash
                ? document.querySelector(window.location.hash)
                : null;
            const containsHashTarget = !!hashTarget && (item === hashTarget || item.contains(hashTarget));
            const containsValidationError = itemContainsValidationError(item);
            if (containsValidationError) item.dataset.validationError = '1';

            const stored = safeRead(storageKey + ':' + collapseId);
            const defaultCollapsed = root.dataset.collapseDefault !== 'open';
            const collapsed = containsHashTarget || containsValidationError
                ? false
                : (stored === null ? defaultCollapsed : stored === '1');

            applyState(item, collapsed, false);

            toggle.addEventListener('click', () => {
                applyState(item, !item.classList.contains('is-collapsed'));
            });
        });

        root.querySelectorAll('[data-collapse-all]').forEach((button) => {
            button.addEventListener('click', () => items.forEach((item) => applyState(item, true)));
        });
        root.querySelectorAll('[data-expand-all]').forEach((button) => {
            button.addEventListener('click', () => items.forEach((item) => applyState(item, false)));
        });

        root.querySelectorAll('.pc-jumpbar a[href^="#"]').forEach((link) => {
            link.addEventListener('click', (event) => {
                const selector = link.getAttribute('href');
                if (!selector || selector === '#') return;

                const target = root.querySelector(selector);
                if (!target) return;

                const item = target.matches('[data-collapsible-item]')
                    ? target
                    : target.closest('[data-collapsible-item]');

                if (item) {
                    applyState(item, false);
                }

                event.preventDefault();
                try {
                    window.history.replaceState(null, '', selector);
                } catch (e) {}

                window.requestAnimationFrame(() => {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                });
            });
        });
    });
})();
</script>