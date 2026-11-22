<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div class="container backend-page py-4" id="employee-teams-page">
    <div class="row">
        <div class="col-12 col-lg-4 mb-4">
            <button type="button"
                    id="add-team"
                    class="btn btn-primary mb-4">
                <i class="fas fa-plus-square me-2"></i>
                Team hinzufügen
            </button>

            <div class="card">
                <div class="card-header">
                    <strong>Teams</strong>
                </div>

                <div id="team-list"
                     class="list-group list-group-flush">
                    <div class="text-muted text-center p-4">
                        Teams werden geladen …
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-8">
            <div id="team-editor"
                 class="card d-none">

                <div class="card-header">
                    <strong id="team-editor-title">
                        Team bearbeiten
                    </strong>
                </div>

                <div class="card-body">
                    <input type="hidden" id="team-id">

                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <label class="form-label" for="team-name">
                                Teamname
                            </label>

                            <input id="team-name"
                                   class="form-control"
                                   maxlength="256">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="team-color">
                                Farbe
                            </label>

                            <input id="team-color"
                                   type="color"
                                   class="form-control form-control-color w-100"
                                   value="#7cbae8">
                        </div>

                        <div class="col-12">
                            <label class="form-label"
                                   for="team-description">
                                Beschreibung
                            </label>

                            <textarea id="team-description"
                                      class="form-control"
                                      rows="2"></textarea>
                        </div>

                        <div class="col-12">
                            <div class="form-check">
                                <input id="team-active"
                                       type="checkbox"
                                       class="form-check-input"
                                       checked>

                                <label class="form-check-label"
                                       for="team-active">
                                    Team aktiv
                                </label>
                            </div>
                        </div>
                    </div>

                    <h5 class="fw-light mb-3">
                        Mitglieder und Rechte
                    </h5>

                    <p class="text-muted">
                        Ein Teammitglied kann zusätzlich Teamleitung oder
                        Einsatzplaner sein. Die Planungsrechte gelten nur
                        innerhalb dieses Teams.
                    </p>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 35%">
                                        Mitarbeiter
                                    </th>
                                    <th>Rolle</th>
                                    <th class="text-center">
                                        Urlaub
                                    </th>
                                    <th class="text-center">
                                        Kalender
                                    </th>
                                    <th class="text-center">
                                        Serien
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach (vars('employees') as $employee): ?>
                                    <?php
                                    $employee_name = trim(
                                        ($employee['first_name'] ?? '') .
                                        ' ' .
                                        ($employee['last_name'] ?? '')
                                    );
                                    ?>

                                    <tr class="team-member-row"
                                        data-user-id="<?= e($employee['id']) ?>">

                                        <td>
                                            <div class="form-check">
                                                <input type="checkbox"
                                                       class="form-check-input team-member-enabled">

                                                <label class="form-check-label">
                                                    <?= e($employee_name) ?>
                                                </label>
                                            </div>
                                        </td>

                                        <td>
                                            <select class="form-select form-select-sm team-member-role"
                                                    disabled>
                                                <option value="member">
                                                    Mitglied
                                                </option>
                                                <option value="lead">
                                                    Teamleitung
                                                </option>
                                                <option value="planner">
                                                    Einsatzplaner
                                                </option>
                                            </select>
                                        </td>

                                        <td class="text-center">
                                            <input type="checkbox"
                                                   class="form-check-input team-can-approve"
                                                   title="Abwesenheiten genehmigen"
                                                   disabled>
                                        </td>

                                        <td class="text-center">
                                            <input type="checkbox"
                                                   class="form-check-input team-can-calendar"
                                                   title="Teamkalender verwalten"
                                                   disabled>
                                        </td>

                                        <td class="text-center">
                                            <input type="checkbox"
                                                   class="form-check-input team-can-series"
                                                   title="Serientermine verwalten"
                                                   disabled>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card-footer d-flex gap-2">
                    <button type="button"
                            id="save-team"
                            class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>
                        Speichern
                    </button>

                    <button type="button"
                            id="cancel-team"
                            class="btn btn-outline-secondary">
                        Abbrechen
                    </button>

                    <button type="button"
                            id="delete-team"
                            class="btn btn-outline-danger ms-auto">
                        <i class="fas fa-trash me-2"></i>
                        Löschen
                    </button>
                </div>
            </div>

            <div id="team-placeholder"
                 class="alert alert-light border">
                Links ein Team auswählen oder ein neues Team anlegen.
            </div>
        </div>
    </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script src="<?= asset_url('assets/js/pages/employee_teams.js') ?>"></script>

<?php end_section('scripts'); ?>
