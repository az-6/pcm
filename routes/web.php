<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PrivateDocumentController;

Route::redirect('/', '/dashboard')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');
    Route::livewire('majelis', 'pages::majelis.index')->name('majelis.index');
    Route::livewire('programs', 'pages::programs.index')->name('programs.index');
    Route::livewire('programs/{workProgram}', 'pages::programs.show')->name('programs.show');
    Route::livewire('submissions', 'pages::submissions.index')->name('submissions.index');
    Route::livewire('reports', 'pages::reports.index')->name('reports.index');
    Route::livewire('users', 'pages::users.index')->name('users.index');
    Route::get('documents/submission-items/{fundSubmissionItem}', [PrivateDocumentController::class, 'submission'])->name('documents.submission-items');
    Route::get('documents/report-expenses/{reportExpense}', [PrivateDocumentController::class, 'expense'])->name('documents.report-expenses');
});

require __DIR__.'/settings.php';
