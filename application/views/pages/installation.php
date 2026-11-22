<!doctype html>
<html lang="<?= config('language_code') ?>">
<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">

    <title>Installation | Modul Scheduler by Dein IT-Kompass</title>

    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/css/themes/default.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/css/general.css') ?>">
</head>
<body class="d-flex flex-column min-vh-100">

<div id="loading" class="position-fixed top-0 start-0 w-100 vh-100 d-flex justify-content-center align-items-center d-none bg-white">
    <img src="<?= base_url('assets/img/loading.gif') ?>" alt="loading">
</div>

<header class="bg-success-subtle mb-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-9 offset-lg-1">
                <h1 class="text-dark-emphasis fw-light py-5">
                    MODUL-Scheduler
                </h1>
            </div>
        </div>    
    </div>
</header>

<div class="container flex-grow-1">
    <div class="row">
        <div class="col-lg-9 offset-lg-1">

            <div>
                <h3>Einrichtung Ihrer Terminverwaltung</h3>

                <p class="text-break">
                    Richten Sie hier die grundlegenden Einstellungen Ihrer Terminverwaltung ein.
                    <strong class="text-primary">
                        <?= site_url('user/login') ?>
                    </strong> 
                    Alle Angaben können später im Administrationsbereich angepasst werden.
                 
                </p>
            </div>

            <div class="alert" hidden></div>

            <div class="row">
                <div class="admin-settings col-lg-6">
                    <h3 class="mb-3 fw-light">Administrator</h3>

                    <div class="mb-3">
                        <label class="form-label" for="first-name">
                            <?= lang('first_name') ?>
                            <span class="text-danger">*</span>
                        </label>
                        <input id="first-name" class="form-control required" maxlength="256">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="last-name">
                            <?= lang('last_name') ?>
                            <span class="text-danger">*</span>
                        </label>
                        <input id="last-name" class="form-control required" maxlength="512">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="email">
                            <?= lang('email') ?>
                            <span class="text-danger">*</span>
                        </label>
                        <input id="email" class="form-control required" maxlength="512">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="username">
                            <?= lang('username') ?>
                            <span class="text-danger">*</span>
                        </label>
                        <input id="username" class="form-control required" maxlength="256">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="password">
                            <?= lang('password') ?>
                            <span class="text-danger">*</span>
                        </label>
                        <input type="password" id="password" class="form-control required" maxlength="512">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="password-confirm">
                            <?= lang('retype_password') ?>
                            <span class="text-danger">*</span>
                        </label>
                        <input type="password" id="password-confirm" class="form-control required" maxlength="512">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="language">
                            <?= lang('language') ?>
                            <span class="text-danger">*</span>
                        </label>
                        <select id="language" class="form-select required">
                            <?php
                            $config_lang = config('language');
                            foreach (vars('available_languages') as $lang): ?>
                                <option value="<?= $lang ?>"<?= $lang == $config_lang ? ' selected' : '' ?>>
                                    <?= ucfirst($lang) ?>
                                </option>
                            <?php endforeach;
                            ?>
                        </select>
                    </div>

                </div>

                <div class="company-settings col-lg-6">
                    <h3 class="mb-3 fw-light">Kundenorganisation</h3>

                    <div class="mb-3">
                        <label class="form-label" for="company-name">
                            <?= lang('company_name') ?>
                            <span class="text-danger">*</span>
                        </label>
                        <input id="company-name" data-field="company_name" class="required form-control">
                        <div class="form-text text-muted">
                            <small>
                                <?= lang('company_name_hint') ?>
                            </small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="company-email">
                            <?= lang('company_email') ?>
                            <span class="text-danger">*</span>
                        </label>
                        <input id="company-email" data-field="company_email" class="required form-control">
                        <div class="form-text text-muted">
                            <small>
                                <?= lang('company_email_hint') ?>
                            </small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="company-link">
                            <?= lang('company_link') ?>
                            <span class="text-danger">*</span>
                        </label>
                        <input id="company-link" data-field="company_link" class="required form-control">
                        <div class="form-text text-muted">
                            <small>
                                <?= lang('company_link_hint') ?>
                            </small>
                        </div>
                    </div>

                </div>
            </div>

            <div class="card mb-5">
                <div class="card-header">
                    <strong>Branchenausstattung und Symbolpakete</strong>
                </div>

                <div class="card-body">
                    <p class="text-muted">
                        Die Symbole werden lokal mit MODUL-Scheduler ausgeliefert.
                        Weitere Pakete können später in den allgemeinen
                        Einstellungen aktiviert werden.
                    </p>

                    <div class="form-check mb-3">
                        <input type="checkbox"
                               class="form-check-input resource-icon-pack"
                               id="icon-pack-core"
                               value="core"
                               checked
                               disabled>

                        <label class="form-check-label" for="icon-pack-core">
                            <strong>Grundausstattung</strong><br>
                            <small class="text-muted">
                                Gebäude, Räume, Ausstattung und allgemeine Ressourcen.
                            </small>
                        </label>
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox"
                               class="form-check-input resource-icon-pack"
                               id="icon-pack-construction"
                               value="construction">

                        <label class="form-check-label" for="icon-pack-construction">
                            <strong>Bau, Handwerk und GaLaBau</strong><br>
                            <small class="text-muted">
                                Bagger, Baumaschinen, Anhänger, Transporter und LKW.
                            </small>
                        </label>
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox"
                               class="form-check-input resource-icon-pack"
                               id="icon-pack-cleaning"
                               value="cleaning">

                        <label class="form-check-label" for="icon-pack-cleaning">
                            <strong>Reinigung</strong><br>
                            <small class="text-muted">
                                Sauger, Reinigungswagen und Reinigungsmaschinen.
                            </small>
                        </label>
                    </div>

                    <div class="form-check">
                        <input type="checkbox"
                               class="form-check-input resource-icon-pack"
                               id="icon-pack-physiotherapy"
                               value="physiotherapy">

                        <label class="form-check-label" for="icon-pack-physiotherapy">
                            <strong>Physiotherapie und Gesundheit</strong><br>
                            <small class="text-muted">
                                Behandlungsliegen, Therapie- und Trainingsgeräte.
                            </small>
                        </label>
                    </div>
                </div>

                    <div class="form-check mt-3">
                        <input type="checkbox"
                               class="form-check-input resource-icon-pack"
                               id="icon-pack-dance"
                               value="dance">

                        <label class="form-check-label"
                               for="icon-pack-dance">
                            <strong>Tanz &amp; Tanzschule</strong><br>
                            <small class="text-muted">
                                Tanzarten, Paar-/Einzeltraining, Workshops
                                und Tanzräume.
                            </small>
                        </label>
                    </div>
            </div>

            <div class="card mb-5" id="module-product-selection">
                <div class="card-header"><strong>MODUL-Produkte</strong></div>
                <div class="card-body">
                    <div class="form-check mb-4">
                        <input class="form-check-input product-selection" type="radio"
                               name="product-selection" id="product-scheduler"
                               value="scheduler" checked>
                        <label class="form-check-label" for="product-scheduler">
                            <strong>MODUL Scheduler</strong><br>
                            <span class="text-muted">Termine, Kunden, Mitarbeiter, Teams, Leistungen und Kalender.</span>
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input product-selection" type="radio"
                               name="product-selection" id="product-erp" value="erp">
                        <label class="form-check-label" for="product-erp">
                            <strong>MODUL Scheduler + ERP</strong><br>
                            <span class="text-muted">Zusätzlich Projekte, Projektzeitplan, Einsätze, Ressourcenverwaltung und Disposition.</span>
                        </label>
                    </div>
                </div>
            </div>

            <p class="mb-5">
                You will be able to set your business logic in the backend settings page after the installation is complete.
                <br>
                Press the following button to complete the installation process.
            </p>


            <div class="mb-3">
                <h3>License</h3>
                Scheduler basiert auf Easy!Appointments und ist lizensiert unter <span class="badge text-bg-primary">GPL-3.0 license</span>. By using the
                code of Easy!Appointments in any way <br> you agree with the terms described in the following url:
                <a href="https://www.gnu.org/licenses/gpl-3.0.en.html">https://www.gnu.org/licenses/gpl-3.0.en.html</a>
            </div>

            <br>

            <button type="button" id="install" class="btn btn-primary mb-3">
                <i class="icon-white icon-ok me-2"></i>
                Installiere Modul Scheduler
            </button>
            
            
        </div>
    </div>

    
</div>

<footer class="bg-light mt-auto">
    <div class="container">
        <div class="row">
            <div class="col-lg-9 offset-lg-1 py-3">
                Powered by <a href="https://Deinitkompass.de">Dein IT-Kompass</a>        
            </div>
        </div>
    </div>
    
</footer>

<?php component('js_vars_script'); ?>
<?php component('js_lang_script'); ?>

<script src="<?= asset_url('assets/vendor/jquery/jquery.min.js') ?>"></script>
<script src="<?= asset_url('assets/vendor/@popperjs-core/popper.min.js') ?>"></script>
<script src="<?= asset_url('assets/vendor/bootstrap/bootstrap.min.js') ?>"></script>

<script src="<?= asset_url('assets/js/app.js') ?>"></script>
<script src="<?= asset_url('assets/js/utils/message.js') ?>"></script>
<script src="<?= asset_url('assets/js/utils/validation.js') ?>"></script>
<script src="<?= asset_url('assets/js/utils/url.js') ?>"></script>
<script src="<?= asset_url('assets/js/pages/installation.js') ?>"></script>

</body>
</html>
