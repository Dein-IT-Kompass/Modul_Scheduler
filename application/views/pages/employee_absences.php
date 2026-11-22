<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div class="container backend-page py-4" id="employee-absences-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1">Abwesenheiten</h3>
            <p class="text-muted mb-0">
                Urlaub beantragen, Krankmeldungen erfassen und Anfragen verwalten.
            </p>
        </div>

        <button type="button"
                class="btn btn-primary"
                id="add-absence">
            <i class="fas fa-plus me-2"></i>
            Abwesenheit erfassen
        </button>
    </div>

    <div id="absence-form-message"
         class="alert d-none">
    </div>

    <div class="card mb-4 d-none" id="absence-form-card">
        <div class="card-header">
            <strong>Neue Abwesenheit</strong>
        </div>

        <div class="card-body">
            <div class="row g-3">
                <?php if (vars('can_manage_absences')): ?>
                    <div class="col-md-6">
                        <label class="form-label" for="absence-employee">
                            Mitarbeiter
                        </label>

                        <select id="absence-employee"
                                class="form-select">
                            <?php foreach (vars('employees') as $employee): ?>
                                <option value="<?= e($employee['id']) ?>">
                                    <?= e(
                                        trim(
                                            ($employee['first_name'] ?? '') .
                                            ' ' .
                                            ($employee['last_name'] ?? '')
                                        )
                                    ) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <input type="hidden"
                           id="absence-employee"
                           value="<?= e(vars('current_user_id')) ?>">
                <?php endif; ?>

                <div class="col-md-6">
                    <label class="form-label" for="absence-type">
                        Abwesenheitsart
                    </label>

                    <select id="absence-type" class="form-select">
                        <option value="vacation">Urlaub</option>
                        <option value="sick">Krank</option>
                        <option value="training">Fortbildung</option>
                        <option value="business_trip">Dienstreise</option>
                        <option value="other">Sonstige Abwesenheit</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="absence-start-date">
                        Beginn
                    </label>

                    <input type="date"
                           id="absence-start-date"
                           class="form-control">
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="absence-end-date">
                        Ende
                    </label>

                    <input type="date"
                           id="absence-end-date"
                           class="form-control">
                </div>

                <div class="col-md-6 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input type="checkbox"
                               id="absence-all-day"
                               class="form-check-input"
                               checked>

                        <label class="form-check-label"
                               for="absence-all-day">
                            Ganztägig
                        </label>
                    </div>
                </div>

                <div class="col-md-3 absence-time d-none">
                    <label class="form-label" for="absence-start-time">
                        Von
                    </label>

                    <input type="time"
                           id="absence-start-time"
                           class="form-control">
                </div>

                <div class="col-md-3 absence-time d-none">
                    <label class="form-label" for="absence-end-time">
                        Bis
                    </label>

                    <input type="time"
                           id="absence-end-time"
                           class="form-control">
                </div>

                <div class="col-12">
                    <label class="form-label" for="absence-notes">
                        Hinweis
                    </label>

                    <textarea id="absence-notes"
                              class="form-control"
                              rows="3"></textarea>
                </div>
            </div>
        </div>

        <div class="card-footer d-flex gap-2">
            <button type="button"
                    id="save-absence"
                    class="btn btn-primary">
                Speichern
            </button>

            <button type="button"
                    id="cancel-absence-form"
                    class="btn btn-outline-secondary">
                Abbrechen
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <strong>Abwesenheiten und Anträge</strong>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Mitarbeiter</th>
                        <th>Art</th>
                        <th>Zeitraum</th>
                        <th>Status</th>
                        <th>Hinweis</th>
                        <th class="text-end">Aktionen</th>
                    </tr>
                </thead>

                <tbody id="absence-results">
                    <tr>
                        <td colspan="6" class="text-muted text-center py-4">
                            Abwesenheiten werden geladen …
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>


<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script src="<?= asset_url('assets/js/pages/employee_absences.js') ?>"></script>

<?php end_section('scripts'); ?>
