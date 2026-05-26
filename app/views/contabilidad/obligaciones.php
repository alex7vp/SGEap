<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$money = static fn (mixed $value): string => '$' . number_format((float) $value, 2, '.', ',');
$levels = is_array($levels ?? null) ? $levels : [];
$courses = is_array($courses ?? null) ? $courses : [];
$students = is_array($students ?? null) ? $students : [];
$filters = is_array($filters ?? null) ? $filters : [];
$pagination = is_array($pagination ?? null) ? $pagination : ['page' => 1, 'pages' => 1, 'total' => count($students), 'limit' => 25];
$reference = is_array($reference ?? null) ? $reference : [];
$csrf = csrfToken();
$renderStatus = static function (array $row): string {
    if ((int) ($row['cfocantidad_pensiones'] ?? 0) <= 0 || empty($row['pension_cfoid']) || empty($row['matricula_cfoid'])) {
        return 'Sin configuracion';
    }

    if ((int) ($row['total_obligaciones'] ?? 0) > 0) {
        return 'Asignado';
    }

    return 'Pendiente';
};
$scholarshipPercent = static function (array $row): string {
    $base = (float) ($row['pension_valor'] ?? 0);
    $type = (string) ($row['pension_descuento_tipo'] ?? '');

    if ($type === 'PORCENTAJE') {
        return number_format((float) ($row['pension_descuento_valor'] ?? 0), 2, '.', '');
    }

    if ($type === 'VALOR_FIJO' && $base > 0) {
        return number_format(((float) ($row['pension_valor_descuento'] ?? 0) / $base) * 100, 2, '.', '');
    }

    return '0.00';
};
$scholarshipAmount = static function (array $row): string {
    return number_format((float) ($row['pension_valor_descuento'] ?? 0), 2, '.', '');
};
$finalPensionValue = static function (array $row): string {
    if (isset($row['pension_valor_final']) && $row['pension_valor_final'] !== null) {
        return number_format((float) $row['pension_valor_final'], 2, '.', '');
    }

    return number_format((float) ($row['pension_valor'] ?? 0), 2, '.', '');
};
$referenceMoney = static function (array $reference, string $key, string $labelKey) use ($money): string {
    if (($reference[$labelKey] ?? null) !== null) {
        return (string) $reference[$labelKey];
    }

    if (($reference[$key] ?? null) !== null) {
        return $money($reference[$key]);
    }

    return 'Sin datos';
};
$referenceValue = static function (array $reference, string $key, string $labelKey): string {
    if (($reference[$labelKey] ?? null) !== null) {
        return (string) $reference[$labelKey];
    }

    if (($reference[$key] ?? null) !== null) {
        return (string) $reference[$key];
    }

    return 'Sin datos';
};
?>

<?php if (empty($currentPeriod)): ?>
    <div class="empty-state">No hay un periodo lectivo seleccionado. Elige uno desde el chip de periodo en el navbar para gestionar obligaciones.</div>
