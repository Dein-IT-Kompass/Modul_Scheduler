$(function () {
    'use strict';

    let resources = [];
    let categories = [];
    let selectedResourceId = null;

    const typeLabels = {
        vehicle: 'Fahrzeug',
        machine: 'Maschine',
        equipment: 'Gerät',
        room: 'Raum',
        workplace: 'Arbeitsplatz',
        other: 'Sonstiges',
    };

    const typeIcons = {
        vehicle: 'fa-truck',
        machine: 'fa-tractor',
        equipment: 'fa-tools',
        room: 'fa-door-open',
        workplace: 'fa-desktop',
        other: 'fa-box',
    };

    const $list = $('#planning-resource-list');
    const $editor = $('#planning-resource-editor');
    const $placeholder = $('#planning-resource-placeholder');
    const $message = $('#planning-resource-message');
    const $filter = $('#resource-filter');

    function notify(text) {
        App.Layouts.Backend.displayNotification(text);
    }

    function showMessage(text, type = 'danger') {
        $message
            .removeClass(
                'd-none alert-danger alert-success alert-warning'
            )
            .addClass(`alert-${type}`)
            .text(text);
    }

    function clearMessage() {
        $message.addClass('d-none').text('');
    }

    function openEditor() {
        $placeholder.addClass('d-none');
        $editor.removeClass('d-none');
    }

    function closeEditor() {
        $editor.addClass('d-none');
        $placeholder.removeClass('d-none');
        selectedResourceId = null;
    }

    function renderCategoryOptions(selectedId = '') {
        const $category = $('#planning-resource-category');

        $category
            .empty()
            .append(
                $('<option/>', {
                    value: '',
                    text: 'Keine Kategorie',
                })
            );

        categories.forEach((category) => {
            $('<option/>', {
                value: category.id,
                text: category.name,
            }).appendTo($category);
        });

        $category.val(
            selectedId ? String(selectedId) : ''
        );
    }

    function renderCarrierOptions(
        selectedId = '',
        excludedResourceId = null
    ) {
        const $carrier = $('#planning-resource-carrier');

        $carrier
            .empty()
            .append(
                $('<option/>', {
                    value: '',
                    text: 'Trägerressource wählen',
                })
            );

        resources
            .filter(
                (resource) =>
                    Number(resource.is_active) === 1 &&
                    Number(resource.id) !==
                        Number(excludedResourceId)
            )
            .forEach((resource) => {
                $('<option/>', {
                    value: resource.id,
                    text: resource.name,
                }).appendTo($carrier);
            });

        $carrier.val(
            selectedId ? String(selectedId) : ''
        );
    }

    function updateLocationBehaviorFields() {
        const carrierBound =
            $('#planning-resource-location-behavior').val() ===
            'carrier_bound';

        $('#planning-resource-carrier-wrap')
            .toggleClass('d-none', !carrierBound);

        $('#planning-resource-carrier')
            .prop('disabled', !carrierBound);

        if (!carrierBound) {
            $('#planning-resource-carrier').val('');
        }
    }

    function resetEditor() {
        selectedResourceId = null;

        $('#planning-resource-id').val('');
        $('#planning-resource-name').val('');
        $('#planning-resource-inventory-number').val('');
        $('#planning-resource-location').val('');
        $('#planning-resource-location-behavior').val('mobile');
        renderCarrierOptions();
        renderCategoryOptions();
        updateLocationBehaviorFields();
        $('#planning-resource-color').val('#7cbae8');
        $('#planning-resource-active').prop('checked', true);
        $('#planning-resource-description').val('');

        $('#planning-resource-editor-title').text('Neue Ressource');
        $('#delete-planning-resource').hide();

        clearMessage();
    }

    function loadResources(selectId = null) {
        $.get(App.Utils.Url.siteUrl('planning_resources/search'))
            .done((response) => {
                resources = response.resources || [];
                categories = response.categories || [];

                renderCategoryOptions();
                renderCarrierOptions();
                renderList();

                if (selectId) {
                    const resource = resources.find(
                        (item) => Number(item.id) === Number(selectId)
                    );

                    if (resource) {
                        editResource(resource);
                    }
                }
            })
            .fail((xhr) => {
                $list.empty().append(
                    $('<div/>', {
                        class: 'alert alert-danger m-3',
                        text:
                            xhr.responseJSON?.message ||
                            'Ressourcen konnten nicht geladen werden.',
                    })
                );
            });
    }

    function renderList() {
        const keyword = $filter.val().trim().toLowerCase();

        const filteredResources = resources.filter((resource) => {
            const searchable = [
                resource.name,
                resource.resource_type,
                typeLabels[resource.resource_type],
                resource.location,
                resource.inventory_number,
            ]
                .filter(Boolean)
                .join(' ')
                .toLowerCase();

            return searchable.includes(keyword);
        });

        $list.empty();

        if (!filteredResources.length) {
            $('<div/>', {
                class: 'text-muted text-center p-4',
                text: keyword
                    ? 'Keine passende Ressource gefunden.'
                    : 'Noch keine Ressourcen vorhanden.',
            }).appendTo($list);

            return;
        }

        filteredResources.forEach((resource) => {
            const isActive = Number(resource.is_active) === 1;
            const details = [
                resource.category_name || null,
                resource.inventory_number || null,
                resource.location || null,
                resource.location_behavior === 'project_bound'
                    ? 'projektgebunden'
                    : null,
                resource.location_behavior === 'carrier_bound'
                    ? `Träger: ${resource.carrier_name || 'nicht gewählt'}`
                    : null,
            ]
                .filter(Boolean)
                .join(' · ');

            const $color = $('<span/>', {
                css: {
                    width: '12px',
                    height: '12px',
                    borderRadius: '50%',
                    backgroundColor: resource.color || '#7cbae8',
                    flex: '0 0 auto',
                },
            });

            const $content = $('<div/>', {
                class: 'flex-grow-1',
                html: [
                    $('<div/>', {
                        class:
                            'fw-semibold' +
                            (isActive ? '' : ' text-muted'),
                        text: resource.name,
                    }),
                    $('<small/>', {
                        class: 'text-muted',
                        text: details,
                    }),
                ],
            });

            const $status = $('<span/>', {
                class: isActive
                    ? 'badge bg-success'
                    : 'badge bg-secondary',
                text: isActive ? 'Aktiv' : 'Inaktiv',
            });

            $('<button/>', {
                type: 'button',
                class:
                    'list-group-item list-group-item-action ' +
                    'd-flex align-items-center gap-3',
                html: [
                    $('<i/>', {
                        class:
                            'fas ' +
                            (typeIcons[resource.resource_type] ||
                                'fa-box') +
                            ' text-muted',
                    }),
                    $color,
                    $content,
                    $status,
                ],
                click: () => editResource(resource),
            }).appendTo($list);
        });
    }

    function editResource(resource) {
        selectedResourceId = Number(resource.id);

        $('#planning-resource-id').val(resource.id);
        $('#planning-resource-name').val(resource.name);
        $('#planning-resource-inventory-number').val(
            resource.inventory_number || ''
        );
        $('#planning-resource-location').val(
            resource.location || ''
        );

        $('#planning-resource-location-behavior').val(
            resource.location_behavior || 'mobile'
        );

        renderCarrierOptions(
            resource.id_carrier_resource || '',
            resource.id
        );

        renderCategoryOptions(
            resource.id_planning_resource_categories || ''
        );

        updateLocationBehaviorFields();

        $('#planning-resource-color').val(
            resource.color || '#7cbae8'
        );
        $('#planning-resource-active').prop(
            'checked',
            Number(resource.is_active) === 1
        );
        $('#planning-resource-description').val(
            resource.description || ''
        );

        $('#planning-resource-editor-title').text(
            'Ressource bearbeiten'
        );
        $('#delete-planning-resource').show();

        clearMessage();
        openEditor();
    }

    function collectResource() {
        return {
            id: $('#planning-resource-id').val() || undefined,
            name: $('#planning-resource-name').val().trim(),
            inventory_number:
                $('#planning-resource-inventory-number').val().trim(),
            location: $('#planning-resource-location').val().trim(),
            location_behavior:
                $('#planning-resource-location-behavior').val(),
            id_carrier_resource:
                $('#planning-resource-carrier').val() || null,
            id_planning_resource_categories:
                $('#planning-resource-category').val() || null,
            color: $('#planning-resource-color').val(),
            is_active: $('#planning-resource-active').prop('checked')
                ? 1
                : 0,
            description:
                $('#planning-resource-description').val().trim(),
        };
    }

    $('#planning-resource-location-behavior').on(
        'change',
        updateLocationBehaviorFields
    );

    $('#add-planning-resource').on('click', () => {
        resetEditor();
        openEditor();
        $('#planning-resource-name').trigger('focus');
    });

    $('#cancel-planning-resource').on('click', closeEditor);

    $('#save-planning-resource').on('click', () => {
        clearMessage();

        const resource = collectResource();

        if (!resource.name) {
            showMessage('Bitte eine Bezeichnung eingeben.');
            $('#planning-resource-name').trigger('focus');
            return;
        }

        $.post(App.Utils.Url.siteUrl('planning_resources/store'), {
            csrf_token: vars('csrf_token'),
            resource,
        })
            .done(() => {
                notify('Ressource gespeichert.');
                closeEditor();
                loadResources();
            })
            .fail((xhr) => {
                showMessage(
                    xhr.responseJSON?.message ||
                        'Ressource konnte nicht gespeichert werden.'
                );
            });
    });

    $('#delete-planning-resource').on('click', () => {
        const resourceId = Number(
            $('#planning-resource-id').val()
        );

        if (
            !resourceId ||
            !window.confirm('Ressource wirklich löschen?')
        ) {
            return;
        }

        $.post(App.Utils.Url.siteUrl('planning_resources/delete'), {
            csrf_token: vars('csrf_token'),
            resource_id: resourceId,
        })
            .done(() => {
                notify('Ressource gelöscht.');
                closeEditor();
                loadResources();
            })
            .fail((xhr) => {
                showMessage(
                    xhr.responseJSON?.message ||
                        'Ressource konnte nicht gelöscht werden.'
                );
            });
    });

    $filter.on('input', renderList);

    $('#clear-resource-filter').on('click', () => {
        $filter.val('');
        renderList();
        $filter.trigger('focus');
    });

    loadResources();
});
