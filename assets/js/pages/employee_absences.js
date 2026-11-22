$(function () {
    'use strict';

    const $formCard = $('#absence-form-card');
    const $results = $('#absence-results');
    const $message = $('#absence-form-message');

    const labels = {
        vacation: 'Urlaub',
        sick: 'Krank',
        training: 'Fortbildung',
        business_trip: 'Dienstreise',
        other: 'Sonstige Abwesenheit',
    };

    const statuses = {
        requested: ['Angefragt', 'warning'],
        approved: ['Genehmigt', 'success'],
        rejected: ['Abgelehnt', 'danger'],
        cancelled: ['Storniert', 'secondary'],
    };

    function showMessage(text, type = 'danger') {
        $message
            .removeClass('d-none alert-danger alert-success alert-warning')
            .addClass(`alert-${type}`)
            .text(text);
    }

    function clearMessage() {
        $message.addClass('d-none').text('');
    }

    function formatDate(value) {
        return moment(value, 'YYYY-MM-DD').format('DD.MM.YYYY');
    }

    function loadRecords() {
        $.get(App.Utils.Url.siteUrl('employee_absences/search'))
            .done((response) => render(response.records || []))
            .fail((xhr) =>
                showMessage(
                    xhr.responseJSON?.message ||
                        'Abwesenheiten konnten nicht geladen werden.',
                ),
            );
    }

    function render(records) {
        $results.empty();

        if (!records.length) {
            $results.append(`
                <tr>
                    <td colspan="6" class="text-muted text-center py-4">
                        Noch keine Abwesenheiten vorhanden.
                    </td>
                </tr>
            `);

            return;
        }

        records.forEach((record) => {
            const employeeName = [
                record.employee_first_name,
                record.employee_last_name,
            ]
                .filter(Boolean)
                .join(' ');

            const status = statuses[record.status] || [
                record.status,
                'secondary',
            ];

            let period =
                formatDate(record.start_date) +
                ' – ' +
                formatDate(record.end_date);

            if (Number(record.all_day) !== 1) {
                period +=
                    ` · ${String(record.start_time).slice(0, 5)}` +
                    `–${String(record.end_time).slice(0, 5)}`;
            }

            const $actions = $('<div/>', {
                class: 'd-flex gap-2 justify-content-end',
            });

            if (
                vars('can_manage_absences') &&
                record.status === 'requested'
            ) {
                $('<button/>', {
                    type: 'button',
                    class: 'btn btn-sm btn-success',
                    text: 'Genehmigen',
                    click: () => decide(record.id, 'approve'),
                }).appendTo($actions);

                $('<button/>', {
                    type: 'button',
                    class: 'btn btn-sm btn-outline-danger',
                    text: 'Ablehnen',
                    click: () => decide(record.id, 'reject'),
                }).appendTo($actions);
            }

            if (
                ['requested', 'approved'].includes(record.status) &&
                (
                    vars('can_manage_absences') ||
                    Number(record.id_users_employee) ===
                        Number(vars('current_user_id'))
                )
            ) {
                $('<button/>', {
                    type: 'button',
                    class: 'btn btn-sm btn-outline-secondary',
                    text: 'Stornieren',
                    click: () => cancel(record.id),
                }).appendTo($actions);
            }

            $('<tr/>')
                .append(
                    $('<td/>').text(employeeName),
                    $('<td/>').text(labels[record.absence_type] || record.absence_type),
                    $('<td/>').text(period),
                    $('<td/>').append(
                        $('<span/>', {
                            class: `badge bg-${status[1]}`,
                            text: status[0],
                        }),
                    ),
                    $('<td/>').text(record.notes || ''),
                    $('<td/>', {
                        class: 'text-end',
                        html: $actions,
                    }),
                )
                .appendTo($results);
        });
    }

    function decide(absenceId, action) {
        const note = window.prompt(
            action === 'approve'
                ? 'Optionaler Hinweis zur Genehmigung:'
                : 'Begründung der Ablehnung:',
            '',
        );

        if (note === null) {
            return;
        }

        $.post(App.Utils.Url.siteUrl(`employee_absences/${action}`), {
            csrf_token: vars('csrf_token'),
            absence_id: absenceId,
            decision_note: note,
        })
            .done(() => loadRecords())
            .fail((xhr) =>
                showMessage(
                    xhr.responseJSON?.message ||
                        'Entscheidung konnte nicht gespeichert werden.',
                ),
            );
    }

    function cancel(absenceId) {
        if (!window.confirm('Abwesenheit wirklich stornieren?')) {
            return;
        }

        $.post(App.Utils.Url.siteUrl('employee_absences/cancel'), {
            csrf_token: vars('csrf_token'),
            absence_id: absenceId,
        })
            .done(() => loadRecords())
            .fail((xhr) =>
                showMessage(
                    xhr.responseJSON?.message ||
                        'Abwesenheit konnte nicht storniert werden.',
                ),
            );
    }

    $('#add-absence').on('click', () => {
        clearMessage();
        $formCard.removeClass('d-none');

        const today = moment().format('YYYY-MM-DD');

        $('#absence-start-date').val(today);
        $('#absence-end-date').val(today);
    });

    $('#cancel-absence-form').on('click', () => {
        $formCard.addClass('d-none');
    });

    $('#absence-all-day').on('change', function () {
        $('.absence-time').toggleClass('d-none', $(this).prop('checked'));
    });

    $('#save-absence').on('click', () => {
        clearMessage();

        const absence = {
            id_users_employee: Number($('#absence-employee').val()),
            absence_type: $('#absence-type').val(),
            start_date: $('#absence-start-date').val(),
            end_date: $('#absence-end-date').val(),
            all_day: $('#absence-all-day').prop('checked') ? 1 : 0,
            start_time: $('#absence-start-time').val() || null,
            end_time: $('#absence-end-time').val() || null,
            notes: $('#absence-notes').val(),
        };

        $.post(App.Utils.Url.siteUrl('employee_absences/store'), {
            csrf_token: vars('csrf_token'),
            absence,
        })
            .done(() => {
                $formCard.addClass('d-none');
                showMessage(
                    absence.absence_type === 'sick'
                        ? 'Krankmeldung wurde eingetragen.'
                        : 'Abwesenheitsantrag wurde gespeichert.',
                    'success',
                );
                loadRecords();
            })
            .fail((xhr) =>
                showMessage(
                    xhr.responseJSON?.message ||
                        'Abwesenheit konnte nicht gespeichert werden.',
                ),
            );
    });

    loadRecords();
});
