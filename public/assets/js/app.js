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

    const applyPhoneMask = (value) => {
        const digits = String(value || '').replace(/\D+/g, '').slice(0, 10);

        if (digits.length === 0) {
            return '';
        }

        if (digits.length <= 2) {
            return '(' + digits;
        }

        if (digits.length <= 6) {
            return '(' + digits.slice(0, 2) + ') ' + digits.slice(2);
        }

        return '(' + digits.slice(0, 2) + ') ' + digits.slice(2, 6) + ' ' + digits.slice(6);
    };

    const wirePhoneMask = (input) => {
        if (!(input instanceof HTMLInputElement)) {
            return;
        }

        input.value = applyPhoneMask(input.value);
        input.addEventListener('input', () => {
            input.value = applyPhoneMask(input.value);
        });
    };

    document.querySelectorAll('[data-phone-mask]').forEach(wirePhoneMask);

    const documentSourceSelector = document.querySelector('[data-document-source-selector]');
    const customFileInput = document.querySelector('[data-file-input]');
    const customFileInputName = document.querySelector('[data-file-input-name]');

    if (customFileInput instanceof HTMLInputElement && customFileInputName instanceof HTMLElement) {
        const syncCustomFileInputName = () => {
            const fileName = customFileInput.files && customFileInput.files.length > 0
                ? customFileInput.files[0].name
                : 'No se eligió ningún archivo';

            customFileInputName.textContent = fileName;
        };

        customFileInput.addEventListener('change', syncCustomFileInputName);
        syncCustomFileInputName();
    }

    if (documentSourceSelector instanceof HTMLElement) {
        const sourceOptions = Array.from(
            documentSourceSelector.querySelectorAll('[data-document-source-option]')
        ).filter((field) => field instanceof HTMLInputElement);
        const sourceCards = Array.from(
            documentSourceSelector.querySelectorAll('[data-document-source-card]')
        ).filter((card) => card instanceof HTMLElement);
        const sourcePanels = document.querySelectorAll('[data-document-source-panel]');
        const urlInput = document.querySelector('[data-document-url-input]');

        const syncDocumentSourcePanels = () => {
            const activeSource = sourceOptions.find((field) => field.checked)?.value || 'upload';

            sourceCards.forEach((card) => {
                const option = card.querySelector('[data-document-source-option]');

                if (!(option instanceof HTMLInputElement)) {
                    return;
                }

                card.classList.toggle('is-active', option.value === activeSource);
            });

            sourcePanels.forEach((panel) => {
                if (!(panel instanceof HTMLElement)) {
                    return;
                }

                panel.hidden = panel.dataset.documentSourcePanel !== activeSource;
            });

            if (urlInput instanceof HTMLInputElement) {
                if (activeSource === 'url') {
                    urlInput.disabled = false;
                } else {
                    urlInput.disabled = true;
                    urlInput.value = '';
                }
            }

            if (customFileInput instanceof HTMLInputElement) {
                customFileInput.disabled = activeSource !== 'upload';
            }
        };

        sourceOptions.forEach((field) => {
            field.addEventListener('change', syncDocumentSourcePanels);
        });

        syncDocumentSourcePanels();
    }

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
            institutionForm.reset();
            institutionForm.hidden = true;
            institutionEditButton.hidden = false;
            institutionCancelButton.hidden = true;
        });
    }

    const trackResettableForms = document.querySelectorAll('form');

    const readFieldState = (field) => {
        if (
            !(field instanceof HTMLInputElement)
            && !(field instanceof HTMLSelectElement)
            && !(field instanceof HTMLTextAreaElement)
        ) {
            return null;
        }

        if (field instanceof HTMLInputElement) {
            if (field.type === 'file') {
                return 'files:' + field.files.length;
            }

            if (field.type === 'checkbox' || field.type === 'radio') {
                return field.checked ? '1' : '0';
            }
        }

        if (field instanceof HTMLSelectElement && field.multiple) {
            return Array.from(field.selectedOptions).map((option) => option.value).join('|');
        }

        return field.value;
    };

    const snapshotFormState = (form) => {
        return Array.from(form.elements)
            .map((field) => readFieldState(field))
            .filter((value) => value !== null)
            .join('||');
    };

    trackResettableForms.forEach((form) => {
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const resetButton = form.querySelector('button[type="reset"]');

        if (!(resetButton instanceof HTMLButtonElement)) {
            return;
        }

        const initialState = snapshotFormState(form);

        const syncResetButtonVisibility = () => {
            resetButton.hidden = snapshotFormState(form) === initialState;
        };

        resetButton.hidden = true;

        form.addEventListener('input', syncResetButtonVisibility);
        form.addEventListener('change', syncResetButtonVisibility);
        form.addEventListener('reset', () => {
            window.setTimeout(() => {
                syncResetButtonVisibility();
            }, 0);
        });
    });

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

    const staffTypeSearchInput = document.querySelector('[data-staff-type-search]');
    const staffTypeTableBody = document.querySelector('[data-staff-type-table-body]');
    const staffTypeTableWrapper = document.querySelector('[data-staff-type-table-wrapper]');
    const staffTypeEmptyWrapper = document.querySelector('[data-staff-type-empty-wrapper]');
    const staffTypeStatus = document.querySelector('[data-staff-type-status]');

    if (
        staffTypeSearchInput instanceof HTMLInputElement
        && staffTypeTableBody instanceof HTMLTableSectionElement
        && staffTypeTableWrapper instanceof HTMLElement
        && staffTypeEmptyWrapper instanceof HTMLElement
        && staffTypeStatus instanceof HTMLElement
    ) {
        let staffTypeDebounceTimer = null;

        const updateStaffTypeStatus = () => {
            const rows = staffTypeTableBody.querySelectorAll('tr').length;
            staffTypeStatus.textContent = rows + ' registro(s)';
        };

        const runStaffTypeSearch = async () => {
            const baseUrl = staffTypeSearchInput.dataset.staffTypeSearchUrl || '';

            if (baseUrl === '') {
                return;
            }

            const url = new URL(baseUrl, window.location.origin);
            url.searchParams.set('q', staffTypeSearchInput.value.trim());

            staffTypeStatus.textContent = 'Buscando...';

            try {
                const response = await fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error('Staff type search request failed');
                }

                const payload = await response.json();
                staffTypeTableBody.innerHTML = payload.html || '';
                staffTypeTableWrapper.hidden = !!payload.isEmpty;
                staffTypeEmptyWrapper.hidden = !payload.isEmpty;

                if (payload.isEmpty) {
                    staffTypeEmptyWrapper.innerHTML = payload.emptyHtml || '<div class="empty-state">No se encontraron registros.</div>';
                    staffTypeStatus.textContent = '0 registro(s)';
                    return;
                }

                updateStaffTypeStatus();
            } catch (error) {
                staffTypeStatus.textContent = 'Error al filtrar';
            }
        };

        staffTypeSearchInput.addEventListener('input', () => {
            if (staffTypeDebounceTimer !== null) {
                window.clearTimeout(staffTypeDebounceTimer);
            }

            staffTypeDebounceTimer = window.setTimeout(runStaffTypeSearch, 250);
        });

        updateStaffTypeStatus();
    }

    const staffListingTypeFilter = document.querySelector('[data-staff-listing-type-filter]');
    const staffListingTableBody = document.querySelector('[data-staff-listing-table-body]');
    const staffListingTableWrapper = document.querySelector('[data-staff-listing-table-wrapper]');
    const staffListingEmptyWrapper = document.querySelector('[data-staff-listing-empty-wrapper]');
    const staffListingStatus = document.querySelector('[data-staff-listing-status]');
    const staffListingNote = document.querySelector('[data-staff-listing-note]');

    if (
        staffListingTypeFilter instanceof HTMLElement
        && staffListingTableBody instanceof HTMLTableSectionElement
        && staffListingTableWrapper instanceof HTMLElement
        && staffListingEmptyWrapper instanceof HTMLElement
        && staffListingStatus instanceof HTMLElement
        && staffListingNote instanceof HTMLElement
    ) {
        const updateStaffListingStatus = () => {
            const rows = staffListingTableBody.querySelectorAll('tr').length;
            staffListingStatus.textContent = rows + ' registro(s)';
        };

        const updateStaffListingNote = () => {
            const selectedOption = staffListingTypeFilter.querySelector('input[name="staff_listing_type"]:checked');
            const selectedText = selectedOption instanceof HTMLInputElement ? selectedOption.value.trim() : '';

            if (selectedText === '') {
                staffListingNote.textContent = 'Mostrando todo el personal institucional registrado.';
                return;
            }

            staffListingNote.textContent = 'Mostrando personal del tipo: ' + selectedText + '.';
        };

        const runStaffListingFilter = async () => {
            const baseUrl = staffListingTypeFilter.dataset.staffListingFilterUrl || '';
            const selectedOption = staffListingTypeFilter.querySelector('input[name="staff_listing_type"]:checked');
            const selectedType = selectedOption instanceof HTMLInputElement ? selectedOption.value.trim() : '';

            if (baseUrl === '') {
                return;
            }

            const url = new URL(baseUrl, window.location.origin);
            url.searchParams.set('tipo', selectedType);

            staffListingStatus.textContent = 'Buscando...';

            try {
                const response = await fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error('Staff listing filter request failed');
                }

                const payload = await response.json();
                staffListingTableBody.innerHTML = payload.html || '';
                staffListingTableWrapper.hidden = !!payload.isEmpty;
                staffListingEmptyWrapper.hidden = !payload.isEmpty;

                if (payload.isEmpty) {
                    staffListingEmptyWrapper.innerHTML = payload.emptyHtml || '<div class="empty-state">No se encontraron registros.</div>';
                    staffListingStatus.textContent = '0 registro(s)';
                    updateStaffListingNote();
                    return;
                }

                updateStaffListingStatus();
                updateStaffListingNote();
            } catch (error) {
                staffListingStatus.textContent = 'Error al filtrar';
            }
        };

        staffListingTypeFilter.addEventListener('change', (event) => {
            if (!(event.target instanceof HTMLInputElement) || event.target.name !== 'staff_listing_type') {
                return;
            }

            runStaffListingFilter();
        });
        updateStaffListingStatus();
        updateStaffListingNote();
    }

    const securityUserSearchInput = document.querySelector('[data-security-user-search]');
    const securityUserStatusFilter = document.querySelector('[data-security-user-status-filter]');
    const securityUserTableBody = document.querySelector('[data-security-user-table-body]');
    const securityUserTableWrapper = document.querySelector('[data-security-user-table-wrapper]');
    const securityUserEmptyWrapper = document.querySelector('[data-security-user-list-wrapper]');
    const securityUserStatus = document.querySelector('[data-security-user-search-status]');

    if (
        securityUserSearchInput instanceof HTMLInputElement
        && securityUserStatusFilter instanceof HTMLSelectElement
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

            const term = securityUserSearchInput.value.trim();
            const status = securityUserStatusFilter.value;

            if (term.length < 2 && status === '') {
                securityUserTableBody.innerHTML = '';
                securityUserTableWrapper.hidden = true;
                securityUserEmptyWrapper.hidden = false;
                securityUserEmptyWrapper.innerHTML = '<div class="empty-state">Escriba al menos 2 caracteres o seleccione un estado para consultar usuarios.</div>';
                securityUserStatus.textContent = 'Escriba al menos 2 caracteres';
                return;
            }

            const url = new URL(baseUrl, window.location.origin);
            url.searchParams.set('q', term);
            url.searchParams.set('estado', status);

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

                const count = typeof payload.count === 'number' ? payload.count : securityUserTableBody.querySelectorAll('tr').length;
                securityUserStatus.textContent = count + ' registro(s)' + (payload.limited ? ' | refine el filtro' : '');
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

        securityUserStatusFilter.addEventListener('change', runSecurityUserSearch);
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

    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        if (!(button instanceof HTMLButtonElement)) {
            return;
        }

        const targetSelector = button.dataset.passwordTarget || '';
        const previousInput = button.previousElementSibling;
        const input = targetSelector === 'previous'
            ? previousInput
            : (targetSelector !== '' ? document.querySelector(targetSelector) : null);
        const icon = button.querySelector('.fa');

        if (!(input instanceof HTMLInputElement)) {
            return;
        }

        button.addEventListener('click', () => {
            const shouldShow = input.type === 'password';
            input.type = shouldShow ? 'text' : 'password';
            button.title = shouldShow ? 'Ocultar clave' : 'Mostrar clave';
            button.setAttribute('aria-label', shouldShow ? 'Ocultar clave' : 'Mostrar clave');

            if (icon instanceof HTMLElement) {
                icon.className = 'fa ' + (shouldShow ? 'fa-eye-slash' : 'fa-eye');
            }
        });
    });

    const wizardTabs = document.querySelectorAll('[data-wizard-tab]');
    const wizardPanels = document.querySelectorAll('[data-wizard-panel]');
    const matriculaForm = document.querySelector('[data-matricula-form]');
    const matriculaDraftButtons = document.querySelectorAll('[data-matricula-draft-save]');
    const matriculaDraftClearButtons = document.querySelectorAll('[data-matricula-draft-clear]');
    const matriculaDraftAlert = document.querySelector('[data-matricula-draft-alert]');
    const matriculaDraftAlertMessage = document.querySelector('[data-matricula-draft-alert-message]');
    const matriculaSubmitButton = document.querySelector('[data-matricula-submit]');

    if (matriculaForm instanceof HTMLFormElement) {
        const draftKey = 'sgeap_matricula_draft';
        const requiredDocumentCheckboxes = Array.from(
            matriculaForm.querySelectorAll('[data-document-required]')
        ).filter((field) => field instanceof HTMLInputElement);

        const syncMatriculaSubmitState = () => {
            if (!(matriculaSubmitButton instanceof HTMLButtonElement)) {
                return;
            }

            if (requiredDocumentCheckboxes.length === 0) {
                matriculaSubmitButton.disabled = false;
                return;
            }

            matriculaSubmitButton.disabled = !requiredDocumentCheckboxes.every((field) => field.checked);
        };

        const showDraftAlert = (message) => {
            if (!(matriculaDraftAlert instanceof HTMLElement)) {
                return;
            }

            if (matriculaDraftAlertMessage instanceof HTMLElement && typeof message === 'string' && message !== '') {
                matriculaDraftAlertMessage.textContent = message;
            }

            matriculaDraftAlert.hidden = false;
        };

        const serializeForm = () => {
            const formData = new FormData(matriculaForm);
            const payload = {};

            formData.forEach((value, key) => {
                if (value instanceof File) {
                    return;
                }

                payload[key] = String(value);
            });

            matriculaForm.querySelectorAll('input[type="checkbox"]').forEach((field) => {
                if (!(field instanceof HTMLInputElement) || field.name === '') {
                    return;
                }

                payload[field.name] = field.checked;
            });

            return payload;
        };

        const restoreDraft = () => {
            const raw = window.localStorage.getItem(draftKey);

            if (raw === null) {
                return;
            }

            try {
                const payload = JSON.parse(raw);

                Object.entries(payload).forEach(([name, value]) => {
                    const field = matriculaForm.querySelector(`[name="${CSS.escape(name)}"]`);

                    if (field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement) {
                        if (field instanceof HTMLInputElement && field.type === 'checkbox') {
                            field.checked = Boolean(value);
                        } else {
                            field.value = String(value);
                        }

                        if (field instanceof HTMLInputElement && field.hasAttribute('data-phone-mask')) {
                            field.dispatchEvent(new Event('input', { bubbles: true }));
                        }

                        field.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
            } catch (error) {
                window.localStorage.removeItem(draftKey);
            }
        };

        restoreDraft();
        syncMatriculaSubmitState();

        requiredDocumentCheckboxes.forEach((field) => {
            field.addEventListener('change', syncMatriculaSubmitState);
        });

        const billingIdType = matriculaForm.querySelector('[data-billing-id-type]');
        const billingIdNumber = matriculaForm.querySelector('[data-billing-id-number]');

        const syncBillingIdentificationField = () => {
            if (!(billingIdType instanceof HTMLSelectElement) || !(billingIdNumber instanceof HTMLInputElement)) {
                return;
            }

            const selectedType = String(billingIdType.value || 'CEDULA').toUpperCase();
            const isRuc = selectedType === 'RUC';

            billingIdNumber.maxLength = isRuc ? 13 : 10;
            billingIdNumber.placeholder = isRuc ? 'Ej: 1790012345001' : 'Ej: 1711894939';
        };

        if (billingIdType instanceof HTMLSelectElement) {
            billingIdType.addEventListener('change', syncBillingIdentificationField);
            syncBillingIdentificationField();
        }

        matriculaDraftButtons.forEach((button) => {
            if (!(button instanceof HTMLButtonElement)) {
                return;
            }

            button.addEventListener('click', () => {
                window.localStorage.setItem(draftKey, JSON.stringify(serializeForm()));
                showDraftAlert('Borrador guardado localmente. Puedes continuar con la matricula y finalizarla despues.');
            });
        });

        matriculaDraftClearButtons.forEach((button) => {
            if (!(button instanceof HTMLButtonElement)) {
                return;
            }

            button.addEventListener('click', () => {
                window.localStorage.removeItem(draftKey);
                matriculaForm.reset();
                matriculaForm.dispatchEvent(new CustomEvent('matricula:draft-cleared'));
                matriculaForm.querySelectorAll('input, select, textarea').forEach((field) => {
                    if (
                        field instanceof HTMLInputElement
                        || field instanceof HTMLSelectElement
                        || field instanceof HTMLTextAreaElement
                    ) {
                        field.dispatchEvent(new Event('input', { bubbles: true }));
                        field.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
                syncMatriculaSubmitState();
                showDraftAlert('Los datos temporales se borraron correctamente.');
            });
        });

        matriculaForm.addEventListener('submit', () => {
            syncMatriculaSubmitState();
            window.localStorage.removeItem(draftKey);
        });
    }

    if (wizardTabs.length > 0 && wizardPanels.length > 0) {
        const activateWizardTab = (target) => {
            wizardTabs.forEach((tab) => {
                if (!(tab instanceof HTMLButtonElement)) {
                    return;
                }

                tab.classList.toggle('is-active', tab.dataset.wizardTab === target);
            });

            wizardPanels.forEach((panel) => {
                if (!(panel instanceof HTMLElement)) {
                    return;
                }

                const isActive = panel.dataset.wizardPanel === target;
                panel.hidden = !isActive;
                panel.classList.toggle('is-active', isActive);
            });
        };

        wizardTabs.forEach((tab) => {
            if (!(tab instanceof HTMLButtonElement)) {
                return;
            }

            tab.addEventListener('click', () => {
                activateWizardTab(tab.dataset.wizardTab || 'persona');
            });
        });
    }

    const familyContainer = document.querySelector('[data-family-rows]');
    const familyToggleButtons = document.querySelectorAll('[data-family-toggle]');
    const representativeOptions = document.querySelector('[data-representative-options]');
    const representativeIndexInput = document.querySelector('[data-representative-index-input]');
    const representativeSourceInput = document.querySelector('[data-representative-source-input]');
    const representativeExternalForm = document.querySelector('[data-representative-external-form]');
    const representativeExternalCedula = document.querySelector('[data-representative-external-cedula]');
    const representativeExternalSearch = document.querySelector('[data-representative-external-search]');
    const representativeExternalAlert = document.querySelector('[data-representative-external-alert]');
    const representativeExternalPersonId = document.querySelector('[data-representative-external-person-id]');
    const familyTemplate = document.querySelector('[data-family-template]');
    const familyAddButton = document.querySelector('[data-family-add]');
    const healthConditionContainer = document.querySelector('[data-health-condition-rows]');
    const healthConditionTemplate = document.querySelector('[data-health-condition-template]');
    const healthConditionAddButton = document.querySelector('[data-health-condition-add]');
    const disabilityToggleInput = document.querySelector('[data-disability-toggle]');
    const disabilityDetailInput = document.querySelector('[data-disability-detail]');
    const repeatedYearsToggleInput = document.querySelector('[data-repeated-years-toggle]');
    const repeatedYearsDetailInput = document.querySelector('[data-repeated-years-detail]');
    const imcWeightInput = document.querySelector('[data-imc-weight]');
    const imcHeightInput = document.querySelector('[data-imc-height]');
    const imcOutputInput = document.querySelector('[data-imc-output]');
    const imcCategoryInput = document.querySelector('[data-imc-category]');
    const measurementDateInput = document.querySelector('[data-measurement-date]');
    const whoBmiReferenceElement = document.querySelector('[data-who-bmi-reference]');
    const imcAlert = document.querySelector('[data-imc-alert]');

    if (
        familyContainer instanceof HTMLElement
        && representativeOptions instanceof HTMLElement
        && representativeIndexInput instanceof HTMLInputElement
    ) {
        const studentCedulaInput = document.querySelector('input[name="person[percedula]"]');

        const getNormalizedCedula = (value) => String(value || '').replace(/\D+/g, '').trim();

        const getFamilySlotRow = (slot) => familyContainer.querySelector(`[data-family-slot="${slot}"]`);

        const getFamilyToggleButton = (slot) => document.querySelector(`[data-family-toggle="${slot}"]`);

        const updateFamilyCardTitles = () => {
            let additionalCounter = 0;

            Array.from(familyContainer.querySelectorAll('[data-family-row]')).forEach((row) => {
                if (!(row instanceof HTMLElement) || !row.hasAttribute('data-family-removable')) {
                    return;
                }

                additionalCounter += 1;
                const title = row.querySelector('[data-family-card-title]');

                if (title instanceof HTMLElement) {
                    title.textContent = 'Familiar adicional ' + additionalCounter;
                }
            });
        };

        const getNextFamilyIndex = () => {
            const rows = Array.from(familyContainer.querySelectorAll('[data-family-row]'));

            return rows.reduce((max, row) => {
                if (!(row instanceof HTMLElement)) {
                    return max;
                }

                const rowIndex = Number.parseInt(row.dataset.familyIndex || '-1', 10);
                return Number.isNaN(rowIndex) ? max : Math.max(max, rowIndex);
            }, 1) + 1;
        };

        const createDynamicFamilyRow = (index) => {
            if (!(familyTemplate instanceof HTMLTemplateElement)) {
                return null;
            }

            const wrapper = document.createElement('div');
            wrapper.innerHTML = familyTemplate.innerHTML.replace(/__INDEX__/g, String(index)).trim();
            const row = wrapper.firstElementChild;

            return row instanceof HTMLElement ? row : null;
        };

        const getNextHealthConditionIndex = () => {
            const rows = Array.from(
                (healthConditionContainer instanceof HTMLElement
                    ? healthConditionContainer.querySelectorAll('[data-health-condition-row]')
                    : [])
            );

            return rows.reduce((max, row) => {
                if (!(row instanceof HTMLElement)) {
                    return max;
                }

                const rowIndex = Number.parseInt(row.dataset.healthConditionIndex || '-1', 10);
                return Number.isNaN(rowIndex) ? max : Math.max(max, rowIndex);
            }, -1) + 1;
        };

        const createHealthConditionRow = (index) => {
            if (!(healthConditionTemplate instanceof HTMLTemplateElement)) {
                return null;
            }

            const wrapper = document.createElement('div');
            wrapper.innerHTML = healthConditionTemplate.innerHTML.replace(/__INDEX__/g, String(index)).trim();
            const row = wrapper.firstElementChild;

            return row instanceof HTMLElement ? row : null;
        };

        const draftHasHealthConditionData = (payload, index) => {
            const prefix = 'health_conditions[' + index + ']';
            const meaningfulFields = [
                prefix + '[tcsid]',
                prefix + '[ecsadescripcion]',
                prefix + '[ecsamedicamentos]',
                prefix + '[ecsaobservacion]',
            ];

            return meaningfulFields.some((name) => {
                const value = payload[name];
                return value !== undefined && String(value).trim() !== '' && String(value).trim() !== '0';
            });
        };

        const wireHealthConditionRow = (row) => {
            if (!(row instanceof HTMLElement) || row.dataset.healthConditionBound === 'true') {
                return;
            }

            row.dataset.healthConditionBound = 'true';

            const removeButton = row.querySelector('[data-health-condition-remove]');

            if (removeButton instanceof HTMLButtonElement) {
                removeButton.addEventListener('click', () => {
                    row.remove();
                });
            }
        };

        const syncDisabilityDetail = () => {
            if (!(disabilityToggleInput instanceof HTMLInputElement) || !(disabilityDetailInput instanceof HTMLTextAreaElement)) {
                return;
            }

            const enabled = disabilityToggleInput.checked;
            disabilityDetailInput.disabled = !enabled;

            if (!enabled) {
                disabilityDetailInput.value = '';
            }
        };

        const syncRepeatedYearsDetail = () => {
            if (!(repeatedYearsToggleInput instanceof HTMLInputElement) || !(repeatedYearsDetailInput instanceof HTMLTextAreaElement)) {
                return;
            }

            const enabled = repeatedYearsToggleInput.checked;
            repeatedYearsDetailInput.disabled = !enabled;

            if (!enabled) {
                repeatedYearsDetailInput.value = '';
            }
        };

        const setFamilyAlert = (row, message, type = 'error') => {
            if (!(row instanceof HTMLElement)) {
                return;
            }

            const alertHost = row.querySelector('[data-family-lookup-alert]');

            if (!(alertHost instanceof HTMLElement)) {
                return;
            }

            if (message === '') {
                alertHost.innerHTML = '';
                alertHost.hidden = true;
                return;
            }

            alertHost.innerHTML =
                '<div class="alert ' + (type === 'success' ? 'alert-success' : 'alert-error') + ' form-field-alert"><span>'
                + message
                + '</span></div>';
            alertHost.hidden = false;
        };

        const setFamilyFieldsDisabled = (row, disabled) => {
            if (!(row instanceof HTMLElement)) {
                return;
            }

            row.querySelectorAll('[data-family-person-field]').forEach((field) => {
                if (field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement) {
                    field.disabled = disabled;
                }
            });
        };

        const setFamilyDetailFieldsDisabled = (row, disabled) => {
            if (!(row instanceof HTMLElement)) {
                return;
            }

            row.querySelectorAll('[data-family-dependent]:not([data-family-person-field])').forEach((field) => {
                if (field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement) {
                    field.disabled = disabled;
                }
            });
        };

        const clearFamilyFields = (row) => {
            if (!(row instanceof HTMLElement)) {
                return;
            }

            row.querySelectorAll('[data-family-dependent]').forEach((field) => {
                if (field instanceof HTMLInputElement) {
                    if (field.type === 'checkbox') {
                        field.checked = false;
                    } else {
                        field.value = '';
                    }
                }

                if (field instanceof HTMLSelectElement) {
                    field.selectedIndex = 0;
                }
            });

            const personIdInput = row.querySelector('[data-family-person-id]');

            if (personIdInput instanceof HTMLInputElement) {
                personIdInput.value = '0';
            }
        };

        const hasDuplicateFamilyCedula = (currentRow, cedula) => {
            const normalizedCedula = getNormalizedCedula(cedula);

            if (normalizedCedula === '') {
                return false;
            }

            if (studentCedulaInput instanceof HTMLInputElement && getNormalizedCedula(studentCedulaInput.value) === normalizedCedula) {
                return true;
            }

            const rows = Array.from(familyContainer.querySelectorAll('[data-family-row]'));

            return rows.some((row) => {
                if (!(row instanceof HTMLElement) || row === currentRow) {
                    return false;
                }

                const cedulaInput = row.querySelector('[data-family-cedula]');

                return cedulaInput instanceof HTMLInputElement && getNormalizedCedula(cedulaInput.value) === normalizedCedula;
            });
        };

        const buildRepresentativeLabel = (row) => {
            const nombres = row.querySelector('[data-family-field="nombres"]');
            const apellidos = row.querySelector('[data-family-field="apellidos"]');
            const parentesco = row.querySelector('[data-family-field="parentesco"]');
            const fullName = ((apellidos instanceof HTMLInputElement ? apellidos.value : '') + ' ' + (nombres instanceof HTMLInputElement ? nombres.value : '')).trim();
            const relationshipLabel =
                parentesco instanceof HTMLSelectElement && parentesco.selectedOptions.length > 0
                    ? parentesco.selectedOptions[0].textContent?.trim() || ''
                    : row instanceof HTMLElement
                        ? row.dataset.familyRelationshipLabel || ''
                        : '';

            if (fullName === '') {
                return relationshipLabel !== '' ? relationshipLabel : 'Familiar sin nombre';
            }

            return relationshipLabel !== '' && relationshipLabel !== 'Seleccione'
                ? fullName + ' (' + relationshipLabel + ')'
                : fullName;
        };

        const setFamilyRowVisible = (slot, visible) => {
            const row = getFamilySlotRow(slot);
            const button = getFamilyToggleButton(slot);

            if (row instanceof HTMLElement) {
                row.hidden = !visible;
            }

            if (button instanceof HTMLButtonElement) {
                button.hidden = visible;
            }
        };

        const enableFamilyRowForManualEntry = (row) => {
            setFamilyFieldsDisabled(row, false);
            setFamilyDetailFieldsDisabled(row, false);
            setFamilyAlert(row, '');
        };

        const syncFixedFamilyVisibility = () => {
            ['mother', 'father'].forEach((slot) => {
                const row = getFamilySlotRow(slot);

                if (!(row instanceof HTMLElement)) {
                    return;
                }

                const shouldBeVisible = rowHasFamilyData(row);
                setFamilyRowVisible(slot, shouldBeVisible);

                if (shouldBeVisible) {
                    if (row.querySelector('[data-family-person-id]') instanceof HTMLInputElement
                        && Number.parseInt(row.querySelector('[data-family-person-id]').value || '0', 10) > 0) {
                        setFamilyFieldsDisabled(row, true);
                        setFamilyDetailFieldsDisabled(row, false);
                    } else {
                        enableFamilyRowForManualEntry(row);
                    }
                } else {
                    setFamilyFieldsDisabled(row, true);
                    setFamilyDetailFieldsDisabled(row, true);
                    setFamilyAlert(row, '');
                }
            });
        };

        const rowHasFamilyData = (row) => {
            if (!(row instanceof HTMLElement)) {
                return false;
            }

            const cedulaInput = row.querySelector('[data-family-cedula]');
            const nombresInput = row.querySelector('[data-family-field="nombres"]');
            const apellidosInput = row.querySelector('[data-family-field="apellidos"]');

            const cedula = cedulaInput instanceof HTMLInputElement ? cedulaInput.value.trim() : '';
            const nombres = nombresInput instanceof HTMLInputElement ? nombresInput.value.trim() : '';
            const apellidos = apellidosInput instanceof HTMLInputElement ? apellidosInput.value.trim() : '';

            return cedula !== '' || nombres !== '' || apellidos !== '';
        };

        const getAgeFromBirthDate = (birthDateValue) => {
            const match = String(birthDateValue || '').match(/^(\d{4})-(\d{2})-(\d{2})$/);

            if (!match) {
                return null;
            }

            const year = Number.parseInt(match[1], 10);
            const month = Number.parseInt(match[2], 10);
            const day = Number.parseInt(match[3], 10);
            const birthDate = new Date(year, month - 1, day);

            if (
                birthDate.getFullYear() !== year
                || birthDate.getMonth() !== month - 1
                || birthDate.getDate() !== day
            ) {
                return null;
            }

            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const birthdayHasPassed =
                today.getMonth() > birthDate.getMonth()
                || (today.getMonth() === birthDate.getMonth() && today.getDate() >= birthDate.getDate());

            if (!birthdayHasPassed) {
                age -= 1;
            }

            return age;
        };

        const rowIsAdultFamily = (row) => {
            if (!(row instanceof HTMLElement)) {
                return false;
            }

            const birthDateInput = row.querySelector('input[name$="[perfechanacimiento]"]');
            const age = birthDateInput instanceof HTMLInputElement ? getAgeFromBirthDate(birthDateInput.value) : null;

            return age !== null && age >= 18;
        };

        const setRepresentativeExternalAlert = (message) => {
            if (!(representativeExternalAlert instanceof HTMLElement)) {
                return;
            }

            if (message === '') {
                representativeExternalAlert.innerHTML = '';
                representativeExternalAlert.hidden = true;
                return;
            }

            representativeExternalAlert.innerHTML = '<div class="alert alert-error form-field-alert"><span>' + message + '</span></div>';
            representativeExternalAlert.hidden = false;
        };

        const setRepresentativeExternalPersonFieldsDisabled = (disabled) => {
            if (!(representativeExternalForm instanceof HTMLElement)) {
                return;
            }

            representativeExternalForm.querySelectorAll('[data-representative-external-person-field]').forEach((field) => {
                if (field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement) {
                    field.disabled = disabled;
                }
            });
        };

        const setRepresentativeExternalVisible = (visible) => {
            if (!(representativeExternalForm instanceof HTMLElement)) {
                return;
            }

            representativeExternalForm.hidden = !visible;
        };

        const clearRepresentativeExternalForm = () => {
            if (!(representativeExternalForm instanceof HTMLElement)) {
                return;
            }

            representativeExternalForm.querySelectorAll('input, select').forEach((field) => {
                if (field instanceof HTMLInputElement && field.type !== 'hidden') {
                    if (field.type === 'checkbox') {
                        field.checked = false;
                    } else {
                        field.value = '';
                    }
                }

                if (field instanceof HTMLSelectElement) {
                    field.selectedIndex = 0;
                }
            });

            if (representativeExternalPersonId instanceof HTMLInputElement) {
                representativeExternalPersonId.value = '0';
            }

            setRepresentativeExternalPersonFieldsDisabled(false);
            setRepresentativeExternalAlert('');
        };

        const syncRepresentativeOptions = () => {
            representativeOptions.innerHTML = '';
            const rows = Array.from(familyContainer.querySelectorAll('[data-family-row]')).filter((row) => (
                rowHasFamilyData(row) && rowIsAdultFamily(row)
            ));

            rows.forEach((row, index) => {
                if (!(row instanceof HTMLElement)) {
                    return;
                }

                const formIndex = row.dataset.familyIndex || String(index);
                const option = document.createElement('label');
                const radio = document.createElement('input');
                const content = document.createElement('div');

                option.className = 'representative-card';
                content.className = 'representative-card-label';
                radio.type = 'radio';
                radio.name = 'representative_option_visual';
                radio.value = formIndex;
                radio.checked = representativeIndexInput.value === formIndex;
                radio.addEventListener('change', () => {
                    representativeIndexInput.value = radio.value;
                    if (representativeSourceInput instanceof HTMLInputElement) {
                        representativeSourceInput.value = 'family';
                    }
                    setRepresentativeExternalVisible(false);
                });
                content.textContent = buildRepresentativeLabel(row);
                option.appendChild(content);
                option.appendChild(radio);
                representativeOptions.appendChild(option);
            });

            const externalOption = document.createElement('label');
            const externalRadio = document.createElement('input');
            const externalContent = document.createElement('div');

            externalOption.className = 'representative-card';
            externalContent.className = 'representative-card-label';
            externalRadio.type = 'radio';
            externalRadio.name = 'representative_option_visual';
            externalRadio.value = 'external';
            externalRadio.checked = representativeSourceInput instanceof HTMLInputElement && representativeSourceInput.value === 'external';
            externalRadio.addEventListener('change', () => {
                if (representativeSourceInput instanceof HTMLInputElement) {
                    representativeSourceInput.value = 'external';
                }

                setRepresentativeExternalVisible(true);
            });
            externalContent.textContent = 'Otro representante';
            externalOption.appendChild(externalContent);
            externalOption.appendChild(externalRadio);
            representativeOptions.appendChild(externalOption);

            if (representativeSourceInput instanceof HTMLInputElement && representativeSourceInput.value === 'external') {
                setRepresentativeExternalVisible(true);
                if (representativeExternalPersonId instanceof HTMLInputElement && Number.parseInt(representativeExternalPersonId.value || '0', 10) > 0) {
                    setRepresentativeExternalPersonFieldsDisabled(true);
                } else {
                    setRepresentativeExternalPersonFieldsDisabled(false);
                }
                externalRadio.checked = true;
                return;
            }

            setRepresentativeExternalVisible(false);

            if (rows.length === 0) {
                if (representativeSourceInput instanceof HTMLInputElement) {
                    representativeSourceInput.value = 'external';
                }

                externalRadio.checked = true;
                setRepresentativeExternalVisible(true);
                representativeIndexInput.value = '-1';
                return;
            }

            if (
                representativeIndexInput.value === ''
                || Number.parseInt(representativeIndexInput.value, 10) < 0
                || !rows.some((row) => row instanceof HTMLElement && (row.dataset.familyIndex || '') === representativeIndexInput.value)
            ) {
                representativeIndexInput.value = rows[0] instanceof HTMLElement ? (rows[0].dataset.familyIndex || '0') : '0';
            }

            if (representativeSourceInput instanceof HTMLInputElement) {
                representativeSourceInput.value = 'family';
            }

            const checkedRadio = representativeOptions.querySelector(`input[type="radio"][value="${CSS.escape(representativeIndexInput.value)}"]`);

            if (checkedRadio instanceof HTMLInputElement) {
                checkedRadio.checked = true;
            }
        };

        const wireFamilyRow = (row) => {
            if (!(row instanceof HTMLElement) || row.dataset.familyBound === 'true') {
                return;
            }

            row.dataset.familyBound = 'true';

            const cedulaInput = row.querySelector('[data-family-cedula]');
            const searchButton = row.querySelector('[data-family-search]');
            const personIdInput = row.querySelector('[data-family-person-id]');
            const removeButton = row.querySelector('[data-family-remove]');
            const inputs = row.querySelectorAll('input, select');

            const runLookup = async () => {
                if (!(cedulaInput instanceof HTMLInputElement) || !(searchButton instanceof HTMLButtonElement)) {
                    return;
                }

                const cedula = getNormalizedCedula(cedulaInput.value);
                const lookupUrl = searchButton.dataset.familySearchUrl || '';

                if (!/^\d{10}$/.test(cedula)) {
                    clearFamilyFields(row);
                    setFamilyFieldsDisabled(row, true);
                    setFamilyDetailFieldsDisabled(row, true);
                    setFamilyAlert(row, 'La cedula debe tener 10 digitos.');
                    syncRepresentativeOptions();
                    return;
                }

                if (hasDuplicateFamilyCedula(row, cedula)) {
                    clearFamilyFields(row);
                    setFamilyFieldsDisabled(row, true);
                    setFamilyDetailFieldsDisabled(row, true);
                    setFamilyAlert(row, 'Esta persona ya fue agregada en otra seccion o coincide con el estudiante.');
                    syncRepresentativeOptions();
                    return;
                }

                if (lookupUrl === '') {
                    return;
                }

                searchButton.disabled = true;

                try {
                    const url = new URL(lookupUrl, window.location.origin);
                    url.searchParams.set('cedula', cedula);

                    const response = await fetch(url.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    const payload = await response.json();

                    if (!response.ok || !payload.found) {
                        clearFamilyFields(row);
                        if (cedulaInput instanceof HTMLInputElement) {
                            cedulaInput.value = cedula;
                        }
                        setFamilyFieldsDisabled(row, false);
                        setFamilyDetailFieldsDisabled(row, false);
                        setFamilyAlert(row, payload.message || 'Persona no registrada, favor completar los datos.');
                        syncRepresentativeOptions();
                        return;
                    }

                    if (personIdInput instanceof HTMLInputElement) {
                        personIdInput.value = String(payload.person?.perid || 0);
                    }

                    row.querySelectorAll('[data-family-dependent]').forEach((field) => {
                        if (!(field instanceof HTMLInputElement) && !(field instanceof HTMLSelectElement)) {
                            return;
                        }

                        const match = field.name.match(/\[(persexo|pernombres|perapellidos|pertelefono1|pertelefono2|percorreo|perfechanacimiento|eciid|istid|perprofesion|perocupacion|perlugardetrabajo|perhablaingles)\]$/);

                        if (!match) {
                            return;
                        }

                        const key = match[1];
                        const nextValue = payload.person?.[key] ?? '';

                        if (field instanceof HTMLInputElement && field.type === 'checkbox') {
                            field.checked = Boolean(nextValue);
                        } else {
                            field.value = String(nextValue);
                        }
                    });

                    setFamilyFieldsDisabled(row, true);
                    setFamilyDetailFieldsDisabled(row, false);
                    setFamilyAlert(row, '');
                    syncRepresentativeOptions();
                } catch (error) {
                    setFamilyAlert(row, 'No se pudo consultar la persona.');
                } finally {
                    searchButton.disabled = false;
                }
            };

            if (searchButton instanceof HTMLButtonElement) {
                searchButton.addEventListener('click', runLookup);
            }

            if (removeButton instanceof HTMLButtonElement) {
                removeButton.addEventListener('click', () => {
                    row.remove();
                    updateFamilyCardTitles();
                    syncRepresentativeOptions();
                });
            }

            if (cedulaInput instanceof HTMLInputElement) {
                cedulaInput.addEventListener('input', () => {
                    if (personIdInput instanceof HTMLInputElement) {
                        personIdInput.value = '0';
                    }

                    clearFamilyFields(row);
                    setFamilyFieldsDisabled(row, true);
                    setFamilyDetailFieldsDisabled(row, true);
                    setFamilyAlert(row, '');
                    syncRepresentativeOptions();
                });
            }

            inputs.forEach((input) => {
                input.addEventListener('input', syncRepresentativeOptions);
                input.addEventListener('change', syncRepresentativeOptions);
                if (input instanceof HTMLInputElement && input.hasAttribute('data-phone-mask')) {
                    wirePhoneMask(input);
                }
            });
        };

        if (familyAddButton instanceof HTMLButtonElement) {
            familyAddButton.addEventListener('click', () => {
                const row = createDynamicFamilyRow(getNextFamilyIndex());

                if (!(row instanceof HTMLElement)) {
                    return;
                }

                familyContainer.appendChild(row);
                wireFamilyRow(row);
                updateFamilyCardTitles();
                syncRepresentativeOptions();
            });
        }

        familyToggleButtons.forEach((button) => {
            if (!(button instanceof HTMLButtonElement)) {
                return;
            }

            button.addEventListener('click', () => {
                const slot = button.dataset.familyToggle || '';
                const row = getFamilySlotRow(slot);

                if (!(row instanceof HTMLElement)) {
                    return;
                }

                setFamilyRowVisible(slot, true);
                clearFamilyFields(row);
                enableFamilyRowForManualEntry(row);
                syncRepresentativeOptions();
            });
        });

        familyContainer.querySelectorAll('[data-family-hide]').forEach((button) => {
            if (!(button instanceof HTMLButtonElement)) {
                return;
            }

            button.addEventListener('click', () => {
                const slot = button.dataset.familyHide || '';
                const row = getFamilySlotRow(slot);

                if (!(row instanceof HTMLElement)) {
                    return;
                }

                clearFamilyFields(row);
                setFamilyFieldsDisabled(row, true);
                setFamilyDetailFieldsDisabled(row, true);
                setFamilyAlert(row, '');
                setFamilyRowVisible(slot, false);
                syncRepresentativeOptions();
            });
        });

        if (
            representativeExternalForm instanceof HTMLElement
            && representativeExternalCedula instanceof HTMLInputElement
            && representativeExternalSearch instanceof HTMLButtonElement
        ) {
            const runRepresentativeExternalLookup = async () => {
                const cedula = getNormalizedCedula(representativeExternalCedula.value);
                const lookupUrl = representativeExternalSearch.dataset.representativeSearchUrl || '';

                if (!/^\d{10}$/.test(cedula)) {
                    clearRepresentativeExternalForm();
                    representativeExternalCedula.value = cedula;
                    setRepresentativeExternalAlert('La cedula debe tener 10 digitos.');
                    return;
                }

                if (studentCedulaInput instanceof HTMLInputElement && getNormalizedCedula(studentCedulaInput.value) === cedula) {
                    clearRepresentativeExternalForm();
                    representativeExternalCedula.value = cedula;
                    setRepresentativeExternalAlert('El representante no puede coincidir con el estudiante.');
                    return;
                }

                if (lookupUrl === '') {
                    return;
                }

                representativeExternalSearch.disabled = true;

                try {
                    const url = new URL(lookupUrl, window.location.origin);
                    url.searchParams.set('cedula', cedula);

                    const response = await fetch(url.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    const payload = await response.json();

                    if (!response.ok || !payload.found) {
                        clearRepresentativeExternalForm();
                        representativeExternalCedula.value = cedula;
                        setRepresentativeExternalAlert(payload.message || 'Persona no registrada, favor completar los datos.');
                        return;
                    }

                    if (representativeExternalPersonId instanceof HTMLInputElement) {
                        representativeExternalPersonId.value = String(payload.person?.perid || 0);
                    }

                    representativeExternalForm.querySelectorAll('[data-representative-external-person-field]').forEach((field) => {
                        if (!(field instanceof HTMLInputElement) && !(field instanceof HTMLSelectElement)) {
                            return;
                        }

                        const match = field.name.match(/\[(persexo|pernombres|perapellidos|pertelefono1|percorreo|pertelefono2|perfechanacimiento|eciid|istid|perprofesion|perocupacion|perlugardetrabajo|perhablaingles)\]$/);

                        if (!match) {
                            return;
                        }

                        const key = match[1];
                        const nextValue = payload.person?.[key] ?? '';

                        if (field instanceof HTMLInputElement && field.type === 'checkbox') {
                            field.checked = Boolean(nextValue);
                        } else {
                            field.value = String(nextValue);
                        }
                    });

                    setRepresentativeExternalPersonFieldsDisabled(true);
                    setRepresentativeExternalAlert('');
                } catch (error) {
                    setRepresentativeExternalAlert('No se pudo consultar la persona.');
                } finally {
                    representativeExternalSearch.disabled = false;
                }
            };

            representativeExternalSearch.addEventListener('click', runRepresentativeExternalLookup);
            representativeExternalCedula.addEventListener('input', () => {
                if (representativeExternalPersonId instanceof HTMLInputElement) {
                    representativeExternalPersonId.value = '0';
                }

                representativeExternalForm.querySelectorAll('[data-representative-external-person-field]').forEach((field) => {
                    if (field instanceof HTMLInputElement || field instanceof HTMLSelectElement) {
                        if (field instanceof HTMLInputElement) {
                            if (field.type === 'checkbox') {
                                field.checked = false;
                            } else {
                                field.value = '';
                            }
                        } else {
                            field.selectedIndex = 0;
                        }
                    }
                });
                setRepresentativeExternalPersonFieldsDisabled(false);
                setRepresentativeExternalAlert('');
            });

            representativeExternalForm.querySelectorAll('input, select').forEach((field) => {
                if (field instanceof HTMLInputElement && field.hasAttribute('data-phone-mask')) {
                    wirePhoneMask(field);
                }
            });
        }

        if (matriculaForm instanceof HTMLFormElement) {
            matriculaForm.addEventListener('matricula:draft-cleared', () => {
                familyContainer.querySelectorAll('[data-family-removable]').forEach((row) => {
                    if (row instanceof HTMLElement) {
                        row.remove();
                    }
                });

                updateFamilyCardTitles();
                clearRepresentativeExternalForm();
                if (healthConditionContainer instanceof HTMLElement) {
                    healthConditionContainer.querySelectorAll('[data-health-condition-row]').forEach((row) => {
                        if (!(row instanceof HTMLElement)) {
                            return;
                        }

                        row.remove();
                    });
                }
                if (representativeSourceInput instanceof HTMLInputElement) {
                    representativeSourceInput.value = 'family';
                }
                syncFixedFamilyVisibility();
                syncRepresentativeOptions();
            });

            const rawDraft = window.localStorage.getItem('sgeap_matricula_draft');

            if (rawDraft !== null) {
                try {
                    const draftPayload = JSON.parse(rawDraft);
                    const requiredIndexes = Object.keys(draftPayload)
                        .map((name) => {
                            const match = name.match(/^family\[(\d+)\]\[/);
                            return match ? Number.parseInt(match[1], 10) : -1;
                        })
                        .filter((index) => index >= 2)
                        .sort((left, right) => left - right);

                    requiredIndexes.forEach((index) => {
                        const existing = familyContainer.querySelector(`[data-family-row][data-family-index="${index}"]`);

                        if (existing instanceof HTMLElement) {
                            return;
                        }

                        const row = createDynamicFamilyRow(index);

                        if (!(row instanceof HTMLElement)) {
                            return;
                        }

                        familyContainer.appendChild(row);
                        wireFamilyRow(row);
                    });

                    const requiredHealthConditionIndexes = Array.from(new Set(
                        Object.keys(draftPayload)
                            .map((name) => {
                                const match = name.match(/^health_conditions\[(\d+)\]\[/);
                                return match ? Number.parseInt(match[1], 10) : -1;
                            })
                            .filter((index) => index >= 0 && draftHasHealthConditionData(draftPayload, index))
                    )).sort((left, right) => left - right);

                    if (healthConditionContainer instanceof HTMLElement && requiredHealthConditionIndexes.length > 0) {
                        healthConditionContainer.querySelectorAll('[data-health-condition-row]').forEach((row) => {
                            if (row instanceof HTMLElement) {
                                row.remove();
                            }
                        });

                        requiredHealthConditionIndexes.forEach((index) => {
                            const row = createHealthConditionRow(index);

                            if (!(row instanceof HTMLElement)) {
                                return;
                            }

                            healthConditionContainer.appendChild(row);
                            wireHealthConditionRow(row);
                        });
                    }

                    Object.entries(draftPayload).forEach(([name, value]) => {
                        const field = matriculaForm.querySelector(`[name="${CSS.escape(name)}"]`);

                        if (field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement) {
                            if (field instanceof HTMLInputElement && field.type === 'checkbox') {
                                field.checked = ['1', 'true', 'on', 'yes'].includes(String(value).toLowerCase());
                            } else {
                                field.value = String(value);
                            }

                            if (field instanceof HTMLInputElement && field.hasAttribute('data-phone-mask')) {
                                field.dispatchEvent(new Event('input', { bubbles: true }));
                            }
                        }
                    });
                    syncFixedFamilyVisibility();
                    syncDisabilityDetail();
                    syncRepeatedYearsDetail();
                } catch (error) {
                    window.localStorage.removeItem('sgeap_matricula_draft');
                }
            }
        }

        Array.from(familyContainer.querySelectorAll('[data-family-row]')).forEach(wireFamilyRow);
        if (healthConditionContainer instanceof HTMLElement) {
            Array.from(healthConditionContainer.querySelectorAll('[data-health-condition-row]')).forEach(wireHealthConditionRow);
        }
        updateFamilyCardTitles();
        syncFixedFamilyVisibility();
        syncRepresentativeOptions();

        if (disabilityToggleInput instanceof HTMLInputElement) {
            disabilityToggleInput.addEventListener('change', syncDisabilityDetail);
        }

        syncDisabilityDetail();

        if (repeatedYearsToggleInput instanceof HTMLInputElement) {
            repeatedYearsToggleInput.addEventListener('change', syncRepeatedYearsDetail);
        }

        syncRepeatedYearsDetail();

        if (healthConditionAddButton instanceof HTMLButtonElement && healthConditionContainer instanceof HTMLElement) {
            healthConditionAddButton.addEventListener('click', () => {
                const row = createHealthConditionRow(getNextHealthConditionIndex());

                if (!(row instanceof HTMLElement)) {
                    return;
                }

                healthConditionContainer.appendChild(row);
                wireHealthConditionRow(row);
            });
        }
    }

    if (
        imcWeightInput instanceof HTMLInputElement
        && imcHeightInput instanceof HTMLInputElement
        && imcOutputInput instanceof HTMLInputElement
    ) {
        const syncImc = () => {
            const peso = Number.parseFloat(imcWeightInput.value);
            const tallaCm = Number.parseFloat(imcHeightInput.value);

            if (!Number.isFinite(peso) || !Number.isFinite(tallaCm) || tallaCm <= 0) {
                imcOutputInput.value = '';
                if (imcCategoryInput instanceof HTMLInputElement) {
                    imcCategoryInput.value = '';
                }
                if (imcAlert instanceof HTMLElement) {
                    imcAlert.hidden = true;
                    imcAlert.textContent = '';
                }
                return;
            }

            const tallaMetros = tallaCm / 100;
            const imc = peso / (tallaMetros * tallaMetros);
            let categoria = 'Peso normal';
            let whoBmiReference = null;
            const birthDateValue = imcCategoryInput instanceof HTMLInputElement ? imcCategoryInput.dataset.studentBirthDate || '' : '';
            const sexValue = imcCategoryInput instanceof HTMLInputElement ? imcCategoryInput.dataset.studentSex || 'sexo no registrado' : 'sexo no registrado';
            const measurementDateValue = measurementDateInput instanceof HTMLInputElement ? measurementDateInput.value : '';
            const birthDate = birthDateValue !== '' ? new Date(birthDateValue + 'T00:00:00') : null;
            const measurementDate = measurementDateValue !== '' ? new Date(measurementDateValue + 'T00:00:00') : new Date();
            let ageMonths = null;

            if (whoBmiReferenceElement instanceof HTMLElement) {
                try {
                    whoBmiReference = JSON.parse(whoBmiReferenceElement.textContent || '{}');
                } catch (error) {
                    whoBmiReference = null;
                }
            }

            if (birthDate instanceof Date && !Number.isNaN(birthDate.getTime())) {
                let ageYears = measurementDate.getFullYear() - birthDate.getFullYear();
                const monthDelta = measurementDate.getMonth() - birthDate.getMonth();
                let monthRemainder = monthDelta;

                if (monthDelta < 0 || (monthDelta === 0 && measurementDate.getDate() < birthDate.getDate())) {
                    ageYears -= 1;
                    monthRemainder += 12;
                }

                if (measurementDate.getDate() < birthDate.getDate()) {
                    monthRemainder -= 1;
                }

                ageMonths = (ageYears * 12) + Math.max(0, monthRemainder);
            }

            const normalizedSex = String(sexValue).toLowerCase();
            const sexKey = normalizedSex === 'femenino' ? 'F' : (normalizedSex === 'masculino' ? 'M' : '');

            if (ageMonths !== null && ageMonths < 61) {
                categoria = 'Requiere curva OMS menor de 5 años';
            } else if (
                ageMonths !== null
                && ageMonths <= 228
                && sexKey !== ''
                && whoBmiReference
                && whoBmiReference[sexKey]
                && whoBmiReference[sexKey][String(Math.max(61, Math.min(228, ageMonths)))]
            ) {
                const reference = whoBmiReference[sexKey][String(Math.max(61, Math.min(228, ageMonths)))];

                if (imc < Number.parseFloat(reference.sd3neg)) {
                    categoria = 'Delgadez severa';
                } else if (imc < Number.parseFloat(reference.sd2neg)) {
                    categoria = 'Delgadez';
                } else if (imc <= Number.parseFloat(reference.sd1)) {
                    categoria = 'Peso normal';
                } else if (imc <= Number.parseFloat(reference.sd2)) {
                    categoria = 'Sobrepeso';
                } else {
                    categoria = 'Obesidad';
                }
            } else if (imc < 18.5) {
                categoria = 'Bajo peso';
            } else if (imc < 25) {
                categoria = 'Peso normal';
            } else if (imc < 30) {
                categoria = 'Sobrepeso';
            } else {
                categoria = 'Obesidad';
            }

            imcOutputInput.value = imc.toFixed(2);
            if (imcCategoryInput instanceof HTMLInputElement) {
                imcCategoryInput.value = categoria;
            }

            if (imcAlert instanceof HTMLElement) {
                imcAlert.textContent = 'Interpretacion del IMC: ' + categoria + '.';
                imcAlert.hidden = false;
            }
        };

        imcWeightInput.addEventListener('input', syncImc);
        imcHeightInput.addEventListener('input', syncImc);
        if (measurementDateInput instanceof HTMLInputElement) {
            measurementDateInput.addEventListener('input', syncImc);
            measurementDateInput.addEventListener('change', syncImc);
        }
        syncImc();
    }

    if (!(familyContainer instanceof HTMLElement)) {
        if (disabilityToggleInput instanceof HTMLInputElement && disabilityDetailInput instanceof HTMLTextAreaElement) {
            const syncStandaloneDisabilityDetail = () => {
                disabilityDetailInput.disabled = !disabilityToggleInput.checked;

                if (!disabilityToggleInput.checked) {
                    disabilityDetailInput.value = '';
                }
            };

            disabilityToggleInput.addEventListener('change', syncStandaloneDisabilityDetail);
            syncStandaloneDisabilityDetail();
        }

        if (repeatedYearsToggleInput instanceof HTMLInputElement && repeatedYearsDetailInput instanceof HTMLTextAreaElement) {
            const syncStandaloneRepeatedYearsDetail = () => {
                repeatedYearsDetailInput.disabled = !repeatedYearsToggleInput.checked;

                if (!repeatedYearsToggleInput.checked) {
                    repeatedYearsDetailInput.value = '';
                }
            };

            repeatedYearsToggleInput.addEventListener('change', syncStandaloneRepeatedYearsDetail);
            syncStandaloneRepeatedYearsDetail();
        }
    }

    if (
        !(familyContainer instanceof HTMLElement)
        && healthConditionContainer instanceof HTMLElement
        && healthConditionTemplate instanceof HTMLTemplateElement
    ) {
        const nextStandaloneHealthConditionIndex = () => {
            return Array.from(healthConditionContainer.querySelectorAll('[data-health-condition-row]')).reduce((max, row) => {
                if (!(row instanceof HTMLElement)) {
                    return max;
                }

                const rowIndex = Number.parseInt(row.dataset.healthConditionIndex || '-1', 10);
                return Number.isNaN(rowIndex) ? max : Math.max(max, rowIndex);
            }, -1) + 1;
        };

        const wireStandaloneHealthConditionRow = (row) => {
            if (!(row instanceof HTMLElement) || row.dataset.healthConditionBound === 'true') {
                return;
            }

            row.dataset.healthConditionBound = 'true';
            const removeButton = row.querySelector('[data-health-condition-remove]');

            if (removeButton instanceof HTMLButtonElement) {
                removeButton.addEventListener('click', () => row.remove());
            }
        };

        Array.from(healthConditionContainer.querySelectorAll('[data-health-condition-row]')).forEach(wireStandaloneHealthConditionRow);

        if (healthConditionAddButton instanceof HTMLButtonElement) {
            healthConditionAddButton.addEventListener('click', () => {
                const wrapper = document.createElement('div');
                wrapper.innerHTML = healthConditionTemplate.innerHTML
                    .replace(/__INDEX__/g, String(nextStandaloneHealthConditionIndex()))
                    .trim();
                const row = wrapper.firstElementChild;

                if (!(row instanceof HTMLElement)) {
                    return;
                }

                healthConditionContainer.appendChild(row);
                wireStandaloneHealthConditionRow(row);
            });
        }
    }

    if (matriculaForm instanceof HTMLFormElement) {
        matriculaForm.addEventListener('submit', () => {
            matriculaForm.querySelectorAll('[data-submit-enable]').forEach((field) => {
                if (field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement) {
                    field.disabled = false;
                }
            });
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

    document.querySelectorAll('[data-health-growth-chart]').forEach((canvas) => {
        if (!(canvas instanceof HTMLCanvasElement) || typeof window.Chart === 'undefined') {
            return;
        }

        let measurements = [];

        try {
            measurements = JSON.parse(canvas.dataset.measurements || '[]');
        } catch (error) {
            measurements = [];
        }

        let referencePoints = [];

        try {
            referencePoints = JSON.parse(canvas.dataset.reference || '[]');
        } catch (error) {
            referencePoints = [];
        }

        if (!Array.isArray(measurements) || measurements.length === 0) {
            return;
        }

        if (!Array.isArray(referencePoints)) {
            referencePoints = [];
        }

        const referenceByDate = new Map(referencePoints.map((point) => [String(point.fecha || ''), point]));
        const referenceValue = (measurement, key) => {
            const point = referenceByDate.get(String(measurement.fecha || ''));

            if (!point || point[key] === null || point[key] === undefined || point[key] === '') {
                return null;
            }

            const value = Number.parseFloat(point[key]);
            return Number.isFinite(value) ? value : null;
        };
        const numericValue = (measurement, key) => {
            const value = Number.parseFloat(measurement[key]);
            return Number.isFinite(value) ? value : null;
        };

        new window.Chart(canvas, {
            type: 'line',
            data: {
                labels: measurements.map((measurement) => String(measurement.fecha || '')),
                datasets: [
                    {
                        label: 'Peso (kg)',
                        data: measurements.map((measurement) => numericValue(measurement, 'peso')),
                        borderColor: '#0f4c81',
                        backgroundColor: 'rgba(15, 76, 129, 0.12)',
                        tension: 0.25,
                        yAxisID: 'weight',
                    },
                    {
                        label: 'Talla (cm)',
                        data: measurements.map((measurement) => numericValue(measurement, 'talla')),
                        borderColor: '#1f9d55',
                        backgroundColor: 'rgba(31, 157, 85, 0.12)',
                        tension: 0.25,
                        yAxisID: 'height',
                    },
                    {
                        label: 'IMC',
                        data: measurements.map((measurement) => numericValue(measurement, 'imc')),
                        borderColor: '#7c3aed',
                        backgroundColor: 'rgba(124, 58, 237, 0.1)',
                        tension: 0.25,
                        yAxisID: 'bmi',
                    },
                    {
                        label: 'OMS delgadez (-2 DE)',
                        data: measurements.map((measurement) => referenceValue(measurement, 'sd2neg')),
                        borderColor: 'rgba(251, 191, 36, 0.75)',
                        borderWidth: 1.75,
                        borderDash: [6, 4],
                        pointRadius: 0,
                        tension: 0.2,
                        spanGaps: true,
                        yAxisID: 'bmi',
                    },
                    {
                        label: 'OMS sobrepeso (+1 DE)',
                        data: measurements.map((measurement) => referenceValue(measurement, 'sd1')),
                        borderColor: 'rgba(248, 113, 113, 0.7)',
                        borderWidth: 1.75,
                        borderDash: [6, 4],
                        pointRadius: 0,
                        tension: 0.2,
                        spanGaps: true,
                        yAxisID: 'bmi',
                    },
                    {
                        label: 'OMS obesidad (+2 DE)',
                        data: measurements.map((measurement) => referenceValue(measurement, 'sd2')),
                        borderColor: 'rgba(252, 165, 165, 0.7)',
                        borderWidth: 1.75,
                        borderDash: [3, 4],
                        pointRadius: 0,
                        tension: 0.2,
                        spanGaps: true,
                        yAxisID: 'bmi',
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                },
                scales: {
                    weight: {
                        type: 'linear',
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Peso kg',
                        },
                    },
                    height: {
                        type: 'linear',
                        position: 'right',
                        grid: {
                            drawOnChartArea: false,
                        },
                        title: {
                            display: true,
                            text: 'Talla cm',
                        },
                    },
                    bmi: {
                        type: 'linear',
                        position: 'right',
                        grid: {
                            drawOnChartArea: false,
                        },
                        title: {
                            display: true,
                            text: 'IMC OMS',
                        },
                    },
                },
            },
        });
    });

    const studentSearchInput = document.querySelector('[data-student-search]');
    const studentCourseFilter = document.querySelector('[data-student-course-filter]');
    const studentTableBody = document.querySelector('[data-student-table-body]');
    const studentTableWrapper = document.querySelector('[data-student-table-wrapper]');
    const studentEmptyWrapper = document.querySelector('[data-student-list-wrapper]');
    const studentStatus = document.querySelector('[data-student-search-status]');
    const studentSortButtons = document.querySelectorAll('[data-student-sort]');

    if (
        studentSearchInput instanceof HTMLInputElement
        && studentCourseFilter instanceof HTMLSelectElement
        && studentTableBody instanceof HTMLTableSectionElement
        && studentTableWrapper instanceof HTMLElement
        && studentEmptyWrapper instanceof HTMLElement
        && studentStatus instanceof HTMLElement
    ) {
        let studentDebounceTimer = null;
        let studentSort = 'apellidos';
        let studentDirection = 'asc';

        const updateStudentSortButtons = () => {
            studentSortButtons.forEach((button) => {
                if (!(button instanceof HTMLButtonElement)) {
                    return;
                }

                const icon = button.querySelector('.fa');
                const isActive = button.dataset.studentSort === studentSort;
                button.classList.toggle('is-active', isActive);
                button.dataset.direction = isActive ? studentDirection : '';

                if (icon instanceof HTMLElement) {
                    icon.className = 'fa ' + (isActive ? (studentDirection === 'asc' ? 'fa-sort-asc' : 'fa-sort-desc') : 'fa-sort');
                }
            });
        };

        const runStudentSearch = async () => {
            const baseUrl = studentSearchInput.dataset.studentSearchUrl || '';

            if (baseUrl === '') {
                return;
            }

            const url = new URL(baseUrl, window.location.origin);
            url.searchParams.set('q', studentSearchInput.value.trim());
            url.searchParams.set('curid', studentCourseFilter.value);
            url.searchParams.set('sort', studentSort);
            url.searchParams.set('direction', studentDirection);
            studentStatus.textContent = 'Buscando...';

            try {
                const response = await fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error('Student search request failed');
                }

                const payload = await response.json();
                studentTableBody.innerHTML = payload.html || '';
                studentTableWrapper.hidden = !!payload.isEmpty;
                studentEmptyWrapper.hidden = !payload.isEmpty;
                studentEmptyWrapper.innerHTML = payload.isEmpty ? (payload.emptyHtml || '<div class="empty-state">No se encontraron estudiantes.</div>') : '';
                studentStatus.textContent = String(payload.count || 0) + ' registro(s)';
            } catch (error) {
                studentStatus.textContent = 'Error al filtrar';
            }
        };

        const queueStudentSearch = () => {
            if (studentDebounceTimer !== null) {
                window.clearTimeout(studentDebounceTimer);
            }

            studentDebounceTimer = window.setTimeout(runStudentSearch, 250);
        };

        studentSearchInput.addEventListener('input', queueStudentSearch);
        studentCourseFilter.addEventListener('change', runStudentSearch);

        studentSortButtons.forEach((button) => {
            if (!(button instanceof HTMLButtonElement)) {
                return;
            }

            button.addEventListener('click', () => {
                const nextSort = button.dataset.studentSort || 'apellidos';

                if (nextSort === studentSort) {
                    studentDirection = studentDirection === 'asc' ? 'desc' : 'asc';
                } else {
                    studentSort = nextSort;
                    studentDirection = 'asc';
                }

                updateStudentSortButtons();
                runStudentSearch();
            });
        });

        updateStudentSortButtons();
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
