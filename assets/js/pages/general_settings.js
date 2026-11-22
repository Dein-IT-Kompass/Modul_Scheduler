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
 * General settings page.
 *
 * This module implements the functionality of the general settings page.
 */
App.Pages.GeneralSettings = (function () {
    const $saveSettings = $('#save-settings');
    const $companyLogo = $('#company-logo');
    const $companyLogoPreview = $('#company-logo-preview');
    const $removeCompanyLogo = $('#remove-company-logo');
    const $companyColor = $('#company-color');
    const $resetCompanyColor = $('#reset-company-color');
    let companyLogoBase64 = '';

    /**
     * Check if the form has invalid values.
     *
     * @return {Boolean}
     */
    function isInvalid() {
        try {
            $('#general-settings .is-invalid').removeClass('is-invalid');

            // Validate required fields.

            let missingRequiredFields = false;

            $('#general-settings .required').each((index, requiredField) => {
                const $requiredField = $(requiredField);

                if (!$requiredField.val()) {
                    $requiredField.addClass('is-invalid');
                    missingRequiredFields = true;
                }
            });

            if (missingRequiredFields) {
                throw new Error(lang('fields_are_required'));
            }

            return false;
        } catch (error) {
            App.Layouts.Backend.displayNotification(error.message);
            return true;
        }
    }

    function deserialize(generalSettings) {
        generalSettings.forEach((generalSetting) => {
            if (generalSetting.name === 'resource_icon_packs') {
                let packs = ['core'];

                try {
                    const parsed = JSON.parse(
                        generalSetting.value || '[]'
                    );

                    if (Array.isArray(parsed)) {
                        packs = ['core', ...parsed];
                    }
                } catch (error) {
                    packs = ['core'];
                }

                packs = [...new Set(packs)];

                $('.resource-icon-pack-setting').each(
                    (index, field) => {
                        $(field).prop(
                            'checked',
                            packs.includes(String($(field).val()))
                        );
                    }
                );

                updateResourceIconPacksField();

                return;
            }

            if (generalSetting.name === 'enabled_modules') {
                let modules = ['scheduler'];
                try {
                    const parsed = JSON.parse(generalSetting.value || '[]');
                    if (Array.isArray(parsed)) modules = ['scheduler', ...parsed];
                } catch (error) {
                    modules = ['scheduler'];
                }
                modules = [...new Set(modules)];
                $('#module-erp').prop('checked', modules.includes('erp'));
                updateEnabledModulesField();
                return;
            }

            if (generalSetting.name === 'company_logo' && generalSetting.value) {
                companyLogoBase64 = generalSetting.value;
                $companyLogoPreview.attr('src', generalSetting.value);
                $companyLogoPreview.prop('hidden', false);
                $removeCompanyLogo.prop('hidden', false);
                return;
            }

            if (generalSetting.name === 'company_color' && generalSetting.value !== '#ffffff') {
                $resetCompanyColor.prop('hidden', false);
            }

            const $field = $('[data-field="' + generalSetting.name + '"]');

            $field.is(':checkbox')
                ? $field.prop('checked', Boolean(Number(generalSetting.value)))
                : $field.val(generalSetting.value);
        });
    }

    function updateResourceIconPacksField() {
        const packs = ['core'];

        $('.resource-icon-pack-setting:checked').each(
            (index, field) => {
                const pack = String($(field).val());

                if (pack && !packs.includes(pack)) {
                    packs.push(pack);
                }
            }
        );

        $('#resource-icon-packs').val(
            JSON.stringify(packs)
        );
    }

    function updateEnabledModulesField() {
        const modules = ['scheduler'];
        if ($('#module-erp').prop('checked')) modules.push('erp');
        $('#enabled-modules').val(JSON.stringify(modules));
    }

    function serialize() {
        const generalSettings = [];

        $('[data-field]').each((index, field) => {
            const $field = $(field);

            generalSettings.push({
                name: $field.data('field'),
                value: $field.is(':checkbox') ? Number($field.prop('checked')) : $field.val(),
            });
        });

        generalSettings.push({
            name: 'company_logo',
            value: companyLogoBase64,
        });

        return generalSettings;
    }

    /**
     * Save the account information.
     */
    function onSaveSettingsClick() {
        if (isInvalid()) {
            App.Layouts.Backend.displayNotification(lang('user_settings_are_invalid'));
            return;
        }

        const generalSettings = serialize();

        App.Http.GeneralSettings.save(generalSettings).done(() => {
            App.Layouts.Backend.displayNotification(lang('settings_saved'), [
                {
                    label: lang('reload'), // Reload Page
                    function: () => window.location.reload(),
                },
            ]);
        });
    }

    /**
     * Convert the selected image to a base64 encoded string.
     */
    function onCompanyLogoChange() {
        const file = $companyLogo[0].files[0];

        if (!file) {
            $removeCompanyLogo.trigger('click');
            return;
        }

        App.Utils.File.toBase64(file).then((base64) => {
            companyLogoBase64 = base64;
            $companyLogoPreview.attr('src', base64);
            $companyLogoPreview.prop('hidden', false);
            $removeCompanyLogo.prop('hidden', false);
        });
    }

    /**
     * Remove the company logo data.
     */
    function onRemoveCompanyLogoClick() {
        companyLogoBase64 = '';
        $companyLogo.val('');
        $companyLogoPreview.attr('src', '#');
        $companyLogoPreview.prop('hidden', true);
        $removeCompanyLogo.prop('hidden', true);
    }

    /**
     * Toggle the reset company color button.
     */
    function onCompanyColorChange() {
        $resetCompanyColor.prop('hidden', $companyColor.val() === '#ffffff');
    }

    /**
     * Set the company color value to "#ffffff" which is the default one.
     */
    function onResetCompanyColorClick() {
        $companyColor.val('#ffffff');
    }

    /**
     * Initialize the module.
     */
    function initialize() {
        $saveSettings.on('click', onSaveSettingsClick);

        $('.module-setting').on('change', updateEnabledModulesField);
        updateEnabledModulesField();

        $companyLogo.on('change', onCompanyLogoChange);

        $removeCompanyLogo.on('click', onRemoveCompanyLogoClick);

        $companyColor.on('change', onCompanyColorChange);

        $resetCompanyColor.on('click', onResetCompanyColorClick);

        $('.resource-icon-pack-setting').on(
            'change',
            updateResourceIconPacksField
        );

        updateResourceIconPacksField();

        const generalSettings = vars('general_settings');

        deserialize(generalSettings);
    }

    document.addEventListener('DOMContentLoaded', initialize);

    return {};
})();
