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

    const securityRows = document.querySelectorAll('[data-security-row]');

    securityRows.forEach((row) => {
        if (!(row instanceof HTMLTableRowElement)) {
            return;
        }

        const editButton = row.querySelector('[data-security-edit]');
        const cancelButton = row.querySelector('[data-security-cancel]');
        const editForm = row.querySelector('[data-security-edit-form]');
        const readonlyActions = row.querySelector('[data-security-actions]');
        const editActions = row.querySelector('[data-security-edit-actions]');
        const editInputs = Array.from(row.querySelectorAll('[data-security-input]'));

        if (
            !(editButton instanceof HTMLButtonElement)
            || !(cancelButton instanceof HTMLButtonElement)
            || !(editForm instanceof HTMLFormElement)
            || !(readonlyActions instanceof HTMLElement)
            || !(editActions instanceof HTMLElement)
            || editInputs.length === 0
        ) {
            return;
        }

        const securityFields = editInputs.filter(
            (field) => field instanceof HTMLInputElement || field instanceof HTMLSelectElement
        );

        if (securityFields.length === 0) {
            return;
        }

        const originalValues = new Map();

        securityFields.forEach((field) => {
            originalValues.set(field.name, field.value);
        });

        const enableSecurityEdit = () => {
            row.classList.add('is-editing');
            readonlyActions.hidden = true;
            editActions.hidden = false;

            securityFields.forEach((field) => {
                if (field instanceof HTMLInputElement) {
                    field.readOnly = false;
                    field.classList.add('is-editing');
                }

                if (field instanceof HTMLSelectElement) {
                    field.disabled = false;
                    field.classList.add('is-editing');
                }
            });

            const firstField = securityFields[0];

            if (firstField instanceof HTMLInputElement || firstField instanceof HTMLSelectElement) {
                firstField.focus();
            }

            if (firstField instanceof HTMLInputElement) {
                firstField.select();
            }
        };

        editButton.addEventListener('click', enableSecurityEdit);

        cancelButton.addEventListener('click', () => {
            row.classList.remove('is-editing');
            readonlyActions.hidden = false;
            editActions.hidden = true;

            securityFields.forEach((field) => {
                const originalValue = originalValues.get(field.name) ?? '';

                field.value = originalValue;

                if (field instanceof HTMLInputElement) {
                    field.readOnly = true;
                    field.classList.remove('is-editing');
                }

                if (field instanceof HTMLSelectElement) {
                    field.disabled = true;
                    field.classList.remove('is-editing');
                }
            });
        });

        editForm.addEventListener('submit', () => {
            securityFields.forEach((field) => {
                originalValues.set(field.name, field.value);
            });
        });
    });

    const institutionForm = document.querySelector('[data-institution-form]');
    const institutionEditButton = document.querySelector('[data-institution-edit]');
    const institutionCancelButton = document.querySelector('[data-institution-cancel]');

    if (
        institutionForm instanceof HTMLFormElement
        && institutionEditButton instanceof HTMLButtonElement
        && institutionCancelButton instanceof HTMLButtonElement
    ) {
        institutionEditButton.addEventListener('click', () => {
            institutionForm.hidden = false;
            institutionEditButton.hidden = true;
            institutionCancelButton.hidden = false;
        });

        institutionCancelButton.addEventListener('click', () => {
            institutionForm.hidden = true;
            institutionEditButton.hidden = false;
            institutionCancelButton.hidden = true;
        });
    }

    const securityUserRoleSearchInput = document.querySelector('[data-security-user-role-search]');
    const securityUserRoleTableBody = document.querySelector('[data-security-user-role-table-body]');
    const securityUserRoleTableWrapper = document.querySelector('[data-security-user-role-table-wrapper]');
    const securityUserRoleEmptyWrapper = document.querySelector('[data-security-user-role-empty-wrapper]');
    const securityUserRoleStatus = document.querySelector('[data-security-user-role-status]');

    if (
        securityUserRoleSearchInput instanceof HTMLInputElement
        && securityUserRoleTableBody instanceof HTMLTableSectionElement
        && securityUserRoleTableWrapper instanceof HTMLElement
        && securityUserRoleEmptyWrapper instanceof HTMLElement
        && securityUserRoleStatus instanceof HTMLElement
    ) {
        let securityDebounceTimer = null;

        const updateSecurityStatus = () => {
            const rows = securityUserRoleTableBody.querySelectorAll('tr').length;
            securityUserRoleStatus.textContent = rows + ' registro(s)';
        };

        const runSecuritySearch = async () => {
            const baseUrl = securityUserRoleSearchInput.dataset.securityUserRoleSearchUrl || '';

            if (baseUrl === '') {
                return;
            }

            const url = new URL(baseUrl, window.location.origin);
            url.searchParams.set('q', securityUserRoleSearchInput.value.trim());

            securityUserRoleStatus.textContent = 'Buscando...';

            try {
                const response = await fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error('Security search request failed');
                }

                const payload = await response.json();
                securityUserRoleTableBody.innerHTML = payload.html || '';
                securityUserRoleTableWrapper.hidden = !!payload.isEmpty;
                securityUserRoleEmptyWrapper.hidden = !payload.isEmpty;

                if (payload.isEmpty) {
                    securityUserRoleEmptyWrapper.innerHTML = payload.emptyHtml || '<div class="empty-state">No se encontraron registros.</div>';
                    securityUserRoleStatus.textContent = '0 registro(s)';
                    return;
                }

                updateSecurityStatus();
            } catch (error) {
                securityUserRoleStatus.textContent = 'Error al filtrar';
            }
        };

        securityUserRoleSearchInput.addEventListener('input', () => {
            if (securityDebounceTimer !== null) {
                window.clearTimeout(securityDebounceTimer);
            }

            securityDebounceTimer = window.setTimeout(runSecuritySearch, 250);
        });

        updateSecurityStatus();
    }

    const securityUserSearchInput = document.querySelector('[data-security-user-search]');
    const securityUserTableBody = document.querySelector('[data-security-user-table-body]');
    const securityUserTableWrapper = document.querySelector('[data-security-user-table-wrapper]');
    const securityUserEmptyWrapper = document.querySelector('[data-security-user-list-wrapper]');
    const securityUserStatus = document.querySelector('[data-security-user-search-status]');

    if (
        securityUserSearchInput instanceof HTMLInputElement
        && securityUserTableBody instanceof HTMLTableSectionElement
        && securityUserTableWrapper instanceof HTMLElement
        && securityUserEmptyWrapper instanceof HTMLElement
        && securityUserStatus instanceof HTMLElement
    ) {
        let securityUserDebounceTimer = null;

        const updateSecurityUserStatus = () => {
            const rows = securityUserTableBody.querySelectorAll('tr').length;
            securityUserStatus.textContent = rows + ' registro(s)';
        };

        const runSecurityUserSearch = async () => {
            const baseUrl = securityUserSearchInput.dataset.securityUserSearchUrl || '';

            if (baseUrl === '') {
                return;
            }

            const url = new URL(baseUrl, window.location.origin);
            url.searchParams.set('q', securityUserSearchInput.value.trim());

            securityUserStatus.textContent = 'Buscando...';

            try {
                const response = await fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error('Security user search request failed');
                }

                const payload = await response.json();
                securityUserTableBody.innerHTML = payload.html || '';
                securityUserTableWrapper.hidden = !!payload.isEmpty;
                securityUserEmptyWrapper.hidden = !payload.isEmpty;

                if (payload.isEmpty) {
                    securityUserEmptyWrapper.innerHTML = payload.emptyHtml || '<div class="empty-state">No se encontraron registros.</div>';
                    securityUserStatus.textContent = '0 registro(s)';
                    return;
                }

                updateSecurityUserStatus();
            } catch (error) {
                securityUserStatus.textContent = 'Error al filtrar';
            }
        };

        securityUserSearchInput.addEventListener('input', () => {
            if (securityUserDebounceTimer !== null) {
                window.clearTimeout(securityUserDebounceTimer);
            }

            securityUserDebounceTimer = window.setTimeout(runSecurityUserSearch, 250);
        });

        updateSecurityUserStatus();
    }

    const personPickerSearch = document.querySelector('[data-person-picker-search]');
    const personPickerValue = document.querySelector('[data-person-picker-value]');
    const personPickerResults = document.querySelector('[data-person-picker-results]');
    const personPickerSelectedInput = document.querySelector('[data-person-picker-selected-input]');
    const personPickerStatus = document.querySelector('[data-person-picker-status]');
    const userPasswordInput = document.querySelector('[data-user-password-input]');
    const userUsernameInput = document.querySelector('[data-user-username-input]');

    if (
        personPickerSearch instanceof HTMLInputElement
        && personPickerValue instanceof HTMLInputElement
        && personPickerResults instanceof HTMLElement
        && personPickerSelectedInput instanceof HTMLInputElement
        && personPickerStatus instanceof HTMLElement
        && userPasswordInput instanceof HTMLInputElement
        && userUsernameInput instanceof HTMLInputElement
    ) {
        let personPickerTimer = null;
        const selectedPersonLabel = personPickerSelectedInput.value;

        const buildUsername = (nombres, apellidos) => {
            const parts = (String(nombres || '') + ' ' + String(apellidos || ''))
                .trim()
                .split(/\s+/)
                .filter(Boolean);

            if (parts.length === 0) {
                return '';
            }

            return parts
                .map((part) => part.slice(0, 2))
                .join('')
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '');
        };

        const selectPerson = (item) => {
            const id = item.id;
            const label = item.label || '';
            const cedula = String(item.cedula || '').trim();
            const username = buildUsername(item.nombres || '', item.apellidos || '');

            personPickerValue.value = String(id);
            personPickerSelectedInput.value = label;
            userPasswordInput.value = cedula;
            userUsernameInput.value = username;
            personPickerStatus.textContent = 'Persona seleccionada';
        };

        const renderPersonResults = (items) => {
            if (!Array.isArray(items) || items.length === 0) {
                personPickerResults.innerHTML = '<tr><td colspan="3" class="security-picker-empty">No hay coincidencias disponibles.</td></tr>';
                personPickerStatus.textContent = '0 registro(s)';
                return;
            }

            personPickerResults.innerHTML = '';

            items.forEach((item) => {
                const row = document.createElement('tr');
                const segments = String(item.label || '').split('|');
                const idCell = document.createElement('td');
                const nameCell = document.createElement('td');
                const actionCell = document.createElement('td');
                const button = document.createElement('button');

                idCell.textContent = (segments[0] || '').trim();
                nameCell.textContent = (segments.slice(1).join('|') || item.label || '').trim();

                button.type = 'button';
                button.className = 'btn-primary btn-auto btn-icon-only btn-icon-small';
                button.innerHTML = '<i class="fa fa-check" aria-hidden="true"></i>';
                button.title = 'Seleccionar persona';
                button.setAttribute('aria-label', 'Seleccionar persona');
                button.addEventListener('click', () => {
                    selectPerson(item);
                });

                actionCell.appendChild(button);
                row.appendChild(idCell);
                row.appendChild(nameCell);
                row.appendChild(actionCell);
                personPickerResults.appendChild(row);
            });

            personPickerStatus.textContent = items.length + ' registro(s)';
        };

        const searchPersons = async () => {
            const baseUrl = personPickerSearch.dataset.personPickerUrl || '';
            const term = personPickerSearch.value.trim();

            if (baseUrl === '' || term.length < 2) {
                personPickerResults.innerHTML = '<tr><td colspan="3" class="security-picker-empty">Escriba al menos 2 caracteres para buscar personas disponibles.</td></tr>';
                personPickerStatus.textContent = 'Escriba al menos 2 caracteres';
                return;
            }

            try {
                const url = new URL(baseUrl, window.location.origin);
                url.searchParams.set('q', term);

                personPickerStatus.textContent = 'Buscando...';

                const response = await fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error('Person picker search failed');
                }

                const payload = await response.json();
                renderPersonResults(payload.items || []);
            } catch (error) {
                personPickerResults.innerHTML = '<tr><td colspan="3" class="security-picker-empty">No se pudo consultar personas.</td></tr>';
                personPickerStatus.textContent = 'Error al filtrar';
            }
        };

        if (selectedPersonLabel !== '') {
            personPickerStatus.textContent = 'Persona seleccionada';
        }

        personPickerSearch.addEventListener('input', () => {
            if (personPickerTimer !== null) {
                window.clearTimeout(personPickerTimer);
            }

            personPickerTimer = window.setTimeout(searchPersons, 250);
        });
    }

    const gradeSearchInput = document.querySelector('[data-grade-search]');
    const gradeTableBody = document.querySelector('[data-grade-table-body]');
    const gradeTableWrapper = document.querySelector('[data-grade-table-wrapper]');
    const gradeEmptyWrapper = document.querySelector('[data-grade-list-wrapper]');
    const gradeStatusLabel = document.querySelector('[data-grade-search-status]');

    if (
        gradeSearchInput instanceof HTMLInputElement
        && gradeTableBody instanceof HTMLTableSectionElement
        && gradeTableWrapper instanceof HTMLElement
        && gradeEmptyWrapper instanceof HTMLElement
        && gradeStatusLabel instanceof HTMLElement
    ) {
        let gradeDebounceTimer = null;

        const updateGradeStatus = () => {
            const rows = gradeTableBody.querySelectorAll('tr').length;
            gradeStatusLabel.textContent = rows + ' registro(s)';
        };

        const runGradeSearch = async () => {
            const baseUrl = gradeSearchInput.dataset.gradeSearchUrl || '';

            if (baseUrl === '') {
                return;
            }

            const url = new URL(baseUrl, window.location.origin);
            url.searchParams.set('q', gradeSearchInput.value.trim());

            gradeStatusLabel.textContent = 'Buscando...';

            try {
                const response = await fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error('Grade search request failed');
                }

                const payload = await response.json();
                gradeTableBody.innerHTML = payload.html || '';
                gradeTableWrapper.hidden = !!payload.isEmpty;
                gradeEmptyWrapper.hidden = !payload.isEmpty;

                if (payload.isEmpty) {
                    gradeEmptyWrapper.innerHTML = payload.emptyHtml || '<div class="empty-state">No se encontraron registros.</div>';
                    gradeStatusLabel.textContent = '0 registro(s)';
                    return;
                }

                updateGradeStatus();
            } catch (error) {
                gradeStatusLabel.textContent = 'Error al filtrar';
            }
        };

        gradeSearchInput.addEventListener('input', () => {
            if (gradeDebounceTimer !== null) {
                window.clearTimeout(gradeDebounceTimer);
            }

            gradeDebounceTimer = window.setTimeout(runGradeSearch, 250);
        });

        updateGradeStatus();
    }

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
