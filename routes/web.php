<?php

use App\Http\Controllers\SearchConsoleController;
use App\Livewire\Crawls\Index as CrawlsIndex;
use App\Livewire\Dashboard;
use App\Livewire\Issues\Index as IssuesIndex;
use App\Livewire\Keywords\Index as KeywordsIndex;
use App\Livewire\Pages\Index as PagesIndex;
use App\Livewire\Projects\Create as ProjectCreate;
use App\Livewire\SearchConsole\Index as SearchConsoleIndex;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::middleware(['auth', 'verified', 'team'])->group(function () {
    Route::get('dashboard', Dashboard::class)->name('dashboard');

    Route::get('projeler/yeni', ProjectCreate::class)->name('projects.create');

    Route::get('projeler/{project}/gecis', function (Project $project, Request $request) {
        abort_unless($project->team_id === $request->user()->current_team_id, 403);

        session(['current_project_id' => $project->id]);

        return redirect()->route('dashboard');
    })->name('projects.switch');

    Route::get('hatalar', IssuesIndex::class)->name('issues');
    Route::get('anahtar-kelimeler', KeywordsIndex::class)->name('keywords');

    Route::get('sayfalar', PagesIndex::class)->name('pages');
    Route::get('tarama-gecmisi', CrawlsIndex::class)->name('crawls');

    Route::get('search-console', SearchConsoleIndex::class)->name('search-console');

    Route::get('entegrasyonlar/search-console/{project}/baglan', [SearchConsoleController::class, 'connect'])
        ->name('search-console.connect');
    Route::get('entegrasyonlar/search-console/callback', [SearchConsoleController::class, 'callback'])
        ->name('search-console.callback');

    Route::view('duzeltmeler', 'placeholder', [
        'title' => 'Düzeltmeler',
        'description' => 'AI düzeltme akışı Sprint 4 ile birlikte gelir.',
    ])->name('fixes');

    Route::view('entegrasyonlar', 'placeholder', [
        'title' => 'Entegrasyonlar',
        'description' => 'WordPress ve Search Console bağlantıları Sprint 3-4 ile birlikte gelir.',
    ])->name('integrations');

    Route::view('proje-ayarlari', 'placeholder', [
        'title' => 'Proje Ayarları',
        'description' => 'Proje düzenleme ekranı Sprint 1 ile birlikte gelir.',
    ])->name('project-settings');
});

Route::view('profile', 'profile')->middleware(['auth'])->name('profile');

Route::post('logout', function (Request $request) {
    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
})->middleware('auth')->name('logout');

require __DIR__.'/auth.php';
