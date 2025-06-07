<?php

use App\Livewire\Email;
use Illuminate\Support\Facades\Route;

// Route::view('/', 'welcome')->name('welcome');

// Route::middleware(['auth'])->group(function () {
Route::view('/', 'dashboard')->name('dashboard');

Route::get('/email/compare', Email\Compare::class)->name('email.compare');

Route::get('/email/clean', Email\Clean::class)->name('email.clean');

// Route::get('/users', Index::class)->name('users.index');

// Route::get('/user/profile', Profile::class)->name('user.profile');
// });

// require __DIR__ . '/auth.php';
