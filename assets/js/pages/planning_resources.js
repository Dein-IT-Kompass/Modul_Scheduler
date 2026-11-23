$(function () {
    'use strict';

    let resources = [];
    let categories = [];
    let selectedResourceId = null;
    let resourceUnavailabilityRecords = [];

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

    const unavailabilityTypeLabels = {
        defect: 'Defekt',
        maintenance: 'Wartung',
        blocked: 'Gesperrt',
        other: 'Sonstige Nichtverfügbarkeit',
    };

    function localDatetime(value) {
        if (!value) {
            return '';
        }

        return moment(
            value,
            'YYYY-MM-DD HH:mm:ss'
        ).format('YYYY-MM-DDTHH:mm');
    }

    function serverDatetime(value) {
        if (!value) {
            return null;
        }

        return moment(
            value,
            'YYYY-MM-DDTHH:mm'
        ).format('YYYY-MM-DD HH:mm:ss');
    }

    function resetUnavailabilityEditor() {
        $('#resource-unavailability-id').val('');
        $('#resource-unavailability-type').val(
            'defect'
        );
        $('#resource-unavailability-title').val('');
        $('#resource-unavailability-start').val(
            moment().format('YYYY-MM-DDTHH:mm')
        );
        $('#resource-unavailability-end').val('');
        $('#resource-unavailability-open-end')
            .prop('checked', true);
        $('#resource-unavailability-notes').val('');

        updateUnavailabilityEndField();

        $('#resource-unavailability-editor')
            .removeClass('d-none');
    }

    function updateUnavailabilityEndField() {
        const isMaintenance =
            $('#resource-unavailability-type').val()
                === 'maintenance';

        if (isMaintenance) {
            $('#resource-unavailability-open-end')
                .prop('checked', false)
                .prop('disabled', true);
        } else {
            $('#resource-unavailability-open-end')
                .prop('disabled', false);
        }

        const openEnd =
            $('#resource-unavailability-open-end')
                .prop('checked');

        $('#resource-unavailability-end')
            .prop('disabled', openEnd);

        if (openEnd) {
            $('#resource-unavailability-end').val('');
        }
    }

    function loadResourceUnavailabilities() {
        if (!selectedResourceId) {
            $('#resource-unavailability-section')
                .addClass('d-none');
            return;
        }

        $('#resource-unavailability-section')
            .removeClass('d-none');

        $.get(
            App.Utils.Url.siteUrl(
                'planning_resources/unavailabilities'
            ),
            {
                resource_id: selectedResourceId,
            }
        )
            .done((response) => {
                resourceUnavailabilityRecords =
                    response.records || [];

                renderAvailabilitySummary(
                    resourceUnavailabilityRecords
                );

                renderResourceUnavailabilities(
                    resourceUnavailabilityRecords
                );
            })
            .fail((xhr) => {
                $('#resource-unavailability-message')
                    .removeClass('d-none alert-success')
                    .addClass('alert-danger')
                    .text(
                        xhr.responseJSON?.message ||
                        'Verfügbarkeiten konnten nicht geladen werden.'
                    );
            });
    }

    function renderResourceUnavailabilities(records) {
        const $list =
            $('#resource-unavailability-list');

        $list.empty();

        const now = moment();

        const relevant = records
            .filter(
                (record) =>
                    record.status === 'active'
            )
            .sort(
                (a, b) =>
                    moment(a.start_datetime).valueOf() -
                    moment(b.start_datetime).valueOf()
            );

        const history = records
            .filter(
                (record) =>
                    record.status !== 'active' ||
                    (
                        record.end_datetime &&
                        moment(record.end_datetime)
                            .isBefore(now)
                    )
            )
            .sort(
                (a, b) =>
                    moment(b.start_datetime).valueOf() -
                    moment(a.start_datetime).valueOf()
            )
            .slice(0, 3);

        const visibleRecords = [
            ...relevant,
            ...history,
        ];

        if (!visibleRecords.length) {
            $list.append(
                $('<div/>', {
                    class:
                        'text-muted text-center py-4',
                    text:
                        'Keine Zeiträume vorhanden.',
                })
            );

            return;
        }

        visibleRecords.forEach((record) => {
            const start =
                moment(record.start_datetime);

            const end = record.end_datetime
                ? moment(record.end_datetime)
                : null;

            const currentlyUnavailable =
                record.status === 'active' &&
                start.isSameOrBefore(now) &&
                (
                    !end ||
                    end.isSameOrAfter(now)
                );

            const upcoming =
                record.status === 'active' &&
                start.isAfter(now) &&
                start.isSameOrBefore(
                    now.clone().add(7, 'days')
                );

            let cardClass =
                'border-start border-4 border-secondary';

            let badgeClass =
                'text-bg-secondary';

            let stateLabel = 'Erledigt';

            if (currentlyUnavailable) {
                if (record.type === 'defect') {
                    cardClass =
                        'border-start border-4 ' +
                        'border-danger bg-danger-subtle';
                    badgeClass = 'text-bg-danger';
                    stateLabel = 'DEFEKT';
                } else if (
                    record.type === 'maintenance'
                ) {
                    cardClass =
                        'border-start border-4 ' +
                        'border-warning bg-warning-subtle';
                    badgeClass = '';
                    stateLabel = 'WARTUNG';
                } else if (
                    record.type === 'blocked'
                ) {
                    cardClass =
                        'border-start border-4 ' +
                        'border-danger bg-danger-subtle';
                    badgeClass = '';
                    stateLabel = 'GESPERRT';
                } else {
                    cardClass =
                        'border-start border-4 ' +
                        'border-danger bg-danger-subtle';
                    badgeClass = 'text-bg-danger';
                    stateLabel =
                        'NICHT VERFÜGBAR';
                }
            } else if (upcoming) {
                cardClass =
                    'border-start border-4 ' +
                    'border-warning bg-warning-subtle';
                badgeClass =
                    'text-bg-warning text-dark';

                stateLabel =
                    record.type === 'maintenance'
                        ? 'WARTUNG GEPLANT'
                        : 'BEVORSTEHEND';
            } else if (record.status === 'active') {
                cardClass =
                    'border-start border-4 ' +
                    'border-primary';
                badgeClass = 'text-bg-primary';

                stateLabel =
                    record.type === 'maintenance'
                        ? 'WARTUNG GEPLANT'
                        : 'GEPLANT';
            }

            const periodEnd = end
                ? end.format('DD.MM.YYYY HH:mm')
                : 'Ende offen';

            const $actions = $('<div/>', {
                class: 'mt-2',
            });

            if (currentlyUnavailable) {
                $('<button/>', {
                    type: 'button',
                    class:
                        'btn btn-sm btn-outline-success ' +
                        'resolve-resource-unavailability',
                    text: 'Wieder verfügbar',
                })
                    .attr('data-id', record.id)
                    .appendTo($actions);
            }

            const $recordItem = $('<div/>', {
                class:
                    'list-group-item ' +
                    cardClass,
                css: {
                    borderLeftColor:
                        $('#planning-resource-color').val()
                        || '#7cbae8',
                },
                html: [
                    $('<div/>', {
                        class:
                            'd-flex justify-content-between gap-3',
                        html: [
                            $('<div/>', {
                                html: [
                                    $('<div/>', {
                                        class: 'fw-semibold',
                                        text:
                                            record.title ||
                                            unavailabilityTypeLabels[
                                                record.type
                                            ] ||
                                            'Nichtverfügbarkeit',
                                    }),
                                    $('<div/>', {
                                        class:
                                            'small text-muted',
                                        text:
                                            start.format(
                                                'DD.MM.YYYY HH:mm'
                                            ) +
                                            ' – ' +
                                            periodEnd,
                                    }),
                                ],
                            }),
                            $('<span/>', {
                                class:
                                    'badge align-self-start ' +
                                    badgeClass,
                                text: stateLabel,
                            }),
                        ],
                    }),
                    record.notes
                        ? $('<div/>', {
                            class: 'small mt-2',
                            text: record.notes,
                        })
                        : null,
                    $actions,
                ].filter(Boolean),
            });

            if (
                currentlyUnavailable &&
                record.type === 'maintenance'
            ) {
                $recordItem
                    .find('.badge')
                    .css({
                        backgroundColor: '#fd7e14',
                        color: '#212529',
                    });
            }

            if (
                currentlyUnavailable &&
                record.type === 'blocked'
            ) {
                $recordItem
                    .find('.badge')
                    .css({
                        backgroundColor: '#842029',
                        color: '#ffffff',
                    });
            }

            $recordItem.appendTo($list);
        });

        if (records.length > visibleRecords.length) {
            $('<button/>', {
                type: 'button',
                class:
                    'list-group-item list-group-item-action ' +
                    'text-center text-primary',
                id: 'open-resource-history-from-list',
                html:
                    '<i class="fas fa-history me-2"></i>' +
                    'Weitere Einträge in der Historie anzeigen',
            }).appendTo($list);
        }
    }

    function renderResourceHistory() {
        const $body =
            $('#resource-unavailability-history-body');

        $body.empty();

        if (!resourceUnavailabilityRecords.length) {
            $body.append(
                $('<tr/>').append(
                    $('<td/>', {
                        colspan: 6,
                        class:
                            'text-center text-muted py-5',
                        text:
                            'Keine Historieneinträge vorhanden.',
                    })
                )
            );

            return;
        }

        resourceUnavailabilityRecords
            .slice()
            .sort(
                (a, b) =>
                    moment(b.start_datetime).valueOf() -
                    moment(a.start_datetime).valueOf()
            )
            .forEach((record) => {
                const status =
                    record.status === 'active'
                        ? 'Aktiv'
                        : 'Erledigt';

                $('<tr/>')
                    .append(
                        $('<td/>').text(
                            unavailabilityTypeLabels[
                                record.type
                            ] || record.type
                        ),
                        $('<td/>').text(
                            record.title || ''
                        ),
                        $('<td/>').text(
                            moment(record.start_datetime)
                                .format('DD.MM.YYYY HH:mm')
                        ),
                        $('<td/>').text(
                            record.end_datetime
                                ? moment(
                                    record.end_datetime
                                ).format(
                                    'DD.MM.YYYY HH:mm'
                                )
                                : 'Offen'
                        ),
                        $('<td/>').text(status),
                        $('<td/>').text(
                            record.notes || ''
                        )
                    )
                    .appendTo($body);
            });
    }

    function resetEditor() {
        selectedResourceId = null;

        $('#resource-unavailability-section')
            .addClass('d-none');
        $('#resource-unavailability-editor')
            .addClass('d-none');

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

    function readableTextColor(backgroundColor) {
        const color = String(
            backgroundColor || '#6c757d'
        ).replace('#', '');

        if (!/^[0-9a-f]{6}$/i.test(color)) {
            return '#ffffff';
        }

        const red = parseInt(color.slice(0, 2), 16);
        const green = parseInt(color.slice(2, 4), 16);
        const blue = parseInt(color.slice(4, 6), 16);

        const brightness =
            (
                red * 299 +
                green * 587 +
                blue * 114
            ) / 1000;

        return brightness > 155
            ? '#212529'
            : '#ffffff';
    }

    function availabilityPresentation(resource) {
        const calendarColor =
            resource.color || '#7cbae8';

        if (Number(resource.is_active) !== 1) {
            return {
                label: 'INAKTIV',
                badgeClass: 'text-bg-secondary',
                badgeStyle: {},
                rowClass: 'resource-state-inactive',
                calendarColor,
            };
        }

        const state =
            resource.availability?.state ||
            'available';

        const type =
            resource.availability?.type ||
            null;

        if (state === 'upcoming') {
            if (type === 'maintenance') {
                return {
                    label: 'WARTUNG GEPLANT',
                    badgeClass:
                        'text-bg-warning text-dark',
                    badgeStyle: {},
                    rowClass:
                        'resource-state-upcoming',
                    calendarColor,
                };
            }

            if (type === 'blocked') {
                return {
                    label: 'SPERRUNG GEPLANT',
                    badgeClass:
                        'text-bg-warning text-dark',
                    badgeStyle: {},
                    rowClass:
                        'resource-state-upcoming',
                    calendarColor,
                };
            }

            return {
                label: 'BALD NICHT VERFÜGBAR',
                badgeClass:
                    'text-bg-warning text-dark',
                badgeStyle: {},
                rowClass:
                    'resource-state-upcoming',
                calendarColor,
            };
        }

        if (state === 'unavailable') {
            if (type === 'defect') {
                return {
                    label: 'DEFEKT',
                    badgeClass: 'text-bg-danger',
                    badgeStyle: {},
                    rowClass:
                        'resource-state-defect',
                    calendarColor,
                };
            }

            if (type === 'maintenance') {
                return {
                    label: 'WARTUNG',
                    badgeClass: '',
                    badgeStyle: {
                        backgroundColor: '#fd7e14',
                        color: '#212529',
                    },
                    rowClass:
                        'resource-state-maintenance',
                    calendarColor,
                };
            }

            if (type === 'blocked') {
                return {
                    label: 'GESPERRT',
                    badgeClass: '',
                    badgeStyle: {
                        backgroundColor: '#842029',
                        color: '#ffffff',
                    },
                    rowClass:
                        'resource-state-blocked',
                    calendarColor,
                };
            }

            return {
                label: 'NICHT VERFÜGBAR',
                badgeClass: 'text-bg-danger',
                badgeStyle: {},
                rowClass:
                    'resource-state-unavailable',
                calendarColor,
            };
        }

        return {
            label: 'AKTIV',
            badgeClass: '',
            badgeStyle: {
                backgroundColor: calendarColor,
                color:
                    readableTextColor(
                        calendarColor
                    ),
            },
            rowClass:
                'resource-state-available',
            calendarColor,
        };
    }

    function renderAvailabilitySummary(records) {
        const now = moment();

        const current = records.find(
            (record) =>
                record.status === 'active' &&
                moment(record.start_datetime)
                    .isSameOrBefore(now) &&
                (
                    !record.end_datetime ||
                    moment(record.end_datetime)
                        .isSameOrAfter(now)
                )
        );

        const upcoming = records
            .filter(
                (record) =>
                    record.status === 'active' &&
                    moment(record.start_datetime)
                        .isAfter(now) &&
                    moment(record.start_datetime)
                        .isSameOrBefore(
                            now.clone().add(7, 'days')
                        )
            )
            .sort(
                (a, b) =>
                    moment(a.start_datetime)
                        .valueOf() -
                    moment(b.start_datetime)
                        .valueOf()
            )[0];

        const $summary =
            $('#resource-availability-summary');

        $summary.removeClass(
            'd-none alert-success alert-warning alert-danger'
        );

        if (current) {
            const type =
                unavailabilityTypeLabels[current.type] ||
                'Nichtverfügbarkeit';

            const end = current.end_datetime
                ? moment(current.end_datetime)
                    .format('DD.MM.YYYY HH:mm')
                : 'Ende offen';

            $summary
                .addClass('alert-danger')
                .html(
                    '<strong>Aktuell nicht verfügbar</strong><br>' +
                    $('<div/>')
                        .text(`${type} · ${end}`)
                        .html()
                );

            return;
        }

        if (upcoming) {
            const type =
                unavailabilityTypeLabels[upcoming.type] ||
                'Nichtverfügbarkeit';

            $summary
                .addClass('alert-warning')
                .html(
                    '<strong>Bald nicht verfügbar</strong><br>' +
                    $('<div/>')
                        .text(
                            `${type} ab ` +
                            moment(upcoming.start_datetime)
                                .format('DD.MM.YYYY HH:mm')
                        )
                        .html()
                );

            return;
        }

        $summary
            .addClass('alert-success')
            .html(
                '<strong>Aktuell verfügbar</strong><br>' +
                'In den nächsten sieben Tagen ist kein ' +
                'Ausfall hinterlegt.'
            );
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
                title: 'Kalenderfarbe',
                css: {
                    width: '14px',
                    height: '14px',
                    borderRadius: '50%',
                    backgroundColor:
                        resource.color || '#7cbae8',
                    border:
                        '2px solid rgba(0, 0, 0, 0.08)',
                    boxShadow:
                        '0 0 0 2px rgba(255, 255, 255, 0.9)',
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

            const availability =
                availabilityPresentation(resource);

            const $status = $('<span/>', {
                class:
                    'badge rounded-pill ' +
                    availability.badgeClass,
                text: availability.label,
                css: availability.badgeStyle,
            });

            $('<button/>', {
                type: 'button',
                class:
                    'list-group-item list-group-item-action ' +
                    'd-flex align-items-center gap-3 ' +
                    availability.rowClass,
                css: {
                    borderLeft:
                        '4px solid ' +
                        availability.calendarColor,
                },
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
        loadResourceUnavailabilities();

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

    $('#add-resource-unavailability').on(
        'click',
        resetUnavailabilityEditor
    );

    $('#cancel-resource-unavailability').on(
        'click',
        () => {
            $('#resource-unavailability-editor')
                .addClass('d-none');
        }
    );

    $('#resource-unavailability-type').on(
        'change',
        updateUnavailabilityEndField
    );

    $('#resource-unavailability-open-end').on(
        'change',
        updateUnavailabilityEndField
    );

    $('#save-resource-unavailability').on(
        'click',
        () => {
            const openEnd =
                $('#resource-unavailability-open-end')
                    .prop('checked');

            const unavailability = {
                id:
                    $('#resource-unavailability-id')
                        .val() || undefined,
                id_planning_resources:
                    selectedResourceId,
                type:
                    $('#resource-unavailability-type')
                        .val(),
                title:
                    $('#resource-unavailability-title')
                        .val()
                        .trim(),
                start_datetime:
                    serverDatetime(
                        $('#resource-unavailability-start')
                            .val()
                    ),
                end_datetime:
                    openEnd
                        ? null
                        : serverDatetime(
                            $('#resource-unavailability-end')
                                .val()
                        ),
                notes:
                    $('#resource-unavailability-notes')
                        .val()
                        .trim(),
            };

            if (!unavailability.start_datetime) {
                showMessage(
                    'Bitte einen Beginn angeben.'
                );
                return;
            }

            if (
                unavailability.type === 'maintenance' &&
                !unavailability.end_datetime
            ) {
                showMessage(
                    'Für ein Wartungsfenster ist ein Ende erforderlich.'
                );
                return;
            }

            $.post(
                App.Utils.Url.siteUrl(
                    'planning_resources/store_unavailability'
                ),
                {
                    csrf_token: vars('csrf_token'),
                    unavailability,
                }
            )
                .done(() => {
                    notify(
                        'Verfügbarkeit gespeichert.'
                    );

                    $('#resource-unavailability-editor')
                        .addClass('d-none');

                    loadResourceUnavailabilities();
                })
                .fail((xhr) => {
                    showMessage(
                        xhr.responseJSON?.message ||
                        'Verfügbarkeit konnte nicht gespeichert werden.'
                    );
                });
        }
    );

    $('#resource-unavailability-list').on(
        'click',
        '.resolve-resource-unavailability',
        (event) => {
            const id = Number(
                $(event.currentTarget).data('id')
            );

            $.post(
                App.Utils.Url.siteUrl(
                    'planning_resources/resolve_unavailability'
                ),
                {
                    csrf_token: vars('csrf_token'),
                    unavailability_id: id,
                    resource_id: selectedResourceId,
                }
            )
                .done(() => {
                    notify(
                        'Ressource ist wieder verfügbar.'
                    );

                    loadResourceUnavailabilities();
                })
                .fail((xhr) => {
                    showMessage(
                        xhr.responseJSON?.message ||
                        'Status konnte nicht geändert werden.'
                    );
                });
        }
    );

    $('#show-resource-unavailability-history').on(
        'click',
        () => {
            renderResourceHistory();

            bootstrap.Modal
                .getOrCreateInstance(
                    document.getElementById(
                        'resource-unavailability-history-modal'
                    )
                )
                .show();
        }
    );

    $('#resource-unavailability-list').on(
        'click',
        '#open-resource-history-from-list',
        () => {
            $('#show-resource-unavailability-history')
                .trigger('click');
        }
    );

    $filter.on('input', renderList);

    $('#clear-resource-filter').on('click', () => {
        $filter.val('');
        renderList();
        $filter.trigger('focus');
    });

    const initialResourceId = Number(
        new URLSearchParams(
            window.location.search
        ).get('resource_id')
    );

    loadResources(
        initialResourceId > 0
            ? initialResourceId
            : null
    );
});
