<?php defined('BASEPATH') or exit('No direct script access allowed');

$config['modules'] = [
    'scheduler' => [
        'name' => 'MODUL Scheduler',
        'required' => true,
        'description' => 'Termin-, Kunden-, Personal- und Kalenderverwaltung.',
    ],
    'erp' => [
        'name' => 'MODUL ERP',
        'required' => false,
        'requires' => ['scheduler'],
        'description' => 'Projekt-, Einsatz- und Ressourcenplanung.',
    ],
];
