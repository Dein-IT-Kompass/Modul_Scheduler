$(function () {
    'use strict';

    let projects = [];
    let projectPhases = [];
    let projectAssignments = [];
    let activeProject = null;

    const statusLabels = {
        planned: ['Geplant', 'secondary'],
        active: ['Aktiv', 'success'],
        paused: ['Pausiert', 'warning'],
        completed: ['Abgeschlossen', 'primary'],
        cancelled: ['Abgebrochen', 'danger'],
    };

    const $list = $('#planning-project-list');
    const $editor = $('#planning-project-editor');
    const $placeholder = $('#planning-project-placeholder');
    const $message = $('#planning-project-message');
    const $filter = $('#planning-project-filter');

    function notify(text) {
        App.Layouts.Backend.displayNotification(text);
    }

    function showMessage(text) {
        $message
            .removeClass('d-none alert-success')
            .addClass('alert-danger')
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
    }

    function setChecked(selector, ids) {
        const selected = (ids || []).map(Number);

        $(selector).each((index, field) => {
            $(field).prop(
                'checked',
                selected.includes(Number($(field).val()))
            );
        });
    }

    function selectedIds(selector) {
        return $(selector + ':checked')
            .map((index, field) => Number($(field).val()))
            .get();
    }

    function resetEditor() {
        $('#planning-project-id').val('');
        $('#planning-project-name').val('');
        $('#planning-project-status').val('planned');
        $('#planning-project-location').val('');
        $('#planning-project-lead').val('');
        $('#planning-project-start-date').val('');
        $('#planning-project-end-date').val('');
        $('#planning-project-color').val('#7cbae8');
        $('#planning-project-description').val('');

        setChecked('.project-team', []);
        setChecked('.project-employee', []);
        setChecked('.project-resource', []);
        setChecked('.project-service', []);

        $('#planning-project-editor-title').text('Neues Projekt');
        $('#planning-project-summary').text('');
        $('#delete-planning-project').hide();

        clearMessage();

        $editor.find('.nav-link:first').trigger('click');
    }

    function formatDate(value) {
        if (!value) {
            return '';
        }

        return moment(value, 'YYYY-MM-DD').format('DD.MM.YYYY');
    }

    function loadProjects() {
        $.get(App.Utils.Url.siteUrl('planning_projects/search'))
            .done((response) => {
                projects = response.projects || [];
                renderList();
            })
            .fail((xhr) => {
                $list.empty().append(
                    $('<div/>', {
                        class: 'alert alert-danger m-3',
                        text:
                            xhr.responseJSON?.message ||
                            'Projekte konnten nicht geladen werden.',
                    })
                );
            });
    }

    function renderList() {
        const keyword = $filter.val().trim().toLowerCase();

        const filtered = projects.filter((project) => {
            return [
                project.name,
                project.location,
                project.lead_first_name,
                project.lead_last_name,
            ]
                .filter(Boolean)
                .join(' ')
                .toLowerCase()
                .includes(keyword);
        });

        $list.empty();

        if (!filtered.length) {
            $list.append(
                $('<div/>', {
                    class: 'text-muted text-center p-4',
                    text: keyword
                        ? 'Kein passendes Projekt gefunden.'
                        : 'Noch keine Projekte vorhanden.',
                })
            );

            return;
        }

        filtered.forEach((project) => {
            const status = statusLabels[project.status] || [
                project.status,
                'secondary',
            ];

            const period = [
                formatDate(project.start_date),
                formatDate(project.end_date),
            ]
                .filter(Boolean)
                .join(' – ');

            const details = [
                period || null,
                project.location || null,
                `${Number(project.assignment_count || 0)} Einsätze`,
            ]
                .filter(Boolean)
                .join(' · ');

            $('<button/>', {
                type: 'button',
                class:
                    'list-group-item list-group-item-action ' +
                    'd-flex gap-3 align-items-start',
                click: () => editProject(project),
                html: [
                    $('<span/>', {
                        css: {
                            width: '12px',
                            height: '12px',
                            marginTop: '6px',
                            borderRadius: '50%',
                            backgroundColor:
                                project.color || '#7cbae8',
                            flex: '0 0 auto',
                        },
                    }),
                    $('<div/>', {
                        class: 'flex-grow-1 text-start',
                        html: [
                            $('<div/>', {
                                class: 'fw-semibold',
                                text: project.name,
                            }),
                            $('<small/>', {
                                class: 'text-muted',
                                text: details,
                            }),
                        ],
                    }),
                    $('<span/>', {
                        class: `badge bg-${status[1]}`,
                        text: status[0],
                    }),
                ],
            }).appendTo($list);
        });
    }

    function editProject(project) {
        activeProject = project;
        projectPhases = [];

        $('#planning-project-id').val(project.id);
        $('#planning-project-name').val(project.name);
        $('#planning-project-status').val(project.status);
        $('#planning-project-location').val(
            project.location || ''
        );
        $('#planning-project-lead').val(
            project.id_users_project_lead || ''
        );
        $('#planning-project-start-date').val(
            project.start_date || ''
        );
        $('#planning-project-end-date').val(
            project.end_date || ''
        );
        $('#planning-project-color').val(
            project.color || '#7cbae8'
        );
        $('#planning-project-description').val(
            project.description || ''
        );

        setChecked('.project-team', project.teams);
        setChecked('.project-employee', project.employees);
        setChecked('.project-resource', project.resources);
        setChecked('.project-service', project.services);

        $('#planning-project-editor-title')
            .text('Projekt bearbeiten');

        $('#planning-project-summary').text(
            `${Number(project.phase_count || 0)} Bausteine · ` +
            `${Number(project.assignment_count || 0)} Einsätze`
        );

        $('#delete-planning-project').show();

        clearMessage();
        openEditor();
        loadProjectPhases();
        loadProjectAssignments();
    }

    const phaseStatusColors = {
        planned: '#6c757d',
        active: '#f28c28',
        paused: '#ffc107',
        completed: '#198754',
        cancelled: '#dc3545',
    };

    function resetPhaseEditor() {
        $('#project-phase-id').val('');
        $('#project-phase-name').val('');
        $('#project-phase-status').val('planned');
        $('#project-phase-start-date').val(
            activeProject?.start_date || ''
        );
        $('#project-phase-end-date').val(
            activeProject?.end_date || ''
        );
        $('#project-phase-progress').val(0);
        $('#project-phase-progress-value').text('0 %');
        $('#project-phase-color').val('#7cbae8');
        $('#project-phase-description').val('');

        renderPhaseParentOptions();

        $('#project-phase-editor-title').text(
            'Neuer Baustein'
        );
        $('#delete-project-phase').hide();
        $('#project-phase-message')
            .addClass('d-none')
            .text('');
    }

    function renderPhaseParentOptions(
        selectedId = '',
        excludedId = null
    ) {
        const $parent = $('#project-phase-parent');

        $parent
            .empty()
            .append(
                $('<option/>', {
                    value: '',
                    text: 'Kein übergeordneter Baustein',
                })
            );

        projectPhases
            .filter(
                (phase) =>
                    Number(phase.id) !== Number(excludedId)
            )
            .forEach((phase) => {
                $('<option/>', {
                    value: phase.id,
                    text: phase.name,
                }).appendTo($parent);
            });

        $parent.val(selectedId ? String(selectedId) : '');
    }

    function loadProjectPhases() {
        if (!activeProject?.id) {
            projectPhases = [];
            renderProjectTimeline();
            return;
        }

        $.get(
            App.Utils.Url.siteUrl(
                'planning_projects/phases'
            ),
            {
                project_id: activeProject.id,
            }
        )
            .done((response) => {
                activeProject =
                    response.project || activeProject;
                projectPhases = response.phases || [];

                renderProjectTimeline();
                renderPhaseParentOptions();
            })
            .fail((xhr) => {
                $('#project-timeline-empty')
                    .removeClass('d-none alert-light')
                    .addClass('alert-danger')
                    .text(
                        xhr.responseJSON?.message ||
                        'Zeitplan konnte nicht geladen werden.'
                    );
            });
    }

    function phaseColor(phase) {
        const mode =
            $('#project-timeline-color-mode').val();

        if (mode === 'phase') {
            return phase.color || '#7cbae8';
        }

        if (mode === 'progress') {
            const progress = Number(
                phase.progress_percent || 0
            );

            if (progress >= 100) {
                return '#198754';
            }

            if (progress >= 60) {
                return '#0d6efd';
            }

            if (progress >= 25) {
                return '#f28c28';
            }

            return '#6c757d';
        }

        return (
            phaseStatusColors[phase.status] ||
            '#6c757d'
        );
    }

    function renderProjectTimeline() {
        const $timeline = $('#project-timeline');
        const $empty = $('#project-timeline-empty');

        $timeline.empty();

        if (!activeProject?.id) {
            $timeline.addClass('d-none');
            $empty
                .removeClass('d-none alert-danger')
                .addClass('alert-light')
                .text(
                    'Projekt zuerst speichern, bevor ein Zeitplan angelegt wird.'
                );
            return;
        }

        $('#project-timeline-period').text(
            [
                formatDate(activeProject.start_date),
                formatDate(activeProject.end_date),
            ]
                .filter(Boolean)
                .join(' – ')
        );

        if (
            !activeProject.start_date ||
            !activeProject.end_date
        ) {
            $timeline.addClass('d-none');
            $empty
                .removeClass('d-none alert-danger')
                .addClass('alert-warning')
                .text(
                    'Für die Zeitachse werden Projektbeginn und Projektende benötigt.'
                );
            return;
        }

        if (!projectPhases.length) {
            $timeline.addClass('d-none');
            $empty
                .removeClass('d-none alert-danger')
                .addClass('alert-light')
                .text(
                    'Noch keine Zeitplan-Bausteine vorhanden.'
                );
            return;
        }

        const projectStart = moment(
            activeProject.start_date,
            'YYYY-MM-DD'
        ).startOf('day');

        const projectEnd = moment(
            activeProject.end_date,
            'YYYY-MM-DD'
        ).startOf('day');

        const totalDays = Math.max(
            1,
            projectEnd.diff(projectStart, 'days') + 1
        );

        projectPhases.forEach((phase) => {
            const phaseStart = moment(
                phase.start_date,
                'YYYY-MM-DD'
            ).startOf('day');

            const phaseEnd = moment(
                phase.end_date,
                'YYYY-MM-DD'
            ).startOf('day');

            const offsetDays = Math.max(
                0,
                phaseStart.diff(projectStart, 'days')
            );

            const durationDays = Math.max(
                1,
                phaseEnd.diff(phaseStart, 'days') + 1
            );

            const left =
                (offsetDays / totalDays) * 100;

            const width =
                (durationDays / totalDays) * 100;

            const indent = phase.id_parent
                ? '↳ '
                : '';

            const $bar = $('<div/>', {
                class: 'project-timeline-bar',
                css: {
                    left: `${left}%`,
                    width: `${Math.min(
                        width,
                        100 - left
                    )}%`,
                    backgroundColor: phaseColor(phase),
                },
                click: () => editProjectPhase(phase),
                html: [
                    $('<div/>', {
                        class:
                            'project-timeline-bar-progress',
                        css: {
                            width:
                                `${Number(
                                    phase.progress_percent || 0
                                )}%`,
                        },
                    }),
                    $('<div/>', {
                        class:
                            'project-timeline-bar-label',
                        text:
                            `${phase.name} · ` +
                            `${Number(
                                phase.progress_percent || 0
                            )}%`,
                    }),
                ],
            });

            $('<div/>', {
                class: 'project-timeline-row',
                html: [
                    $('<div/>', {
                        class: 'project-timeline-label',
                        html: [
                            $('<div/>', {
                                class: 'fw-semibold',
                                text: indent + phase.name,
                            }),
                            $('<small/>', {
                                class: 'text-muted',
                                text:
                                    `${formatDate(
                                        phase.start_date
                                    )} – ` +
                                    `${formatDate(
                                        phase.end_date
                                    )}`,
                            }),
                        ],
                    }),
                    $('<div/>', {
                        class: 'project-timeline-track',
                        html: $bar,
                    }),
                ],
            }).appendTo($timeline);
        });

        $empty.addClass('d-none');
        $timeline.removeClass('d-none');
    }

    function editProjectPhase(phase) {
        $('#project-phase-id').val(phase.id);
        $('#project-phase-name').val(phase.name);
        $('#project-phase-status').val(phase.status);
        $('#project-phase-start-date').val(
            phase.start_date
        );
        $('#project-phase-end-date').val(
            phase.end_date
        );
        $('#project-phase-progress').val(
            phase.progress_percent || 0
        );
        $('#project-phase-progress-value').text(
            `${Number(phase.progress_percent || 0)} %`
        );
        $('#project-phase-color').val(
            phase.color || '#7cbae8'
        );
        $('#project-phase-description').val(
            phase.description || ''
        );

        renderPhaseParentOptions(
            phase.id_parent || '',
            phase.id
        );

        $('#project-phase-editor-title').text(
            'Baustein bearbeiten'
        );
        $('#delete-project-phase').show();
        $('#project-phase-editor').removeClass('d-none');
    }

    function collectProjectPhase() {
        return {
            id: $('#project-phase-id').val() || undefined,
            id_planning_projects: activeProject?.id,
            id_parent:
                $('#project-phase-parent').val() || null,
            name: $('#project-phase-name').val().trim(),
            start_date:
                $('#project-phase-start-date').val(),
            end_date:
                $('#project-phase-end-date').val(),
            progress_percent: Number(
                $('#project-phase-progress').val()
            ),
            status: $('#project-phase-status').val(),
            color: $('#project-phase-color').val(),
            description:
                $('#project-phase-description').val().trim(),
        };
    }

    function assignmentLocalValue(value) {
        if (!value) {
            return '';
        }

        return moment(
            value,
            'YYYY-MM-DD HH:mm:ss'
        ).format('YYYY-MM-DDTHH:mm');
    }

    function assignmentServerValue(value) {
        if (!value) {
            return '';
        }

        return moment(
            value,
            'YYYY-MM-DDTHH:mm'
        ).format('YYYY-MM-DD HH:mm:ss');
    }

    function renderAssignmentPhaseOptions(
        selectedId = ''
    ) {
        const $phase =
            $('#project-assignment-phase');

        $phase
            .empty()
            .append(
                $('<option/>', {
                    value: '',
                    text: 'Kein Baustein',
                })
            );

        projectPhases.forEach((phase) => {
            $('<option/>', {
                value: phase.id,
                text: phase.name,
            }).appendTo($phase);
        });

        $phase.val(
            selectedId ? String(selectedId) : ''
        );
    }

    function resetAssignmentEditor() {
        $('#project-assignment-id').val('');
        $('#project-assignment-title').val('');
        $('#project-assignment-status').val(
            'planned'
        );
        $('#project-assignment-location').val(
            activeProject?.location || ''
        );
        $('#project-assignment-priority').val('70');
        $('#project-assignment-color').val(
            '#f28c28'
        );
        $('#project-assignment-notes').val('');

        const start = moment()
            .add(1, 'day')
            .hour(7)
            .minute(0)
            .second(0);

        const end = start.clone().hour(16);

        $('#project-assignment-start').val(
            start.format('YYYY-MM-DDTHH:mm')
        );
        $('#project-assignment-end').val(
            end.format('YYYY-MM-DDTHH:mm')
        );

        renderAssignmentPhaseOptions();

        setChecked('.assignment-team', []);
        setChecked('.assignment-employee', []);
        setChecked('.assignment-resource', []);

        $('#project-assignment-editor-title')
            .text('Neuer Einsatz');

        $('#delete-project-assignment').hide();

        $('#project-assignment-message')
            .addClass('d-none')
            .text('');
    }

    function loadProjectAssignments() {
        if (!activeProject?.id) {
            projectAssignments = [];
            renderProjectAssignments();
            return;
        }

        $.get(
            App.Utils.Url.siteUrl(
                'planning_projects/assignments'
            ),
            {
                project_id: activeProject.id,
            }
        )
            .done((response) => {
                activeProject =
                    response.project || activeProject;

                projectAssignments =
                    response.assignments || [];

                renderProjectAssignments();

                const assignmentCount =
                    projectAssignments.length;

                $('#planning-project-summary').text(
                    `${projectPhases.length} Bausteine · ` +
                    `${assignmentCount} Einsätze`
                );

                loadProjects();
            })
            .fail((xhr) => {
                $('#project-assignments-empty')
                    .removeClass('d-none alert-light')
                    .addClass('alert-danger')
                    .text(
                        xhr.responseJSON?.message ||
                        'Einsätze konnten nicht geladen werden.'
                    );
            });
    }

    function renderProjectAssignments() {
        const $list =
            $('#project-assignment-list');

        const $empty =
            $('#project-assignments-empty');

        $list.empty();

        if (!activeProject?.id) {
            $empty
                .removeClass('d-none alert-danger')
                .addClass('alert-light')
                .text(
                    'Projekt zuerst speichern, bevor Einsätze angelegt werden.'
                );
            return;
        }

        if (!projectAssignments.length) {
            $empty
                .removeClass('d-none alert-danger')
                .addClass('alert-light')
                .text(
                    'Noch keine konkreten Einsätze vorhanden.'
                );
            return;
        }

        $empty.addClass('d-none');

        projectAssignments.forEach((assignment) => {
            const period =
                moment(
                    assignment.start_datetime,
                    'YYYY-MM-DD HH:mm:ss'
                ).format('DD.MM.YYYY HH:mm') +
                ' – ' +
                moment(
                    assignment.end_datetime,
                    'YYYY-MM-DD HH:mm:ss'
                ).format('DD.MM.YYYY HH:mm');

            const details = [
                period,
                assignment.location || null,
                `${Number(
                    assignment.employee_count || 0
                )} Mitarbeiter`,
                `${Number(
                    assignment.resource_count || 0
                )} Ressourcen`,
            ]
                .filter(Boolean)
                .join(' · ');

            $('<button/>', {
                type: 'button',
                class:
                    'list-group-item ' +
                    'list-group-item-action ' +
                    'd-flex gap-3 align-items-start',
                click: () =>
                    editProjectAssignment(assignment),
                html: [
                    $('<span/>', {
                        css: {
                            width: '12px',
                            height: '12px',
                            marginTop: '6px',
                            borderRadius: '50%',
                            backgroundColor:
                                assignment.color ||
                                '#f28c28',
                            flex: '0 0 auto',
                        },
                    }),
                    $('<div/>', {
                        class:
                            'flex-grow-1 text-start',
                        html: [
                            $('<div/>', {
                                class: 'fw-semibold',
                                text: assignment.title,
                            }),
                            $('<small/>', {
                                class: 'text-muted',
                                text: details,
                            }),
                        ],
                    }),
                ],
            }).appendTo($list);
        });
    }

    function editProjectAssignment(assignment) {
        $('#project-assignment-id').val(
            assignment.id
        );
        $('#project-assignment-title').val(
            assignment.title
        );
        $('#project-assignment-status').val(
            assignment.status
        );
        $('#project-assignment-location').val(
            assignment.location || ''
        );
        $('#project-assignment-start').val(
            assignmentLocalValue(
                assignment.start_datetime
            )
        );
        $('#project-assignment-end').val(
            assignmentLocalValue(
                assignment.end_datetime
            )
        );
        $('#project-assignment-priority').val(
            String(assignment.priority || 70)
        );
        $('#project-assignment-color').val(
            assignment.color || '#f28c28'
        );
        $('#project-assignment-notes').val(
            assignment.notes || ''
        );

        renderAssignmentPhaseOptions(
            assignment.id_planning_project_phases ||
                ''
        );

        setChecked(
            '.assignment-team',
            assignment.teams
        );
        setChecked(
            '.assignment-employee',
            assignment.employees
        );
        setChecked(
            '.assignment-resource',
            assignment.resources
        );

        $('#project-assignment-editor-title')
            .text('Einsatz bearbeiten');

        $('#delete-project-assignment').show();

        $('#project-assignment-message')
            .addClass('d-none')
            .text('');

        $('#project-assignment-editor')
            .removeClass('d-none');
    }

    function collectProjectAssignment() {
        return {
            id:
                $('#project-assignment-id').val() ||
                undefined,
            id_planning_projects:
                activeProject?.id,
            id_planning_project_phases:
                $('#project-assignment-phase').val() ||
                null,
            title:
                $('#project-assignment-title')
                    .val()
                    .trim(),
            start_datetime:
                assignmentServerValue(
                    $('#project-assignment-start').val()
                ),
            end_datetime:
                assignmentServerValue(
                    $('#project-assignment-end').val()
                ),
            location:
                $('#project-assignment-location')
                    .val()
                    .trim(),
            priority: Number(
                $('#project-assignment-priority').val()
            ),
            status:
                $('#project-assignment-status').val(),
            color:
                $('#project-assignment-color').val(),
            notes:
                $('#project-assignment-notes')
                    .val()
                    .trim(),
            teams:
                selectedIds('.assignment-team'),
            employees:
                selectedIds('.assignment-employee'),
            resources:
                selectedIds('.assignment-resource'),
        };
    }

    function collectProject() {
        return {
            id: $('#planning-project-id').val() || undefined,
            name: $('#planning-project-name').val().trim(),
            status: $('#planning-project-status').val(),
            location:
                $('#planning-project-location').val().trim(),
            id_users_project_lead:
                $('#planning-project-lead').val() || null,
            start_date:
                $('#planning-project-start-date').val() || null,
            end_date:
                $('#planning-project-end-date').val() || null,
            color: $('#planning-project-color').val(),
            description:
                $('#planning-project-description').val().trim(),
            teams: selectedIds('.project-team'),
            employees: selectedIds('.project-employee'),
            resources: selectedIds('.project-resource'),
            services: selectedIds('.project-service'),
        };
    }

    $('#planning-project-timeline-tab-button').on(
        'shown.bs.tab',
        renderProjectTimeline
    );

    $('#project-timeline-color-mode').on(
        'change',
        renderProjectTimeline
    );

    $('#add-project-phase').on('click', () => {
        if (!activeProject?.id) {
            notify(
                'Projekt zuerst speichern, bevor ein Baustein angelegt wird.'
            );
            return;
        }

        resetPhaseEditor();
        $('#project-phase-editor').removeClass('d-none');
        $('#project-phase-name').trigger('focus');
    });

    $('#cancel-project-phase').on('click', () => {
        $('#project-phase-editor').addClass('d-none');
    });

    $('#project-phase-progress').on(
        'input',
        (event) => {
            $('#project-phase-progress-value').text(
                `${Number($(event.currentTarget).val())} %`
            );
        }
    );

    $('#save-project-phase').on('click', () => {
        const phase = collectProjectPhase();
        const $phaseMessage =
            $('#project-phase-message');

        $phaseMessage.addClass('d-none').text('');

        if (
            !phase.id_planning_projects ||
            !phase.name ||
            !phase.start_date ||
            !phase.end_date
        ) {
            $phaseMessage
                .removeClass('d-none')
                .addClass('alert-danger')
                .text(
                    'Bezeichnung, Beginn und Ende sind erforderlich.'
                );
            return;
        }

        $.post(
            App.Utils.Url.siteUrl(
                'planning_projects/store_phase'
            ),
            {
                csrf_token: vars('csrf_token'),
                phase,
            }
        )
            .done(() => {
                notify('Zeitplan-Baustein gespeichert.');
                $('#project-phase-editor')
                    .addClass('d-none');
                loadProjectPhases();
            })
            .fail((xhr) => {
                $phaseMessage
                    .removeClass('d-none')
                    .addClass('alert-danger')
                    .text(
                        xhr.responseJSON?.message ||
                        'Baustein konnte nicht gespeichert werden.'
                    );
            });
    });

    $('#delete-project-phase').on('click', () => {
        const phaseId = Number(
            $('#project-phase-id').val()
        );

        if (
            !phaseId ||
            !window.confirm(
                'Zeitplan-Baustein wirklich löschen?'
            )
        ) {
            return;
        }

        $.post(
            App.Utils.Url.siteUrl(
                'planning_projects/delete_phase'
            ),
            {
                csrf_token: vars('csrf_token'),
                phase_id: phaseId,
            }
        )
            .done(() => {
                notify('Zeitplan-Baustein gelöscht.');
                $('#project-phase-editor')
                    .addClass('d-none');
                loadProjectPhases();
            })
            .fail((xhr) => {
                $('#project-phase-message')
                    .removeClass('d-none')
                    .addClass('alert-danger')
                    .text(
                        xhr.responseJSON?.message ||
                        'Baustein konnte nicht gelöscht werden.'
                    );
            });
    });

    $('#planning-project-assignments-tab-button').on(
        'shown.bs.tab',
        renderProjectAssignments
    );

    $('#add-project-assignment').on('click', () => {
        if (!activeProject?.id) {
            notify(
                'Projekt zuerst speichern, bevor ein Einsatz angelegt wird.'
            );
            return;
        }

        resetAssignmentEditor();

        $('#project-assignment-editor')
            .removeClass('d-none');

        $('#project-assignment-title')
            .trigger('focus');
    });

    $('#cancel-project-assignment').on(
        'click',
        () => {
            $('#project-assignment-editor')
                .addClass('d-none');
        }
    );

    $('#save-project-assignment').on(
        'click',
        () => {
            const assignment =
                collectProjectAssignment();

            const $assignmentMessage =
                $('#project-assignment-message');

            $assignmentMessage
                .addClass('d-none')
                .text('');

            if (
                !assignment.id_planning_projects ||
                !assignment.title ||
                !assignment.start_datetime ||
                !assignment.end_datetime
            ) {
                $assignmentMessage
                    .removeClass('d-none')
                    .addClass('alert-danger')
                    .text(
                        'Titel, Beginn und Ende sind erforderlich.'
                    );
                return;
            }

            if (
                assignment.end_datetime <=
                assignment.start_datetime
            ) {
                $assignmentMessage
                    .removeClass('d-none')
                    .addClass('alert-danger')
                    .text(
                        'Das Einsatzende muss nach dem Beginn liegen.'
                    );
                return;
            }

            $.post(
                App.Utils.Url.siteUrl(
                    'planning_projects/store_assignment'
                ),
                {
                    csrf_token: vars('csrf_token'),
                    assignment,
                }
            )
                .done(() => {
                    notify('Projekteinsatz gespeichert.');

                    $('#project-assignment-editor')
                        .addClass('d-none');

                    loadProjectAssignments();
                })
                .fail((xhr) => {
                    $assignmentMessage
                        .removeClass('d-none')
                        .addClass('alert-danger')
                        .text(
                            xhr.responseJSON?.message ||
                            'Einsatz konnte nicht gespeichert werden.'
                        );
                });
        }
    );

    $('#delete-project-assignment').on(
        'click',
        () => {
            const assignmentId = Number(
                $('#project-assignment-id').val()
            );

            if (
                !assignmentId ||
                !window.confirm(
                    'Projekteinsatz wirklich löschen?'
                )
            ) {
                return;
            }

            $.post(
                App.Utils.Url.siteUrl(
                    'planning_projects/delete_assignment'
                ),
                {
                    csrf_token: vars('csrf_token'),
                    assignment_id: assignmentId,
                }
            )
                .done(() => {
                    notify('Projekteinsatz gelöscht.');

                    $('#project-assignment-editor')
                        .addClass('d-none');

                    loadProjectAssignments();
                })
                .fail((xhr) => {
                    $('#project-assignment-message')
                        .removeClass('d-none')
                        .addClass('alert-danger')
                        .text(
                            xhr.responseJSON?.message ||
                            'Einsatz konnte nicht gelöscht werden.'
                        );
                });
        }
    );

    $('#add-planning-project').on('click', () => {
        activeProject = null;
        projectPhases = [];
        projectAssignments = [];
        resetEditor();
        openEditor();
        $('#planning-project-name').trigger('focus');
    });

    $('#cancel-planning-project').on('click', closeEditor);

    $('#save-planning-project').on('click', () => {
        clearMessage();

        const project = collectProject();

        if (!project.name) {
            showMessage('Bitte einen Projektname eingeben.');
            return;
        }

        if (
            project.start_date &&
            project.end_date &&
            project.end_date < project.start_date
        ) {
            showMessage(
                'Das Projektende darf nicht vor dem Projektbeginn liegen.'
            );
            return;
        }

        $.post(App.Utils.Url.siteUrl('planning_projects/store'), {
            csrf_token: vars('csrf_token'),
            project,
        })
            .done(() => {
                notify('Projekt gespeichert.');
                closeEditor();
                loadProjects();
            })
            .fail((xhr) => {
                showMessage(
                    xhr.responseJSON?.message ||
                    'Projekt konnte nicht gespeichert werden.'
                );
            });
    });

    $('#delete-planning-project').on('click', () => {
        const projectId = Number(
            $('#planning-project-id').val()
        );

        if (
            !projectId ||
            !window.confirm('Projekt wirklich löschen?')
        ) {
            return;
        }

        $.post(App.Utils.Url.siteUrl('planning_projects/delete'), {
            csrf_token: vars('csrf_token'),
            project_id: projectId,
        })
            .done(() => {
                notify('Projekt gelöscht.');
                closeEditor();
                loadProjects();
            })
            .fail((xhr) => {
                showMessage(
                    xhr.responseJSON?.message ||
                    'Projekt konnte nicht gelöscht werden.'
                );
            });
    });

    $filter.on('input', renderList);

    $('#clear-planning-project-filter').on('click', () => {
        $filter.val('');
        renderList();
        $filter.trigger('focus');
    });

    loadProjects();
});
