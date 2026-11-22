$(function () {
    'use strict';

    let teams = [];

    const $list = $('#team-list');
    const $editor = $('#team-editor');
    const $placeholder = $('#team-placeholder');

    function message(text) {
        App.Layouts.Backend.displayNotification(text);
    }

    function resetMembers() {
        $('.team-member-row').each((_, row) => {
            const $row = $(row);

            $row.find('input[type="checkbox"]').prop('checked', false);
            $row
                .find('.team-member-role')
                .val('member');

            setMemberEnabled($row, false);
        });
    }

    function setMemberEnabled($row, enabled) {
        $row
            .find(
                '.team-member-role, .team-can-approve, ' +
                '.team-can-calendar, .team-can-series'
            )
            .prop('disabled', !enabled);

        if (!enabled) {
            $row
                .find(
                    '.team-can-approve, .team-can-calendar, ' +
                    '.team-can-series'
                )
                .prop('checked', false);
        }
    }

    function resetEditor() {
        $('#team-id').val('');
        $('#team-name').val('');
        $('#team-description').val('');
        $('#team-color').val('#7cbae8');
        $('#team-active').prop('checked', true);

        $('#team-editor-title').text('Neues Team');

        resetMembers();

        $('#delete-team').hide();
    }

    function load() {
        $.get(App.Utils.Url.siteUrl('employee_teams/search'))
            .done((response) => {
                teams = response.teams || [];
                renderList();
            })
            .fail((xhr) => {
                message(
                    xhr.responseJSON?.message ||
                    'Teams konnten nicht geladen werden.'
                );
            });
    }

    function renderList() {
        $list.empty();

        if (!teams.length) {
            $('<div/>', {
                class: 'text-muted text-center p-4',
                text: 'Noch keine Teams vorhanden.',
            }).appendTo($list);

            return;
        }

        teams.forEach((team) => {
            const activeText =
                Number(team.is_active) === 1
                    ? ''
                    : ' · inaktiv';

            $('<button/>', {
                type: 'button',
                class:
                    'list-group-item list-group-item-action ' +
                    'd-flex align-items-center gap-2',
                html: [
                    $('<span/>', {
                        css: {
                            width: '12px',
                            height: '12px',
                            borderRadius: '50%',
                            backgroundColor: team.color || '#7cbae8',
                            display: 'inline-block',
                        },
                    }),
                    $('<span/>', {
                        text: team.name + activeText,
                    }),
                    $('<span/>', {
                        class: 'badge bg-secondary ms-auto',
                        text: (team.members || []).length,
                    }),
                ],
                click: () => editTeam(team),
            }).appendTo($list);
        });
    }

    function editTeam(team) {
        resetEditor();

        $('#team-editor-title').text('Team bearbeiten');
        $('#team-id').val(team.id);
        $('#team-name').val(team.name);
        $('#team-description').val(team.description || '');
        $('#team-color').val(team.color || '#7cbae8');
        $('#team-active').prop(
            'checked',
            Number(team.is_active) === 1
        );

        (team.members || []).forEach((member) => {
            const $row = $(
                `.team-member-row[data-user-id="${member.id_users}"]`
            );

            if (!$row.length) {
                return;
            }

            $row
                .find('.team-member-enabled')
                .prop('checked', true);

            setMemberEnabled($row, true);

            $row
                .find('.team-member-role')
                .val(member.team_role || 'member');

            $row
                .find('.team-can-approve')
                .prop(
                    'checked',
                    Number(member.can_approve_absences) === 1
                );

            $row
                .find('.team-can-calendar')
                .prop(
                    'checked',
                    Number(member.can_manage_calendar) === 1
                );

            $row
                .find('.team-can-series')
                .prop(
                    'checked',
                    Number(member.can_manage_series) === 1
                );
        });

        $('#delete-team').show();

        $placeholder.addClass('d-none');
        $editor.removeClass('d-none');
    }

    function collectTeam() {
        const members = [];

        $('.team-member-row').each((_, row) => {
            const $row = $(row);

            if (
                !$row
                    .find('.team-member-enabled')
                    .prop('checked')
            ) {
                return;
            }

            members.push({
                id_users: Number($row.data('user-id')),
                team_role:
                    $row.find('.team-member-role').val(),
                can_approve_absences:
                    $row.find('.team-can-approve').prop('checked')
                        ? 1
                        : 0,
                can_manage_calendar:
                    $row.find('.team-can-calendar').prop('checked')
                        ? 1
                        : 0,
                can_manage_series:
                    $row.find('.team-can-series').prop('checked')
                        ? 1
                        : 0,
            });
        });

        return {
            id: $('#team-id').val() || undefined,
            name: $('#team-name').val().trim(),
            description: $('#team-description').val().trim(),
            color: $('#team-color').val(),
            is_active: $('#team-active').prop('checked')
                ? 1
                : 0,
            members,
        };
    }

    $('.team-member-enabled').on('change', function () {
        const $row = $(this).closest('.team-member-row');

        setMemberEnabled(
            $row,
            $(this).prop('checked')
        );
    });

    $('.team-member-role').on('change', function () {
        const $row = $(this).closest('.team-member-row');
        const role = $(this).val();

        if (role === 'lead') {
            $row
                .find('.team-can-approve')
                .prop('checked', true);

            $row
                .find('.team-can-calendar')
                .prop('checked', true);

            $row
                .find('.team-can-series')
                .prop('checked', true);
        }

        if (role === 'planner') {
            $row
                .find('.team-can-calendar')
                .prop('checked', true);

            $row
                .find('.team-can-series')
                .prop('checked', true);
        }
    });

    $('#add-team').on('click', () => {
        resetEditor();

        $placeholder.addClass('d-none');
        $editor.removeClass('d-none');
    });

    $('#cancel-team').on('click', () => {
        $editor.addClass('d-none');
        $placeholder.removeClass('d-none');
    });

    $('#save-team').on('click', () => {
        const team = collectTeam();

        if (!team.name) {
            message('Bitte einen Teamnamen eingeben.');
            return;
        }

        $.post(App.Utils.Url.siteUrl('employee_teams/store'), {
            csrf_token: vars('csrf_token'),
            team,
        })
            .done(() => {
                message('Team gespeichert.');
                $editor.addClass('d-none');
                $placeholder.removeClass('d-none');
                load();
            })
            .fail((xhr) => {
                message(
                    xhr.responseJSON?.message ||
                    'Team konnte nicht gespeichert werden.'
                );
            });
    });

    $('#delete-team').on('click', () => {
        const teamId = Number($('#team-id').val());

        if (
            !teamId ||
            !window.confirm('Team wirklich löschen?')
        ) {
            return;
        }

        $.post(App.Utils.Url.siteUrl('employee_teams/delete'), {
            csrf_token: vars('csrf_token'),
            team_id: teamId,
        })
            .done(() => {
                message('Team gelöscht.');
                $editor.addClass('d-none');
                $placeholder.removeClass('d-none');
                load();
            })
            .fail((xhr) => {
                message(
                    xhr.responseJSON?.message ||
                    'Team konnte nicht gelöscht werden.'
                );
            });
    });

    load();
});
