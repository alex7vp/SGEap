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
                    field.value = '';
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
                    field.value = '';
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
            const rows = Array.from(familyContainer.querySelectorAll('[data-family-row]')).filter((row) => rowHasFamilyData(row));

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

                        const match = field.name.match(/\[(persexo|pernombres|perapellidos|pertelefono1|pertelefono2|percorreo)\]$/);

                        if (!match) {
                            return;
                        }

                        const key = match[1];
                        const nextValue = payload.person?.[key] ?? '';
                        field.value = String(nextValue);
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

                        const match = field.name.match(/\[(persexo|pernombres|perapellidos|pertelefono1|percorreo)\]$/);

                        if (!match) {
                            return;
                        }

                        const key = match[1];
                        field.value = String(payload.person?.[key] ?? '');
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
                            field.value = '';
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

                    Object.entries(draftPayload).forEach(([name, value]) => {
                        const field = matriculaForm.querySelector(`[name="${CSS.escape(name)}"]`);

                        if (field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement) {
                            field.value = String(value);

                            if (field instanceof HTMLInputElement && field.hasAttribute('data-phone-mask')) {
                                field.dispatchEvent(new Event('input', { bubbles: true }));
                            }
                        }
                    });
                    syncFixedFamilyVisibility();
                } catch (error) {
                    window.localStorage.removeItem('sgeap_matricula_draft');
                }
            }
        }

        Array.from(familyContainer.querySelectorAll('[data-family-row]')).forEach(wireFamilyRow);
        updateFamilyCardTitles();
        syncFixedFamilyVisibility();
        syncRepresentativeOptions();
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
