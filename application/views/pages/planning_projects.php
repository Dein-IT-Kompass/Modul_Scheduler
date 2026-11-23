<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div class="container-fluid backend-page py-4"
     id="planning-projects-page">

    <div class="row g-4">
        <div class="col-12 col-xl-4 col-xxl-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h3 class="mb-1">Projekte / Einsätze</h3>
                    <p class="text-muted mb-0">
                        Projektstammdaten und Einsatzplanung.
                    </p>
                </div>

                <button type="button"
                        id="add-planning-project"
                        class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>
                    Projekt
                </button>
            </div>

            <div class="input-group mb-3">
                <input type="search"
                       id="planning-project-filter"
                       class="form-control"
                       placeholder="Projekte durchsuchen">

                <button type="button"
                        id="clear-planning-project-filter"
                        class="btn btn-outline-secondary">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="card">
                <div class="card-header">
                    <strong>Projektübersicht</strong>
                </div>

                <div id="planning-project-list"
                     class="list-group list-group-flush">
                    <div class="text-muted text-center p-4">
                        Projekte werden geladen …
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-8 col-xxl-9">
            <div id="planning-project-placeholder"
                 class="alert alert-light border">
                Links ein Projekt auswählen oder ein neues Projekt anlegen.
            </div>

            <div id="planning-project-editor"
                 class="card d-none">

                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong id="planning-project-editor-title">
                        Neues Projekt
                    </strong>

                    <span id="planning-project-summary"
                          class="text-muted small"></span>
                </div>

                <div class="card-body">
                    <div id="planning-project-message"
                         class="alert d-none"></div>

                    <input type="hidden" id="planning-project-id">

                    <ul class="nav nav-tabs mb-4">
                        <li class="nav-item">
                            <button type="button"
                                    class="nav-link active"
                                    data-bs-toggle="tab"
                                    data-bs-target="#project-overview-tab">
                                Übersicht
                            </button>
                        </li>

                        <li class="nav-item">
                            <button type="button"
                                    id="planning-project-timeline-tab-button"
                                    class="nav-link"
                                    data-bs-toggle="tab"
                                    data-bs-target="#project-timeline-tab">
                                Zeitplan
                            </button>
                        </li>

                        <li class="nav-item">
                            <button type="button"
                                    id="planning-project-assignments-tab-button"
                                    class="nav-link"
                                    data-bs-toggle="tab"
                                    data-bs-target="#project-assignments-tab">
                                Einsätze
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div id="project-overview-tab"
                             class="tab-pane fade show active">

                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label for="planning-project-name"
                                           class="form-label">
                                        Projektname
                                        <span class="text-danger">*</span>
                                    </label>

                                    <input id="planning-project-name"
                                           class="form-control"
                                           maxlength="256"
                                           placeholder="Projekttitel eingeben">
                                </div>

                                <div class="col-md-4">
                                    <label for="planning-project-status"
                                           class="form-label">
                                        Status
                                    </label>

                                    <select id="planning-project-status"
                                            class="form-select">
                                        <option value="planned">Geplant</option>
                                        <option value="active">Aktiv</option>
                                        <option value="paused">Pausiert</option>
                                        <option value="completed">Abgeschlossen</option>
                                        <option value="cancelled">Abgebrochen</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="planning-project-customer"
                                           class="form-label">
                                        Kunde
                                        <span class="text-danger">*</span>
                                    </label>

                                    <select id="planning-project-customer"
                                            class="form-select">
                                        <option value="">
                                            Kunde auswählen
                                        </option>

                                        <?php foreach (vars('customers') as $customer): ?>
                                            <?php
                                            $customerName = trim(
                                                ($customer['first_name'] ?? '')
                                                . ' '
                                                . ($customer['last_name'] ?? '')
                                            );

                                            $customerAddress = trim(
                                                implode(
                                                    ', ',
                                                    array_filter([
                                                        trim(
                                                            (string) (
                                                                $customer['address'] ?? ''
                                                            )
                                                        ),
                                                        trim(
                                                            (string) (
                                                                ($customer['zip_code'] ?? '')
                                                                . ' '
                                                                . ($customer['city'] ?? '')
                                                            )
                                                        ),
                                                    ])
                                                )
                                            );

                                            $customerPhone =
                                                $customer['mobile_number']
                                                ?? $customer['phone_number']
                                                ?? '';
                                            ?>

                                            <option
                                                value="<?= (int) $customer['id'] ?>"
                                                data-address="<?= htmlspecialchars(
                                                    $customerAddress,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>"
                                                data-phone="<?= htmlspecialchars(
                                                    (string) $customerPhone,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>">
                                                <?= htmlspecialchars(
                                                    $customerName ?: $customer['email'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">
                                        Kundenadresse
                                    </label>

                                    <div id="planning-project-customer-address"
                                         class="form-control bg-light text-muted"
                                         style="min-height: 38px;">
                                        Noch kein Kunde ausgewählt.
                                    </div>

                                    <div id="planning-project-customer-phone"
                                         class="form-text">
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-check">
                                        <input type="checkbox"
                                               class="form-check-input"
                                               id="planning-project-custom-location">

                                        <label class="form-check-label"
                                               for="planning-project-custom-location">
                                            Abweichende Baustellenadresse
                                        </label>
                                    </div>
                                </div>

                                <div class="col-12 d-none"
                                     id="planning-project-location-wrapper">
                                    <label for="planning-project-location"
                                           class="form-label">
                                        Baustellenadresse
                                    </label>

                                    <input id="planning-project-location"
                                           class="form-control"
                                           maxlength="512"
                                           placeholder="Straße, Hausnummer, PLZ Ort">

                                    <div class="form-text">
                                        Leer lassen, wenn die Kundenadresse
                                        als Einsatzort verwendet werden soll.
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label for="planning-project-lead"
                                           class="form-label">
                                        Projektleitung
                                    </label>

                                    <select id="planning-project-lead"
                                            class="form-select">
                                        <option value="">
                                            Keine Projektleitung
                                        </option>

                                        <?php foreach (vars('employees') as $employee): ?>
                                            <option value="<?= e($employee['id']) ?>">
                                                <?= e(trim(
                                                    ($employee['first_name'] ?? '') .
                                                    ' ' .
                                                    ($employee['last_name'] ?? '')
                                                )) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="planning-project-start-date"
                                                   class="form-label">
                                                Planungsstart
                                            </label>

                                            <input id="planning-project-start-date"
                                                   type="text"
                                                   class="form-control"
                                                   inputmode="numeric"
                                                   placeholder="TT.MM.JJJJ"
                                                   autocomplete="off">
                                        </div>

                                        <div class="col-md-6">
                                            <label for="planning-project-end-date"
                                                   class="form-label">
                                                Voraussichtliches Projektenddatum
                                            </label>

                                            <input id="planning-project-end-date"
                                                   type="text"
                                                   class="form-control"
                                                   inputmode="numeric"
                                                   placeholder="optional"
                                                   autocomplete="off">

                                            <div class="form-text">
                                                Optional. Kann sich durch den
                                                Projektverlauf noch ändern.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label for="planning-project-color"
                                           class="form-label">
                                        Projektfarbe
                                    </label>

                                    <input id="planning-project-color"
                                           type="color"
                                           class="form-control form-control-color w-100"
                                           value="#7cbae8">
                                </div>

                                <div class="col-12">
                                    <label for="planning-project-description"
                                           class="form-label">
                                        Beschreibung
                                    </label>

                                    <textarea id="planning-project-description"
                                              class="form-control"
                                              rows="6"></textarea>
                                </div>
                            </div>
                        </div>

                        

                        <div id="project-timeline-tab"
                             class="tab-pane fade">

                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                                <div>
                                    <h5 class="mb-1 fw-light">
                                        Projektzeitplan
                                    </h5>

                                    <div id="project-timeline-period"
                                         class="small text-muted">
                                    </div>
                                </div>

                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <label for="project-timeline-color-mode"
                                           class="form-label mb-0">
                                        Darstellung
                                    </label>

                                    <select id="project-timeline-color-mode"
                                            class="form-select form-select-sm"
                                            style="width: auto;">
                                        <option value="status">
                                            Nach Status
                                        </option>
                                        <option value="progress">
                                            Nach Fortschritt
                                        </option>
                                        <option value="phase">
                                            Bausteinfarbe
                                        </option>
                                    </select>

                                    <button type="button"
                                            id="add-project-phase"
                                            class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus me-2"></i>
                                        Baustein
                                    </button>
                                </div>
                            </div>

                            <div id="project-phase-editor"
                                 class="card mb-4 d-none">

                                <div class="card-header">
                                    <strong id="project-phase-editor-title">
                                        Neuer Baustein
                                    </strong>
                                </div>

                                <div class="card-body">
                                    <div id="project-phase-message"
                                         class="alert d-none"></div>

                                    <input type="hidden"
                                           id="project-phase-id">

                                    <div class="row g-3">
                                        <div class="col-md-8">
                                            <label for="project-phase-name"
                                                   class="form-label">
                                                Bezeichnung
                                                <span class="text-danger">*</span>
                                            </label>

                                            <input id="project-phase-name"
                                                   class="form-control"
                                                   maxlength="256">
                                        </div>

                                        <div class="col-md-4">
                                            <label for="project-phase-status"
                                                   class="form-label">
                                                Status
                                            </label>

                                            <select id="project-phase-status"
                                                    class="form-select">
                                                <option value="planned">
                                                    Geplant
                                                </option>
                                                <option value="active">
                                                    Aktiv
                                                </option>
                                                <option value="paused">
                                                    Pausiert
                                                </option>
                                                <option value="completed">
                                                    Abgeschlossen
                                                </option>
                                                <option value="cancelled">
                                                    Abgebrochen
                                                </option>
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="project-phase-start-date"
                                                   class="form-label">
                                                Beginn
                                            </label>

                                            <input id="project-phase-start-date"
                                                   type="date"
                                                   class="form-control">
                                        </div>

                                        <div class="col-md-6">
                                            <label for="project-phase-end-date"
                                                   class="form-label">
                                                Ende
                                            </label>

                                            <input id="project-phase-end-date"
                                                   type="date"
                                                   class="form-control">
                                        </div>

                                        <div class="col-md-6">
                                            <label for="project-phase-parent"
                                                   class="form-label">
                                                Übergeordneter Baustein
                                            </label>

                                            <select id="project-phase-parent"
                                                    class="form-select">
                                                <option value="">
                                                    Kein übergeordneter Baustein
                                                </option>
                                            </select>
                                        </div>

                                        <div class="col-md-4">
                                            <label for="project-phase-progress"
                                                   class="form-label">
                                                Fortschritt
                                            </label>

                                            <input id="project-phase-progress"
                                                   type="range"
                                                   class="form-range"
                                                   min="0"
                                                   max="100"
                                                   step="5"
                                                   value="0">

                                            <div class="text-center">
                                                <span id="project-phase-progress-value">
                                                    0 %
                                                </span>
                                            </div>
                                        </div>

                                        <div class="col-md-2">
                                            <label for="project-phase-color"
                                                   class="form-label">
                                                Fallback-Farbe
                                            </label>

                                            <input id="project-phase-color"
                                                   type="color"
                                                   class="form-control form-control-color w-100"
                                                   value="#7cbae8">
                                        </div>

                                        <div class="col-12">
                                            <label for="project-phase-description"
                                                   class="form-label">
                                                Beschreibung
                                            </label>

                                            <textarea id="project-phase-description"
                                                      class="form-control"
                                                      rows="3"></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-footer d-flex gap-2">
                                    <button type="button"
                                            id="save-project-phase"
                                            class="btn btn-primary">
                                        Speichern
                                    </button>

                                    <button type="button"
                                            id="cancel-project-phase"
                                            class="btn btn-outline-secondary">
                                        Abbrechen
                                    </button>

                                    <button type="button"
                                            id="delete-project-phase"
                                            class="btn btn-outline-danger ms-auto">
                                        Löschen
                                    </button>
                                </div>
                            </div>

                            <div id="project-timeline-empty"
                                 class="alert alert-light border">
                                Noch keine Zeitplan-Bausteine vorhanden.
                            </div>

                            <div id="project-timeline"
                                 class="project-timeline d-none">
                            </div>
                        </div>

                        <div id="project-assignments-tab"
                             class="tab-pane fade">

                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                                <div>
                                    <h5 class="mb-1 fw-light">
                                        Konkrete Einsätze
                                    </h5>

                                    <p class="text-muted mb-0">
                                        Diese Einsätze blockieren Mitarbeiter
                                        und Ressourcen im Kalender.
                                    </p>
                                </div>

                                <button type="button"
                                        id="add-project-assignment"
                                        class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus me-2"></i>
                                    Einsatz
                                </button>
                            </div>

                            <div id="project-assignment-editor"
                                 class="card mb-4 d-none">

                                <div class="card-header">
                                    <strong id="project-assignment-editor-title">
                                        Neuer Einsatz
                                    </strong>
                                </div>

                                <div class="card-body">
                                    <div id="project-assignment-message"
                                         class="alert d-none"></div>

                                    <input type="hidden"
                                           id="project-assignment-id">

                                    <div class="row g-3">
                                        <div class="col-md-8">
                                            <label for="project-assignment-title"
                                                   class="form-label">
                                                Titel
                                                <span class="text-danger">*</span>
                                            </label>

                                            <input id="project-assignment-title"
                                                   class="form-control"
                                                   maxlength="256">
                                        </div>

                                        <div class="col-md-4">
                                            <label for="project-assignment-status"
                                                   class="form-label">
                                                Status
                                            </label>

                                            <select id="project-assignment-status"
                                                    class="form-select">
                                                <option value="planned">
                                                    Geplant
                                                </option>
                                                <option value="confirmed">
                                                    Bestätigt
                                                </option>
                                                <option value="in_progress">
                                                    In Arbeit
                                                </option>
                                                <option value="completed">
                                                    Abgeschlossen
                                                </option>
                                                <option value="cancelled">
                                                    Abgebrochen
                                                </option>
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="project-assignment-phase"
                                                   class="form-label">
                                                Projektbaustein
                                            </label>

                                            <select id="project-assignment-phase"
                                                    class="form-select">
                                                <option value="">
                                                    Kein Baustein
                                                </option>
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="project-assignment-location"
                                                   class="form-label">
                                                Einsatzort
                                            </label>

                                            <input id="project-assignment-location"
                                                   class="form-control"
                                                   maxlength="512">
                                        </div>

                                        <div class="col-md-6">
                                            <label for="project-assignment-start"
                                                   class="form-label">
                                                Beginn
                                                <span class="text-danger">*</span>
                                            </label>

                                            <input id="project-assignment-start"
                                                   type="datetime-local"
                                                   class="form-control">
                                        </div>

                                        <div class="col-md-6">
                                            <label for="project-assignment-end"
                                                   class="form-label">
                                                Ende
                                                <span class="text-danger">*</span>
                                            </label>

                                            <input id="project-assignment-end"
                                                   type="datetime-local"
                                                   class="form-control">
                                        </div>

                                        <div class="col-md-6">
                                            <label for="project-assignment-priority"
                                                   class="form-label">
                                                Priorität
                                            </label>

                                            <select id="project-assignment-priority"
                                                    class="form-select">
                                                <option value="50">
                                                    Regulär
                                                </option>
                                                <option value="70"
                                                        selected>
                                                    Konkreter Einsatz
                                                </option>
                                                <option value="80">
                                                    Dringender Einsatz
                                                </option>
                                                <option value="90">
                                                    Sperre / Wartung
                                                </option>
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="project-assignment-color"
                                                   class="form-label">
                                                Fallback-Farbe
                                            </label>

                                            <input id="project-assignment-color"
                                                   type="color"
                                                   class="form-control form-control-color w-100"
                                                   value="#f28c28">
                                        </div>

                                        <div class="col-12">
                                            <label for="project-assignment-notes"
                                                   class="form-label">
                                                Hinweise
                                            </label>

                                            <textarea id="project-assignment-notes"
                                                      class="form-control"
                                                      rows="3"></textarea>
                                        </div>

                                        <div class="col-md-4">
                                            <h6>Teams</h6>

                                            <div class="border rounded p-3 planning-assignment-checklist">
                                                <?php foreach (vars('teams') as $team): ?>
                                                    <div class="form-check mb-2">
                                                        <input type="checkbox"
                                                               class="form-check-input assignment-team"
                                                               id="assignment-team-<?= e($team['id']) ?>"
                                                               value="<?= e($team['id']) ?>">

                                                        <label class="form-check-label"
                                                               for="assignment-team-<?= e($team['id']) ?>">
                                                            <?= e($team['name']) ?>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <h6>Mitarbeiter</h6>

                                            <div class="border rounded p-3 planning-assignment-checklist">
                                                <?php foreach (vars('employees') as $employee): ?>
                                                    <div class="form-check mb-2">
                                                        <input type="checkbox"
                                                               class="form-check-input assignment-employee"
                                                               id="assignment-employee-<?= e($employee['id']) ?>"
                                                               value="<?= e($employee['id']) ?>">

                                                        <label class="form-check-label"
                                                               for="assignment-employee-<?= e($employee['id']) ?>">
                                                            <?= e(trim(
                                                                ($employee['first_name'] ?? '') .
                                                                ' ' .
                                                                ($employee['last_name'] ?? '')
                                                            )) ?>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <h6>Ressourcen</h6>

                                            <div class="border rounded p-3 planning-assignment-checklist">
                                                <?php foreach (vars('resources') as $resource): ?>
                                                    <div class="form-check mb-2">
                                                        <input type="checkbox"
                                                               class="form-check-input assignment-resource"
                                                               id="assignment-resource-<?= e($resource['id']) ?>"
                                                               value="<?= e($resource['id']) ?>">

                                                        <label class="form-check-label"
                                                               for="assignment-resource-<?= e($resource['id']) ?>">
                                                            <?= e($resource['name']) ?>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-footer d-flex gap-2">
                                    <button type="button"
                                            id="save-project-assignment"
                                            class="btn btn-primary">
                                        Speichern
                                    </button>

                                    <button type="button"
                                            id="cancel-project-assignment"
                                            class="btn btn-outline-secondary">
                                        Abbrechen
                                    </button>

                                    <button type="button"
                                            id="delete-project-assignment"
                                            class="btn btn-outline-danger ms-auto">
                                        Löschen
                                    </button>
                                </div>
                            </div>

                            <div id="project-assignments-empty"
                                 class="alert alert-light border">
                                Noch keine konkreten Einsätze vorhanden.
                            </div>

                            <div id="project-assignment-list"
                                 class="list-group">
                            </div>
                        </div>

                        </div>
                    </div>
                </div>

                <div class="card-footer d-flex gap-2">
                    <button type="button"
                            id="save-planning-project"
                            class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>
                        Speichern
                    </button>

                    <button type="button"
                            id="cancel-planning-project"
                            class="btn btn-outline-secondary">
                        Abbrechen
                    </button>

                    <button type="button"
                            id="delete-planning-project"
                            class="btn btn-outline-danger ms-auto">
                        <i class="fas fa-trash me-2"></i>
                        Löschen
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .planning-project-checklist {
        max-height: 280px;
        overflow-y: auto;
    }
    .project-timeline {
        border: 1px solid var(--bs-border-color);
        border-radius: .375rem;
        overflow: hidden;
    }

    .project-timeline-row {
        display: grid;
        grid-template-columns: minmax(180px, 25%) 1fr;
        min-height: 62px;
        border-bottom: 1px solid var(--bs-border-color);
    }

    .project-timeline-row:last-child {
        border-bottom: 0;
    }

    .project-timeline-label {
        padding: .75rem;
        background: var(--bs-light);
        border-right: 1px solid var(--bs-border-color);
    }

    .project-timeline-track {
        position: relative;
        min-height: 62px;
        background-image: linear-gradient(
            to right,
            rgba(0, 0, 0, .06) 1px,
            transparent 1px
        );
        background-size: 10% 100%;
    }

    .project-timeline-bar {
        position: absolute;
        top: 16px;
        height: 30px;
        min-width: 8px;
        border-radius: .35rem;
        color: #fff;
        cursor: pointer;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, .25);
    }

    .project-timeline-bar-progress {
        height: 100%;
        background: rgba(0, 0, 0, .2);
        pointer-events: none;
    }

    .project-timeline-bar-label {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        padding: 0 .5rem;
        font-size: .8rem;
        white-space: nowrap;
        pointer-events: none;
    }

    .planning-assignment-checklist {
        max-height: 240px;
        overflow-y: auto;
    }

</style>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script src="<?= asset_url('assets/js/pages/planning_projects.js') ?>"></script>

<?php end_section('scripts'); ?>
