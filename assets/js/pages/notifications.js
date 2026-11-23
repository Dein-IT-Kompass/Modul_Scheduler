App.Pages.Notifications = (function () {
    const $page = $('#notifications-page');

    function post(action, data) {
        return $.post(
            App.Utils.Url.siteUrl(
                `notifications/${action}`
            ),
            {
                csrf_token: vars('csrf_token'),
                ...data,
            }
        );
    }

    function removeRow(event) {
        $(event.currentTarget)
            .closest('tr')
            .fadeOut(180, function () {
                $(this).remove();
            });
    }

    function escapeHtml(value) {
        return $('<div/>')
            .text(value || '')
            .html();
    }

    function loadTrash() {
        $.get(
            App.Utils.Url.siteUrl(
                'notifications/trash'
            )
        ).done((response) => {
            const $tbody =
                $('.notification-table tbody');

            $tbody.empty();

            if (!response.notifications.length) {
                $tbody.append(
                    $('<tr/>', {
                        html: $('<td/>', {
                            colspan: 5,
                            class:
                                'text-center ' +
                                'text-muted py-5',
                            text:
                                'Der Papierkorb ist leer.',
                        }),
                    })
                );

                return;
            }

            response.notifications.forEach(
                (notification) => {
                    const $row = $('<tr/>', {
                        'data-recipient-id':
                            notification.recipient_id,
                        html: [
                            $('<td/>', {
                                text:
                                    notification.severity,
                            }),
                            $('<td/>', {
                                html:
                                    '<div class="fw-semibold">' +
                                    escapeHtml(
                                        notification.title
                                    ) +
                                    '</div>' +
                                    '<small class="text-muted">' +
                                    escapeHtml(
                                        notification.message
                                    ) +
                                    '</small>',
                            }),
                            $('<td/>', {
                                text: 'Gelöscht',
                            }),
                            $('<td/>', {
                                text:
                                    notification
                                        .deleted_datetime ||
                                    '',
                            }),
                            $('<td/>', {
                                class:
                                    'text-end text-nowrap',
                                html: [
                                    $('<button/>', {
                                        type: 'button',
                                        class:
                                            'btn btn-sm ' +
                                            'btn-outline-primary ' +
                                            'notification-restore me-1',
                                        text:
                                            'Wiederherstellen',
                                    }),
                                    $('<button/>', {
                                        type: 'button',
                                        class:
                                            'btn btn-sm ' +
                                            'btn-outline-secondary ' +
                                            'notification-history',
                                        html:
                                            '<i class="fas fa-history"></i>',
                                    }),
                                ],
                            }),
                        ],
                    });

                    $tbody.append($row);
                }
            );
        });
    }

    function loadHistory(recipientId) {
        $.get(
            App.Utils.Url.siteUrl(
                'notifications/history'
            ),
            {
                recipient_id: recipientId,
            }
        ).done((response) => {
            const $list =
                $('#notification-history-list');

            $list.empty();

            if (!response.history.length) {
                $list.append(
                    $('<div/>', {
                        class:
                            'text-muted text-center p-4',
                        text:
                            'Noch kein Verlauf vorhanden.',
                    })
                );
            }

            response.history.forEach(
                (entry) => {
                    $('<div/>', {
                        class:
                            'list-group-item px-0',
                        html: [
                            $('<div/>', {
                                class:
                                    'd-flex justify-content-between gap-3',
                                html: [
                                    $('<strong/>', {
                                        text:
                                            entry.action,
                                    }),
                                    $('<small/>', {
                                        class:
                                            'text-muted text-nowrap',
                                        text:
                                            entry.create_datetime,
                                    }),
                                ],
                            }),
                            $('<div/>', {
                                class:
                                    'text-muted mt-1',
                                text:
                                    entry.details || '',
                            }),
                        ],
                    }).appendTo($list);
                }
            );

            bootstrap.Modal
                .getOrCreateInstance(
                    document.getElementById(
                        'notification-history-modal'
                    )
                )
                .show();
        });
    }

    function initialize() {
        $page.on(
            'change',
            '#dashboard-assignment-filter select',
            () => {
                document
                    .getElementById(
                        'dashboard-assignment-filter'
                    )
                    ?.submit();
            }
        );

        $page.on(
            'click',
            '.notification-read',
            (event) => {
                const id = Number(
                    $(event.currentTarget)
                        .closest('tr')
                        .data('recipient-id')
                );

                post('read', {
                    recipient_id: id,
                }).done(() => {
                    const $row =
                        $(event.currentTarget)
                            .closest('tr');

                    $row.find(
                        '.notification-read'
                    )
                        .prop('disabled', true)
                        .text('Gelesen');

                    $row.find('td')
                        .eq(2)
                        .text('Gelesen');

                    App.Layouts.Backend
                        .displayNotification(
                            'Meldung als gelesen markiert.'
                        );
                });
            }
        );

        $page.on(
            'click',
            '.notification-snooze',
            (event) => {
                const $button =
                    $(event.currentTarget);

                const id = Number(
                    $button
                        .closest('tr')
                        .data('recipient-id')
                );

                post('snooze', {
                    recipient_id: id,
                    mode:
                        $button.data('mode'),
                }).done(removeRow.bind(null, event));
            }
        );

        $page.on(
            'click',
            '.notification-view-filter',
            (event) => {
                const $button =
                    $(event.currentTarget);

                $('.notification-view-filter')
                    .removeClass('btn-primary')
                    .addClass(
                        'btn-outline-secondary'
                    );

                $button
                    .removeClass(
                        'btn-outline-secondary'
                    )
                    .addClass('btn-primary');

                if (
                    $button.data('view') ===
                    'trash'
                ) {
                    loadTrash();
                } else {
                    window.location.reload();
                }
            }
        );

        $page.on(
            'click',
            '.notification-restore',
            (event) => {
                const id = Number(
                    $(event.currentTarget)
                        .closest('tr')
                        .data('recipient-id')
                );

                post('restore', {
                    recipient_id: id,
                }).done(() => {
                    removeRow(event);

                    App.Layouts.Backend
                        .displayNotification(
                            'Meldung wurde wiederhergestellt.'
                        );
                });
            }
        );

        $page.on(
            'click',
            '.notification-history',
            (event) => {
                const id = Number(
                    $(event.currentTarget)
                        .closest('tr')
                        .data('recipient-id')
                );

                loadHistory(id);
            }
        );

        $page.on(
            'click',
            '.notification-delete',
            (event) => {
                const id = Number(
                    $(event.currentTarget)
                        .closest('tr')
                        .data('recipient-id')
                );

                if (
                    !window.confirm(
                        'Meldung aus deiner Übersicht entfernen? Das zugrunde liegende Problem bleibt bestehen.'
                    )
                ) {
                    return;
                }

                post('delete', {
                    recipient_id: id,
                }).done(removeRow.bind(null, event));
            }
        );
    }

    document.addEventListener(
        'DOMContentLoaded',
        initialize
    );

    return {
        initialize,
    };
})();
