/* ----------------------------------------------------------------------------
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.5.0
 * ---------------------------------------------------------------------------- */

/**
 * Calendar page.
 *
 * This module implements the functionality of the backend calendar page.
 */
App.Pages.Calendar = (function () {
    const $insertWorkingPlanException = $('#insert-working-plan-exception');

    const moment = window.moment;

    const CALENDAR_VISIBILITY_STORAGE_KEY =
        'MODUL.Calendar.EventVisibility';

    const defaultEventVisibility = {
        appointments: true,
        unavailabilities: true,
        blocked_periods: true,
    };

    function getEventVisibility() {
        try {
            const stored = JSON.parse(
                window.localStorage.getItem(
                    CALENDAR_VISIBILITY_STORAGE_KEY
                ) || '{}'
            );

            return {
                ...defaultEventVisibility,
                ...stored,
            };
        } catch (error) {
            return {
                ...defaultEventVisibility,
            };
        }
    }

    let selectedCalendarIds = [];

    function getCalendarViewFilters() {
        return {
            calendar_ids: [
                ...selectedCalendarIds,
            ],
        };
    }

    function initializeCalendarPickerOffcanvas() {
        if (
            document.getElementById(
                'calendar-picker-offcanvas'
            )
        ) {
            return;
        }

        const $button =
            $('#calendar-view-config-button');

        const $dropdown =
            $button.closest('.dropdown');

        const $menu =
            $dropdown
                .find('.dropdown-menu')
                .first();

        if (!$button.length || !$menu.length) {
            return;
        }

        const $offcanvas = $('<div/>', {
            id: 'calendar-picker-offcanvas',
            class: 'offcanvas offcanvas-start',
            tabindex: -1,
            'aria-labelledby':
                'calendar-picker-offcanvas-title',
            css: {
                width: 'min(480px, 96vw)',
            },
        });

        const $header = $('<div/>', {
            class:
                'offcanvas-header border-bottom',
            html: [
                $('<div/>', {
                    html: [
                        $('<h5/>', {
                            id:
                                'calendar-picker-offcanvas-title',
                            class:
                                'offcanvas-title mb-1',
                            text:
                                'Kalender auswählen',
                        }),

                        $('<div/>', {
                            class:
                                'small text-muted',
                            text:
                                'Mehrere Kalender können gleichzeitig angezeigt werden.',
                        }),
                    ],
                }),

                $('<button/>', {
                    type: 'button',
                    class: 'btn-close',
                    'data-bs-dismiss': 'offcanvas',
                    'aria-label': 'Schließen',
                }),
            ],
        });

        const $body = $('<div/>', {
            class:
                'offcanvas-body d-flex flex-column',
        });

        const $content = $('<div/>', {
            class: 'flex-grow-1',
        });

        // Den vorhandenen Picker-Inhalt übernehmen.
        $menu.children().appendTo($content);

        // Alte Alle-aus-Schalter aus dem Dropdown entfernen.
        $content
            .find('#calendar-view-hide-all')
            .closest('.d-flex')
            .remove();

        $content
            .find('#calendar-view-show-all')
            .remove();

        const $footer = $('<div/>', {
            class:
                'border-top pt-3 mt-3 ' +
                'd-flex justify-content-between gap-2',
            html: [
                $('<button/>', {
                    type: 'button',
                    id: 'calendar-picker-clear',
                    class:
                        'btn btn-outline-secondary',
                    text: 'Alle aus',
                }),

                $('<button/>', {
                    type: 'button',
                    class: 'btn btn-primary',
                    'data-bs-dismiss': 'offcanvas',
                    text: 'Übernehmen',
                }),
            ],
        });

        $body.append(
            $content,
            $footer
        );

        $offcanvas.append(
            $header,
            $body
        );

        $('body').append($offcanvas);

        // Alten Dropdown-Auslöser in Offcanvas-Auslöser verwandeln.
        $button
            .removeAttr('data-bs-auto-close')
            .removeAttr('aria-expanded')
            .removeClass('dropdown-toggle')
            .attr(
                'data-bs-toggle',
                'offcanvas'
            )
            .attr(
                'data-bs-target',
                '#calendar-picker-offcanvas'
            )
            .attr(
                'aria-controls',
                'calendar-picker-offcanvas'
            );

        $dropdown
            .removeClass('dropdown')
            .addClass('w-100');

        // Das alte leere Dropdown-Menü entfernen.
        $menu.remove();
    }

    function populateCalendarViewFilters() {
        const calendars =
            vars('planning_calendars') || [];

        const containerByType = {
            project: '#calendar-project-options',
            team: '#calendar-team-options',
            employee: '#calendar-employee-options',
            resource: '#calendar-resource-options',
        };

        Object.values(containerByType).forEach(
            (selector) => $(selector).empty()
        );

        calendars.forEach((calendar) => {
            const container =
                containerByType[calendar.entity_type];

            if (!container) {
                return;
            }

            const checkboxId =
                `planning-calendar-${calendar.id}`;

            $('<div/>', {
                class: 'calendar-view-option-row',
                html: [
                    $('<input/>', {
                        type: 'checkbox',
                        id: checkboxId,
                        class:
                            'form-check-input ' +
                            'calendar-view-filter-option',
                        value: calendar.id,
                        checked:
                            selectedCalendarIds.includes(
                                Number(calendar.id)
                            ),
                    }),
                    $('<label/>', {
                        for: checkboxId,
                        class:
                            'form-check-label ' +
                            'calendar-view-option-label',
                        text: calendar.name,
                    }),
                    $('<input/>', {
                        type: 'color',
                        class:
                            'form-control ' +
                            'form-control-color ' +
                            'calendar-item-color',
                        value:
                            calendar.color ||
                            '#7cbae8',
                        title:
                            'Kalenderfarbe ändern',
                        'data-calendar-id':
                            calendar.id,
                    }),
                ],
            }).appendTo(container);
        });

        Object.entries(containerByType).forEach(
            ([type, selector]) => {
                if (!$(selector).children().length) {
                    $('<div/>', {
                        class:
                            'small text-muted px-2 py-2',
                        text:
                            'Keine Kalender vorhanden.',
                    }).appendTo(selector);
                }
            }
        );

        updateCalendarViewFilterCounts();
    }

    function updateCalendarViewPickerLabel() {
        const $selected =
            $('.calendar-view-filter-option:checked');

        const count = $selected.length;

        let label = 'Kalender auswählen';

        if (count === 1) {
            const inputId =
                $selected.first().attr('id');

            const selectedLabel = $(
                `label[for="${inputId}"]`
            )
                .text()
                .trim();

            label = selectedLabel || '1 Kalender aktiv';
        } else if (count > 1) {
            label = `${count} Kalender aktiv`;
        }

        $('#calendar-view-config-label').text(label);
    }

    function updateCalendarViewFilterCounts() {
        const calendars =
            vars('planning_calendars') || [];

        function countType(types) {
            return selectedCalendarIds.filter(
                (calendarId) => {
                    const calendar = calendars.find(
                        (item) =>
                            Number(item.id) ===
                            Number(calendarId)
                    );

                    return (
                        calendar &&
                        types.includes(
                            calendar.entity_type
                        )
                    );
                }
            ).length;
        }

        $('#calendar-project-count').text(
            countType(['project'])
        );

        $('#calendar-personnel-count').text(
            countType([
                'team',
                'employee',
            ])
        );

        $('#calendar-resource-count').text(
            countType(['resource'])
        );

        updateCalendarViewPickerLabel();
    }

    function saveCalendarViewFilters() {
        selectedCalendarIds =
            $('.calendar-view-filter-option:checked')
                .map(
                    (index, field) =>
                        Number($(field).val())
                )
                .get();

        updateCalendarViewFilterCounts();

        $('#reload-appointments')
            .trigger('click');
    }

    function applyEventVisibilityToControls() {
        const visibility = getEventVisibility();

        $('.calendar-event-visibility').each(
            (index, field) => {
                const eventType =
                    $(field).data('event-type');

                $(field).prop(
                    'checked',
                    visibility[eventType] !== false
                );
            }
        );
    }

    function saveEventVisibilityFromControls() {
        const visibility = {};

        $('.calendar-event-visibility').each(
            (index, field) => {
                visibility[
                    $(field).data('event-type')
                ] = $(field).prop('checked');
            }
        );

        window.localStorage.setItem(
            CALENDAR_VISIBILITY_STORAGE_KEY,
            JSON.stringify(visibility)
        );

        $('#reload-appointments').trigger('click');
    }

    /**
     * Add the page event listeners.
     */
    function addEventListeners() {
        const $calendarPage = $('#calendar-page');

        $calendarPage.on('click', '#toggle-fullscreen', (event) => {
            const $toggleFullscreen = $(event.target);
            const element = document.documentElement;
            const isFullScreen =
                document.fullScreenElement || document.mozFullScreen || document.webkitIsFullScreen || false;

            if (isFullScreen) {
                // Exit fullscreen mode.
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.msExitFullscreen) {
                    document.msExitFullscreen();
                } else if (document.mozCancelFullScreen) {
                    document.mozCancelFullScreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                }

                $toggleFullscreen.removeClass('btn-success').addClass('btn-light');
            } else {
                // Switch to fullscreen mode.
                if (element.requestFullscreen) {
                    element.requestFullscreen();
                } else if (element.msRequestFullscreen) {
                    element.msRequestFullscreen();
                } else if (element.mozRequestFullScreen) {
                    element.mozRequestFullScreen();
                } else if (element.webkitRequestFullscreen) {
                    element.webkitRequestFullscreen();
                }
                $toggleFullscreen.removeClass('btn-light').addClass('btn-success');
            }
        });

        applyEventVisibilityToControls();
        initializeCalendarPickerOffcanvas();
        populateCalendarViewFilters();

        $calendarPage.on(
            'change',
            '.calendar-event-visibility',
            saveEventVisibilityFromControls
        );

        $(document)
            .off(
                'change.calendarPicker',
                '#calendar-picker-offcanvas ' +
                '.calendar-view-filter-option'
            )
            .on(
                'change.calendarPicker',
                '#calendar-picker-offcanvas ' +
                '.calendar-view-filter-option',
                (event) => {
                    event.stopPropagation();
                    saveCalendarViewFilters();
                }
            );

        $(document)
            .off(
                'click.calendarClear',
                '#calendar-picker-clear'
            )
            .on(
                'click.calendarClear',
                '#calendar-picker-clear',
                (event) => {
                    event.preventDefault();
                    event.stopPropagation();

                    selectedCalendarIds = [];

                    $(
                        '#calendar-picker-offcanvas ' +
                        '.calendar-view-filter-option'
                    ).prop('checked', false);

                    updateCalendarViewFilterCounts();

                    $('#reload-appointments')
                        .trigger('click');
                }
            );

        $calendarPage.on(
            'change',
            '.calendar-item-color',
            (event) => {
                const $field =
                    $(event.currentTarget);

                const calendarId =
                    Number(
                        $field.data('calendar-id')
                    );

                const color =
                    String($field.val());

                if (!calendarId) {
                    return;
                }

                $field.prop('disabled', true);

                $.post(
                    App.Utils.Url.siteUrl(
                        'calendar/save_planning_calendar_color'
                    ),
                    {
                        csrf_token: vars('csrf_token'),
                        calendar_id: calendarId,
                        color,
                    }
                )
                    .done((response) => {
                        const calendar = (
                            vars('planning_calendars') || []
                        ).find(
                            (item) =>
                                Number(item.id) ===
                                calendarId
                        );

                        if (calendar) {
                            calendar.color =
                                response.color || color;
                        }

                        App.Layouts.Backend
                            .displayNotification(
                                'Kalenderfarbe gespeichert.'
                            );

                        $('#reload-appointments')
                            .trigger('click');
                    })
                    .fail((xhr) => {
                        App.Layouts.Backend
                            .displayNotification(
                                xhr.responseJSON?.message ||
                                'Kalenderfarbe konnte nicht gespeichert werden.'
                            );

                        populateCalendarViewFilters();
                    })
                    .always(() => {
                        $field.prop(
                            'disabled',
                            false
                        );
                    });
            }
        );



        $calendarPage.on(
            'click',
            '#calendar-view-show-all',
            (event) => {
                event.preventDefault();
                event.stopPropagation();

                $('.calendar-event-visibility')
                    .prop('checked', true);

                $('.calendar-view-filter-option')
                    .prop('checked', true);

                saveCalendarViewFilters();
                saveEventVisibilityFromControls();
            }
        );

        $calendarPage.on(
            'click',
            '#calendar-view-hide-all',
            (event) => {
                event.preventDefault();
                event.stopPropagation();

                $('.calendar-event-visibility')
                    .prop('checked', false);

                $('.calendar-view-filter-option')
                    .prop('checked', false);

                saveCalendarViewFilters();
                saveEventVisibilityFromControls();
            }
        );

        $insertWorkingPlanException.on('click', () => {
            const providerId = $('#select-filter-item').val();

            if (providerId === App.Utils.CalendarDefaultView.FILTER_TYPE_ALL) {
                return;
            }

            const provider = vars('available_providers').find((availableProvider) => {
                return Number(availableProvider.id) === Number(providerId);
            });

            if (!provider) {
                throw new Error('Provider could not be found: ' + providerId);
            }

            App.Components.WorkingPlanExceptionsModal.add().done((workingPlanException) => {
                const successCallback = (response) => {
                    App.Layouts.Backend.displayNotification(lang('working_plan_exception_saved'));

                    // Update the in-memory provider data with the new exception
                    let exceptions = JSON.parse(provider.settings.working_plan_exceptions || '[]');
                    if (!Array.isArray(exceptions)) {
                        exceptions = [];
                    }

                    // Add the new exception (with ID from response if available)
                    if (response && response.id) {
                        workingPlanException.id = response.id;
                    }
                    exceptions.push(workingPlanException);
                    provider.settings.working_plan_exceptions = JSON.stringify(exceptions);

                    $('#select-filter-item').trigger('change'); // Update the calendar.
                };

                App.Http.Calendar.saveWorkingPlanException(workingPlanException, providerId, successCallback, null);
            });
        });
    }

    /**
     * Get calendar selection end date.
     *
     * On calendar slot selection, calculate the end date based on the provided start date.
     *
     * @param {Object} info Holding the "start" and "end" props, as provided by FullCalendar.
     *
     * @return {Date}
     */
    function getSelectionEndDate(info) {
        const startMoment = moment(info.start);
        const endMoment = moment(info.end);
        const startTillEndDiff = endMoment.diff(startMoment);
        const startTillEndDuration = moment.duration(startTillEndDiff);
        const durationInMinutes = startTillEndDuration.asMinutes();
        const minDurationInMinutes = 15;

        if (durationInMinutes <= minDurationInMinutes) {
            const serviceId = $('#select-service').val();
            const service = vars('available_services').find(
                (availableService) => Number(availableService.id) === Number(serviceId),
            );

            if (service) {
                endMoment.add(service.duration - durationInMinutes, 'minutes');
            }
        }

        return endMoment.toDate();
    }

    /**
     * Initialize the module.
     *
     * This function makes the necessary initialization for the default backend calendar page.
     *
     * If this module is used in another page then this function might not be needed.
     */
    function initialize() {
        // Load and initialize the calendar view.
        if (vars('calendar_view') === 'table') {
            App.Utils.CalendarTableView.initialize();
        } else {
            App.Utils.CalendarDefaultView.initialize();
        }

        App.Pages.Calendar.addEventListeners();
    }

    document.addEventListener('DOMContentLoaded', initialize);

    return {
        addEventListeners,
        getSelectionEndDate,
        getEventVisibility,
        getCalendarViewFilters,
    };
})();
