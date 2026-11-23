<?php extend('layouts/backend_layout'); ?>

<?php section('styles'); ?>
<style>
    #notifications-page .dashboard-stat-card {
        min-height: 92px;
    }

    #notifications-page .dashboard-stat-card .fs-3 {
        line-height: 1.1;
    }

    #notifications-page .assignment-date {
        min-width: 78px;
    }

    #notifications-page .assignment-filter {
        width: auto;
        min-width: 150px;
    }

    #notifications-page .project-status {
        min-width: 84px;
        text-align: center;
    }

    #notifications-page .notification-table td {
        vertical-align: middle;
    }

    #notifications-page .notification-dashboard-card {
        overflow: hidden;
    }

    @media (max-width: 767.98px) {
        #notifications-page .assignment-filter {
            width: 100%;
        }

        #notifications-page .assignment-widget-header {
            align-items: stretch !important;
        }

        #notifications-page .assignment-widget-header strong {
            width: 100%;
        }

        #notifications-page .assignment-widget-header .btn {
            width: 100%;
        }
    }
</style>
<?php end_section('styles'); ?>

<?php section('content'); ?>

<?php
$dashboard = vars('dashboard');
$dashboardFilters = vars('dashboard_filters');

$statusLabels = [
    'planned' => ['Geplant', 'text-bg-primary'],
    'active' => ['Aktiv', 'text-bg-success'],
    'paused' => ['Pausiert', 'text-bg-warning'],
    'completed' => ['Abgeschlossen', 'text-bg-secondary'],
    'cancelled' => ['Abgebrochen', 'text-bg-danger'],
];
?>

