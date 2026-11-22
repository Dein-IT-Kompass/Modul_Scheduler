$(function () {
    'use strict';

    let categories = [];

    const $list = $('#resource-category-list');
    const $editor = $('#resource-category-editor');
    const $placeholder = $('#resource-category-placeholder');
    const $message = $('#resource-category-message');

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

    function categoryDepth(category) {
        let depth = 0;
        let parentId = Number(category.id_parent || 0);
        const visited = {};

        while (parentId && !visited[parentId]) {
            visited[parentId] = true;

            const parent = categories.find(
                (item) => Number(item.id) === parentId
            );

            if (!parent) {
                break;
            }

            depth++;
            parentId = Number(parent.id_parent || 0);
        }

        return depth;
    }

    function orderedCategories() {
        const result = [];

        function appendChildren(parentId, depth) {
            categories
                .filter(
                    (category) =>
                        Number(category.id_parent || 0) ===
                        Number(parentId || 0)
                )
                .sort(
                    (a, b) =>
                        Number(a.sort_order) -
                            Number(b.sort_order) ||
                        a.name.localeCompare(b.name)
                )
                .forEach((category) => {
                    result.push({...category, depth});
                    appendChildren(category.id, depth + 1);
                });
        }

        appendChildren(0, 0);

        return result;
    }

    function renderParentOptions(
        selectedId = '',
        excludedId = null
    ) {
        const $parent = $('#resource-category-parent');

        $parent.empty().append(
            $('<option/>', {
                value: '',
                text: 'Keine Oberkategorie',
            })
        );

        orderedCategories()
            .filter(
                (category) =>
                    Number(category.id) !== Number(excludedId)
            )
            .forEach((category) => {
                $('<option/>', {
                    value: category.id,
                    text:
                        `${'— '.repeat(category.depth)}` +
                        category.name,
                }).appendTo($parent);
            });

        $parent.val(
            selectedId ? String(selectedId) : ''
        );
    }

    function selectCategoryIcon(icon) {
        const selectedIcon = icon || 'fa-box';

        $('#resource-category-icon').val(selectedIcon);

        $('.resource-category-icon-option')
            .removeClass('btn-primary')
            .addClass('btn-outline-secondary');

        $(
            `.resource-category-icon-option[data-icon="${selectedIcon}"]`
        )
            .removeClass('btn-outline-secondary')
            .addClass('btn-primary');
    }

    function resetEditor() {
        $('#resource-category-id').val('');
        $('#resource-category-name').val('');
        $('#resource-category-color').val('#7cbae8');
        selectCategoryIcon('fa-box');
        $('#resource-category-active').prop('checked', true);
        $('#resource-category-description').val('');

        renderParentOptions();

        $('#resource-category-editor-title')
            .text('Neue Kategorie');

        $('#delete-resource-category').hide();

        clearMessage();
    }

    function loadCategories(selectId = null) {
        $.get(
            App.Utils.Url.siteUrl(
                'planning_resource_categories/search'
            )
        )
            .done((response) => {
                categories = response.categories || [];
                renderList();

                if (selectId) {
                    const category = categories.find(
                        (item) =>
                            Number(item.id) === Number(selectId)
                    );

                    if (category) {
                        editCategory(category);
                    }
                }
            })
            .fail((xhr) => {
                $list.empty().append(
                    $('<div/>', {
                        class: 'alert alert-danger m-3',
                        text:
                            xhr.responseJSON?.message ||
                            'Kategorien konnten nicht geladen werden.',
                    })
                );
            });
    }

    function renderList() {
        $list.empty();

        if (!categories.length) {
            $list.append(
                $('<div/>', {
                    class: 'text-muted text-center p-4',
                    text: 'Noch keine Kategorien vorhanden.',
                })
            );

            return;
        }

        orderedCategories().forEach((category) => {
            const isActive =
                Number(category.is_active) === 1;

            const details = [
                `${category.resource_count || 0} Ressourcen`,
                `${category.child_count || 0} Unterkategorien`,
            ].join(' · ');

            $('<button/>', {
                type: 'button',
                class:
                    'list-group-item list-group-item-action ' +
                    'd-flex gap-3 align-items-center',
                css: {
                    paddingLeft:
                        `${16 + category.depth * 24}px`,
                },
                html: [
                    $('<span/>', {
                        css: {
                            width: '12px',
                            height: '12px',
                            borderRadius: '50%',
                            backgroundColor:
                                category.color || '#7cbae8',
                            flex: '0 0 auto',
                        },
                    }),
                    $('<i/>', {
                        class:
                            `fas ${category.icon || 'fa-box'} ` +
                            'text-muted',
                    }),
                    $('<div/>', {
                        class: 'flex-grow-1 text-start',
                        html: [
                            $('<div/>', {
                                class:
                                    'fw-semibold' +
                                    (isActive
                                        ? ''
                                        : ' text-muted'),
                                text: category.name,
                            }),
                            $('<small/>', {
                                class: 'text-muted',
                                text: details,
                            }),
                        ],
                    }),
                    $('<span/>', {
                        class: isActive
                            ? 'badge bg-success'
                            : 'badge bg-secondary',
                        text: isActive
                            ? 'Aktiv'
                            : 'Inaktiv',
                    }),
                ],
                click: () => editCategory(category),
            }).appendTo($list);
        });
    }

    function editCategory(category) {
        $('#resource-category-id').val(category.id);
        $('#resource-category-name').val(category.name);
        $('#resource-category-color').val(
            category.color || '#7cbae8'
        );
        selectCategoryIcon(category.icon || 'fa-box');
        $('#resource-category-active').prop(
            'checked',
            Number(category.is_active) === 1
        );
        $('#resource-category-description').val(
            category.description || ''
        );

        renderParentOptions(
            category.id_parent || '',
            category.id
        );

        $('#resource-category-editor-title')
            .text('Kategorie bearbeiten');

        $('#delete-resource-category').show();

        clearMessage();
        openEditor();
    }

    function collectCategory() {
        return {
            id: $('#resource-category-id').val() || undefined,
            name: $('#resource-category-name').val().trim(),
            id_parent:
                $('#resource-category-parent').val() || null,
            description:
                $('#resource-category-description').val().trim(),
            color: $('#resource-category-color').val(),
            icon:
                $('#resource-category-icon').val().trim() ||
                'fa-box',
            is_active:
                $('#resource-category-active').prop('checked')
                    ? 1
                    : 0,
        };
    }

    $('#resource-category-icon-picker').on(
        'click',
        '.resource-category-icon-option',
        (event) => {
            selectCategoryIcon(
                $(event.currentTarget).data('icon')
            );
        }
    );

    $('#add-resource-category').on('click', () => {
        resetEditor();
        openEditor();
        $('#resource-category-name').trigger('focus');
    });

    $('#cancel-resource-category').on(
        'click',
        closeEditor
    );

    $('#save-resource-category').on('click', () => {
        const category = collectCategory();

        if (!category.name) {
            showMessage(
                'Bitte einen Kategorienamen eingeben.'
            );
            return;
        }

        $.post(
            App.Utils.Url.siteUrl(
                'planning_resource_categories/store'
            ),
            {
                csrf_token: vars('csrf_token'),
                category,
            }
        )
            .done((response) => {
                notify('Ressourcenkategorie gespeichert.');
                loadCategories(response.id);
            })
            .fail((xhr) => {
                showMessage(
                    xhr.responseJSON?.message ||
                    'Kategorie konnte nicht gespeichert werden.'
                );
            });
    });

    $('#delete-resource-category').on('click', () => {
        const categoryId = Number(
            $('#resource-category-id').val()
        );

        if (
            !categoryId ||
            !window.confirm(
                'Ressourcenkategorie wirklich löschen?'
            )
        ) {
            return;
        }

        $.post(
            App.Utils.Url.siteUrl(
                'planning_resource_categories/delete'
            ),
            {
                csrf_token: vars('csrf_token'),
                category_id: categoryId,
            }
        )
            .done(() => {
                notify('Ressourcenkategorie gelöscht.');
                closeEditor();
                loadCategories();
            })
            .fail((xhr) => {
                showMessage(
                    xhr.responseJSON?.message ||
                    'Kategorie konnte nicht gelöscht werden.'
                );
            });
    });

    loadCategories();
});
