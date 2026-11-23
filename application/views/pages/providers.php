<?php extend('layouts/backend_layout'); ?>

<?php section('styles'); ?>
<style>
    #providers .record-detail-navigation {
        display: flex;
        align-items: center;
        min-height: 34px;
    }

    #providers .record-detail-navigation .btn {
        white-space: nowrap;
    }

    #providers .switch-view {
        flex-wrap: nowrap !important;
        white-space: nowrap;
    }

    #providers .switch-view .nav-item {
        flex: 0 0 auto;
    }

    /*
     * Responsive provider workspace.
     *
     * The original backend layout is optimized for rather narrow forms.
     * Employee administration now contains working plans, absence approval
     * rules and calendar administration, so the workspace should make use
     * of the available screen width.
     */
    #providers {
        width: min(96vw, 1400px);
        max-width: 1400px;
        margin-left: auto;
        margin-right: auto;
    }

    #providers .tab-content,
    #providers .tab-pane {
        width: 100%;
    }

    #providers #absence-settings .card {
        width: 100%;
        max-width: none;
    }

    #providers #absence-settings .card-body {
        width: 100%;
    }

    #providers #absence-settings .table-responsive {
        width: 100%;
        overflow-x: auto;
    }

    #providers #absence-settings table {
        width: 100%;
        min-width: 700px;
    }

    #providers #absence-settings th:first-child,
    #providers #absence-settings td:first-child {
        width: 42%;
    }

    #providers #absence-settings th:nth-child(2),
    #providers #absence-settings td:nth-child(2) {
        width: 23%;
    }

    #providers #absence-settings th:nth-child(3),
    #providers #absence-settings td:nth-child(3),
    #providers #absence-settings th:nth-child(4),
    #providers #absence-settings td:nth-child(4) {
        width: 17.5%;
        white-space: nowrap;
    }

    @media (max-width: 991.98px) {
        #providers {
            width: calc(100vw - 24px);
            max-width: none;
        }

        #providers .switch-view {
            overflow-x: auto;
            padding-bottom: 4px;
        }
    }

    @media (max-width: 575.98px) {
        #providers {
            width: calc(100vw - 16px);
        }

        #providers #absence-settings .card {
            border-left: 0;
            border-right: 0;
        }
    }

    #providers .switch-view .nav-link {
        white-space: nowrap;
    }
</style>
<?php end_section('styles'); ?>

<?php section('content'); ?>

<div class="container backend-page py-3" id="providers-page">
    <div class="row" id="providers">
        <div id="filter-providers" class="filter-records column col-12 mb-4">
            <button id="add-provider" class="btn btn-primary add-record-btn mb-4">
                <i class="fas fa-plus-square me-2"></i>
                <?= lang('add') ?>
            </button>

            <form class="mb-4">
                <div class="input-group">
                    <input type="text" class="key form-control" aria-label="keyword">

                    <button class="filter btn btn-outline-secondary" type="submit"
                            data-tippy-content="<?= lang('filter') ?>">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>

            <h4 class="mb-3 fw-light">
                <?= lang('providers') ?>
            </h4>

            <div class="results overflow-auto" style="max-height: 650px;">
                <!-- JS -->
            </div>
        </div>

        <div class="record-details column col-12 mb-4">

            <div class="record-detail-navigation mb-3">
                <button type="button"
                        id="back-to-providers"
                        class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>
                    Mitarbeiter
                </button>

                <span id="provider-detail-breadcrumb"
                      class="ms-3 text-muted">
                </span>
            </div>
            <div class="float-md-start mb-4 me-4">
                <div class="add-edit-delete-group btn-group">
                    <button id="edit-provider" class="btn btn-outline-secondary" disabled="disabled">
                        <i class="fas fa-edit me-2"></i>
                        <?= lang('edit') ?>
                    </button>
                </div>

                <div class="save-cancel-group" style="display:none;">
                    <button id="save-provider" class="btn btn-primary">
                        <i class="fas fa-check-square me-2"></i>
                        <?= lang('save') ?>
                    </button>
                    <button id="cancel-provider" class="btn btn-outline-secondary">
                        <?= lang('cancel') ?>
                    </button>
                    <button id="delete-provider" class="btn btn-outline-danger ms-2">
                        <i class="fas fa-trash-alt me-2"></i>
                        <?= lang('delete') ?>
                    </button>
                </div>

            </div>

            <ul class="nav nav-pills switch-view d-flex flex-row flex-nowrap gap-1 align-items-center">
                <li class="nav-item">
                    <a class="nav-link active" href="#details" data-bs-toggle="tab">
                        <?= lang('details') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#working-plan" data-bs-toggle="tab">
                        <?= lang('working_plan') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link"
                       href="#absence-settings"
                       data-bs-toggle="tab">
                        Abwesenheiten
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link"
                       href="#deployment-calendar"
                       data-bs-toggle="tab">
                        Einsatzkalender
                    </a>
                </li>
            </ul>

            <?php
