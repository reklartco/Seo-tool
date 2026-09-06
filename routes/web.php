<?php

use App\Http\Controllers\PluginDownloadController;
use App\Http\Controllers\SearchConsoleController;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Crawls\Index as CrawlsIndex;
use App\Livewire\Dashboard;
use App\Livewire\Fixes\Index as FixesIndex;
use App\Livewire\Integrations\Index as IntegrationsIndex;
use App\Livewire\Issues\Index as IssuesIndex;
use App\Livewire\Keywords\Index as KeywordsIndex;
use App\Livewire\Pages\Index as PagesIndex;
use App\Livewire\Projects\Create as ProjectCreate;
use App\Livewire\Projects\Settings as ProjectSettings;
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

    Route::get('duzeltmeler', FixesIndex::class)->name('fixes');
    Route::get('entegrasyonlar', IntegrationsIndex::class)->name('integrations');
    Route::get('entegrasyonlar/eklenti', PluginDownloadController::class)->name('integrations.plugin');
    Route::get('proje-ayarlari', ProjectSettings::class)->name('project-settings');

    Route::get('admin', AdminDashboard::class)->middleware('super-admin')->name('admin');
});

Route::view('profile', 'profile')->middleware(['auth'])->name('profile');

Route::post('logout', function (Request $request) {
    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
})->middleware('auth')->name('logout');

require __DIR__.'/auth.php';
