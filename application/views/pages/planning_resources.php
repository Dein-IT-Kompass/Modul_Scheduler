<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div class="container backend-page py-4" id="planning-resources-page">
    <div class="row g-4">
        <div class="col-12 col-lg-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h3 class="mb-1">Ressourcen</h3>
                    <p class="text-muted mb-0">
                        Fahrzeuge, Maschinen, Geräte, Räume und Arbeitsplätze.
                    </p>
                </div>

                <button type="button"
                        id="add-planning-resource"
                        class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>
                    Ressource
                </button>
            </div>

            <div class="input-group mb-3">
                <input type="search"
                       id="resource-filter"
                       class="form-control"
                       placeholder="Ressourcen durchsuchen">

                <button type="button"
                        class="btn btn-outline-secondary"
                        id="clear-resource-filter"
                        title="Filter leeren">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="card">
                <div class="card-header">
                    <strong>Ressourcenbestand</strong>
                </div>

                <div id="planning-resource-list"
                     class="list-group list-group-flush">
                    <div class="text-muted text-center p-4">
                        Ressourcen werden geladen …
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-7">
            <div id="planning-resource-placeholder"
                 class="alert alert-light border">
                Links eine Ressource auswählen oder eine neue Ressource anlegen.
            </div>

            <div id="planning-resource-editor"
                 class="card d-none">

                <div class="card-header">
                    <strong id="planning-resource-editor-title">
                        Neue Ressource
                    </strong>
                </div>

                <div class="card-body">
                    <div id="planning-resource-message"
                         class="alert d-none">
                    </div>

                    <input type="hidden"
                           id="planning-resource-id">

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="planning-resource-name"
                                   class="form-label">
                                Bezeichnung
                                <span class="text-danger">*</span>
                 *          </label>

              *             <input id="planning-resource-name"
                                   class="form-control"
                                   maxlength="256"
                                   placeholder="z. B. Minibagger CAT 301">
                        </div>


                        <div class="col-md-6">
                            <label for="planning-resource-inventory-number"
                                   class="form-label">
                                Inventarnummer
                            </label>

                            <input id="planning-resource-inventory-number"
                                   class="form-control"
                                   maxlength="128"
                                   placeholder="z. B. BAG-001">
                        </div>

                        <div class="col-md-6">
                            <label for="planning-resource-location"
                                   class="form-label">
                                Standardstandort
                            </label>

                            <input id="planning-resource-location"
                                   class="form-control"
                                   maxlength="256"
                                   placeholder="z. B. Betriebshof Bitburg">
                            <div class="form-text">
                                Heimatstandort der Ressource. Bei einem Einsatz kann
                                automatisch der Termin-, Kunden- oder Projektstandort
                                verwendet werden.
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="planning-resource-location-behavior"
                                   class="form-label">
                                Standortverhalten
                            </label>

                            <select id="planning-resource-location-behavior"
                                    class="form-select">
                                <option value="stationary">
                                    Standortgebunden
                                </option>
                                <option value="mobile">
                                    Mobil / folgt dem Termin
                                </option>
                                <option value="carrier_bound">
                                    An Trägerressource gebunden
                                </option>
                                <option value="project_bound">
                                    Projekt als Basisstandort
                                </option>
                            </select>

                            <div class="form-text">
                                Bestimmt den regulären Basisstandort. Zeitlich
                                begrenzte Einsätze mit höherer Priorität können
                                diese Zuordnung vorübergehend übersteuern.
                            </div>
                        </div>

                        <div class="col-md-6 d-none"
                             id="planning-resource-carrier-wrap">
                            <label for="planning-resource-carrier"
                                   class="form-label">
                                Trägerressource
                            </label>

                            <select id="planning-resource-carrier"
                                    class="form-select">
                                <option value="">
                                    Trägerressource wählen
                                </option>
                            </select>

                            <div class="form-text">
                                Zum Beispiel ein Nasssauger im Transporter.
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="planning-resource-category"
                                   class="form-label">
                                Kategorie
                            </label>

                            <select id="planning-resource-category"
                                    class="form-select">
                                <option value="">
                                    Keine Kategorie
                                </option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="planning-resource-color"
                                   class="form-label">
                                Kalenderfarbe
                            </label>

                            <input id="planning-resource-color"
                                   type="color"
                                   class="form-control form-control-color w-100"
                                   value="#7cbae8">
                        </div>

                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input id="planning-resource-active"
                                       type="checkbox"
                                       class="form-check-input"
                                       checked>

                                <label for="planning-resource-active"
                                       class="form-check-label">
                                    Ressource aktiv
                                </label>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="planning-resource-description"
                                   class="form-label">
                                Beschreibung / Hinweise
                            </label>

                            <textarea id="planning-resource-description"
                                      class="form-control"
                                      rows="5"
                                      placeholder="Ausstattung, Führerscheinanforderung, Wartungshinweise …"></textarea>
                        </div>
                    </div>
                </div>

                <div class="card-footer d-flex flex-wrap gap-2">
                    <button type="button"
                            id="save-planning-resource"
                            class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>
                        Speichern
                    </button>

                    <button type="button"
                            id="cancel-planning-resource"
                            class="btn btn-outline-secondary">
                        Abbrechen
                    </button>

                    <button type="button"
                            id="delete-planning-resource"
                            class="btn btn-outline-danger ms-auto">
                        <i class="fas fa-trash me-2"></i>
                        Löschen
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script src="<?= asset_url('assets/js/pages/planning_resources.js') ?>"></script>

<?php end_section('scripts'); ?>