<div class="container-fluid backend-page py-3"
     id="notifications-page">

    <div class="mb-4">
        <h3 class="fw-light mb-1">
            Disposition und Meldungen
        </h3>

        <p class="text-muted mb-0">
            Aktuelle Einsätze, Projekte, Abwesenheiten und
            betrieblicher Handlungsbedarf.
        </p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg">
            <div class="card h-100 dashboard-stat-card">
                <div class="card-body">
                    <div class="text-muted small">
                        Einsätze heute
                    </div>

                    <div class="fs-3 fw-semibold">
                        <?= (int) $dashboard['today_count'] ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg">
            <div class="card h-100 dashboard-stat-card">
                <div class="card-body">
                    <div class="text-muted small">
                        Einsätze im gewählten Zeitraum
                    </div>

                    <div class="fs-3 fw-semibold">
                        <?= (int) $dashboard['week_count'] ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg">
            <div class="card h-100 dashboard-stat-card">
                <div class="card-body">
                    <div class="text-muted small">
                        Aktive Projekte
                    </div>

                    <div class="fs-3 fw-semibold">
                        <?= (int) $dashboard[
                            'active_project_count'
                        ] ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg">
            <a href="<?= site_url('employee_absences') ?>"
               class="card h-100 dashboard-stat-card
                      text-decoration-none text-body">
                <div class="card-body">
                    <div class="text-muted small">
                        Offene Abwesenheiten
                    </div>

                    <div class="fs-3 fw-semibold">
                        <?= (int) $dashboard[
                            'open_absence_count'
                        ] ?>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-12 col-lg">
            <div class="card h-100 dashboard-stat-card
                        border-warning">
                <div class="card-body">
                    <div class="text-muted small">
                        Handlungsbedarf
                    </div>

                    <div class="fs-3 fw-semibold">
                        <?= (int) $dashboard[
                            'notification_count'
                        ] ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12 col-xl-8">
            <div class="card h-100">
                <div class="card-header">
                    <form method="get"
                          action="<?= site_url('notifications') ?>"
                          id="dashboard-assignment-filter"
                          class="assignment-widget-header d-flex
                                 flex-wrap align-items-center gap-2">

                        <strong class="me-auto">
                            Einsätze
                        </strong>

                        <select name="range"
                                id="dashboard-range"
                                class="form-select form-select-sm
                                       assignment-filter"
                                aria-label="Zeitraum der Einsätze">

                            <option value="this_next_week"
                                <?= $dashboardFilters['range']
                                    === 'this_next_week'
                                    ? 'selected'
                                    : '' ?>>
                                Diese und nächste Woche
                            </option>

                            <option value="month"
                                <?= $dashboardFilters['range']
                                    === 'month'
                                    ? 'selected'
                                    : '' ?>>
                                Dieser Monat
                            </option>

                            <option value="year"
                                <?= $dashboardFilters['range']
                                    === 'year'
                                    ? 'selected'
                                    : '' ?>>
                                Dieses Jahr
                            </option>

                            <option value="all"
                                <?= $dashboardFilters['range']
                                    === 'all'
                                    ? 'selected'
                                    : '' ?>>
                                Alle zukünftigen
                            </option>
                        </select>

                        <select name="project_status"
                                id="dashboard-project-status"
                                class="form-select form-select-sm
                                       assignment-filter"
                                aria-label="Projektstatus">

                            <option value="all"
                                <?= $dashboardFilters[
                                    'project_status'
                                ] === 'all'
                                    ? 'selected'
                                    : '' ?>>
                                Alle Status
                            </option>

                            <option value="planned"
                                <?= $dashboardFilters[
                                    'project_status'
                                ] === 'planned'
                                    ? 'selected'
                                    : '' ?>>
                                Geplant
                            </option>

                            <option value="active"
                                <?= $dashboardFilters[
                                    'project_status'
                                ] === 'active'
                                    ? 'selected'
                                    : '' ?>>
                                Aktiv
                            </option>

                            <option value="paused"
                                <?= $dashboardFilters[
                                    'project_status'
                                ] === 'paused'
                                    ? 'selected'
                                    : '' ?>>
                                Pausiert
                            </option>
                        </select>

                        <a href="<?= site_url('planning_projects') ?>"
                           class="btn btn-sm btn-outline-primary">
                            Zur Einsatzplanung
                        </a>
                    </form>
                </div>

                <div class="list-group list-group-flush">
                    <?php if (!$dashboard['assignments']): ?>
                        <div class="text-muted text-center py-5">
                            Für die gewählte Auswahl sind keine
                            Einsätze vorhanden.
                        </div>
                    <?php endif; ?>

                    <?php foreach (
                        $dashboard['assignments'] as $assignment
                    ): ?>
                        <?php
                        $start = strtotime(
                            $assignment['start_datetime']
                        );

                        $end = strtotime(
                            $assignment['end_datetime']
                        );

                        $customerName = trim(
                            ($assignment[
                                'customer_first_name'
                            ] ?? '') .
                            ' ' .
                            ($assignment[
                                'customer_last_name'
                            ] ?? '')
                        );
                        ?>

                        <div class="list-group-item py-3">
                            <div class="d-flex gap-3">
                                <div class="assignment-date text-center">
                                    <div class="fw-semibold">
                                        <?= date('d.m.', $start) ?>
                                    </div>

                                    <div class="text-muted small">
                                        <?= date('H:i', $start) ?>
                                        bis
                                        <?= date('H:i', $end) ?>
                                    </div>
                                </div>

                                <div class="flex-grow-1">
                                    <div class="d-flex flex-wrap
                                                align-items-center gap-2">
                                        <div class="fw-semibold">
                                            <?= e(
                                                $assignment['title']
                                            ) ?>
                                        </div>

                                        <?php if (
                                            $assignment[
                                                'project_status'
                                            ] === 'paused'
                                        ): ?>
                                            <span class="badge
                                                         text-bg-warning">
                                                Projekt pausiert
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="text-muted small">
                                        <?= e(
                                            $assignment[
                                                'project_name'
                                            ]
                                        ) ?>

                                        <?php if ($customerName): ?>
                                            · <?= e($customerName) ?>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (
                                        !empty($assignment['location'])
                                    ): ?>
                                        <div class="small mt-1">
                                            <i class="fas fa-location-dot
                                                      me-1 text-muted"></i>
                                            <?= e(
                                                $assignment['location']
                                            ) ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="small mt-1">
                                        <?php if (
                                            $assignment['employees']
                                        ): ?>
                                            <span class="me-3">
                                                <i class="fas fa-users
                                                          me-1 text-muted"></i>
                                                <?= e(
                                                    implode(
                                                        ', ',
                                                        $assignment[
                                                            'employees'
                                                        ]
                                                    )
                                                ) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge
                                                         text-bg-warning
                                                         me-2">
                                                Kein Mitarbeiter
                                            </span>
                                        <?php endif; ?>

                                        <?php if (
                                            $assignment['resources']
                                        ): ?>
                                            <span>
                                                <i class="fas
                                                          fa-boxes-stacked
                                                          me-1 text-muted"></i>
                                                <?= e(
                                                    implode(
                                                        ', ',
                                                        $assignment[
                                                            'resources'
                                                        ]
                                                    )
                                                ) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (
                    !empty(
                        $dashboard['assignments_truncated']
                    )
                ): ?>
                    <div class="card-footer text-muted small">
                        Angezeigt werden die ersten 50 von
                        <?= (int) $dashboard[
                            'matching_assignment_count'
                        ] ?>
                        passenden Einsätzen. Bitte Auswahl
                        weiter eingrenzen.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card h-100">
                <div class="card-header d-flex
                            justify-content-between
                            align-items-center">
                    <strong>Projekte</strong>

                    <a href="<?= site_url('planning_projects') ?>"
                       class="btn btn-sm btn-outline-secondary">
                        Alle
                    </a>
                </div>

                <div class="list-group list-group-flush">
                    <?php if (!$dashboard['projects']): ?>
                        <div class="text-muted text-center py-5">
                            Keine laufenden Projekte.
                        </div>
                    <?php endif; ?>

                    <?php foreach (
                        $dashboard['projects'] as $project
                    ): ?>
                        <?php
                        $status = $statusLabels[
                            $project['status']
                        ] ?? [
                            ucfirst(
                                (string) $project['status']
                            ),
                            'text-bg-secondary',
                        ];

                        $customerName = trim(
                            ($project[
                                'customer_first_name'
                            ] ?? '') .
                            ' ' .
                            ($project[
                                'customer_last_name'
                            ] ?? '')
                        );
                        ?>

                        <div class="list-group-item py-3">
                            <div class="d-flex
                                        justify-content-between
                                        align-items-start gap-3">
                                <div class="min-w-0">
                                    <div class="fw-semibold">
                                        <?= e($project['name']) ?>
                                    </div>

                                    <?php if ($customerName): ?>
                                        <div class="text-muted small">
                                            <?= e($customerName) ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (
                                        !empty(
                                            $project['start_date']
                                        )
                                    ): ?>
                                        <div class="text-muted small mt-1">
                                            Planungsstart:
                                            <?= date(
                                                'd.m.Y',
                                                strtotime(
                                                    $project[
                                                        'start_date'
                                                    ]
                                                )
                                            ) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="d-flex flex-column
                                            align-items-end gap-1">

                                    <span class="badge rounded-pill
                                                 project-status
                                                 <?= e($status[1]) ?>">
                                        <?= e(
                                            strtoupper($status[0])
                                        ) ?>
                                    </span>

                                    <?php if (
                                        !empty(
                                            $project['impact_count']
                                        )
                                    ): ?>
                                        <?php
                                        $impactClass =
                                            $project[
                                                'impact_severity'
                                            ] === 'critical'
                                                ? 'text-bg-danger'
                                                : 'text-bg-warning text-dark';
                                        ?>

                                        <span class="badge rounded-pill
                                                     <?= e(
                                                         $impactClass
                                                     ) ?>"
                                              title="<?= (int) $project[
                                                  'impact_count'
                                              ] ?> offene Beeinträchtigung(en)">
                                            <i class="fas
                                                      fa-triangle-exclamation
                                                      me-1"></i>
                                            ACHTUNG
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between
                align-items-center mb-3">
        <div>
            <h4 class="mb-1">Meldungen</h4>

            <div class="text-muted small">
                Warnungen, Wiedervorlagen und betriebliche Hinweise.
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-3">
        <button type="button"
                class="btn btn-primary notification-view-filter"
                data-view="active">
            Aktive Meldungen
        </button>

        <button type="button"
                class="btn btn-outline-secondary
                       notification-view-filter"
                data-view="trash">
            <i class="fas fa-trash-restore me-2"></i>
            Papierkorb
        </button>
    </div>

    <div class="card notification-dashboard-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0
                              notification-table">
                    <thead>
                    <tr>
                        <th>Priorität</th>
                        <th>Meldung</th>
                        <th>Status</th>
                        <th>Wiedervorlage</th>
                        <th class="text-end">Aktionen</th>
                    </tr>
                    </thead>

                    <tbody>
                    <?php if (!vars('notifications')): ?>
                        <tr>
                            <td colspan="5"
                                class="text-center text-muted py-5">
                                Keine Meldungen vorhanden.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach (
                        vars('notifications') as $notification
                    ): ?>
                        <?php
                        $badge = match (
                            $notification['severity']
                        ) {
                            'critical' => 'danger',
                            'warning' => 'warning',
                            'success' => 'success',
                            default => 'primary',
                        };

                        $severityLabel = match (
                            $notification['severity']
                        ) {
                            'critical' => 'KRITISCH',
                            'warning' => 'WARNUNG',
                            'success' => 'OK',
                            default => 'HINWEIS',
                        };
                        ?>

                        <tr data-recipient-id="<?= e(
                            $notification['recipient_id']
                        ) ?>">
                            <td>
                                <span class="badge bg-<?= e(
                                    $badge
                                ) ?>">
                                    <?= e($severityLabel) ?>
                                </span>
                            </td>

                            <td>
                                <div class="fw-semibold">
                                    <?= e(
                                        $notification['title']
                                    ) ?>
                                </div>

                                <small class="text-muted">
                                    <?= e(
                                        $notification['message']
                                        ?? ''
                                    ) ?>
                                </small>
                            </td>

                            <td>
                                <?= $notification['is_read']
                                    ? 'Gelesen'
                                    : 'Ungelesen' ?>

                                <?php if (
                                    $notification['is_pinned']
                                ): ?>
                                    · Angepinnt
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= e(
                                    $notification[
                                        'snoozed_until'
                                    ] ?? 'Sofort'
                                ) ?>
                            </td>

                            <td class="text-end text-nowrap">
                                <?php if (
                                    !empty(
                                        $notification['action_url']
                                    )
                                ): ?>
                                    <a class="btn btn-sm
                                              btn-outline-primary"
                                       href="<?= e(
                                           $notification[
                                               'action_url'
                                           ]
                                       ) ?>">
                                        <?= e(
                                            $notification[
                                                'action_label'
                                            ] ?? 'Öffnen'
                                        ) ?>
                                    </a>
                                <?php endif; ?>

                                <button type="button"
                                        class="btn btn-sm
                                               btn-outline-secondary
                                               notification-read">
                                    Gelesen
                                </button>

                                <div class="dropdown d-inline-block">
                                    <button type="button"
                                            class="btn btn-sm
                                                   btn-outline-secondary
                                                   dropdown-toggle"
                                            data-bs-toggle="dropdown">
                                        Wiedervorlage
                                    </button>

                                    <ul class="dropdown-menu">
                                        <li>
                                            <button type="button"
                                                    class="dropdown-item
                                                           notification-snooze"
                                                    data-mode="24h">
                                                In 24 Stunden
                                            </button>
                                        </li>

                                        <li>
                                            <button type="button"
                                                    class="dropdown-item
                                                           notification-snooze"
                                                    data-mode="week">
                                                In einer Woche
                                            </button>
                                        </li>
                                    </ul>
                                </div>

                                <button type="button"
                                        class="btn btn-sm
                                               btn-outline-secondary
                                               notification-history"
                                        title="Verlauf">
                                    <i class="fas fa-history"></i>
                                </button>

                                <button type="button"
                                        class="btn btn-sm
                                               btn-outline-danger
                                               notification-delete">
                                    Löschen
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade"
     id="notification-history-modal"
     tabindex="-1"
     aria-hidden="true">
    <div class="modal-dialog modal-lg
                modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    Meldungsverlauf
                </h5>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Schließen">
                </button>
            </div>

            <div class="modal-body">
                <div id="notification-history-list"
                     class="list-group list-group-flush">
                </div>
            </div>
        </div>
    </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>
<script src="<?= asset_url(
    'assets/js/pages/notifications.js'
) ?>"></script>
<?php end_section('scripts'); ?>
