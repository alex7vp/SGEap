<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$money = static fn (mixed $value): string => '$' . number_format((float) $value, 2, '.', ',');
$receipts = is_array($receipts ?? null) ? $receipts : [];
$courses = is_array($courses ?? null) ? $courses : [];
$filters = is_array($filters ?? null) ? $filters : [];
$pagination = is_array($pagination ?? null) ? $pagination : ['page' => 1, 'pages' => 1, 'total' => count($receipts), 'limit' => 25];
$csrf = csrfToken();
$canReversePayments = !empty($canReversePayments);
$status = in_array((string) ($status ?? 'EN_REVISION'), ['EN_REVISION', 'APROBADO', 'RECHAZADO', 'REVERSADO'], true) ? (string) $status : 'EN_REVISION';
$statusLabels = [
    'EN_REVISION' => 'En revision',
    'APROBADO' => 'Aprobado',
    'RECHAZADO' => 'Rechazado',
    'REVERSADO' => 'Reversado',
];
$statusFilterLabels = [
    'EN_REVISION' => 'En revision',
    'APROBADO' => 'Aprobados',
    'RECHAZADO' => 'Rechazados',
    'REVERSADO' => 'Reversados',
];
$receiptPayload = static function (array $receipt) use ($statusLabels): string {
    $receipt['archivo_url'] = !empty($receipt['cpagarchivo_ruta']) ? asset((string) $receipt['cpagarchivo_ruta']) : '';
    $receipt['estado_label'] = $statusLabels[(string) ($receipt['cpagestado'] ?? '')] ?? (string) ($receipt['cpagestado'] ?? '');

    return htmlspecialchars(json_encode($receipt, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
};
?>

<?php if (empty($currentPeriod)): ?>
    <div class="empty-state">No hay un periodo lectivo seleccionado para revisar comprobantes.</div>
<?php else: ?>
    <p class="module-note">Revise el comprobante desde el detalle y registre la aprobacion o rechazo segun corresponda.</p>

    <section class="security-assignment-block">
        <form class="toolbar toolbar-filter accounting-receipts-toolbar" method="GET" action="<?= $h(baseUrl('contabilidad/comprobantes')); ?>" data-accounting-receipt-filter>
            <div class="filter-box filter-box-compact">
                <label class="sr-only" for="receipt-status">Estado</label>
                <select id="receipt-status" name="estado" data-accounting-receipt-status>
                    <?php foreach ($statusFilterLabels as $key => $label): ?>
                        <option value="<?= $h($key); ?>" <?= $status === $key ? 'selected' : ''; ?>><?= $h($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-box">
                <label class="sr-only" for="receipt-search">Buscar estudiante</label>
                <input id="receipt-search" type="search" name="q" value="<?= $h($filters['q'] ?? ''); ?>" placeholder="Buscar estudiante" data-accounting-receipt-search>
            </div>
            <div class="filter-box filter-box-compact">
                <label class="sr-only" for="receipt-course">Curso</label>
                <select id="receipt-course" name="curso" data-accounting-receipt-course>
                    <option value="0">Todos los cursos</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?= $h($course['curid'] ?? ''); ?>" <?= (int) ($filters['curso'] ?? 0) === (int) ($course['curid'] ?? 0) ? 'selected' : ''; ?>>
                            <?= $h(trim((string) (($course['nednombre'] ?? '') . ' | ' . ($course['granombre'] ?? '') . ' ' . ($course['prlnombre'] ?? '')))); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="btn-secondary btn-auto" type="submit">
                <i class="fa fa-filter" aria-hidden="true"></i>
                Filtrar
            </button>
            <span class="table-status" data-accounting-receipt-count><?= count($receipts); ?> de <?= $h($pagination['total'] ?? count($receipts)); ?> registro(s)</span>
        </form>

        <table class="data-table accounting-receipts-table" data-accounting-receipt-table <?= $receipts === [] ? 'hidden' : ''; ?>>
            <thead>
                <tr>
                    <th>Estudiante</th>
                    <th>Curso</th>
                    <th>Obligacion</th>
                    <th>Valor establecido</th>
                    <th>Saldo</th>
                    <th>Estado</th>
                    <th>Registro</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody data-accounting-receipt-rows>
                <?php foreach ($receipts as $receipt): ?>
                    <?php
                    $studentName = trim((string) (($receipt['perapellidos'] ?? '') . ' ' . ($receipt['pernombres'] ?? '')));
                    $courseName = trim((string) (($receipt['granombre'] ?? '') . ' ' . ($receipt['prlnombre'] ?? '')));
                    $receiptStatus = (string) ($receipt['cpagestado'] ?? '');
                    ?>
                    <tr>
                        <td>
                            <strong><?= $h($studentName !== '' ? $studentName : 'Estudiante'); ?></strong>
                            <span class="cell-subtitle"><?= $h($receipt['percedula'] ?? ''); ?></span>
                        </td>
                        <td><?= $h($courseName); ?></td>
                        <td><?= $h($receipt['cobdescripcion'] ?? ''); ?></td>
                        <td><?= $h($money($receipt['cpagvalor_reportado'] ?? 0)); ?></td>
                        <td><?= $h($money($receipt['cobsaldo_pendiente'] ?? 0)); ?></td>
                        <td><span class="state-pill <?= $receiptStatus === 'APROBADO' ? 'state-pill-active' : 'state-pill-inactive'; ?>"><?= $h($statusLabels[$receiptStatus] ?? $receiptStatus); ?></span></td>
                        <td><?= $h($receipt['cpagfecha_registro'] ?? ''); ?></td>
                        <td>
                            <button class="icon-button icon-button-view" type="button" title="Ver detalle" aria-label="Ver detalle" data-receipt-detail data-receipt="<?= $receiptPayload($receipt); ?>">
                                <i class="fa fa-search" aria-hidden="true"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="actions-row accounting-pagination" data-accounting-receipt-pagination <?= $receipts === [] ? 'hidden' : ''; ?>>
            <button class="btn-secondary btn-auto" type="button" data-accounting-receipt-prev <?= (int) ($pagination['page'] ?? 1) <= 1 ? 'disabled' : ''; ?>>
                <i class="fa fa-chevron-left" aria-hidden="true"></i>
                Anterior
            </button>
            <span class="table-status" data-accounting-receipt-page-status>Pagina <?= $h($pagination['page'] ?? 1); ?> de <?= $h($pagination['pages'] ?? 1); ?></span>
            <button class="btn-secondary btn-auto" type="button" data-accounting-receipt-next <?= (int) ($pagination['page'] ?? 1) >= (int) ($pagination['pages'] ?? 1) ? 'disabled' : ''; ?>>
                Siguiente
                <i class="fa fa-chevron-right" aria-hidden="true"></i>
            </button>
        </div>
        <div class="empty-state" data-accounting-receipt-empty <?= $receipts === [] ? '' : 'hidden'; ?>>No existen comprobantes con los filtros seleccionados.</div>
    </section>
<?php endif; ?>

<div class="modal-backdrop" data-receipt-detail-modal hidden>
    <div class="modal-card accounting-receipt-detail-modal" role="dialog" aria-modal="true" aria-labelledby="receipt-detail-title">
        <div class="modal-header">
            <h3 id="receipt-detail-title">Detalle del comprobante</h3>
            <button class="modal-close" type="button" aria-label="Cerrar" data-receipt-detail-close>
                <i class="fa fa-times" aria-hidden="true"></i>
            </button>
        </div>
        <div class="modal-body">
            <dl class="accounting-detail-list" data-receipt-detail-summary></dl>
            <div class="accounting-detail-file" data-receipt-detail-file></div>

            <form method="POST" data-receipt-review-form class="accounting-review-modal-form">
                <?= csrfField(); ?>
                <input type="hidden" name="cpagid" data-receipt-review-id>
                <div class="segmented-options" data-receipt-review-options>
                    <label>
                        <input type="radio" name="revision_estado" value="APROBADO" checked data-receipt-review-state>
                        <span>Aprobar</span>
                    </label>
                    <label>
                        <input type="radio" name="revision_estado" value="RECHAZADO" data-receipt-review-state>
                        <span>Rechazar</span>
                    </label>
                </div>

                <div class="form-grid" data-receipt-approval-fields>
                    <label>
                        <span>Valor aprobado</span>
                        <input type="number" name="valor_aprobado" min="0.01" step="0.01" data-receipt-approved-value>
                    </label>
                    <label>
                        <span>Numero de factura</span>
                        <input type="text" name="factura" maxlength="80" placeholder="Numero de factura">
                    </label>
                    <label class="form-group-full">
                        <span>Observacion interna</span>
                        <input type="text" name="observacion" maxlength="250" data-receipt-observation>
                    </label>
                    <label class="checkbox-inline form-group-full" data-receipt-duplicate-field hidden>
                        <input type="checkbox" name="confirmar_duplicado" value="1" data-receipt-duplicate-check>
                        <span>Continuar pese al duplicado</span>
                    </label>
                </div>

                <div class="form-grid" data-receipt-reject-fields hidden>
                    <label class="form-group-full">
                        <span>Motivo de rechazo</span>
                        <input type="text" name="motivo" maxlength="250" data-receipt-reject-reason>
                    </label>
                </div>
            </form>

            <form method="POST" action="<?= $h(baseUrl('contabilidad/pagos/reversar')); ?>" data-receipt-reverse-form class="accounting-review-modal-form" hidden>
                <?= csrfField(); ?>
                <input type="hidden" name="cpagid" data-receipt-reverse-id>
                <div class="form-grid">
                    <label class="form-group-full">
                        <span>Motivo de reverso</span>
                        <input type="text" name="motivo_reverso" maxlength="250" required data-receipt-reverse-reason>
                    </label>
                </div>
            </form>

            <div class="empty-state" data-receipt-readonly-note hidden></div>
        </div>
        <div class="modal-actions">
            <button class="btn-secondary btn-auto" type="button" data-receipt-detail-close>Cancelar</button>
            <button class="btn-secondary btn-auto" type="submit" form="" data-receipt-reverse-save hidden>Reversar</button>
            <button class="btn-primary btn-auto" type="submit" form="" data-receipt-review-save>Guardar</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.querySelector('[data-accounting-receipt-filter]');
    const statusInput = document.querySelector('[data-accounting-receipt-status]');
    const searchInput = document.querySelector('[data-accounting-receipt-search]');
    const courseInput = document.querySelector('[data-accounting-receipt-course]');
    const table = document.querySelector('[data-accounting-receipt-table]');
    const rowsContainer = document.querySelector('[data-accounting-receipt-rows]');
    const emptyState = document.querySelector('[data-accounting-receipt-empty]');
    const countLabel = document.querySelector('[data-accounting-receipt-count]');
    const prevButton = document.querySelector('[data-accounting-receipt-prev]');
    const nextButton = document.querySelector('[data-accounting-receipt-next]');
    const pageStatus = document.querySelector('[data-accounting-receipt-page-status]');
    const paginationBlock = document.querySelector('[data-accounting-receipt-pagination]');
    const detailModal = document.querySelector('[data-receipt-detail-modal]');
    const detailSummary = document.querySelector('[data-receipt-detail-summary]');
    const detailFile = document.querySelector('[data-receipt-detail-file]');
    const detailCloseButtons = document.querySelectorAll('[data-receipt-detail-close]');
    const reviewForm = document.querySelector('[data-receipt-review-form]');
    const reviewId = document.querySelector('[data-receipt-review-id]');
    const reviewOptions = document.querySelector('[data-receipt-review-options]');
    const approvalFields = document.querySelector('[data-receipt-approval-fields]');
    const rejectFields = document.querySelector('[data-receipt-reject-fields]');
    const approvedValue = document.querySelector('[data-receipt-approved-value]');
    const observation = document.querySelector('[data-receipt-observation]');
    const duplicateField = document.querySelector('[data-receipt-duplicate-field]');
    const duplicateCheck = document.querySelector('[data-receipt-duplicate-check]');
    const rejectReason = document.querySelector('[data-receipt-reject-reason]');
    const readonlyNote = document.querySelector('[data-receipt-readonly-note]');
    const saveButton = document.querySelector('[data-receipt-review-save]');
    const reverseForm = document.querySelector('[data-receipt-reverse-form]');
    const reverseId = document.querySelector('[data-receipt-reverse-id]');
    const reverseReason = document.querySelector('[data-receipt-reverse-reason]');
    const reverseButton = document.querySelector('[data-receipt-reverse-save]');
    const endpoint = <?= json_encode(baseUrl('contabilidad/comprobantes'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    const approveEndpoint = <?= json_encode(baseUrl('contabilidad/comprobantes/aprobar'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    const rejectEndpoint = <?= json_encode(baseUrl('contabilidad/comprobantes/rechazar'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    const canReversePayments = <?= $canReversePayments ? 'true' : 'false'; ?>;
    const assetBase = <?= json_encode(rtrim(asset(''), '/') . '/', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    const statusLabels = <?= json_encode($statusLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    const pageLimit = <?= (int) ($pagination['limit'] ?? 25); ?>;
    let currentPage = <?= (int) ($pagination['page'] ?? 1); ?>;
    let totalPages = <?= (int) ($pagination['pages'] ?? 1); ?>;
    let timer = null;

    const escapeHtml = function (value) {
        return String(value ?? '').replace(/[&<>"']/g, function (char) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[char];
        });
    };

    const money = function (value) {
        return '$' + Number(value || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    };

    const fileUrl = function (path) {
        return path ? assetBase + String(path).replace(/^\/+/, '') : '';
    };

    const receiptPayload = function (receipt) {
        const payload = Object.assign({}, receipt);
        payload.archivo_url = fileUrl(receipt.cpagarchivo_ruta || '');
        payload.estado_label = statusLabels[receipt.cpagestado] || receipt.cpagestado || '';
        return payload;
    };

    const renderRows = function (rows, meta) {
        const pagination = meta || {};
        currentPage = parseInt(pagination.page || currentPage || '1', 10);
        totalPages = parseInt(pagination.pages || totalPages || '1', 10);

        if (rowsContainer) {
            rowsContainer.innerHTML = rows.map(function (receipt) {
                const payload = receiptPayload(receipt);
                const student = `${receipt.perapellidos || ''} ${receipt.pernombres || ''}`.trim() || 'Estudiante';
                const course = `${receipt.granombre || ''} ${receipt.prlnombre || ''}`.trim();
                const statusClass = receipt.cpagestado === 'APROBADO' ? 'state-pill-active' : 'state-pill-inactive';

                return `
                    <tr>
                        <td>
                            <strong>${escapeHtml(student)}</strong>
                            <span class="cell-subtitle">${escapeHtml(receipt.percedula || '')}</span>
                        </td>
                        <td>${escapeHtml(course)}</td>
                        <td>${escapeHtml(receipt.cobdescripcion || '')}</td>
                        <td>${escapeHtml(money(receipt.cpagvalor_reportado || 0))}</td>
                        <td>${escapeHtml(money(receipt.cobsaldo_pendiente || 0))}</td>
                        <td><span class="state-pill ${statusClass}">${escapeHtml(payload.estado_label)}</span></td>
                        <td>${escapeHtml(receipt.cpagfecha_registro || '')}</td>
                        <td>
                            <button class="icon-button icon-button-view" type="button" title="Ver detalle" aria-label="Ver detalle" data-receipt-detail data-receipt="${escapeHtml(JSON.stringify(payload))}">
                                <i class="fa fa-search" aria-hidden="true"></i>
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        const total = parseInt(pagination.total || rows.length || '0', 10);

        if (table) {
            table.hidden = rows.length === 0;
        }

        if (emptyState) {
            emptyState.hidden = rows.length > 0;
        }

        if (paginationBlock) {
            paginationBlock.hidden = rows.length === 0;
        }

        if (countLabel) {
            const start = total === 0 ? 0 : ((currentPage - 1) * pageLimit) + 1;
            const end = total === 0 ? 0 : start + rows.length - 1;
            countLabel.textContent = `${start}-${end} de ${total} registro(s)`;
        }

        if (pageStatus) {
            pageStatus.textContent = `Pagina ${currentPage} de ${totalPages}`;
        }

        if (prevButton) {
            prevButton.disabled = currentPage <= 1;
        }

        if (nextButton) {
            nextButton.disabled = currentPage >= totalPages;
        }
    };

    const fetchRows = async function (page) {
        const url = new URL(endpoint, window.location.origin);
        url.searchParams.set('ajax', '1');
        url.searchParams.set('estado', statusInput ? statusInput.value : 'EN_REVISION');
        url.searchParams.set('q', searchInput ? searchInput.value : '');
        url.searchParams.set('curso', courseInput ? courseInput.value : '0');
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

    const showReviewFields = function () {
        const state = reviewForm ? String(new FormData(reviewForm).get('revision_estado') || 'APROBADO') : 'APROBADO';
        const approving = state === 'APROBADO';

        if (approvalFields) {
            approvalFields.hidden = !approving;
        }

        if (rejectFields) {
            rejectFields.hidden = approving;
        }

        if (approvedValue) {
            approvedValue.required = approving;
        }

        if (rejectReason) {
            rejectReason.required = !approving;
        }

        if (observation) {
            observation.required = approving && duplicateField && !duplicateField.hidden;
        }

        if (duplicateCheck) {
            duplicateCheck.required = approving && duplicateField && !duplicateField.hidden;
        }
    };

    const openDetail = function (receipt) {
        const student = `${receipt.perapellidos || ''} ${receipt.pernombres || ''}`.trim() || 'Estudiante';
        const course = `${receipt.granombre || ''} ${receipt.prlnombre || ''}`.trim();
        const duplicateId = parseInt(receipt.duplicado_cpagid || '0', 10);
        const inReview = receipt.cpagestado === 'EN_REVISION';
        const canReverseThisReceipt = canReversePayments && receipt.cpagestado === 'APROBADO';

        if (detailSummary) {
            detailSummary.innerHTML = `
                <div><dt>Estudiante</dt><dd>${escapeHtml(student)}</dd></div>
                <div><dt>Curso</dt><dd>${escapeHtml(course)}</dd></div>
                <div><dt>Obligacion</dt><dd>${escapeHtml(receipt.cobdescripcion || '')}</dd></div>
                <div><dt>Estado</dt><dd>${escapeHtml(receipt.estado_label || receipt.cpagestado || '')}</dd></div>
                <div><dt>Registro</dt><dd>${escapeHtml(receipt.cpagfecha_registro || '')}</dd></div>
                <div><dt>Valor establecido</dt><dd>${escapeHtml(money(receipt.cpagvalor_reportado || 0))}</dd></div>
                <div><dt>Saldo pendiente</dt><dd>${escapeHtml(money(receipt.cobsaldo_pendiente || 0))}</dd></div>
                ${receipt.cpagvalor_aprobado ? `<div><dt>Valor aprobado</dt><dd>${escapeHtml(money(receipt.cpagvalor_aprobado))}</dd></div>` : ''}
                ${receipt.cpagdocumento_externo_numero ? `<div><dt>Factura</dt><dd>${escapeHtml(receipt.cpagdocumento_externo_numero)}</dd></div>` : ''}
                ${receipt.cpagobservacion_interna ? `<div><dt>Observacion</dt><dd>${escapeHtml(receipt.cpagobservacion_interna)}</dd></div>` : ''}
                ${receipt.cpagmotivo_rechazo ? `<div><dt>Motivo rechazo</dt><dd>${escapeHtml(receipt.cpagmotivo_rechazo)}</dd></div>` : ''}
                ${receipt.cpagmotivo_reverso ? `<div><dt>Motivo reverso</dt><dd>${escapeHtml(receipt.cpagmotivo_reverso)}</dd></div>` : ''}
                ${duplicateId > 0 ? `<div><dt>Duplicado</dt><dd>Posible duplicado #${escapeHtml(duplicateId)}</dd></div>` : ''}
            `;
        }

        if (detailFile) {
            detailFile.innerHTML = receipt.archivo_url
                ? `<a class="btn-secondary btn-auto" href="${escapeHtml(receipt.archivo_url)}" target="_blank" rel="noopener"><i class="fa fa-file-text-o" aria-hidden="true"></i> Ver comprobante</a><span>${escapeHtml(receipt.cpagarchivo_nombre || '')}</span>`
                : '<span class="state-pill">Sin archivo</span>';
        }

        if (reviewForm) {
            reviewForm.hidden = !inReview;
            reviewForm.action = approveEndpoint;
        }

        if (reviewId) {
            reviewId.value = receipt.cpagid || '';
        }

        if (reverseForm) {
            reverseForm.hidden = !canReverseThisReceipt;
        }

        if (reverseId) {
            reverseId.value = receipt.cpagid || '';
        }

        if (reverseReason) {
            reverseReason.value = '';
        }

        if (approvedValue) {
            approvedValue.value = Number(receipt.cobsaldo_pendiente || receipt.cpagvalor_reportado || 0).toFixed(2);
        }

        if (observation) {
            observation.value = '';
        }

        if (rejectReason) {
            rejectReason.value = '';
        }

        if (duplicateField) {
            duplicateField.hidden = duplicateId <= 0;
        }

        if (duplicateCheck) {
            duplicateCheck.checked = false;
        }

        if (reviewOptions) {
            const approveRadio = reviewOptions.querySelector('input[value="APROBADO"]');

            if (approveRadio) {
                approveRadio.checked = true;
            }
        }

        if (readonlyNote) {
            readonlyNote.hidden = inReview || canReverseThisReceipt;
            readonlyNote.textContent = inReview || canReverseThisReceipt ? '' : 'Este comprobante ya fue revisado y no puede modificarse desde esta ventana.';
        }

        if (saveButton) {
            saveButton.hidden = !inReview;
            saveButton.setAttribute('form', reviewForm ? reviewForm.id || 'receipt-review-form' : '');
        }

        if (reverseButton) {
            reverseButton.hidden = !canReverseThisReceipt;
            reverseButton.setAttribute('form', reverseForm ? reverseForm.id || 'receipt-reverse-form' : '');
        }

        if (reviewForm && !reviewForm.id) {
            reviewForm.id = 'receipt-review-form';
        }

        if (reverseForm && !reverseForm.id) {
            reverseForm.id = 'receipt-reverse-form';
        }

        showReviewFields();

        if (detailModal) {
            detailModal.hidden = false;
        }
    };

    if (filterForm) {
        filterForm.addEventListener('submit', function (event) {
            event.preventDefault();
            currentPage = 1;
            fetchRows(1);
        });
    }

    [statusInput, courseInput].forEach(function (input) {
        if (input) {
            input.addEventListener('change', scheduleFetch);
        }
    });

    if (searchInput) {
        searchInput.addEventListener('input', scheduleFetch);
    }

    if (prevButton) {
        prevButton.addEventListener('click', function () {
            if (currentPage > 1) {
                fetchRows(currentPage - 1);
            }
        });
    }

    if (nextButton) {
        nextButton.addEventListener('click', function () {
            if (currentPage < totalPages) {
                fetchRows(currentPage + 1);
            }
        });
    }

    document.addEventListener('click', function (event) {
        const detailButton = event.target.closest('[data-receipt-detail]');

        if (!detailButton) {
            return;
        }

        try {
            openDetail(JSON.parse(detailButton.getAttribute('data-receipt') || '{}'));
        } catch (error) {
            openDetail({});
        }
    });

    document.addEventListener('change', function (event) {
        if (event.target.closest('[data-receipt-review-state]')) {
            showReviewFields();
        }
    });

    if (reviewForm) {
        reviewForm.addEventListener('submit', function () {
            const state = String(new FormData(reviewForm).get('revision_estado') || 'APROBADO');
            reviewForm.action = state === 'RECHAZADO' ? rejectEndpoint : approveEndpoint;
        });
    }

    if (reverseForm) {
        reverseForm.addEventListener('submit', function (event) {
            if (!window.confirm('Confirma que desea reversar este pago aprobado?')) {
                event.preventDefault();
            }
        });
    }

    detailCloseButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            if (detailModal) {
                detailModal.hidden = true;
            }
        });
    });
});
</script>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
