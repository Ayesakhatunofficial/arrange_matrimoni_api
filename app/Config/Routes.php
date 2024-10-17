<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS");
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Credentials: true');
    header("Access-Control-Allow-Headers: *");
    die;
}

header("Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: *");



$routes->group('auth', ['namespace' => '\App\Controllers'], static function ($routes) {
    $routes->post('register', 'Auth::register');
    $routes->post('login', 'Auth::login');
    $routes->post('verify-otp', 'Auth::otpVerify');
    $routes->post('send-otp', 'Auth::sendOtp');
    $routes->post('send-register-otp', 'Auth::sendRegisterOtp');
});

$routes->group('master', ['namespace' => '\App\Controllers'], static function ($routes) {
    $routes->get('caste', 'Master::caste');
    $routes->get('gothram', 'Master::gothram');
    $routes->get('gon', 'Master::gon');
    $routes->get('religion', 'Master::religion');
    $routes->get('dashboard_banners', 'Master::dashboardBanners');
    $routes->get('dashboard-video', 'Master::dashboardVideo');
    $routes->get('contact-details', 'Master::contactDetails');
});

$routes->group('user', ['namespace' => '\App\Controllers', 'filter' => 'authFilter'], static function ($routes) {
    $routes->get('profile', 'User::profile');
    $routes->get('recent-profile', 'User::recentProfile');
    $routes->get('profile-suggestion', 'User::profileSuggestion');
    $routes->post('profile-details', 'User::profileDetails');
    $routes->get('membership-plan', 'User::membershipPlan');
    $routes->get('latest-stories', 'User::latestStories');
    $routes->get('success-stories', 'User::successStories');
    $routes->post('like', 'User::like');
    $routes->get('you-like-someone', 'User::youLikeSomeone');
    $routes->get('someone-like-you', 'User::someoneLikeYou');
    $routes->post('shortlist', 'User::shortList');
    $routes->get('shortlist-list', 'User::shortlistList');
    $routes->post('search', 'User::search');
    $routes->get('notifications', 'User::notifications');
    $routes->get('notification/date', 'User::notificationDate');
    $routes->get('notification/seen', 'User::notificationSeen');
    $routes->get('notification/count', 'User::notificationCount');
    $routes->post('contact-us', 'User::contactUs');
    $routes->get('profile-statement', 'User::profileStatement');
    $routes->get('office-tag', 'User::officeTag');
    $routes->post('profile-deactivate', 'User::profileDeactivate');
});

$routes->group('payment', ['namespace' => '\App\Controllers', 'filter' => 'authFilter'], static function ($routes) {
    $routes->post('create-order', 'Payment::initiatePayment');
    $routes->post('check-order-status', 'Payment::checkStatus');
});

$routes->match(['get', 'post'], 'payment/callback-webhook', 'Payment::webhook');

$routes->match(['get', 'post'], 'payment/redirect', 'Payment::handleRedirect');






// $routes->get('/', 'Home::index');
