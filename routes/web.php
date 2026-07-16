<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\BracketController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PrelimsController;
use App\Http\Controllers\PublicEventController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\RegistrationFieldController;
use App\Http\Controllers\RegistrationReviewController;
use App\Http\Controllers\SuperAdminDashboardController;
use App\Http\Controllers\UserRegistrationController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/public/events', [PublicEventController::class, 'index'])
    ->name('events.public.index');

Route::get('/public/events/{event}', [PublicEventController::class, 'show'])
    ->name('events.public.show');

Route::get('/public/events/{event}/register', [RegistrationController::class, 'create'])
    ->name('events.public.register');

Route::post('/public/events/{event}/register', [RegistrationController::class, 'store']);

Route::middleware('auth')->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    Route::get('/superadmin/dashboard', [SuperAdminDashboardController::class, 'index'])
        ->name('superadmin.dashboard');

    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])
        ->name('dashboard.admin');

    Route::resource('companies', CompanyController::class)->except(['show']);

    Route::get('companies/{company}', [CompanyController::class, 'show'])
        ->name('companies.show');

    Route::patch('companies/{company}/suspend', [CompanyController::class, 'suspend'])
        ->name('companies.suspend');

    Route::patch('companies/{company}/activate', [CompanyController::class, 'activate'])
        ->name('companies.activate');

    Route::patch('companies/{company}/approve', [CompanyController::class, 'approve'])
        ->name('companies.approve');

    Route::patch('companies/{company}/reject', [CompanyController::class, 'reject'])
        ->name('companies.reject');

    Route::resource('events', EventController::class);

    Route::prefix('events/{event}/categories')->name('events.categories.')->group(function () {
        Route::post('/', [\App\Http\Controllers\EventCategoryController::class, 'store'])->name('store');
        Route::put('/{category}', [\App\Http\Controllers\EventCategoryController::class, 'update'])->name('update');
        Route::delete('/{category}', [\App\Http\Controllers\EventCategoryController::class, 'destroy'])->name('destroy');

        Route::prefix('{category}/prelims')->name('prelims.')->group(function () {
            Route::get('/', [PrelimsController::class, 'show'])->name('show');
            Route::post('/start', [PrelimsController::class, 'start'])->name('start');
            Route::patch('/order', [PrelimsController::class, 'reorder'])->name('reorder');
            Route::post('/next', [PrelimsController::class, 'next'])->name('next');
            Route::post('/jump', [PrelimsController::class, 'jump'])->name('jump');
            Route::post('/complete', [PrelimsController::class, 'complete'])->name('complete');
        });
    });

    Route::prefix('events/{event}/fields')->name('events.fields.')->group(function () {
        Route::post('/', [RegistrationFieldController::class, 'store'])->name('store');
        Route::delete('/{field}', [RegistrationFieldController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('events/{event}/registrations')->name('events.registrations.')->group(function () {
        Route::get('/', [RegistrationReviewController::class, 'index'])->name('index');
        Route::patch('/{registration}', [RegistrationReviewController::class, 'update'])->name('update');
    });

    Route::prefix('events/{event}/bracket')->name('events.bracket.')->group(function () {
        Route::get('/', [BracketController::class, 'show'])->name('show');
        Route::post('/', [BracketController::class, 'store'])->name('store');
        Route::post('/matches/{match}', [BracketController::class, 'updateMatch'])->name('update-match');
        Route::delete('/{battle}', [BracketController::class, 'destroy'])->name('destroy');
    });

    Route::get('/my-registrations', [UserRegistrationController::class, 'index'])->name('registrations.index');
});