<?php else: ?>
    <p class="module-note">Asigna y revisa las obligaciones base de matricula y pension para <?= $h($currentPeriod['pledescripcion'] ?? ''); ?>.</p>

    <section class="security-assignment-block">
        <header class="security-assignment-header">
            <div>
                <h3>Asignacion de obligaciones</h3>
                <p>Busca por estudiante, filtra por nivel o curso y asigna el valor real de pension con beca.</p>
            </div>
        </header>

        <form class="toolbar toolbar-filter" method="GET" action="<?= $h(baseUrl('contabilidad/obligaciones')); ?>" data-accounting-obligation-filter>
            <div class="filter-box">
                <label class="sr-only" for="accounting-obligation-search">Buscar estudiante</label>
                <input
                    id="accounting-obligation-search"
                    type="search"
                    name="q"
                    value="<?= $h($filters['q'] ?? ''); ?>"
                    placeholder="Buscar estudiante"
                    data-accounting-obligation-search
                >
            </div>
            <div class="filter-box filter-box-compact">
                <label class="sr-only" for="accounting-obligation-level">Nivel</label>
                <select id="accounting-obligation-level" name="nivel" data-accounting-obligation-level>
                    <option value="0">Todos los niveles</option>
                    <?php foreach ($levels as $level): ?>
                        <option value="<?= $h($level['nedid'] ?? ''); ?>" <?= (int) ($filters['nivel'] ?? 0) === (int) ($level['nedid'] ?? 0) ? 'selected' : ''; ?>>
                            <?= $h($level['nednombre'] ?? ''); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-box filter-box-compact">
                <label class="sr-only" for="accounting-obligation-course">Curso</label>
                <select id="accounting-obligation-course" name="curso" data-accounting-obligation-course>
                    <option value="0">Todos los cursos</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?= $h($course['curid'] ?? ''); ?>" <?= (int) ($filters['curso'] ?? 0) === (int) ($course['curid'] ?? 0) ? 'selected' : ''; ?>>
                            <?= $h(trim((string) (($course['nednombre'] ?? '') . ' | ' . ($course['granombre'] ?? '') . ' ' . ($course['prlnombre'] ?? '')))); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="btn-secondary btn-auto" type="submit">
                <i class="fa fa-search" aria-hidden="true"></i>
                Buscar
            </button>
            <span class="table-status" data-accounting-obligation-status><?= count($students); ?> de <?= $h($pagination['total'] ?? count($students)); ?> registro(s)</span>
        </form>

        <section class="toolbar toolbar-filter" data-accounting-reference>
            <span class="table-status">Referencia:</span>
            <span class="state-pill">Matricula <strong data-reference-matricula><?= $h($referenceMoney($reference, 'matricula', 'matricula_label')); ?></strong></span>
            <span class="state-pill">Pension <strong data-reference-pension><?= $h($referenceMoney($reference, 'pension', 'pension_label')); ?></strong></span>
            <span class="state-pill">Meses <strong data-reference-months><?= $h($referenceValue($reference, 'meses', 'meses_label')); ?></strong></span>
        </section>

        <table class="data-table" data-accounting-obligation-table>
            <thead>
                <tr>
                    <th>Estudiante</th>
                    <th>Curso</th>
                    <th>Beca %</th>
                    <th>Beca valor</th>
                    <th>Valor pension</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody data-accounting-obligation-rows>
                <?php foreach ($students as $student): ?>
                    <?php $status = $renderStatus($student); ?>
                    <?php $formId = 'accounting-obligation-form-' . (int) ($student['matid'] ?? 0); ?>
                    <tr>
                        <td>
                            <strong><?= $h(trim((string) (($student['perapellidos'] ?? '') . ' ' . ($student['pernombres'] ?? '')))); ?></strong>
                            <span class="cell-subtitle"><?= $h($student['percedula'] ?? ''); ?></span>
                        </td>
                        <td><?= $h(trim((string) (($student['granombre'] ?? '') . ' ' . ($student['prlnombre'] ?? '')))); ?></td>
                        <td><input class="table-input accounting-scholarship-percent-input" form="<?= $h($formId); ?>" type="number" name="beca_porcentaje" min="0" max="100" step="0.01" value="<?= $h($scholarshipPercent($student)); ?>" readonly data-scholarship-percent data-base-pension="<?= $h($student['pension_valor'] ?? 0); ?>"></td>
                        <td><input class="table-input accounting-scholarship-amount-input" form="<?= $h($formId); ?>" type="number" name="beca_valor" min="0" step="0.01" value="<?= $h($scholarshipAmount($student)); ?>" readonly data-scholarship-amount></td>
                        <td><input class="table-input" type="number" min="0" step="0.01" value="<?= $h($finalPensionValue($student)); ?>" readonly data-final-pension></td>
                        <td><span class="state-pill <?= $status === 'Asignado' ? 'state-pill-active' : 'state-pill-inactive'; ?>"><?= $h($status); ?></span></td>
                        <td>
                            <form class="accounting-actions-inline" id="<?= $h($formId); ?>" method="POST" action="<?= $h(baseUrl('contabilidad/obligaciones/generar')); ?>">
                                <?= csrfField(); ?>
                                <input type="hidden" name="matid" value="<?= $h($student['matid'] ?? ''); ?>">
                                <?php if ($status === 'Asignado'): ?>
                                    <button class="icon-button icon-button-edit" type="button" title="Editar valores" aria-label="Editar valores" data-obligation-row-edit>
                                        <i class="fa fa-pencil" aria-hidden="true"></i>
                                    </button>
                                    <button class="icon-button icon-button-cancel" type="button" title="Cancelar edicion" aria-label="Cancelar edicion" data-obligation-row-cancel hidden>
                                        <i class="fa fa-times" aria-hidden="true"></i>
                                    </button>
                                    <button class="icon-button icon-button-view" type="button" title="Ver obligaciones" aria-label="Ver obligaciones" data-obligation-detail data-matid="<?= $h($student['matid'] ?? ''); ?>">
                                        <i class="fa fa-search" aria-hidden="true"></i>
                                    </button>
                                <?php endif; ?>
                                <button class="icon-button icon-button-save" type="submit" title="<?= $status === 'Asignado' ? 'Actualizar obligaciones' : 'Asignar obligaciones'; ?>" aria-label="<?= $status === 'Asignado' ? 'Actualizar obligaciones' : 'Asignar obligaciones'; ?>" <?= $status === 'Sin configuracion' ? 'disabled' : ''; ?> data-obligation-row-assign>
                                    <i class="fa <?= $status === 'Asignado' ? 'fa-refresh' : 'fa-check-square-o'; ?>" aria-hidden="true"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="empty-state" data-accounting-obligation-empty <?= $students === [] ? '' : 'hidden'; ?>>No se encontraron estudiantes con los filtros aplicados.</div>
        <div class="actions-row accounting-pagination" data-accounting-obligation-pagination>
            <button class="btn-secondary btn-auto" type="button" data-accounting-page-prev <?= (int) ($pagination['page'] ?? 1) <= 1 ? 'disabled' : ''; ?>>
                <i class="fa fa-chevron-left" aria-hidden="true"></i>
                Anterior
            </button>
            <span class="table-status" data-accounting-page-status>Pagina <?= $h($pagination['page'] ?? 1); ?> de <?= $h($pagination['pages'] ?? 1); ?></span>
            <button class="btn-secondary btn-auto" type="button" data-accounting-page-next <?= (int) ($pagination['page'] ?? 1) >= (int) ($pagination['pages'] ?? 1) ? 'disabled' : ''; ?>>
                Siguiente
                <i class="fa fa-chevron-right" aria-hidden="true"></i>
            </button>
        </div>
    </section>
