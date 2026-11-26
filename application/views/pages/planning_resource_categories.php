<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div class="container backend-page py-4"
     id="planning-resource-categories-page">

    <div class="row g-4">
        <div class="col-12 col-lg-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h3 class="mb-1">Ressourcenkategorien</h3>
                    <p class="text-muted mb-0">
                        Branchenspezifische Kategorien und Unterkategorien.
                    </p>
                </div>

                <button id="add-resource-category"
                        type="button"
                        class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>
                    Kategorie
                </button>
            </div>

            <div class="card">
                <div class="card-header">
                    <strong>Kategorien</strong>
                </div>

                <div id="resource-category-list"
                     class="list-group list-group-flush">
                    <div class="text-muted text-center p-4">
                        Kategorien werden geladen …
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-7">
            <div id="resource-category-placeholder"
                 class="alert alert-light border">
                Links eine Kategorie auswählen oder eine neue Kategorie
                anlegen.
            </div>

            <div id="resource-category-editor"
                 class="card d-none">

                <div class="card-header">
                    <strong id="resource-category-editor-title">
                        Neue Kategorie
                    </strong>
                </div>

                <div class="card-body">
                    <div id="resource-category-message"
                         class="alert d-none">
                    </div>

                    <input type="hidden"
                           id="resource-category-id">

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="resource-category-name"
                                   class="form-label">
                                Bezeichnung
                                <span class="text-danger">*</span>
                            </label>

                            <input id="resource-category-name"
                                   class="form-control"
                                   maxlength="256"
                                   placeholder="z. B. Therapiegeräte">
                        </div>


                        <div class="col-md-8">
                            <label for="resource-category-parent"
                                   class="form-label">
                                Oberkategorie
                            </label>

                            <select id="resource-category-parent"
                                    class="form-select">
                                <option value="">
                                    Keine Oberkategorie
                                </option>
                            </select>

                            <div class="form-text">
                                Beispiel: Therapiegeräte → Elektrotherapie.
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label for="resource-category-color"
                                   class="form-label">
                                Farbe
                            </label>

                            <input id="resource-category-color"
                                   type="color"
                                   class="form-control form-control-color w-100"
                                   value="#7cbae8">
                        </div>

                                                <div class="col-12">
                            <label class="form-label">
                                Icon
                            </label>

                            <input type="hidden"
                                   id="resource-category-icon"
                                   value="fa-box">

                            <div id="resource-category-icon-picker"
                                 class="d-flex flex-wrap gap-2">

                                <?php
                                $configured_packs = json_decode(
                                    (string) setting(
                                        'resource_icon_packs'
                                    ),
                                    true
                                );

                                $configured_packs = is_array(
                                    $configured_packs
                                )
                                    ? $configured_packs
                                    : ['core'];

                                if (
                                    !in_array(
                                        'core',
                                        $configured_packs,
                                        true
                                    )
                                ) {
                                    array_unshift(
                                        $configured_packs,
                                        'core'
                                    );
                                }

                                $icon_packs = [
                                    'core' => [
                                        'fa-box',
                                        'fa-boxes',
                                        'fa-pallet',
                                        'fa-dolly',
                                        'fa-home',
                                        'fa-building',
                                        'fa-warehouse',
                                        'fa-door-open',
                                        'fa-chair',
                                        'fa-desktop',
                                        'fa-laptop',
                                    ],

                                    'construction' => [
                                        'fa-car',
                                        'fa-truck',
                                        'fa-trailer',
                                        'fa-tractor',
                                        'fa-tools',
                                        'fa-toolbox',
                                        'fa-wrench',
                                        'fa-hard-hat',
                                        'fa-plug',
                                    ],

                                    'cleaning' => [
                                        'fa-broom',
                                        'fa-spray-can',
                                        'fa-pump-soap',
                                        'fa-soap',
                                    ],

                                    'physiotherapy' => [
                                        'fa-bed',
                                        'fa-procedures',
                                        'fa-user-md',
                                        'fa-heartbeat',
                                        'fa-notes-medical',
                                        'fa-dumbbell',
                                    ],

                                    'dance' => [
                                        'fa-walking',
                                        'fa-running',
                                        'fa-heartbeat',
                                    ],
                                ];

                                $resource_icons = [];

                                foreach (
                                    $configured_packs as $pack
                                ) {
                                    if (
                                        empty(
                                            $icon_packs[$pack]
                                        )
                                    ) {
                                        continue;
                                    }

                                    $resource_icons = array_merge(
                                        $resource_icons,
                                        $icon_packs[$pack]
                                    );
                                }

                                $resource_icons = array_values(
                                    array_unique(
                                        $resource_icons
                                    )
                                );
                                ?>

                                <?php foreach ($resource_icons as $resource_icon): ?>
                                    <button type="button"
                                            class="btn btn-outline-secondary resource-category-icon-option"
                                            data-icon="<?= e($resource_icon) ?>"
                                            title="<?= e($resource_icon) ?>">
                                        <i class="fas <?= e($resource_icon) ?>"></i>
                                    </button>
                                <?php endforeach; ?>
                            </div>

                            <div class="form-text">
                                Einfach das gewünschte Symbol auswählen.
                            </div>
                        </div>
                        </div>

                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input id="resource-category-active"
                                       type="checkbox"
                                       class="form-check-input"
                                       checked>

                                <label for="resource-category-active"
                                       class="form-check-label">
                                    Aktiv
                                </label>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="resource-category-description"
                                   class="form-label">
                                Beschreibung
                            </label>

                            <textarea id="resource-category-description"
                                      class="form-control"
                                      rows="4"></textarea>
                        </div>
                    </div>
                </div>

                <div class="card-footer d-flex gap-2">
                    <button id="save-resource-category"
                            type="button"
                            class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>
                        Speichern
                    </button>

                    <button id="cancel-resource-category"
                            type="button"
                            class="btn btn-outline-secondary">
                        Abbrechen
                    </button>

                    <button id="delete-resource-category"
                            type="button"
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

<script src="<?= asset_url(
    'assets/js/pages/planning_resource_categories.js'
) ?>"></script>

<?php end_section('scripts'); ?>
