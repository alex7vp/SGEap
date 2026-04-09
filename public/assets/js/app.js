document.addEventListener('DOMContentLoaded', () => {
    const firstField = document.querySelector('input[name="username"]');
    const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
    const shell = document.querySelector('.shell');
    const alertCloseButtons = document.querySelectorAll('[data-alert-close]');

    if (firstField instanceof HTMLInputElement) {
        firstField.focus();
    }

    if (sidebarToggle instanceof HTMLButtonElement && shell instanceof HTMLElement) {
        sidebarToggle.addEventListener('click', () => {
            shell.classList.toggle('sidebar-open');
        });
    }

    alertCloseButtons.forEach((button) => {
        if (!(button instanceof HTMLButtonElement)) {
            return;
        }

        button.addEventListener('click', () => {
            const alert = button.closest('[data-alert]');

            if (alert instanceof HTMLElement) {
                alert.remove();
            }
        });
    });

    const catalogRows = document.querySelectorAll('[data-catalog-row]');

    catalogRows.forEach((row) => {
        if (!(row instanceof HTMLTableRowElement)) {
            return;
        }

        const editButton = row.querySelector('[data-catalog-edit]');
        const cancelButton = row.querySelector('[data-catalog-cancel]');
        const editForm = row.querySelector('[data-catalog-edit-form]');
        const editInput = row.querySelector('[data-catalog-input]');
        const saveButton = row.querySelector('[data-catalog-save]');
        const actionGroup = row.querySelector('[data-catalog-actions]');

        if (
            !(editButton instanceof HTMLButtonElement)
            || !(cancelButton instanceof HTMLButtonElement)
            || !(editForm instanceof HTMLFormElement)
            || !(editInput instanceof HTMLInputElement)
            || !(saveButton instanceof HTMLButtonElement)
            || !(actionGroup instanceof HTMLElement)
        ) {
            return;
        }

        let originalValue = editInput.value;

        const enableCatalogEdit = () => {
            row.classList.add('is-editing');
            actionGroup.hidden = true;
            editInput.readOnly = false;
            editInput.classList.add('is-editing');
            saveButton.hidden = false;
            cancelButton.hidden = false;
            editInput.focus();
            editInput.select();
        };

        editButton.addEventListener('click', enableCatalogEdit);

        cancelButton.addEventListener('click', () => {
            row.classList.remove('is-editing');
            actionGroup.hidden = false;
            saveButton.hidden = true;
            cancelButton.hidden = true;
            editInput.readOnly = true;
            editInput.value = originalValue;
            editInput.classList.remove('is-editing');
        });

        editForm.addEventListener('submit', () => {
            originalValue = editInput.value;
        });
    });

    const searchInput = document.querySelector('[data-person-search]');
    const tableBody = document.querySelector('[data-person-table-body]');
    const tableWrapper = document.querySelector('[data-person-table-wrapper]');
    const emptyWrapper = document.querySelector('[data-person-list-wrapper]');
    const statusLabel = document.querySelector('[data-person-search-status]');

    if (
        !(searchInput instanceof HTMLInputElement)
        || !(tableBody instanceof HTMLTableSectionElement)
        || !(tableWrapper instanceof HTMLElement)
        || !(emptyWrapper instanceof HTMLElement)
        || !(statusLabel instanceof HTMLElement)
    ) {
        return;
    }

    let debounceTimer = null;

    const updateStatus = () => {
        const rows = tableBody.querySelectorAll('tr').length;
        statusLabel.textContent = rows + ' registro(s)';
    };

    const runSearch = async () => {
        const baseUrl = searchInput.dataset.personSearchUrl || '';

        if (baseUrl === '') {
            return;
        }

        const url = new URL(baseUrl, window.location.origin);
        url.searchParams.set('q', searchInput.value.trim());

        statusLabel.textContent = 'Buscando...';

        try {
            const response = await fetch(url.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error('Search request failed');
            }

            const payload = await response.json();
            tableBody.innerHTML = payload.html || '';
            tableWrapper.hidden = !!payload.isEmpty;
            emptyWrapper.hidden = !payload.isEmpty;

            if (payload.isEmpty) {
                emptyWrapper.innerHTML = payload.emptyHtml || '<div class="empty-state">No se encontraron registros.</div>';
                statusLabel.textContent = '0 registro(s)';
                return;
            }

            updateStatus();
        } catch (error) {
            statusLabel.textContent = 'Error al filtrar';
        }
    };

    searchInput.addEventListener('input', () => {
        if (debounceTimer !== null) {
            window.clearTimeout(debounceTimer);
        }

        debounceTimer = window.setTimeout(runSearch, 250);
    });

    updateStatus();
});