<?php endif; ?>

<div class="modal-backdrop" data-accounting-confirm-modal hidden>
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="accounting-confirm-title">
        <div class="modal-header">
            <h3 id="accounting-confirm-title">Confirmar asignacion</h3>
            <button class="modal-close" type="button" aria-label="Cerrar" data-accounting-confirm-cancel>
                <i class="fa fa-times" aria-hidden="true"></i>
            </button>
        </div>
        <div class="modal-body">
            <p>Se generaran las obligaciones de matricula y pensiones para el estudiante seleccionado. Si ya existen, se actualizaran los valores de pension aplicables.</p>
        </div>
        <div class="modal-actions">
            <button class="btn-secondary btn-auto" type="button" data-accounting-confirm-cancel>Cancelar</button>
            <button class="icon-button icon-button-save" type="button" title="Asignar obligaciones" aria-label="Asignar obligaciones" data-accounting-confirm-accept>
                <i class="fa fa-check-square-o" aria-hidden="true"></i>
            </button>
        </div>
    </div>
</div>

<div class="modal-backdrop" data-obligation-detail-modal hidden>
    <div class="modal-card accounting-detail-modal" role="dialog" aria-modal="true" aria-labelledby="obligation-detail-title">
        <div class="modal-header">
            <h3 id="obligation-detail-title">Obligaciones generadas</h3>
            <button class="modal-close" type="button" aria-label="Cerrar" data-obligation-detail-close>
                <i class="fa fa-times" aria-hidden="true"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="empty-state" data-obligation-detail-empty hidden>No existen obligaciones generadas para este estudiante.</div>
            <table class="data-table" data-obligation-detail-table>
                <thead>
                    <tr>
                        <th>Obligacion</th>
                        <th>Vence</th>
                        <th class="accounting-detail-value-column">Valor</th>
                        <th>Pagado</th>
                        <th>Saldo</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody data-obligation-detail-rows></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal-backdrop" data-obligation-annul-confirm-modal hidden>
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="obligation-annul-confirm-title">
        <div class="modal-header">
            <h3 id="obligation-annul-confirm-title">Confirmar anulacion</h3>
            <button class="modal-close" type="button" aria-label="Cerrar" data-obligation-annul-cancel>
                <i class="fa fa-times" aria-hidden="true"></i>
            </button>
        </div>
        <div class="modal-body">
            <p>La obligacion quedara anulada y no se tomara como saldo pendiente. Esta accion quedara registrada para auditoria.</p>
        </div>
        <div class="modal-actions">
            <button class="btn-secondary btn-auto" type="button" data-obligation-annul-cancel>Cancelar</button>
            <button class="icon-button icon-button-delete" type="button" title="Anular obligacion" aria-label="Anular obligacion" data-obligation-annul-accept>
                <i class="fa fa-trash" aria-hidden="true"></i>
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('[data-accounting-obligation-filter]');
    const search = document.querySelector('[data-accounting-obligation-search]');
    const level = document.querySelector('[data-accounting-obligation-level]');
    const course = document.querySelector('[data-accounting-obligation-course]');
    const rowsContainer = document.querySelector('[data-accounting-obligation-rows]');
    const statusLabel = document.querySelector('[data-accounting-obligation-status]');
    const emptyState = document.querySelector('[data-accounting-obligation-empty]');
    const referenceMatriculation = document.querySelector('[data-reference-matricula]');
    const referencePension = document.querySelector('[data-reference-pension]');
    const referenceMonths = document.querySelector('[data-reference-months]');
    const prevPageButton = document.querySelector('[data-accounting-page-prev]');
    const nextPageButton = document.querySelector('[data-accounting-page-next]');
    const pageStatus = document.querySelector('[data-accounting-page-status]');
    const confirmModal = document.querySelector('[data-accounting-confirm-modal]');
    const confirmAccept = document.querySelector('[data-accounting-confirm-accept]');
    const confirmCancelButtons = document.querySelectorAll('[data-accounting-confirm-cancel]');
    const detailModal = document.querySelector('[data-obligation-detail-modal]');
    const detailRows = document.querySelector('[data-obligation-detail-rows]');
    const detailEmpty = document.querySelector('[data-obligation-detail-empty]');
    const detailClose = document.querySelector('[data-obligation-detail-close]');
    const annulConfirmModal = document.querySelector('[data-obligation-annul-confirm-modal]');
    const annulAccept = document.querySelector('[data-obligation-annul-accept]');
    const annulCancelButtons = document.querySelectorAll('[data-obligation-annul-cancel]');
    const endpoint = <?= json_encode(baseUrl('contabilidad/obligaciones'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    const detailEndpoint = <?= json_encode(baseUrl('contabilidad/obligaciones/detalle'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    const generateEndpoint = <?= json_encode(baseUrl('contabilidad/obligaciones/generar'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    const updateEndpoint = <?= json_encode(baseUrl('contabilidad/obligaciones/actualizar'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    const annulEndpoint = <?= json_encode(baseUrl('contabilidad/obligaciones/anular'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    const csrf = <?= json_encode($csrf, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    let currentPage = <?= (int) ($pagination['page'] ?? 1); ?>;
    let totalPages = <?= (int) ($pagination['pages'] ?? 1); ?>;
    const pageLimit = <?= (int) ($pagination['limit'] ?? 25); ?>;
    let pendingAssignForm = null;
    let pendingAnnulForm = null;
    let timer = null;

    if (!form || !rowsContainer) {
        return;
    }

    const escapeHtml = function (value) {
        return String(value ?? '').replace(/[&<>"']/g, function (char) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[char];
        });
    };

    const money = function (value) {
        return '$' + Number(value || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    };

    const referenceMoney = function (reference, key, labelKey) {
        if (reference && reference[labelKey] !== null && reference[labelKey] !== undefined) {
            return String(reference[labelKey]);
        }

        if (reference && reference[key] !== null && reference[key] !== undefined) {
            return money(reference[key]);
        }

        return 'Sin datos';
    };

    const referenceValue = function (reference, key, labelKey) {
        if (reference && reference[labelKey] !== null && reference[labelKey] !== undefined) {
            return String(reference[labelKey]);
        }

        if (reference && reference[key] !== null && reference[key] !== undefined) {
            return String(reference[key]);
        }

        return 'Sin datos';
    };

    const rowStatus = function (row) {
        const expected = parseInt(row.cfocantidad_pensiones || '0', 10);

        if (expected <= 0 || !row.pension_cfoid || !row.matricula_cfoid) {
            return 'Sin configuracion';
        }

        if (parseInt(row.total_obligaciones || '0', 10) > 0) {
            return 'Asignado';
        }

        return 'Pendiente';
    };

    const scholarshipPercent = function (row) {
        const base = Number(row.pension_valor || 0);

        if (row.pension_descuento_tipo === 'PORCENTAJE') {
            return Number(row.pension_descuento_valor || 0).toFixed(2);
        }

        if (row.pension_descuento_tipo === 'VALOR_FIJO' && base > 0) {
            return ((Number(row.pension_valor_descuento || 0) / base) * 100).toFixed(2);
        }

        return '0.00';
    };

    const scholarshipAmount = function (row) {
        return Number(row.pension_valor_descuento || 0).toFixed(2);
    };

    const finalPension = function (row) {
        return Number(row.pension_valor_final || row.pension_valor || 0).toFixed(2);
    };

    const syncScholarshipInputs = function (container) {
        const scope = container || document;
        scope.querySelectorAll('[data-scholarship-percent]').forEach(function (percentInput) {
            const row = percentInput.closest('tr');
            const amountInput = row ? row.querySelector('[data-scholarship-amount]') : null;
            const finalInput = row ? row.querySelector('[data-final-pension]') : null;
            const base = Number(percentInput.getAttribute('data-base-pension') || 0);

            const updateFromPercent = function () {
                const percent = Math.max(0, Math.min(100, Number(percentInput.value || 0)));
                const amount = Math.min(base, base * (percent / 100));

                if (amountInput) {
                    amountInput.value = amount.toFixed(2);
                }

                if (finalInput) {
                    finalInput.value = Math.max(0, base - amount).toFixed(2);
                }
            };

            const updateFromAmount = function () {
                const amount = amountInput ? Math.max(0, Math.min(base, Number(amountInput.value || 0))) : 0;
                const percent = base > 0 ? (amount / base) * 100 : 0;

                percentInput.value = percent.toFixed(2);

                if (finalInput) {
                    finalInput.value = Math.max(0, base - amount).toFixed(2);
                }
            };

            const normalizeAmount = function () {
                if (!amountInput) {
                    return;
                }

                const amount = Math.max(0, Math.min(base, Number(amountInput.value || 0)));
                amountInput.value = amount.toFixed(2);
                updateFromAmount();
            };

            percentInput.addEventListener('input', updateFromPercent);

            if (amountInput) {
                amountInput.addEventListener('input', updateFromAmount);
                amountInput.addEventListener('blur', normalizeAmount);
            }

            updateFromPercent();
        });
    };

    const renderRows = function (rows, meta) {
        const pagination = meta || {};
        currentPage = parseInt(pagination.page || currentPage || '1', 10);
        totalPages = parseInt(pagination.pages || totalPages || '1', 10);

        rowsContainer.innerHTML = rows.map(function (row) {
            const status = rowStatus(row);
            const disabled = status === 'Sin configuracion' ? 'disabled' : '';
            const assigned = status === 'Asignado';
            const student = `${row.perapellidos || ''} ${row.pernombres || ''}`.trim();
            const courseName = `${row.granombre || ''} ${row.prlnombre || ''}`.trim();
            const formId = `accounting-obligation-form-${row.matid || ''}`;
            const basePension = Number(row.pension_valor || 0).toFixed(2);
            const assignedActions = assigned ? `
                            <button class="icon-button icon-button-edit" type="button" title="Editar valores" aria-label="Editar valores" data-obligation-row-edit>
                                <i class="fa fa-pencil" aria-hidden="true"></i>
                            </button>
                            <button class="icon-button icon-button-cancel" type="button" title="Cancelar edicion" aria-label="Cancelar edicion" data-obligation-row-cancel hidden>
                                <i class="fa fa-times" aria-hidden="true"></i>
                            </button>
                            <button class="icon-button icon-button-view" type="button" title="Ver obligaciones" aria-label="Ver obligaciones" data-obligation-detail data-matid="${escapeHtml(row.matid || '')}">
                                <i class="fa fa-search" aria-hidden="true"></i>
                            </button>
            ` : '';
            const assignTitle = assigned ? 'Actualizar obligaciones' : 'Asignar obligaciones';
            const assignIcon = assigned ? 'fa-refresh' : 'fa-check-square-o';

            return `
                <tr>
                    <td>
                        <strong>${escapeHtml(student)}</strong>
                        <span class="cell-subtitle">${escapeHtml(row.percedula || '')}</span>
                    </td>
                    <td>${escapeHtml(courseName)}</td>
                    <td><input class="table-input accounting-scholarship-percent-input" form="${escapeHtml(formId)}" type="number" name="beca_porcentaje" min="0" max="100" step="0.01" value="${escapeHtml(scholarshipPercent(row))}" readonly data-scholarship-percent data-base-pension="${escapeHtml(basePension)}"></td>
                    <td><input class="table-input accounting-scholarship-amount-input" form="${escapeHtml(formId)}" type="number" name="beca_valor" min="0" step="0.01" value="${escapeHtml(scholarshipAmount(row))}" readonly data-scholarship-amount></td>
                    <td><input class="table-input" type="number" min="0" step="0.01" value="${escapeHtml(finalPension(row))}" readonly data-final-pension></td>
                    <td><span class="state-pill ${status === 'Asignado' ? 'state-pill-active' : 'state-pill-inactive'}">${escapeHtml(status)}</span></td>
                    <td>
                        <form class="accounting-actions-inline" id="${escapeHtml(formId)}" method="POST" action="${escapeHtml(generateEndpoint)}">
                            <input type="hidden" name="_csrf_token" value="${escapeHtml(csrf)}">
                            <input type="hidden" name="matid" value="${escapeHtml(row.matid || '')}">
                            ${assignedActions}
                            <button class="icon-button icon-button-save" type="submit" title="${escapeHtml(assignTitle)}" aria-label="${escapeHtml(assignTitle)}" ${disabled} data-obligation-row-assign>
                                <i class="fa ${escapeHtml(assignIcon)}" aria-hidden="true"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            `;
        }).join('');

        const total = parseInt(pagination.total || rows.length || '0', 10);

        if (emptyState) {
            emptyState.hidden = rows.length > 0;
        }

        if (statusLabel) {
            const start = total === 0 ? 0 : ((currentPage - 1) * pageLimit) + 1;
            const end = total === 0 ? 0 : start + rows.length - 1;
            statusLabel.textContent = `${start}-${end} de ${total} registro(s)`;
        }

        if (pageStatus) {
            pageStatus.textContent = `Pagina ${currentPage} de ${totalPages}`;
        }

        if (prevPageButton) {
            prevPageButton.disabled = currentPage <= 1;
        }

        if (nextPageButton) {
            nextPageButton.disabled = currentPage >= totalPages;
        }

        const reference = pagination.reference || {};

        if (referenceMatriculation) {
            referenceMatriculation.textContent = referenceMoney(reference, 'matricula', 'matricula_label');
        }

        if (referencePension) {
            referencePension.textContent = referenceMoney(reference, 'pension', 'pension_label');
        }

        if (referenceMonths) {
            referenceMonths.textContent = referenceValue(reference, 'meses', 'meses_label');
        }

        syncScholarshipInputs(rowsContainer);
    };

    const fetchRows = async function (page) {
        const url = new URL(endpoint, window.location.origin);
        url.searchParams.set('ajax', '1');
        url.searchParams.set('q', search ? search.value : '');
        url.searchParams.set('nivel', level ? level.value : '0');
        url.searchParams.set('curso', course ? course.value : '0');
        url.searchParams.set('page', String(page || currentPage || 1));
        url.searchParams.set('limit', String(pageLimit));

        const response = await fetch(url.toString(), {headers: {'Accept': 'application/json'}});
        const payload = await response.json();
        renderRows(Array.isArray(payload.rows) ? payload.rows : [], payload);
    };

    const scheduleFetch = function () {
        window.clearTimeout(timer);
        timer = window.setTimeout(function () {
            currentPage = 1;
            fetchRows(1);
        }, 250);
    };

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        currentPage = 1;
        fetchRows(1);
    });

    [search, level, course].forEach(function (input) {
        if (!input) {
            return;
        }

        input.addEventListener(input.tagName === 'SELECT' ? 'change' : 'input', scheduleFetch);
    });

    if (prevPageButton) {
        prevPageButton.addEventListener('click', function () {
            if (currentPage > 1) {
                fetchRows(currentPage - 1);
            }
        });
    }

    if (nextPageButton) {
        nextPageButton.addEventListener('click', function () {
            if (currentPage < totalPages) {
                fetchRows(currentPage + 1);
            }
        });
    }

    document.addEventListener('submit', function (event) {
        const assignForm = event.target.closest('form[action$="/contabilidad/obligaciones/generar"]');

        if (!assignForm || assignForm.dataset.confirmed === '1') {
            return;
        }

        event.preventDefault();
        pendingAssignForm = assignForm;

        if (confirmModal) {
            confirmModal.hidden = false;
        }
    });

    if (confirmAccept) {
        confirmAccept.addEventListener('click', function () {
            if (!pendingAssignForm) {
                return;
            }

            pendingAssignForm.dataset.confirmed = '1';
            pendingAssignForm.submit();
        });
    }

    confirmCancelButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            pendingAssignForm = null;

            if (confirmModal) {
                confirmModal.hidden = true;
            }
        });
    });

    const renderDetailRows = function (rows) {
        if (!detailRows || !detailEmpty) {
            return;
        }

        detailRows.innerHTML = rows.map(function (row) {
            const disabled = row.cobestado === 'ANULADO' || Number(row.cobvalor_pagado || 0) > 0 ? 'disabled' : '';

            return `
                <tr>
                    <td>${escapeHtml(row.cobdescripcion || '')}</td>
                    <td>${escapeHtml(row.cobfecha_vencimiento || '')}</td>
                    <td class="accounting-detail-value-column">
                        <form method="POST" action="${escapeHtml(updateEndpoint)}" id="obligation-update-${escapeHtml(row.cobid)}">
                            <input type="hidden" name="_csrf_token" value="${escapeHtml(csrf)}">
                            <input type="hidden" name="cobid" value="${escapeHtml(row.cobid)}">
                            <input class="table-input accounting-detail-value-input" type="number" name="cobvalor_final" min="0" max="999.99" step="0.01" value="${Number(row.cobvalor_final || 0).toFixed(2)}" readonly data-detail-value-input>
                        </form>
                    </td>
                    <td>${escapeHtml(money(row.cobvalor_pagado))}</td>
                    <td>${escapeHtml(money(row.cobsaldo_pendiente))}</td>
                    <td><span class="state-pill ${row.cobestado === 'PAGADO' ? 'state-pill-active' : 'state-pill-inactive'}">${escapeHtml(row.cobestado || '')}</span></td>
                    <td>
                        <div class="accounting-actions-inline">
                        <button class="icon-button icon-button-edit" type="button" title="Editar" aria-label="Editar" data-detail-row-edit ${row.cobestado === 'ANULADO' ? 'disabled' : ''}>
                            <i class="fa fa-pencil" aria-hidden="true"></i>
                        </button>
                        <button class="icon-button icon-button-cancel" type="button" title="Cancelar edicion" aria-label="Cancelar edicion" data-detail-row-cancel hidden>
                            <i class="fa fa-times" aria-hidden="true"></i>
                        </button>
                        <button class="icon-button icon-button-save" type="submit" form="obligation-update-${escapeHtml(row.cobid)}" title="Actualizar" aria-label="Actualizar" disabled data-detail-row-save>
                            <i class="fa fa-floppy-o" aria-hidden="true"></i>
                        </button>
                        <form method="POST" action="${escapeHtml(annulEndpoint)}" class="inline-form" data-obligation-annul-form>
                            <input type="hidden" name="_csrf_token" value="${escapeHtml(csrf)}">
                            <input type="hidden" name="cobid" value="${escapeHtml(row.cobid)}">
                            <input type="hidden" name="motivo" value="Anulacion desde gestion contable">
                            <button class="icon-button icon-button-delete" type="submit" title="Anular" aria-label="Anular" ${disabled}>
                                <i class="fa fa-trash" aria-hidden="true"></i>
                            </button>
                        </form>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        detailEmpty.hidden = rows.length > 0;
    };

    document.addEventListener('click', async function (event) {
        const rowEditButton = event.target.closest('[data-obligation-row-edit]');

        if (rowEditButton) {
            const row = rowEditButton.closest('tr');
            const percentInput = row ? row.querySelector('[data-scholarship-percent]') : null;
            const amountInput = row ? row.querySelector('[data-scholarship-amount]') : null;
            const cancelButton = row ? row.querySelector('[data-obligation-row-cancel]') : null;

            [percentInput, amountInput].forEach(function (input) {
                if (input && !input.dataset.originalValue) {
                    input.dataset.originalValue = input.value;
                }
            });

            [percentInput, amountInput].forEach(function (input) {
                if (input) {
                    input.readOnly = false;
                }
            });

            rowEditButton.hidden = true;

            if (cancelButton) {
                cancelButton.hidden = false;
            }

            if (percentInput) {
                percentInput.focus();
                percentInput.select();
            }

            return;
        }

        const rowCancelButton = event.target.closest('[data-obligation-row-cancel]');

        if (rowCancelButton) {
            const row = rowCancelButton.closest('tr');
            const percentInput = row ? row.querySelector('[data-scholarship-percent]') : null;
            const amountInput = row ? row.querySelector('[data-scholarship-amount]') : null;
            const finalInput = row ? row.querySelector('[data-final-pension]') : null;
            const editButton = row ? row.querySelector('[data-obligation-row-edit]') : null;

            [percentInput, amountInput].forEach(function (input) {
                if (input) {
                    input.value = input.dataset.originalValue || input.value;
                    input.readOnly = true;
                }
            });

            if (finalInput && percentInput) {
                const base = Number(percentInput.getAttribute('data-base-pension') || 0);
                const percent = Number(percentInput.value || 0);
                finalInput.value = Math.max(0, base - (base * (percent / 100))).toFixed(2);
            }

            rowCancelButton.hidden = true;

            if (editButton) {
                editButton.hidden = false;
            }

            return;
        }

        const detailEditButton = event.target.closest('[data-detail-row-edit]');

        if (detailEditButton) {
            const row = detailEditButton.closest('tr');
            const valueInput = row ? row.querySelector('[data-detail-value-input]') : null;
            const saveButton = row ? row.querySelector('[data-detail-row-save]') : null;
            const cancelButton = row ? row.querySelector('[data-detail-row-cancel]') : null;

            if (valueInput) {
                if (!valueInput.dataset.originalValue) {
                    valueInput.dataset.originalValue = valueInput.value;
                }

                valueInput.readOnly = false;
                valueInput.focus();
                valueInput.select();
            }

            if (saveButton) {
                saveButton.disabled = false;
            }

            detailEditButton.hidden = true;

            if (cancelButton) {
                cancelButton.hidden = false;
            }

            return;
        }

        const detailCancelButton = event.target.closest('[data-detail-row-cancel]');

        if (detailCancelButton) {
            const row = detailCancelButton.closest('tr');
            const valueInput = row ? row.querySelector('[data-detail-value-input]') : null;
            const saveButton = row ? row.querySelector('[data-detail-row-save]') : null;
            const editButton = row ? row.querySelector('[data-detail-row-edit]') : null;

            if (valueInput) {
                valueInput.value = valueInput.dataset.originalValue || valueInput.value;
                valueInput.readOnly = true;
            }

            if (saveButton) {
                saveButton.disabled = true;
            }

            detailCancelButton.hidden = true;

            if (editButton) {
                editButton.hidden = false;
            }

            return;
        }

        const detailButton = event.target.closest('[data-obligation-detail]');

        if (!detailButton) {
            return;
        }

        const url = new URL(detailEndpoint, window.location.origin);
        url.searchParams.set('matid', detailButton.getAttribute('data-matid') || '');
        const response = await fetch(url.toString(), {headers: {'Accept': 'application/json'}});
        const payload = await response.json();
        renderDetailRows(Array.isArray(payload.rows) ? payload.rows : []);

        if (detailModal) {
            detailModal.hidden = false;
        }
    });

    if (detailClose) {
        detailClose.addEventListener('click', function () {
            if (detailModal) {
                detailModal.hidden = true;
            }
        });
    }

    document.addEventListener('submit', function (event) {
        const annulForm = event.target.closest('[data-obligation-annul-form]');

        if (!annulForm || annulForm.dataset.confirmed === '1') {
            return;
        }

        event.preventDefault();
        pendingAnnulForm = annulForm;

        if (annulConfirmModal) {
            annulConfirmModal.hidden = false;
        }
    });

    if (annulAccept) {
        annulAccept.addEventListener('click', function () {
            if (!pendingAnnulForm) {
                return;
            }

            pendingAnnulForm.dataset.confirmed = '1';
            pendingAnnulForm.submit();
        });
    }

    annulCancelButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            pendingAnnulForm = null;

            if (annulConfirmModal) {
                annulConfirmModal.hidden = true;
            }
        });
    });

    syncScholarshipInputs(document);
});
</script>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
