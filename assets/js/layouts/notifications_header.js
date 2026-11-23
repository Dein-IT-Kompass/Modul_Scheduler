App.Layouts.NotificationsHeader = (function () {
    const $badge =
        $('#header-notification-count');

    const $list =
        $('#header-notification-list');

    function severityClass(severity) {
        switch (severity) {
            case 'critical':
                return 'danger';

            case 'warning':
                return 'warning';

            case 'success':
                return 'success';

            default:
                return 'primary';
        }
    }

    function render(response) {
        const count =
            Number(response.count || 0);

        $badge.text(
            count > 99 ? '99+' : count
        );

        $badge.toggleClass(
            'd-none',
            count === 0
        );

        $list.empty();

        const notifications =
            response.notifications || [];

        if (!notifications.length) {
            $list.append(
                $('<div/>', {
                    class:
                        'text-center text-muted p-4',
                    text:
                        'Keine aktuellen Meldungen.',
                })
            );

            return;
        }

        notifications.slice(0, 5).forEach(
            (notification) => {
                const color =
                    severityClass(
                        notification.severity
                    );

                const $item =
                    $('<a/>', {
                        href:
                            notification.action_url ||
                            App.Utils.Url.siteUrl(
                                'notifications'
                            ),
                        class:
                            'dropdown-item ' +
                            'text-wrap border-bottom ' +
                            'px-3 py-3',
                    });

                const $top =
                    $('<div/>', {
                        class:
                            'd-flex gap-2 ' +
                            'align-items-start',
                    });

                $('<span/>', {
                    class:
                        `badge bg-${color} mt-1`,
                    text:
                        notification.severity ===
                        'critical'
                            ? '!'
                            : '•',
                }).appendTo($top);

                const $content =
                    $('<div/>', {
                        class: 'flex-grow-1',
                    });

                $('<div/>', {
                    class:
                        notification.is_read
                            ? 'fw-normal'
                            : 'fw-semibold',
                    text:
                        notification.title,
                }).appendTo($content);

                $('<small/>', {
                    class:
                        'text-muted d-block mt-1',
                    text:
                        notification.message || '',
                }).appendTo($content);

                $top.append($content);
                $item.append($top);
                $list.append($item);
            }
        );

        if (notifications.length > 5) {
            $('<a/>', {
                href:
                    App.Utils.Url.siteUrl(
                        'notifications'
                    ),
                class:
                    'dropdown-item ' +
                    'text-center py-2',
                text:
                    'Weitere Meldungen anzeigen',
            }).appendTo($list);
        }
    }

    function refresh() {
        $.get(
            App.Utils.Url.siteUrl(
                'notifications/list'
            )
        )
            .done(render)
            .fail(() => {
                $badge.addClass('d-none');
            });
    }

    function initialize() {
        if (
            !$('#header-notifications').length
        ) {
            return;
        }

        refresh();

        $('#header-notifications-toggle')
            .on('show.bs.dropdown', refresh);

        setInterval(
            refresh,
            60000
        );
    }

    document.addEventListener(
        'DOMContentLoaded',
        initialize
    );

    return {
        refresh,
    };
})();