// This form message is outside the details view, so that it can be
// visible when the user has working plan view active.
?>

            <div class="form-message alert mt-4" style="display:none;"></div>

            <div class="tab-content">
                <div class="details-view tab-pane fade show active clearfix" id="details">
                    <h4 class="mb-3 fw-light">
                        <?= lang('details') ?>
                    </h4>

                    <input type="hidden" id="id" class="record-id">

                    <div class="row">
                        <div class="details col-12 col-lg-6">
                            <div class="mb-3">
                                <label class="form-label" for="first-name">
                                    <?= lang('first_name') ?>
                                    <span class="text-danger" hidden>*</span>
                                </label>
                                <input id="first-name" class="form-control required" maxlength="256" disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="last-name">
                                    <?= lang('last_name') ?>
                                    <span class="text-danger" hidden>*</span>
                                </label>
                                <input id="last-name" class="form-control required" maxlength="512" disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="email">
                                    <?= lang('email') ?>
                                    <span class="text-danger" hidden>*</span>
                                </label>
                                <input id="email" class="form-control required" max="512" disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="phone-number">
                                    <?= lang('phone_number') ?>
                                </label>
                                <input id="phone-number" class="form-control" max="128" disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="mobile-number">
                                    <?= lang('mobile_number') ?>

                                </label>
                                <input id="mobile-number" class="form-control" maxlength="128" disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="address">
                                    <?= lang('address') ?>
                                </label>
                                <input id="address" class="form-control" maxlength="256" disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="city">
                                    <?= lang('city') ?>

                                </label>
                                <input id="city" class="form-control" maxlength="256" disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="state">
                                    <?= lang('state') ?>
                                </label>
                                <input id="state" class="form-control" maxlength="256" disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="zip-code">
                                    <?= lang('zip_code') ?>

                                </label>
                                <input id="zip-code" class="form-control" maxlength="64" disabled>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label"
                                           for="employment-start">
                                        Eintritt
                                    </label>

                                    <input type="date"
                                           id="employment-start"
                                           class="form-control"
                                           disabled>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label"
                                           for="employment-end">
                                        Austritt
                                    </label>

                                    <input type="date"
                                           id="employment-end"
                                           class="form-control"
                                           disabled>
                                </div>
                            </div>

                            <div class="form-text text-muted mb-3">
                                Historische Einsätze bleiben nach einem
                                Austritt vollständig erhalten. Zukünftige
                                Einsätze nach dem Austrittsdatum werden als
                                Beeinträchtigung gemeldet.
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="notes">
                                    <?= lang('notes') ?>
                                </label>
                                <textarea id="notes" class="form-control" rows="3" disabled></textarea>
                            </div>

                        </div>
                        <div class="settings col-12 col-lg-6">
                            <div class="mb-3">
                                <label class="form-label" for="username">
                                    <?= lang('username') ?>
                                    <span class="text-danger" hidden>*</span>
                                </label>
                                <input id="username" class="form-control required" maxlength="256" disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="password">
                                    <?= lang('password') ?>
                                    <span class="text-danger" hidden>*</span>
                                </label>
                                <input type="password" id="password" class="form-control required"
                                       maxlength="512" autocomplete="new-password" disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="password-confirm">
                                    <?= lang('retype_password') ?>
                                    <span class="text-danger" hidden>*</span>
                                </label>
                                <input type="password" id="password-confirm"
                                       class="form-control required" maxlength="512"
                                       autocomplete="new-password" disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="calendar-view">
                                    <?= lang('calendar') ?>
                                    <span class="text-danger" hidden>*</span>
                                </label>
                                <select id="calendar-view" class="form-select required" disabled>
                                    <option value="default"><?= lang('default') ?></option>
                                    <option value="table"><?= lang('table') ?></option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="language">
                                    <?= lang('language') ?>
                                    <span class="text-danger" hidden>*</span>
                                </label>
                                <select id="language" class="form-select required" disabled>
                                    <?php foreach (vars('available_languages') as $available_language): ?>
                                        <option value="<?= $available_language ?>">
                                            <?= ucfirst($available_language) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="timezone">
                                    <?= lang('timezone') ?>
                                    <span class="text-danger" hidden>*</span>
                                </label>
                                <?php component('timezone_dropdown', [
                                    'attributes' => 'id="timezone" class="form-select required" disabled',
                                    'grouped_timezones' => vars('grouped_timezones'),
                                ]); ?>
                            </div>

                            <?php if (setting('ldap_is_active')): ?>
                                <div class="mb-3">
                                    <label for="ldap-dn" class="form-label">
                                        <?= lang('ldap_dn') ?>
                                    </label>
                                    <input type="text" id="ldap-dn" class="form-control" maxlength="100" disabled/>
                                </div>
                            <?php endif; ?>

                            <div>
                                <label class="form-label mb-3">
                                    <?= lang('options') ?>
                                </label>
                            </div>

                            <div class="border rounded mb-3 p-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is-private">
                                    <label class="form-check-label" for="is-private">
                                        <?= lang('hide_from_public') ?>
                                    </label>
                                </div>

                                <div class="form-text text-muted mb-3">
                                    <small>
                                        <?= lang('private_hint') ?>
                                    </small>
                                </div>

                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="notifications" disabled>
                                    <label class="form-check-label" for="notifications">
                                        <?= lang('receive_notifications') ?>
                                    </label>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label mb-0">
                                    <?= lang('services') ?>
                                </label>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" id="select-all-services" class="btn btn-outline-secondary" disabled>
                                        <?= lang('select_all') ?>
                                    </button>
                                    <button type="button" id="select-none-services" class="btn btn-outline-secondary" disabled>
                                        <?= lang('select_none') ?>
                                    </button>
                                </div>
                            </div>

                            <div id="provider-services" class="card card-body border">
                                <!-- JS -->
                            </div>

                        </div>
                    </div>
                </div>

                <div class="tab-pane fade"
                     id="absence-settings">

                    <div class="card mt-4">
                        <div class="card-header">
                            <strong>
                                Abwesenheiten und Genehmigungen
                            </strong>
                        </div>

                        <div class="card-body">
                            <div class="mb-3">
                                <label
                                    class="form-label"
                                    for="absence-approval-mode">
                                    Urlaubs- und Abwesenheitsanträge
                                </label>

                                <select
                                    id="absence-approval-mode"
                                    class="form-select"
                                    disabled>
                                    <option value="approval_required">
                                        Genehmigung erforderlich
                                    </option>
                                    <option value="self_approved">
                                        Keine Genehmigung erforderlich
                                    </option>
                                </select>

                                <div class="form-text">
                                    Krankmeldungen werden unabhängig von
                                    dieser Einstellung sofort als aktive
                                    Abwesenheit berücksichtigt.
                                </div>
                            </div>

                            <hr>

                            <div class="d-flex flex-wrap
                                        justify-content-between
                                        align-items-center gap-2 mb-3">
                                <div>
                                    <h5 class="mb-1">
                                        Genehmiger / Autorisatoren
                                    </h5>
                                    <div class="text-muted small">
                                        Administratoren, Vorgesetzte und
                                        Mitarbeiter können unabhängig von
                                        der Einsatzplanung ausgewählt werden.
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    id="save-absence-approvers"
                                    class="btn btn-primary btn-sm"
                                    disabled>
                                    <i class="fas fa-check me-2"></i>
                                    Genehmiger speichern
                                </button>
                            </div>

                            <div
                                id="absence-approvers-message"
                                class="alert d-none">
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm
                                              align-middle">
                                    <thead>
                                        <tr>
                                            <th>Benutzer</th>
                                            <th>Rolle</th>
                                            <th class="text-center">
                                                Genehmigen
                                            </th>
                                            <th class="text-center">
                                                Direkt eintragen
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody
                                        id="absence-approvers-results">
                                        <tr>
                                            <td colspan="4"
                                                class="text-muted
                                                       text-center py-4">
                                                Mitarbeiter auswählen.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="form-text">
                                <strong>Genehmigen:</strong>
                                Darf Anträge annehmen oder ablehnen.
                                <br>
                                <strong>Direkt eintragen:</strong>
                                Darf Urlaub für diesen Mitarbeiter sofort
                                genehmigt eintragen.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade"
                     id="deployment-calendar">

                    <div class="card mt-4">
                        <div class="card-header">
                            <strong>
                                Persönlicher Einsatzkalender
                            </strong>
                        </div>

                        <div class="card-body">
                            <div id="deployment-calendar-message"
                                 class="alert d-none">
                            </div>

                            <div id="deployment-calendar-empty">
                                <p class="text-muted">
                                    Für diesen Mitarbeiter wurde noch kein
                                    Kalenderzugang erstellt.
                                </p>

                                <button type="button"
                                        id="create-deployment-calendar"
                                        class="btn btn-primary"
                                        disabled>
                                    <i class="fas fa-qrcode me-2"></i>
                                    Kalenderzugang erstellen
                                </button>
                            </div>

                            <div id="deployment-calendar-active"
                                 class="d-none">

                                <div class="alert alert-success">
                                    <strong>Status:</strong>
                                    Kalenderzugang aktiv
                                </div>

                                <div id="deployment-calendar-secret"
                                     class="d-none">

                                    <div class="row g-4 align-items-center">
                                        <div class="col-md-auto">
                                            <canvas id="deployment-calendar-qr"
                                                    width="260"
                                                    height="260">
                                            </canvas>
                                        </div>

                                        <div class="col">
                                            <p>
                                                QR-Code mit dem Smartphone
                                                scannen und den persönlichen
                                                Einsatzkalender abonnieren.
                                            </p>

                                            <a id="deployment-calendar-webcal"
                                               class="btn btn-primary mb-2"
                                               href="#">
                                                <i class="fas fa-calendar-plus me-2"></i>
                                                Auf diesem Gerät hinzufügen
                                            </a>

                                            <button type="button"
                                                    id="copy-deployment-calendar"
                                                    class="btn btn-outline-secondary mb-2">
                                                <i class="fas fa-copy me-2"></i>
                                                HTTPS-Link kopieren
                                            </button>

                                            <input type="text"
                                                   id="deployment-calendar-url"
                                                   class="form-control mt-2"
                                                   readonly>
                                        </div>
                                    </div>

                                    <hr>
                                </div>

                                <p id="deployment-calendar-token-hint"
                                   class="text-muted">
                                    Dieser Kalenderzugang wurde vor der
                                    verschlüsselten Tokenspeicherung erstellt.
                                    Bitte den Zugangsschlüssel einmal erneuern.
                                </p>

                                <button type="button"
                                        id="rotate-deployment-calendar"
                                        class="btn btn-outline-warning"
                                        disabled>
                                    <i class="fas fa-rotate me-2"></i>
                                    Zugangsschlüssel erneuern
                                </button>

                                <button type="button"
                                        id="disable-deployment-calendar"
                                        class="btn btn-outline-danger ms-2"
                                        disabled>
                                    <i class="fas fa-ban me-2"></i>
                                    Zugang deaktivieren
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="working-plan-view tab-pane fade clearfix" id="working-plan">
                    <h4 class="mb-3 fw-light">
                        <?= lang('working_plan') ?>
                    </h4>

                    <button id="reset-working-plan" class="btn btn-primary"
                            data-tippy-content="<?= lang('reset_working_plan') ?>">
                        <i class="fas fa-undo-alt me-2"></i>
                        <?= lang('reset_plan') ?></button>
                    <div class="table-responsive">
                        <table class="working-plan table table-striped mt-2">
                            <thead>
                            <tr>
                                <th><?= lang('day') ?></th>
                                <th><?= lang('start') ?></th>
                                <th><?= lang('end') ?></th>
                            </tr>
                            </thead>
                            <tbody><!-- Dynamic Content --></tbody>
                        </table>
                    </div>

                    <br>

                    <h4 class="mb-3 fw-light">
                        <?= lang('breaks') ?>
                    </h4>

                    <p>
                        <?= lang('add_breaks_during_each_day') ?>
                    </p>

                    <div>
                        <button type="button" class="add-break btn btn-primary">
                            <i class="fas fa-plus-square me-2"></i>
                            <?= lang('add_break') ?>
                        </button>
                    </div>

                    <br>

                    <div class="table-responsive">
                        <table class="breaks table table-striped">
                            <thead>
                            <tr>
                                <th><?= lang('day') ?></th>
                                <th><?= lang('start') ?></th>
                                <th><?= lang('end') ?></th>
                                <th><?= lang('actions') ?></th>
                            </tr>
                            </thead>
                            <tbody><!-- Dynamic Content --></tbody>
                        </table>
                    </div>

                    <br>

                    <h4 class="mb-3 fw-light">
                        <?= lang('working_plan_exceptions') ?>
                    </h4>

                    <p>
                        <?= lang('add_working_plan_exceptions_during_each_day') ?>
                    </p>

                    <div>
                        <button type="button" class="add-working-plan-exception btn btn-primary me-2">
                            <i class="fas fa-plus-square me-2"></i>
                            <?= lang('add_working_plan_exception') ?>
                        </button>
                    </div>

                    <br>

                    <div class="table-responsive">
                        <table class="working-plan-exceptions table table-striped">
                            <thead>
                            <tr>
                                <th><?= lang('date') ?></th>
                                <th><?= lang('start') ?></th>
                                <th><?= lang('end') ?></th>
                                <th><?= lang('actions') ?></th>
                            </tr>
                            </thead>
                            <tbody><!-- Dynamic Content --></tbody>
                        </table>
                    </div>

                    <?php component('working_plan_exceptions_modal'); ?>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= asset_url('assets/vendor/qrcode/qrcode.min.js') ?>"></script>
<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script src="<?= asset_url('assets/vendor/jquery-jeditable/jquery.jeditable.min.js') ?>"></script>
<script src="<?= asset_url('assets/js/utils/ui.js') ?>"></script>
<script src="<?= asset_url('assets/js/utils/working_plan.js') ?>"></script>
<script src="<?= asset_url('assets/js/http/account_http_client.js') ?>"></script>
<script src="<?= asset_url('assets/js/http/providers_http_client.js') ?>"></script>
<script src="<?= asset_url('assets/js/pages/providers.js') ?>"></script>

<?php end_section('scripts'); ?>



