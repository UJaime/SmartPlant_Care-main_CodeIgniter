<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'SmartPlant::index');
$routes->get('index.php', 'SmartPlant::index');
$routes->get('login', 'SmartPlant::login');
$routes->post('login', 'SmartPlant::login');
$routes->get('register', 'SmartPlant::register');
$routes->post('register', 'SmartPlant::register');
$routes->get('reset-password', 'SmartPlant::resetPassword');
$routes->post('reset-password', 'SmartPlant::resetPassword');
$routes->get('dashboard', 'SmartPlant::dashboard');
$routes->post('dashboard', 'SmartPlant::dashboard');
$routes->get('devices/new', 'SmartPlant::deviceCreate');
$routes->post('devices/new', 'SmartPlant::deviceCreate');
$routes->get('hardware/connect', 'SmartPlant::hardwareConnect');
$routes->get('hardware/api', 'SmartPlant::hardwareApi');
$routes->post('hardware/api', 'SmartPlant::hardwareApi');
$routes->get('store', 'SmartPlant::store');
$routes->get('support', 'SmartPlant::support');
$routes->post('support', 'SmartPlant::support');
$routes->post('ai-assistant', 'SmartPlant::aiAssistant');
$routes->post('ai_assistant', 'SmartPlant::aiAssistant');
$routes->get('inventory', 'SmartPlant::inventory');
$routes->post('inventory', 'SmartPlant::inventory');
$routes->post('purchase', 'SmartPlant::purchase');
$routes->get('plantas', 'Plantas::index');

