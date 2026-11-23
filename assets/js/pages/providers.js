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
 * Providers page.
 *
 * This module implements the functionality of the providers page.
 */
App.Pages.Providers = (function () {
    const $providers = $('#providers');
    const $id = $('#id');
    const $firstName = $('#first-name');
    const $lastName = $('#last-name');
    const $email = $('#email');
    const $mobileNumber = $('#mobile-number');
    const $phoneNumber = $('#phone-number');
    const $address = $('#address');
    const $city = $('#city');
    const $state = $('#state');
    const $zipCode = $('#zip-code');
    const $isPrivate = $('#is-private');
    const $notes = $('#notes');
    const $employmentStart = $('#employment-start');
    const $employmentEnd = $('#employment-end');
    const $absenceApprovalMode = $('#absence-approval-mode');
    const $language = $('#language');
    const $timezone = $('#timezone');
    const $ldapDn = $('#ldap-dn');
    const $username = $('#username');
    const $password = $('#password');
    const $passwordConfirmation = $('#password-confirm');
    const $notifications = $('#notifications');
    const $calendarView = $('#calendar-view');
    const $filterProviders = $('#filter-providers');
    let filterResults = {};
    let filterLimit = 20;
    let workingPlanManager;

    let absenceApproverCandidates = [];

    function showAbsenceApproverMessage(
        message,
        type = 'danger'
    ) {
        $('#absence-approvers-message')
            .removeClass(
                'd-none alert-success alert-danger alert-warning'
            )
            .addClass(`alert-${type}`)
            .text(message);
    }

    function resetAbsenceApprovers() {
        absenceApproverCandidates = [];

        $('#absence-approvers-message')
            .addClass('d-none')
            .text('');

        $('#save-absence-approvers')
            .prop('disabled', true);

        $('#absence-approvers-results').html(`
            <tr>
                <td colspan="4"
                    class="text-muted text-center py-4">
                    Mitarbeiter auswählen.
                </td>
            </tr>
        `);
    }

    function roleLabel(roleSlug) {
        const labels = {
            admin: 'Administrator',
            administrator: 'Administrator',
            provider: 'Mitarbeiter',
            secretary: 'Verwaltung / Assistenz',
        };

        return labels[roleSlug] || roleSlug || 'Benutzer';
    }

    function renderAbsenceApprovers(candidates, rules) {
        const $results = $('#absence-approvers-results');
        const rulesByApprover = {};

        rules.forEach((rule) => {
            rulesByApprover[
                Number(rule.id_users_approver)
            ] = rule;
        });

        $results.empty();

        if (!candidates.length) {
            $results.append(`
                <tr>
                    <td colspan="4"
                        class="text-muted text-center py-4">
                        Keine geeigneten Benutzer vorhanden.
                    </td>
                </tr>
            `);

            return;
        }

        candidates.forEach((candidate) => {
            const candidateId = Number(candidate.id);
            const rule = rulesByApprover[candidateId] || {};

            const name = [
                candidate.first_name,
                candidate.last_name,
            ]
                .filter(Boolean)
                .join(' ') || candidate.email;

            const $approve = $('<input/>', {
                type: 'checkbox',
                class:
                    'form-check-input absence-can-approve',
            })
                .attr('data-user-id', candidateId)
                .prop(
                    'checked',
                    Number(rule.can_approve) === 1
                );

            const $createApproved = $('<input/>', {
                type: 'checkbox',
                class:
                    'form-check-input ' +
                    'absence-can-create-approved',
            })
                .attr('data-user-id', candidateId)
                .prop(
                    'checked',
                    Number(
                        rule.can_create_approved
                    ) === 1
                );

            $('<tr/>')
                .append(
                    $('<td/>').append(
                        $('<div/>', {
                            class: 'fw-semibold',
                            text: name,
                        }),
                        $('<div/>', {
                            class: 'small text-muted',
                            text: candidate.email || '',
                        })
                    ),
                    $('<td/>').text(
                        roleLabel(candidate.role_slug)
                    ),
                    $('<td/>', {
                        class: 'text-center',
                    }).append($approve),
                    $('<td/>', {
                        class: 'text-center',
                    }).append($createApproved)
                )
                .appendTo($results);
        });

        $('#save-absence-approvers')
            .prop('disabled', false);
    }

    function loadAbsenceApprovers() {
        const providerId = Number($id.val());

        resetAbsenceApprovers();

        if (!providerId) {
            return;
        }

        $.get(
            App.Utils.Url.siteUrl(
                'providers/absence_approval_rules'
            ),
            {
                provider_id: providerId,
            }
        )
            .done((response) => {
                absenceApproverCandidates =
                    response.candidates || [];

                renderAbsenceApprovers(
                    absenceApproverCandidates,
                    response.rules || []
                );
            })
            .fail((xhr) => {
                showAbsenceApproverMessage(
                    xhr.responseJSON?.message ||
                    'Genehmiger konnten nicht geladen werden.'
                );
            });
    }

    function collectAbsenceApproverRules() {
        const rules = [];

        absenceApproverCandidates.forEach(
            (candidate) => {
                const userId = Number(candidate.id);

                const canApprove = $(
                    `.absence-can-approve` +
                    `[data-user-id="${userId}"]`
                ).prop('checked');

                const canCreateApproved = $(
                    `.absence-can-create-approved` +
                    `[data-user-id="${userId}"]`
                ).prop('checked');

                if (
                    !canApprove &&
                    !canCreateApproved
                ) {
                    return;
                }

                rules.push({
                    id_users_approver: userId,
                    can_approve:
                        canApprove ? 1 : 0,
                    can_create_approved:
                        canCreateApproved ? 1 : 0,
                });
            }
        );

        return rules;
    }

    function saveAbsenceApprovers() {
        const providerId = Number($id.val());

        if (!providerId) {
            return;
        }

        $('#save-absence-approvers')
            .prop('disabled', true);

        $.post(
            App.Utils.Url.siteUrl(
                'providers/save_absence_approval_rules'
            ),
            {
                csrf_token: vars('csrf_token'),
                provider_id: providerId,
                rules: collectAbsenceApproverRules(),
            }
        )
            .done(() => {
                showAbsenceApproverMessage(
                    'Genehmiger wurden gespeichert.',
                    'success'
                );

                loadAbsenceApprovers();
            })
            .fail((xhr) => {
                showAbsenceApproverMessage(
                    xhr.responseJSON?.message ||
                    'Genehmiger konnten nicht gespeichert werden.'
                );

                $('#save-absence-approvers')
                    .prop('disabled', false);
            });
    }

    let deploymentCalendarHttpsUrl = '';

    function resetDeploymentCalendar() {
        deploymentCalendarHttpsUrl = '';

        $('#deployment-calendar-empty')
            .removeClass('d-none');

        $('#deployment-calendar-active')
            .addClass('d-none');

        $('#deployment-calendar-secret')
            .addClass('d-none');

        $('#deployment-calendar-url').val('');

        $('#deployment-calendar-webcal')
            .attr('href', '#');

        $('#create-deployment-calendar')
            .prop('disabled', !$id.val());

        $('#rotate-deployment-calendar')
            .prop('disabled', true);

        $('#disable-deployment-calendar')
            .prop('disabled', true);

        $('#deployment-calendar-message')
            .addClass('d-none')
            .text('');
    }

    function loadDeploymentCalendar() {
        const employeeId = Number($id.val());

        resetDeploymentCalendar();

        if (!employeeId) {
            return;
        }

        $('#create-deployment-calendar')
            .prop('disabled', false);

        $.get(
            App.Utils.Url.siteUrl(
                'planning_calendar_subscriptions/status'
            ),
            {
                employee_id: employeeId,
            }
        )
            .done((response) => {
                const subscription =
                    response.subscription;

                if (
                    subscription &&
                    Number(subscription.is_active)
                ) {
                    $('#deployment-calendar-empty')
                        .addClass('d-none');

                    $('#deployment-calendar-active')
                        .removeClass('d-none');

                    $('#rotate-deployment-calendar')
                        .prop('disabled', false);

                    $('#disable-deployment-calendar')
                        .prop('disabled', false);

                    if (
                        response.https_url &&
                        response.webcal_url
                    ) {
                        displayDeploymentCalendarSecret(
                            response
                        );

                        $('#deployment-calendar-message')
                            .addClass('d-none')
                            .text('');
                    }
                }
            })
            .fail((xhr) => {
                showDeploymentCalendarMessage(
                    xhr.responseJSON?.message ||
                    'Kalenderstatus konnte nicht geladen werden.',
                    'danger'
                );
            });
    }

    function showDeploymentCalendarMessage(
        message,
        type = 'danger'
    ) {
        $('#deployment-calendar-message')
            .removeClass(
                'd-none alert-success alert-danger'
            )
            .addClass(`alert-${type}`)
            .text(message);
    }

    function displayDeploymentCalendarSecret(
        response
    ) {
        deploymentCalendarHttpsUrl =
            response.https_url;

        $('#deployment-calendar-empty')
            .addClass('d-none');

        $('#deployment-calendar-active')
            .removeClass('d-none');

        $('#deployment-calendar-secret')
            .removeClass('d-none');

        $('#deployment-calendar-token-hint')
            .addClass('d-none');

        $('#deployment-calendar-url')
            .val(response.https_url);

        $('#deployment-calendar-webcal')
            .attr('href', response.webcal_url);

        $('#rotate-deployment-calendar')
            .prop('disabled', false);

        $('#disable-deployment-calendar')
            .prop('disabled', false);

        const canvas = document.getElementById(
            'deployment-calendar-qr'
        );

        if (window.QRCode && canvas) {
            QRCode.toCanvas(
                canvas,
                response.webcal_url,
                {
                    width: 260,
                    margin: 2,
                    errorCorrectionLevel: 'M',
                },
                (error) => {
                    if (error) {
                        showDeploymentCalendarMessage(
                            'QR-Code konnte nicht erzeugt werden.'
                        );
                    }
                }
            );
        }

        showDeploymentCalendarMessage(
            'Kalenderzugang wurde erstellt. Der geheime Link wird nur jetzt vollständig angezeigt.',
            'success'
        );
    }

    function requestDeploymentCalendar(
        endpoint
    ) {
        const employeeId = Number($id.val());

        if (!employeeId) {
            return;
        }

        $.post(
            App.Utils.Url.siteUrl(endpoint),
            {
                csrf_token: vars('csrf_token'),
                employee_id: employeeId,
            }
        )
            .done(displayDeploymentCalendarSecret)
            .fail((xhr) => {
                showDeploymentCalendarMessage(
                    xhr.responseJSON?.message ||
                    'Kalenderzugang konnte nicht erstellt werden.'
                );
            });
    }

    /**
     * Register deployment calendar actions.
     */
    function addDeploymentCalendarEventListeners() {
        $providers.off(
            '.deploymentCalendar'
        );

        $providers.on(
            'click.deploymentCalendar',
            '#create-deployment-calendar',
            (event) => {
                event.preventDefault();

                requestDeploymentCalendar(
                    'planning_calendar_subscriptions/create'
                );
            }
        );

        $providers.on(
            'click.deploymentCalendar',
            '#rotate-deployment-calendar',
            (event) => {
                event.preventDefault();

                if (
                    !window.confirm(
                        'Der bisherige Kalenderlink wird sofort ungültig. Neuen Link erzeugen?'
                    )
                ) {
                    return;
                }

                requestDeploymentCalendar(
                    'planning_calendar_subscriptions/rotate'
                );
            }
        );

        $providers.on(
            'click.deploymentCalendar',
            '#disable-deployment-calendar',
            (event) => {
                event.preventDefault();

                const employeeId =
                    Number($id.val());

                if (!employeeId) {
                    return;
                }

                if (
                    !window.confirm(
                        'Kalenderzugang wirklich deaktivieren?'
                    )
                ) {
                    return;
                }

                $.post(
                    App.Utils.Url.siteUrl(
                        'planning_calendar_subscriptions/disable'
                    ),
                    {
                        csrf_token:
                            vars('csrf_token'),

                        employee_id:
                            employeeId,
                    }
                )
                    .done(() => {
                        resetDeploymentCalendar();

                        showDeploymentCalendarMessage(
                            'Kalenderzugang wurde deaktiviert.',
                            'success'
                        );
                    })
                    .fail((xhr) => {
                        showDeploymentCalendarMessage(
                            xhr.responseJSON?.message ||
                            'Kalenderzugang konnte nicht deaktiviert werden.'
                        );
                    });
            }
        );

        $providers.on(
            'click.deploymentCalendar',
            '#copy-deployment-calendar',
            async (event) => {
                event.preventDefault();

                if (!deploymentCalendarHttpsUrl) {
                    return;
                }

                try {
                    await navigator.clipboard.writeText(
                        deploymentCalendarHttpsUrl
                    );
                } catch (error) {
                    const $url =
                        $('#deployment-calendar-url');

                    $url.trigger('select');

                    document.execCommand('copy');
                }

                App.Layouts.Backend
                    .displayNotification(
                        'ICS-Link wurde kopiert.'
                    );
            }
        );
    }

    /**
     * Add the page event listeners.
     */
    function addEventListeners() {
        $providers.on(
            'click',
            '#save-absence-approvers',
            (event) => {
                event.preventDefault();
                saveAbsenceApprovers();
            }
        );

        /**
         * Event: Filter Providers Form "Submit"
         *
         * Filter the provider records with the given key string.
         *
         * @param {jQuery.Event} event
         */
        $providers.on('submit', '#filter-providers form', (event) => {
            event.preventDefault();
            const key = $('#filter-providers .key').val();
            $('.selected').removeClass('selected');
            App.Pages.Providers.resetForm();
            App.Pages.Providers.filter(key);
        });

        /**
         * Event: Filter Provider Row "Click"
         *
         * Display the selected provider data to the user.
         */
        $providers.on('click', '.provider-row', (event) => {
            if ($filterProviders.find('.filter').prop('disabled')) {
                $filterProviders.find('.results').css('color', '#AAA');
                return; // Exit because we are currently on edit mode.
            }

            const providerId = $(event.currentTarget).attr('data-id');
            const provider = filterResults.find((filterResult) => Number(filterResult.id) === Number(providerId));

            App.Pages.Providers.display(provider);
            loadDeploymentCalendar();
            $filterProviders.find('.selected').removeClass('selected');
            $(event.currentTarget).addClass('selected');
            $('#edit-provider, #delete-provider').prop('disabled', false);

            // Automatically enter edit mode
            $('#providers-page').addClass('editing');
            $providers.find('.add-edit-delete-group').hide();
            $providers.find('.save-cancel-group').show();
            $providers.find('#delete-provider').show(); // Show delete button when editing
            $filterProviders.find('button').prop('disabled', true);
            $filterProviders.find('.results').css('color', '#AAA');
            $providers.find('.record-details').find('input, select, textarea').prop('disabled', false);
            $providers.find('.record-details .form-label span').prop('hidden', false);
            $('#password, #password-confirm').removeClass('required');
            $('#provider-services input:checkbox').prop('disabled', false);
            $('#select-all-services, #select-none-services').prop('disabled', false);
            $providers
                .find(
                    '.add-break, .edit-break, .delete-break, .add-working-plan-exception, .edit-working-plan-exception, .delete-working-plan-exception, #reset-working-plan',
                )
                .prop('disabled', false);
            $('#providers input:checkbox').prop('disabled', false);
            workingPlanManager.timepickers(false);
        });

        /**
         * Return to the provider overview.
         */
        $providers.on(
            'click',
            '#back-to-providers',
            (event) => {
                event.preventDefault();

                App.Pages.Providers.resetForm();

                $('#providers-page')
                    .removeClass('editing');

                $filterProviders
                    .find('button')
                    .prop('disabled', false);

                $filterProviders
                    .find('.results')
                    .css('color', '');

                $('#provider-detail-breadcrumb')
                    .text('');

                $('.switch-view a[href="#details"]')
                    .tab('show');

                $('#filter-providers .key')
                    .trigger('focus');
            }
        );

        /**
         * Event: Add New Provider Button "Click"
         */
        $providers.on('click', '#add-provider', () => {
            App.Pages.Providers.resetForm();
            $('#providers-page').addClass('editing');
            $filterProviders.find('button').prop('disabled', true);
            $filterProviders.find('.results').css('color', '#AAA');
            $providers.find('.add-edit-delete-group').hide();
            $providers.find('.save-cancel-group').show();
            $providers.find('#delete-provider').hide(); // Hide delete button when adding
            $providers.find('.record-details').find('input, select, textarea').prop('disabled', false);
            $providers.find('.record-details .form-label span').prop('hidden', false);
            $('#password, #password-confirm').addClass('required');
            $providers
                .find(
                    '.add-break, .edit-break, .delete-break, .add-working-plan-exception, .edit-working-plan-exception, .delete-working-plan-exception, #reset-working-plan',
                )
                .prop('disabled', false);
            $('#provider-services input:checkbox').prop('disabled', false);
            $('#select-all-services, #select-none-services').prop('disabled', false);

            // Apply default working plan
            const companyWorkingPlan = JSON.parse(vars('company_working_plan'));
            workingPlanManager.setup(companyWorkingPlan);
            workingPlanManager.timepickers(false);
        });

        /**
         * Event: Edit Provider Button "Click"
         */
        $providers.on('click', '#edit-provider', () => {
            $('#providers-page').addClass('editing');
            $providers.find('.add-edit-delete-group').hide();
            $providers.find('.save-cancel-group').show();
            $filterProviders.find('button').prop('disabled', true);
            $filterProviders.find('.results').css('color', '#AAA');
            $providers.find('.record-details').find('input, select, textarea').prop('disabled', false);
            $providers.find('.record-details .form-label span').prop('hidden', false);
            $('#password, #password-confirm').removeClass('required');
            $('#provider-services input:checkbox').prop('disabled', false);
            $('#select-all-services, #select-none-services').prop('disabled', false);
            $providers
                .find(
                    '.add-break, .edit-break, .delete-break, .add-working-plan-exception, .edit-working-plan-exception, .delete-working-plan-exception, #reset-working-plan',
                )
                .prop('disabled', false);
            $('#providers input:checkbox').prop('disabled', false);
            workingPlanManager.timepickers(false);
        });

        /**
         * Event: Delete Provider Button "Click"
         */
        $providers.on('click', '#delete-provider', () => {
            const providerId = $id.val();

            const buttons = [
                {
                    text: lang('cancel'),
                    click: (event, messageModal) => {
                        messageModal.hide();
                    },
                },
                {
                    text: lang('delete'),
                    click: (event, messageModal) => {
                        App.Pages.Providers.remove(providerId);
                        messageModal.hide();
                    },
                },
            ];

            App.Utils.Message.show(lang('delete_provider'), lang('delete_record_prompt'), buttons);
        });

        /**
         * Event: Save Provider Button "Click"
         */
        $providers.on('click', '#save-provider', () => {
            const workingPlan = workingPlanManager.get();

            if (workingPlan === null) {
                return;
            }

            const provider = {
                first_name: $firstName.val(),
                last_name: $lastName.val(),
                email: $email.val(),
                mobile_number: $mobileNumber.val(),
                phone_number: $phoneNumber.val(),
                address: $address.val(),
                city: $city.val(),
                state: $state.val(),
                zip_code: $zipCode.val(),
                is_private: Number($isPrivate.prop('checked')),
                notes: $notes.val(),
                employment_start:
                    $employmentStart.val() || null,
                employment_end:
                    $employmentEnd.val() || null,
                absence_approval_mode:
                    $absenceApprovalMode.val() ||
                    'approval_required',
                language: $language.val(),
                timezone: $timezone.val(),
                ldap_dn: $ldapDn.val(),
                settings: {
                    username: $username.val(),
                    working_plan: JSON.stringify(workingPlan),
                    working_plan_exceptions: JSON.stringify(workingPlanManager.getWorkingPlanExceptions()),
                    notifications: Number($notifications.prop('checked')),
                    calendar_view: $calendarView.val(),
                },
            };

            // Include provider services.
            provider.services = [];
            $('#provider-services input:checkbox').each((index, checkboxEl) => {
                if ($(checkboxEl).prop('checked')) {
                    provider.services.push($(checkboxEl).attr('data-id'));
                }
            });

            // Include password if changed.
            if ($password.val() !== '') {
                provider.settings.password = $password.val();
            }

            // Include id if changed.
            if ($id.val() !== '') {
                provider.id = $id.val();
            }

            if (!App.Pages.Providers.validate()) {
                return;
            }

            App.Pages.Providers.save(provider);
        });

        /**
         * Event: Cancel Provider Button "Click"
         *
         * Cancel add or edit of an provider record.
         */
        $providers.on('click', '#cancel-provider', () => {
            const id = $('#filter-providers .selected').attr('data-id');
            App.Pages.Providers.resetForm();
            $('#providers-page').removeClass('editing');
            if (id) {
                App.Pages.Providers.select(id, true);
            }
        });

        /**
         * Event: Reset Working Plan Button "Click".
         */
        $providers.on('click', '#reset-working-plan', () => {
            $('.breaks tbody').empty();
            $('.working-plan-exceptions tbody').empty();
            $('.work-start, .work-end').val('');
            const companyWorkingPlan = JSON.parse(vars('company_working_plan'));
            workingPlanManager.setup(companyWorkingPlan);
            workingPlanManager.timepickers(false);
        });

        /**
         * Event: Select All Services Button "Click"
         */
        $providers.on('click', '#select-all-services', () => {
            $('#provider-services input:checkbox').prop('checked', true);
        });

        /**
         * Event: Select None Services Button "Click"
         */
        $providers.on('click', '#select-none-services', () => {
            $('#provider-services input:checkbox').prop('checked', false);
        });
    }

    /**
     * Save provider record to database.
     *
     * @param {Object} provider Contains the provider record data. If an 'id' value is provided
     * then the update operation is going to be executed.
     */
    function save(provider) {
        App.Http.Providers.save(provider).then((response) => {
            App.Layouts.Backend.displayNotification(lang('provider_saved'));
            App.Pages.Providers.resetForm();
            $('#providers-page').removeClass('editing');
            $('#filter-providers .key').val('');
            App.Pages.Providers.filter('', response.id, true);
        });
    }

    /**
     * Delete a provider record from database.
     *
     * @param {Number} id Record id to be deleted.
     */
    function remove(id) {
        App.Http.Providers.destroy(id).then(() => {
            App.Layouts.Backend.displayNotification(lang('provider_deleted'));
            App.Pages.Providers.resetForm();
            $('#providers-page').removeClass('editing');
            App.Pages.Providers.filter($('#filter-providers .key').val());
        });
    }

    /**
     * Validates a provider record.
     *
     * @return {Boolean} Returns the validation result.
     */
    function validate() {
        $providers.find('.is-invalid').removeClass('is-invalid');
        $providers.find('.form-message').removeClass('alert-danger').hide();

        try {
            // Validate required fields.
            let missingRequired = false;

            $providers.find('.required').each((index, requiredFieldEl) => {
                if (!$(requiredFieldEl).val()) {
                    $(requiredFieldEl).addClass('is-invalid');
                    missingRequired = true;
                }
            });

            if (missingRequired) {
                throw new Error(lang('fields_are_required'));
            }

            // Validate passwords.
            if ($password.val() !== $passwordConfirmation.val()) {
                $('#password, #password-confirm').addClass('is-invalid');
                throw new Error(lang('passwords_mismatch'));
            }

            if ($password.val().length < vars('min_password_length') && $password.val() !== '') {
                $('#password, #password-confirm').addClass('is-invalid');
                throw new Error(lang('password_length_notice').replace('$number', vars('min_password_length')));
            }

            // Validate user email.
            if (!App.Utils.Validation.email($email.val())) {
                $email.addClass('is-invalid');
                throw new Error(lang('invalid_email'));
            }

            // Validate phone number.
            const phoneNumber = $phoneNumber.val();

            if (phoneNumber && !App.Utils.Validation.phone(phoneNumber)) {
                $phoneNumber.addClass('is-invalid');
                throw new Error(lang('invalid_phone'));
            }

            // Validate mobile number.
            const mobileNumber = $mobileNumber.val();

            if (mobileNumber && !App.Utils.Validation.phone(mobileNumber)) {
                $mobileNumber.addClass('is-invalid');
                throw new Error(lang('invalid_phone'));
            }

            // Check if username exists
            if ($username.attr('already-exists') === 'true') {
                $username.addClass('is-invalid');
                throw new Error(lang('username_already_exists'));
            }

            return true;
        } catch (error) {
            $('#providers .form-message').addClass('alert-danger').text(error.message).show();
            return false;
        }
    }

    /**
     * Resets the provider tab form back to its initial state.
     */
    function resetForm() {
        resetAbsenceApprovers();
        $('#provider-detail-breadcrumb').text('');

        $filterProviders.find('.selected').removeClass('selected');
        $filterProviders.find('button').prop('disabled', false);
        $filterProviders.find('.results').css('color', '');

        $providers.find('.add-edit-delete-group').show();
        $providers.find('.save-cancel-group').hide();
        $providers.find('.record-details h4 a').remove();
        $providers.find('.record-details').find('input, select, textarea').val('').prop('disabled', true);
        $providers.find('.record-details .form-label span').prop('hidden', true);
        $providers.find('.record-details #calendar-view').val('default');
        $providers.find('.record-details #language').val(vars('default_language'));
        $providers.find('.record-details #timezone').val(vars('default_timezone'));
        $providers.find('.record-details #is-private').prop('checked', false);
        $providers.find('.record-details #notifications').prop('checked', true);
        $providers.find('.add-break, .add-working-plan-exception, #reset-working-plan').prop('disabled', true);

        workingPlanManager.timepickers(true);
        $providers.find('#providers .working-plan input:checkbox').prop('disabled', true);
        $('.breaks').find('.edit-break, .delete-break').prop('disabled', true);
        $('.working-plan-exceptions')
            .find('.edit-working-plan-exception, .delete-working-plan-exception')
            .prop('disabled', true);

        $providers.find('.record-details .is-invalid').removeClass('is-invalid');
        $providers.find('.record-details .form-message').hide();

        $('#edit-provider, #delete-provider').prop('disabled', true);
        $('#provider-services input:checkbox').prop('disabled', true).prop('checked', false);
        $('#select-all-services, #select-none-services').prop('disabled', true);
        $('#provider-services a').remove();
        $('#providers .working-plan tbody').empty();
        $('#providers .breaks tbody').empty();
        $('#providers .working-plan-exceptions tbody').empty();
    }

    /**
     * Display a provider record into the provider form.
     *
     * @param {Object} provider Contains the provider record data.
     */
    function display(provider) {
        $id.val(provider.id);
        loadAbsenceApprovers();

        $('#provider-detail-breadcrumb').text(
            [
                provider.first_name,
                provider.last_name,
            ]
                .filter(Boolean)
                .join(' ')
        );


        $firstName.val(provider.first_name);
        $lastName.val(provider.last_name);
        $email.val(provider.email);
        $mobileNumber.val(provider.mobile_number);
        $phoneNumber.val(provider.phone_number);
        $address.val(provider.address);
        $city.val(provider.city);
        $state.val(provider.state);
        $zipCode.val(provider.zip_code);
        $isPrivate.prop('checked', provider.is_private);
        $notes.val(provider.notes);
        $absenceApprovalMode.val(
            provider.absence_approval_mode ||
            'approval_required'
        );
        $language.val(provider.language);
        $timezone.val(provider.timezone);
        $ldapDn.val(provider.ldap_dn);

        $username.val(provider.settings.username);
        $calendarView.val(provider.settings.calendar_view);
        $notifications.prop('checked', Boolean(Number(provider.settings.notifications)));

        // Add dedicated provider link.
        let dedicatedUrl = App.Utils.Url.siteUrl('?provider=' + encodeURIComponent(provider.id));
        let $link = $('<a/>', {
            'href': dedicatedUrl,
            'target': '_blank',
            'data-bs-toggle': 'tooltip',
            'title': lang('booking_link'),
            'aria-label': lang('booking_link'),
            'html': [
                $('<i/>', {
                    'class': 'fas fa-link',
                }),
            ],
        });

        $providers.find('.details-view h4').find('a').remove().end().append($link);
        new bootstrap.Tooltip($link[0]);

        $('#provider-services a').remove();
        $('#provider-services input:checkbox').prop('checked', false);

        provider.services.forEach((providerServiceId) => {
            const $checkbox = $('#provider-services input[data-id="' + providerServiceId + '"]');

            if (!$checkbox.length) {
                return;
            }

            $checkbox.prop('checked', true);

            // Add dedicated service-provider link.
            dedicatedUrl = App.Utils.Url.siteUrl(
                '?provider=' + encodeURIComponent(provider.id) + '&service=' + encodeURIComponent(providerServiceId),
            );

            $link = $('<a/>', {
                'href': dedicatedUrl,
                'target': '_blank',
                'class': 'ms-2',
                'data-bs-toggle': 'tooltip',
                'title': lang('booking_link'),
                'aria-label': lang('booking_link'),
                'html': [
                    $('<i/>', {
                        'class': 'fas fa-link',
                    }),
                ],
            });

            $checkbox.parent().append($link);
            new bootstrap.Tooltip($link[0]);
        });

        // Display working plan
        const workingPlan = JSON.parse(provider.settings.working_plan);
        workingPlanManager.setup(workingPlan);
        $('.working-plan').find('input').prop('disabled', true);
        $('.breaks').find('.edit-break, .delete-break').prop('disabled', true);
        $providers.find('.working-plan-exceptions tbody').empty();
        const workingPlanExceptions = JSON.parse(provider.settings.working_plan_exceptions);
        workingPlanManager.setupWorkingPlanExceptions(workingPlanExceptions);
        $('.working-plan-exceptions')
            .find('.edit-working-plan-exception, .delete-working-plan-exception')
            .prop('disabled', true);
        $providers.find('.working-plan input:checkbox').prop('disabled', true);
    }

    /**
     * Filters provider records depending a string keyword.
     *
     * @param {string} keyword This is used to filter the provider records of the database.
     * @param {numeric} selectId Optional, if set, when the function is complete a result row can be set as selected.
     * @param {bool} show Optional (false), if true the selected record will be also displayed.
     */
    function filter(keyword, selectId = null, show = false) {
        App.Http.Providers.search(keyword, filterLimit).then((response) => {
            filterResults = response;

            $filterProviders.find('.results').empty();
            response.forEach((provider) => {
                $('#filter-providers .results').append(App.Pages.Providers.getFilterHtml(provider)).append($('<hr/>'));
            });

            if (!response.length) {
                $filterProviders.find('.results').append(
                    $('<em/>', {
                        'text': lang('no_records_found'),
                    }),
                );
            } else if (response.length === filterLimit) {
                $('<button/>', {
                    'type': 'button',
                    'class': 'btn btn-outline-secondary w-100 load-more text-center',
                    'text': lang('load_more'),
                    'click': () => {
                        filterLimit += 20;
                        App.Pages.Providers.filter(keyword, selectId, show);
                    },
                }).appendTo('#filter-providers .results');
            }

            if (selectId) {
                App.Pages.Providers.select(selectId, show);
            }
        });
    }

    /**
     * Get an provider row html code that is going to be displayed on the filter results list.
     *
     * @param {Object} provider Contains the provider record data.
     *
     * @return {String} The html code that represents the record on the filter results list.
     */
    function getFilterHtml(provider) {
        const name = provider.first_name + ' ' + provider.last_name;

        let info = provider.email;

        info = provider.mobile_number ? info + ', ' + provider.mobile_number : info;

        info = provider.phone_number ? info + ', ' + provider.phone_number : info;

        return $('<div/>', {
            'class': 'provider-row entry',
            'data-id': provider.id,
            'html': [
                $('<strong/>', {
                    'text': name,
                }),
                $('<br/>'),
                $('<small/>', {
                    'class': 'text-muted',
                    'text': info,
                }),
                $('<br/>'),
            ],
        });
    }

    /**
     * Select and display a providers filter result on the form.
     *
     * @param {Number} id Record id to be selected.
     * @param {Boolean} show Optional (false), if true the record will be displayed on the form.
     */
    function select(id, show = false) {
        // Select record in filter results.
        $filterProviders.find('.provider-row[data-id="' + id + '"]').addClass('selected');

        // Display record in form (if display = true).
        if (show) {
            const provider = filterResults.find((filterResult) => Number(filterResult.id) === Number(id));

            App.Pages.Providers.display(provider);

            $('#edit-provider, #delete-provider').prop('disabled', false);
        }
    }

    /**
     * Initialize the module.
     */
    function initialize() {
        workingPlanManager = new App.Utils.WorkingPlan();
        workingPlanManager.addEventListeners();

        App.Pages.Providers.resetForm();
        App.Pages.Providers.filter('');
        App.Pages.Providers.addEventListeners();
        addDeploymentCalendarEventListeners();

        vars('services').forEach((service) => {
            const checkboxId = `provider-service-${service.id}`;

            $('<div/>', {
                'class': 'checkbox',
                'html': [
                    $('<div/>', {
                        'class': 'checkbox form-check',
                        'html': [
                            $('<input/>', {
                                'id': checkboxId,
                                'class': 'form-check-input',
                                'type': 'checkbox',
                                'data-id': service.id,
                                'prop': {
                                    'disabled': true,
                                },
                            }),
                            $('<label/>', {
                                'class': 'form-check-label',
                                'text': service.name,
                                'for': checkboxId,
                            }),
                        ],
                    }),
                ],
            }).appendTo('#provider-services');
        });
    }

    document.addEventListener('DOMContentLoaded', initialize);

    return {
        filter,
        save,
        remove,
        validate,
        getFilterHtml,
        resetForm,
        display,
        select,
        addEventListeners,
    };
})();
