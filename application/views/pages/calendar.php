<?php extend('layouts/backend_layout'); ?>

<?php section('styles'); ?>
<link rel="stylesheet" href="<?= asset_url('assets/css/series_planner.css') ?>">
<style>
    #calendar-view-filter-groups {
        max-height: 52vh;
        overflow-y: auto;
    }

    .calendar-view-options {
        max-height: 220px;
        overflow-y: auto;
    }

    .calendar-view-option-label {
        font-size: .9rem;
    }


    .calendar-view-option-row {
        display: flex;
        align-items: center;
        gap: .45rem;
        margin-bottom: .4rem;
        padding: .15rem .4rem;
    }

    .calendar-view-option-row .form-check-input {
        margin: 0;
        flex: 0 0 auto;
    }

    .calendar-view-option-row .calendar-view-option-label {
        flex: 1 1 auto;
        min-width: 0;
    }

    .calendar-item-color {
        width: 34px;
        height: 26px;
        padding: .1rem;
        flex: 0 0 34px;
    }


    .fc-planning-calendar-event {
        min-width: 0;
        border-width: 2px !important;
        box-shadow:
            0 1px 3px rgba(0, 0, 0, .18);
    }

    .fc-planning-calendar-event .fc-event-main {
        overflow: hidden;
    }

    .fc-planning-calendar-event .fc-event-title {
        white-space: normal;
        overflow-wrap: anywhere;
        line-height: 1.25;
    }

    .fc-project-assignment {
        opacity: .88;
        border-left-width: 5px !important;
        border-left-style: solid !important;
    }

    .fc-project-assignment .fc-event-main {
        font-weight: 600;
    }
</style>
<?php end_section('styles'); ?>

<?php section('content'); ?>

<div class="container-fluid backend-page pt-0 pb-0" id="calendar-page">
    <div class="row mb-3 text-white py-3 bg-dark" id="calendar-toolbar">
        <div id="calendar-filter" class="col-lg-5">
            <div class="calendar-filter-items">
                <div class="d-none">
                    <label for="select-filter-item"
                           class="visually-hidden">
                        Technischer Terminkontext
                    </label>

                    <select id="select-filter-item"
                        class="form-select flex-grow-1 mb-2 mb-lg-0"
                        data-tippy-content="<?= lang('select_filter_item_hint') ?>"
                        aria-label="Filter">
                    <!-- JS -->
                    </select>
                </div>

                <div class="dropdown w-100">
                    <button type="button"
                            id="calendar-view-config-button"
                            class="btn btn-light dropdown-toggle w-100 text-start d-flex align-items-center"
                            data-bs-toggle="dropdown"
                            data-bs-auto-close="outside"
                            aria-expanded="false">
                        <i class="fas fa-calendar-alt me-2"></i>

                        <span id="calendar-view-config-label"
                              class="flex-grow-1">
                            Alle Kalender
                        </span>
                    </button>

                    <div class="dropdown-menu p-3"
                         style="width: min(440px, 92vw);">

                        <h6 class="dropdown-header px-0 pt-0">
                            Kalender auswählen
                        </h6>

                        <p class="small text-muted">
                            Ausgewählte Projekte, Beschäftigte, Teams und Ressourcen
                            zeigen automatisch alle zugehörigen Belegungen.
                        </p>



                        <p class="small text-muted">
                            Mehrere Einträge können gleichzeitig aktiviert
                            werden. Die Auswahl wird mit ODER verknüpft.
                        </p>


                        <div class="accordion accordion-flush"
                             id="calendar-view-filter-groups">

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button type="button"
                                            class="accordion-button collapsed py-2"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#calendar-filter-projects">

                                        Projekte
                                    </button>
                                </h2>

                                <div id="calendar-filter-projects"
                                     class="accordion-collapse collapse">


                                    <div id="calendar-project-options"
                                         class="accordion-body px-1 py-2 calendar-view-options">
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button type="button"
                                            class="accordion-button collapsed py-2"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#calendar-filter-personnel">

                                        Personal
                                    </button>
                                </h2>

                                <div id="calendar-filter-personnel"
                                     class="accordion-collapse collapse">


                                    <div class="px-2 pt-2 small fw-semibold text-muted">
                                        Teams
                                    </div>

                                    <div id="calendar-team-options"
                                         class="px-1 py-2 calendar-view-options">
                                    </div>

                                    <div class="px-2 pt-2 small fw-semibold text-muted border-top">
                                        Mitarbeiter
                                    </div>

                                    <div id="calendar-employee-options"
                                         class="px-1 py-2 calendar-view-options">
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button type="button"
                                            class="accordion-button collapsed py-2"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#calendar-filter-resources">

                                        Ressourcen
                                    </button>
                                </h2>

                                <div id="calendar-filter-resources"
                                     class="accordion-collapse collapse">


                                    <div id="calendar-resource-options"
                                         class="accordion-body px-1 py-2 calendar-view-options">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex gap-2">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="calendar-actions" class="col-lg-7 text-md-end">
            <?php if (vars('calendar_view') === CALENDAR_VIEW_DEFAULT): ?>
                <button
                    id="enable-sync"
                    class="btn btn-light"
                    data-tippy-content="<?= lang('enable_appointment_sync_hint') ?>"
                    hidden>
                    <i class="fas fa-rotate me-2"></i>
                    <?= lang('enable_sync') ?>
                </button>

                <div class="btn-group" id="sync-button-group" hidden>
                    <button type="button" class="btn btn-light" id="trigger-sync"
                            data-tippy-content="<?= lang('trigger_sync_hint') ?>">
                        <i class="fas fa-rotate me-2"></i>
                        <?= lang('synchronize') ?>
                    </button>
                    <button type="button" class="btn btn-light dropdown-toggle dropdown-toggle-split"
                            data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="visually-hidden">
                            Toggle Dropdown
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="#" id="disable-sync">
                                <?= lang('disable_sync') ?>
                            </a>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (can('add', PRIV_APPOINTMENTS)): ?>
                <div class="dropdown d-inline-block">
                    <button class="btn btn-light" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-plus-square"></i>
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="#" id="insert-appointment">
                                <?= lang('appointment') ?>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" id="new-appointment-series">
                                <i class="fas fa-repeat me-2"></i>
                                Serientermin
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" id="insert-unavailability">
                                <?= lang('unavailability') ?>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#"
                               id="insert-working-plan-exception" <?= session('role_slug') !== DB_SLUG_ADMIN
                                   ? 'hidden'
                                   : '' ?>>
                                <?= lang('working_plan_exception') ?>
                            </a>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>

            <button id="reload-appointments" class="btn btn-light"
                    data-tippy-content="<?= lang('reload_appointments_hint') ?>">
                <i class="fas fa-sync-alt"></i>
            </button>

            <?php if (vars('calendar_view') === CALENDAR_VIEW_DEFAULT): ?>
                <a class="btn btn-light mb-0" href="<?= site_url('calendar?view=table') ?>"
                   data-tippy-content="<?= lang('table') ?>">
                    <i class="fas fa-table"></i>
                </a>
            <?php endif; ?>

            <?php if (vars('calendar_view') === CALENDAR_VIEW_TABLE): ?>
                <a class="btn btn-light mb-0" href="<?= site_url('calendar?view=default') ?>"
                   data-tippy-content="<?= lang('default') ?>">
                    <i class="fas fa-calendar-alt"></i>
                </a>
            <?php endif; ?>

        </div>
    </div>

    <div id="sync-alert" class="alert alert-danger alert-dismissible mb-3" role="alert" hidden>
        <h6 class="alert-heading">
            <i class="fas fa-triangle-exclamation me-2"></i>
            <span id="sync-alert-title"></span>
        </h6>
        <p id="sync-alert-hint" class="mb-1"></p>
        <small id="sync-alert-details" class="d-block text-muted"></small>
        <?php if (can('edit', PRIV_SYSTEM_SETTINGS)): ?>
            <a href="<?= site_url('caldav_settings') ?>" class="btn btn-sm btn-outline-danger mt-3">
                <i class="fas fa-cogs me-2"></i>
                <?= lang('configure') ?>
            </a>
        <?php endif; ?>
        <button type="button" class="btn-close" id="sync-alert-close" aria-label="<?= lang('close') ?>"></button>
    </div>

    <div id="calendar">
        <!-- Dynamically Generated Content -->
    </div>
