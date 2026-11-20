<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div id="about-page" class="container backend-page py-3">
    <div id="about" class="col-lg-8 offset-lg-2">

        <div class="text-center my-5">
            <img src="<?= base_url('assets/img/logo.png') ?>"
                 alt="MODUL-Scheduler Logo"
                 class="mb-4">

            <h3>MODUL-Scheduler</h3>

            <h6 class="text-primary">
                Terminverwaltung
            </h6>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="fw-light mb-0">
                    Über MODUL-Scheduler
                </h5>
            </div>

            <div class="card-body">
                <p>
                    MODUL-Scheduler ist eine flexible Termin- und
                    Organisationsplattform für unterschiedliche Einsatzbereiche
                    und Branchen.
                </p>

                <p class="mb-0">
                    Entwicklung und Integration:
                    <strong>Dein IT-Kompass</strong>
                </p>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="fw-light mb-0">
                    Version
                </h5>
            </div>

            <div class="card-body">
                <strong><?= config('version') ?></strong>
            </div>
        </div>

        <div class="card mb-5">
            <div class="card-header">
                <h5 class="fw-light mb-0">
                    Open-Source-Basis
                </h5>
            </div>

            <div class="card-body">
                <p>
                    MODUL-Scheduler basiert auf dem Open-Source-Projekt
                    <strong>Easy!Appointments</strong>.
                </p>

                <p>
                    Die Software wird unter den Bedingungen der
                    GNU General Public License Version 3 (GPL-3.0)
                    bereitgestellt.
                </p>

                <a class="btn btn-outline-secondary"
                   href="https://www.gnu.org/licenses/gpl-3.0.en.html"
                   target="_blank">
                    <i class="fas fa-external-link-alt me-2"></i>
                    GPL-3.0 Lizenz
                </a>
            </div>
        </div>

    </div>
</div>

<?php end_section('content'); ?>

<?php component('backend_footer', [
    'user_display_name' => vars('user_display_name'),
]); ?>

<?php end_extend(); ?>
