<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// ─── Public routes (no authentication required) ──────────────────────────────
$routes->get('/',            'Public\HomeController::index');
$routes->get('agenda',       'Public\AgendaController::index');
$routes->get('predios',      'Public\BuildingsController::index');
$routes->get('ambientes',        'Public\RoomsController::index');
$routes->get('ambientes/(:num)', 'Public\RoomsController::show/$1');
$routes->get('equipamentos', 'Public\EquipmentController::index');

// ─── Authentication routes ────────────────────────────────────────────────────
$routes->get( 'login',                       'Auth\LoginController::index');
$routes->post('login',                       'Auth\LoginController::attempt');
$routes->get( 'logout',                      'Auth\LoginController::logout');
$routes->get( 'esqueci-senha',               'Auth\LoginController::forgotPassword');
$routes->post('esqueci-senha',               'Auth\LoginController::sendResetLink');
$routes->get( 'redefinir-senha/(:segment)',  'Auth\LoginController::resetPassword/$1');
$routes->post('redefinir-senha/(:segment)', 'Auth\LoginController::updatePassword/$1');

// Google OAuth2
$routes->get('auth/google',          'Auth\GoogleController::redirect');
$routes->get('auth/google/callback', 'Auth\GoogleController::callback');

// ─── Authenticated routes (require login) ────────────────────────────────────
$routes->group('', ['filter' => 'auth'], static function ($routes) {

    $routes->get('dashboard', 'DashboardController::index');

    // ── Reservas ──────────────────────────────────────────────────────────────
    $routes->get( 'reservas',                           'BookingsController::index');
    $routes->get( 'reservas/nova',                      'BookingsController::create');
    $routes->post('reservas',                           'BookingsController::store');
    $routes->get( 'reservas/disponibilidade',           'BookingsController::availability');
    $routes->get( 'reservas/salas-disponiveis',         'BookingsController::availableRooms');
    $routes->get( 'reservas/(:num)',                    'BookingsController::show/$1');
    $routes->post('reservas/(:num)/cancelar',           'BookingsController::cancel/$1');

    // Approval (staff only — not_requester filter)
    $routes->get( 'reservas/pendentes',                 'BookingsController::pending',    ['filter' => 'not_requester']);
    // Batch approval (must be before :num routes)
    $routes->post('reservas/lote/aprovar',              'BookingsController::batchApprove', ['filter' => 'not_requester']);
    $routes->post('reservas/lote/recusar',              'BookingsController::batchReject',  ['filter' => 'not_requester']);
    $routes->post('reservas/(:num)/aprovar',            'BookingsController::approve/$1', ['filter' => 'not_requester']);
    $routes->post('reservas/(:num)/recusar',            'BookingsController::reject/$1',  ['filter' => 'not_requester']);
    $routes->post('reservas/(:num)/ausente',            'BookingsController::markAbsent/$1', ['filter' => 'not_requester']);

    // Authenticated agenda
    $routes->get('reservas/agenda', 'BookingsController::agenda');

    // ── Admin ─────────────────────────────────────────────────────────────────
    $routes->group('admin', ['filter' => 'not_requester'], static function ($routes) {

        // Buildings
        $routes->get( 'predios',                       'Admin\BuildingsController::index');
        $routes->post('predios',                       'Admin\BuildingsController::store');
        $routes->post('predios/(:num)/update',         'Admin\BuildingsController::update/$1');
        $routes->post('predios/(:num)/delete',         'Admin\BuildingsController::delete/$1');

        // Rooms / Ambientes
        $routes->get( 'ambientes',                     'Admin\RoomsController::index');
        $routes->post('ambientes',                     'Admin\RoomsController::store');
        $routes->post('ambientes/(:num)/update',       'Admin\RoomsController::update/$1');
        $routes->post('ambientes/(:num)/delete',       'Admin\RoomsController::delete/$1');

        // Equipment / Equipamentos
        $routes->get( 'equipamentos',                  'Admin\EquipmentController::index');
        $routes->post('equipamentos',                  'Admin\EquipmentController::store');
        $routes->post('equipamentos/(:num)/update',    'Admin\EquipmentController::update/$1');
        $routes->post('equipamentos/(:num)/delete',    'Admin\EquipmentController::delete/$1');

        // Users
        $routes->get( 'usuarios',                      'Admin\UsersController::index');
        $routes->post('usuarios/(:num)/role',          'Admin\UsersController::updateRole/$1');
        $routes->post('usuarios/(:num)/toggle-active', 'Admin\UsersController::toggleActive/$1');

        // Holidays
        $routes->get( 'feriados',                      'Admin\HolidaysController::index');
        $routes->post('feriados',                      'Admin\HolidaysController::store');
        $routes->post('feriados/(:num)/update',        'Admin\HolidaysController::update/$1');
        $routes->post('feriados/(:num)/delete',        'Admin\HolidaysController::delete/$1');

        // Reports
        $routes->get('relatorios',             'Admin\ReportsController::index');
        $routes->get('relatorios/exportar-csv','Admin\ReportsController::exportCsv');
        $routes->get('relatorios/exportar-pdf','Admin\ReportsController::exportPdf');

        // Audit
        $routes->get('auditoria', 'Admin\AuditController::index');

        // Settings
        $routes->get( 'configuracoes', 'Admin\SettingsController::index');
        $routes->post('configuracoes', 'Admin\SettingsController::update');

        // Operating hours
        $routes->get( 'horarios', 'Admin\OperatingHoursController::index');
        $routes->post('horarios', 'Admin\OperatingHoursController::update');
    });

    // ── Profile ───────────────────────────────────────────────────────────────
    $routes->get( 'perfil',       'ProfileController::index');
    $routes->post('perfil/info',  'ProfileController::updateInfo');
    $routes->post('perfil/senha', 'ProfileController::updatePassword');
});

// ── Public API ────────────────────────────────────────────────────────────────
$routes->get('api/agenda/events',  'Api\AgendaController::events');
$routes->get('api/agenda/filters', 'Api\AgendaController::filters');

// ── Authenticated API ─────────────────────────────────────────────────────────
$routes->get('api/reservas/agenda-events', 'Api\AgendaController::userEvents', ['filter' => 'auth']);