</div>

<!-- Page Components -->

<?php component('appointments_modal', [
    'available_services' => vars('available_services'),
    'appointment_status_options' => vars('appointment_status_options'),
    'timezones' => vars('timezones'),
    'require_first_name' => vars('require_first_name'),
    'require_last_name' => vars('require_last_name'),
    'require_email' => vars('require_email'),
    'require_phone_number' => vars('require_phone_number'),
    'require_address' => vars('require_address'),
    'require_city' => vars('require_city'),
    'require_zip_code' => vars('require_zip_code'),
    'require_notes' => vars('require_notes'),
]); ?>

<?php component('unavailabilities_modal', [
    'timezones' => vars('timezones'),
    'timezone' => vars('timezone'),
]); ?>

<?php component('working_plan_exceptions_modal'); ?>

<?php component('series_planner_modal'); ?>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script src="<?= asset_url('assets/vendor/fullcalendar/index.global.min.js') ?>"></script>
<script src="<?= asset_url('assets/vendor/fullcalendar-moment/index.global.min.js') ?>"></script>
<script src="<?= asset_url('assets/vendor/jquery-jeditable/jquery.jeditable.min.js') ?>"></script>
<script src="<?= asset_url('assets/js/utils/ui.js') ?>"></script>
<script src="<?= asset_url('assets/js/utils/calendar_default_view.js') ?>"></script>
<script src="<?= asset_url('assets/js/utils/calendar_table_view.js') ?>"></script>
<script src="<?= asset_url('assets/js/utils/calendar_event_popover.js') ?>"></script>
<script src="<?= asset_url('assets/js/http/calendar_http_client.js') ?>"></script>
<script src="<?= asset_url('assets/js/http/customers_http_client.js') ?>"></script>
<?php if (vars('calendar_view') === CALENDAR_VIEW_DEFAULT): ?>
    <script src="<?= asset_url('assets/js/utils/calendar_sync.js') ?>"></script>
    <script src="<?= asset_url('assets/js/http/google_http_client.js') ?>"></script>
    <script src="<?= asset_url('assets/js/http/caldav_http_client.js') ?>"></script>
<?php endif; ?>
<script src="<?= asset_url('assets/js/pages/calendar.js') ?>"></script>
<script src="<?= asset_url('assets/js/pages/series_planner.js') ?>"></script>

<?php end_section('scripts'); ?>

