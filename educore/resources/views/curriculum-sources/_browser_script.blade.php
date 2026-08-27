(() => {
    const root = document.querySelector('[data-repository-browser]');
    if (!root) return;

    const storageKey = @json($repositorySelectionKey ?? 'educore_academic_repository_selection');
    const classButtons = [...root.querySelectorAll('[data-class-target]')];
    const classPanels = [...root.querySelectorAll('[data-class-panel]')];
    const url = new URL(window.location.href);
    let saved = { class: '', terms: {} };

    try {
        const stored = JSON.parse(localStorage.getItem(storageKey) || '{}');
        if (stored && typeof stored === 'object') {
            saved.class = typeof stored.class === 'string' ? stored.class : '';
            saved.terms = stored.terms && typeof stored.terms === 'object' ? stored.terms : {};
        }
    } catch (_) {
        saved = { class: '', terms: {} };
    }

    const selectionFields = () => ({
        classField: root.querySelector('[data-selection-class-field]'),
        termField: root.querySelector('[data-selection-term-field]'),
    });

    const persist = (classKey, termKey) => {
        saved.class = classKey;
        if (termKey) saved.terms[classKey] = termKey;
        try { localStorage.setItem(storageKey, JSON.stringify(saved)); } catch (_) {}

        url.searchParams.set('selected_class', classKey);
        if (termKey) url.searchParams.set('selected_term', termKey);
        else url.searchParams.delete('selected_term');
        window.history.replaceState({}, '', `${url.pathname}${url.search}${url.hash}`);

        const fields = selectionFields();
        if (fields.classField) fields.classField.value = classKey;
        if (fields.termField) fields.termField.value = termKey || '';
    };

    const filterSubjects = classPanel => {
        const search = classPanel.querySelector('[data-subject-search]');
        const termPanel = [...classPanel.querySelectorAll('[data-term-panel]')].find(panel => !panel.hidden);
        if (!search || !termPanel) return;

        const query = search.value.trim().toLowerCase();
        let visible = 0;
        termPanel.querySelectorAll('[data-subject-name]').forEach(subject => {
            subject.hidden = query !== '' && !subject.dataset.subjectName.includes(query);
            if (!subject.hidden) visible++;
        });
        const empty = termPanel.querySelector('.repo-no-subjects');
        if (empty) empty.hidden = visible > 0;
    };

    const activateTerm = (classPanel, requestedKey, scroll = false) => {
        const tabs = [...classPanel.querySelectorAll('[data-term-target]')];
        const panels = [...classPanel.querySelectorAll('[data-term-panel]')];
        const tab = tabs.find(item => item.dataset.termKey === requestedKey) || tabs[0];
        if (!tab) return '';

        tabs.forEach(item => {
            item.classList.toggle('active', item === tab);
            item.setAttribute('aria-selected', item === tab ? 'true' : 'false');
            item.tabIndex = item === tab ? 0 : -1;
        });
        panels.forEach(panel => panel.hidden = panel.id !== tab.dataset.termTarget);
        filterSubjects(classPanel);

        if (scroll) {
            const selectedPanel = panels.find(panel => panel.id === tab.dataset.termTarget);
            selectedPanel?.scrollIntoView({behavior:'smooth',block:'nearest'});
        }

        return tab.dataset.termKey || '';
    };

    const activateClass = (button, scroll = false) => {
        if (!button) return;
        classButtons.forEach(item => {
            item.classList.toggle('active', item === button);
            item.setAttribute('aria-selected', item === button ? 'true' : 'false');
            item.tabIndex = item === button ? 0 : -1;
        });
        classPanels.forEach(panel => panel.hidden = panel.id !== button.dataset.classTarget);

        const classKey = button.dataset.classKey || '';
        const classPanel = classPanels.find(panel => panel.id === button.dataset.classTarget);
        if (!classPanel) return;
        const urlTerm = url.searchParams.get('selected_class') === classKey ? url.searchParams.get('selected_term') : '';
        const termKey = activateTerm(classPanel, urlTerm || saved.terms[classKey] || '', false);
        persist(classKey, termKey);

        if (scroll) {
            classPanel.scrollIntoView({behavior:'smooth',block:'start'});
        }
    };

    classButtons.forEach(button => button.addEventListener('click', () => activateClass(button, true)));
    classPanels.forEach(classPanel => {
        classPanel.querySelectorAll('[data-term-target]').forEach(tab => tab.addEventListener('click', () => {
            const classButton = classButtons.find(button => button.dataset.classTarget === classPanel.id);
            const termKey = activateTerm(classPanel, tab.dataset.termKey || '', true);
            persist(classButton?.dataset.classKey || '', termKey);
        }));
        classPanel.querySelector('[data-subject-search]')?.addEventListener('input', () => filterSubjects(classPanel));
    });

    const requestedClass = url.searchParams.get('selected_class') || saved.class;
    const initialButton = classButtons.find(button => button.dataset.classKey === requestedClass) || classButtons[0];
    activateClass(initialButton, false);
})();
