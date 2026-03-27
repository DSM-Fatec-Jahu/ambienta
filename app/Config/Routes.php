<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// ─── Public routes (no authentication required) ──────────────────────────────
$routes->get('/',            'Public\HomeController::index');
$routes->get('agenda',       'Public\AgendaController::index');
$routes->get('predios',      'Public\BuildingsController::index');
$routes->get('ambientes',        'Public\RoomsController::index');
$routes->get('ambientes/(:num)', 'Public\RoomsController::show/$1');
$routes->get('recursos', 'Public\ResourceController::index');
// Legacy redirect: keep old URL working
$routes->get('equipamentos', 'Public\ResourceController::index');

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

// User invite acceptance (public — no auth required)
$routes->get( 'convite/(:segment)', 'Auth\LoginController::acceptInvite/$1');
$routes->post('convite/(:segment)', 'Auth\LoginController::processInvite/$1');

// QR Code Check-in (public — token is the credential)
$routes->get('checkin/(:segment)', 'BookingsController::qrCheckin/$1');

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

    // Edit pending booking
    $routes->get( 'reservas/(:num)/editar',             'BookingsController::edit/$1');
    $routes->post('reservas/(:num)/editar',             'BookingsController::update/$1');

    // Rating, iCal, Check-in & Series (must be before :num routes)
    $routes->get( 'reservas/calendario.ics',            'BookingsController::exportIcal');
    $routes->post('reservas/(:num)/avaliar',            'BookingsController::rate/$1');
    $routes->post('reservas/(:num)/checkin',            'BookingsController::checkIn/$1');
    $routes->post('reservas/(:num)/cancelar-serie',     'BookingsController::cancelSeries/$1');
    $routes->post('reservas/(:num)/comentario',         'BookingsController::addComment/$1');

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
        $routes->get( 'ambientes',                                               'Admin\RoomsController::index');
        $routes->post('ambientes',                                               'Admin\RoomsController::store');
        $routes->post('ambientes/(:num)/update',                                 'Admin\RoomsController::update/$1');
        $routes->post('ambientes/(:num)/delete',                                 'Admin\RoomsController::delete/$1');
        $routes->post('ambientes/(:num)/manutencao',                             'Admin\RoomsController::setMaintenance/$1');
        // Room resources — Sprint R2: now handled by RoomResourceController
        $routes->get( 'ambientes/(:num)/recursos',                                'Admin\RoomResourceController::index/$1');
        $routes->post('ambientes/(:num)/recursos',                                'Admin\RoomResourceController::store/$1');
        $routes->post('ambientes/(:num)/recursos/(:num)/delete',                  'Admin\RoomResourceController::destroy/$1/$2');
        // Legacy aliases (mantém compatibilidade com links/scripts existentes)
        $routes->get( 'ambientes/(:num)/equipamentos',                            'Admin\RoomEquipmentController::index/$1');
        $routes->post('ambientes/(:num)/equipamentos',                            'Admin\RoomEquipmentController::store/$1');
        $routes->post('ambientes/(:num)/equipamentos/(:num)/delete',              'Admin\RoomEquipmentController::destroy/$1/$2');

        // Resources / Recursos
        $routes->get( 'recursos',                            'Admin\ResourceController::index');
        $routes->post('recursos',                            'Admin\ResourceController::store');
        $routes->get( 'recursos/template-xlsx',              'Admin\ResourceController::templateXlsx');
        $routes->post('recursos/importar',                   'Admin\ResourceController::importFile');
        $routes->post('recursos/(:num)/update',              'Admin\ResourceController::update/$1');
        $routes->post('recursos/(:num)/delete',              'Admin\ResourceController::destroy/$1');
        $routes->get( 'recursos/(:num)/historico',           'Admin\ResourceController::history/$1');
        // Legacy equipment routes — redirect to ResourceController
        $routes->get( 'equipamentos',                        'Admin\ResourceController::index');
        $routes->post('equipamentos',                        'Admin\ResourceController::store');
        $routes->get( 'equipamentos/template-csv',           'Admin\ResourceController::templateXlsx');
        $routes->get( 'equipamentos/template-xlsx',          'Admin\ResourceController::templateXlsx');
        $routes->post('equipamentos/importar',               'Admin\ResourceController::importFile');
        $routes->post('equipamentos/(:num)/update',          'Admin\ResourceController::update/$1');
        $routes->post('equipamentos/(:num)/delete',          'Admin\ResourceController::destroy/$1');
        $routes->get( 'equipamentos/(:num)/historico',       'Admin\ResourceController::history/$1');

        // Users
        $routes->get( 'usuarios',                                  'Admin\UsersController::index');
        $routes->post('usuarios/convidar',                         'Admin\UsersController::invite');
        $routes->post('usuarios/convites/(:num)/revogar',          'Admin\UsersController::revokeInvite/$1');
        $routes->post('usuarios/(:num)/role',                      'Admin\UsersController::updateRole/$1');
        $routes->post('usuarios/(:num)/toggle-active',             'Admin\UsersController::toggleActive/$1');

        // Holidays
        $routes->get( 'feriados',                          'Admin\HolidaysController::index');
        $routes->post('feriados',                          'Admin\HolidaysController::store');
        $routes->post('feriados/(:num)/update',            'Admin\HolidaysController::update/$1');
        $routes->post('feriados/(:num)/delete',            'Admin\HolidaysController::delete/$1');
        $routes->post('feriados/importar-api/(:num)',      'Admin\HolidaysController::importFromApi/$1');

        // Reports
        $routes->get('relatorios',                         'Admin\ReportsController::index');
        $routes->get('relatorios/exportar-csv',            'Admin\ReportsController::exportCsv');
        $routes->get('relatorios/exportar-pdf',            'Admin\ReportsController::exportPdf');
        $routes->get('relatorios/ocupacao',                'Admin\ReportsController::occupancy');
        $routes->get('relatorios/ocupacao/exportar-csv',   'Admin\ReportsController::exportOccupancyCsv');
        $routes->get('relatorios/recursos',                  'Admin\ReportsController::equipment');
        $routes->get('relatorios/recursos/exportar-csv',     'Admin\ReportsController::exportEquipmentCsv');
        // Legacy aliases — mantém URLs anteriores funcionando
        $routes->get('relatorios/equipamentos',              'Admin\ReportsController::equipment');
        $routes->get('relatorios/equipamentos/exportar-csv', 'Admin\ReportsController::exportEquipmentCsv');
        $routes->get('relatorios/usuarios',                  'Admin\ReportsController::userActivity');
        $routes->get('relatorios/usuarios/exportar-csv',     'Admin\ReportsController::exportUserActivityCsv');

        // Availability grid
        $routes->get('disponibilidade', 'Admin\AvailabilityController::index');

        // Audit
        $routes->get('auditoria',                    'Admin\AuditController::index');
        $routes->get('auditoria/exportar-csv',       'Admin\AuditController::exportCsv');

        // Settings
        $routes->get( 'configuracoes', 'Admin\SettingsController::index');
        $routes->post('configuracoes', 'Admin\SettingsController::update');

        // Operating hours
        $routes->get( 'horarios', 'Admin\OperatingHoursController::index');
        $routes->post('horarios', 'Admin\OperatingHoursController::update');

        // Booking Resources — Sprint R4 (RN-R04): approval panel
        // Sprint R5 (RN-R05/RN-R07): return confirmation panel
        $routes->get( 'recursos-reservas',                              'Admin\BookingResourceController::index');
        $routes->post('recursos-reservas/(:num)/aprovar',               'Admin\BookingResourceController::approve/$1');
        $routes->post('recursos-reservas/(:num)/recusar',               'Admin\BookingResourceController::reject/$1');
        $routes->post('recursos-reservas/(:num)/confirmar-devolucao',   'Admin\BookingResourceController::confirmReturn/$1');
        $routes->post('recursos-reservas/(:num)/rejeitar-devolucao',    'Admin\BookingResourceController::rejectReturn/$1');

        // Blackouts
        $routes->get( 'bloqueios',                     'Admin\BlackoutsController::index');
        $routes->post('bloqueios',                     'Admin\BlackoutsController::store');
        $routes->post('bloqueios/(:num)/delete',       'Admin\BlackoutsController::delete/$1');
    });

    // ── Booking Resource return — RN-R05: accessible to requester AND staff ──
    $routes->post('reservas/recursos/(:num)/devolver', 'Admin\BookingResourceController::returnResource/$1');

    // ── Waitlist (must be before :num routes) ─────────────────────────────────
    $routes->get( 'reservas/lista-espera',              'BookingsController::myWaitlist');
    $routes->post('reservas/lista-espera',              'BookingsController::joinWaitlist');
    $routes->post('reservas/lista-espera/(:num)/sair',  'BookingsController::leaveWaitlist/$1');

    // ── Notifications page ────────────────────────────────────────────────────
    $routes->get('notificacoes', 'NotificationsController::index');

    // ── Profile ───────────────────────────────────────────────────────────────
    $routes->get( 'perfil',               'ProfileController::index');
    $routes->post('perfil/info',          'ProfileController::updateInfo');
    $routes->post('perfil/senha',         'ProfileController::updatePassword');
    $routes->post('perfil/avatar',        'ProfileController::uploadAvatar');
    $routes->post('perfil/avatar/remover','ProfileController::deleteAvatar');
});

// ── Public API ────────────────────────────────────────────────────────────────
$routes->get('api/agenda/events',  'Api\AgendaController::events');
$routes->get('api/agenda/filters', 'Api\AgendaController::filters');

// ── Authenticated API ─────────────────────────────────────────────────────────
$routes->get('api/reservas/agenda-events', 'Api\AgendaController::userEvents', ['filter' => 'auth']);

// ── Notifications API (authenticated) ─────────────────────────────────────────
$routes->get( 'api/notificacoes',              'Api\NotificationsController::index',      ['filter' => 'auth']);
$routes->post('api/notificacoes/todas-lidas',  'Api\NotificationsController::markAllRead', ['filter' => 'auth']);
$routes->post('api/notificacoes/(:num)/lida',  'Api\NotificationsController::markRead/$1', ['filter' => 'auth']);
