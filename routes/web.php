<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContributionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GreetingMediaController;
use App\Http\Controllers\GreetingQrController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentProofController;
use App\Http\Controllers\PreviewController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecipientController;
use App\Http\Controllers\RecipientReactionController;
use App\Http\Controllers\RecipientReplyController;
use App\Http\Controllers\RecipientVideoController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\StudioContributionController;
use App\Http\Controllers\StudioController;
use App\Http\Controllers\StudioStyleController;
use App\Http\Controllers\TemplateDemoController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Publik: Beranda & Katalog
|--------------------------------------------------------------------------
*/

Route::get('/', HomeController::class)->name('home');
Route::get('kebijakan-privasi', [LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('syarat-ketentuan', [LegalController::class, 'terms'])->name('legal.terms');
Route::get('katalog', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('katalog/{template}', [CatalogController::class, 'show'])->name('catalog.show');
Route::get('katalog/{template}/demo', TemplateDemoController::class)->name('catalog.demo');

/*
|--------------------------------------------------------------------------
| Studio (tanpa login, draf anonim via cookie)
|--------------------------------------------------------------------------
*/

Route::post('katalog/{template}/buat', [StudioController::class, 'store'])
    ->middleware('throttle:20,1')
    ->name('studio.store');

Route::prefix('studio/{greeting}')->name('studio.')->group(function () {
    Route::get('/', [StudioController::class, 'edit'])->name('edit');
    Route::put('/', [StudioController::class, 'update'])->name('update');
    Route::get('gaya', [StudioStyleController::class, 'show'])->name('style');
    Route::put('gaya', [StudioStyleController::class, 'update'])->name('style.update');
    Route::post('media', [GreetingMediaController::class, 'store'])->middleware('throttle:30,1')->name('media.store');
    Route::delete('media/{media}', [GreetingMediaController::class, 'destroy'])->name('media.destroy');
    Route::post('pratinjau', [PreviewController::class, 'store'])->name('preview');
    Route::post('patungan', [StudioContributionController::class, 'enable'])->name('contributions.enable');
    Route::delete('patungan', [StudioContributionController::class, 'disable'])->name('contributions.disable');
    Route::patch('patungan/{contribution}', [StudioContributionController::class, 'toggle'])->name('contributions.toggle');
});

Route::get('pratinjau/{greeting}', [PreviewController::class, 'show'])->name('preview.show');

Route::get('patungan/{token}', [ContributionController::class, 'show'])->name('contributions.show');
Route::post('patungan/{token}', [ContributionController::class, 'store'])->middleware('throttle:10,1')->name('contributions.store');

/*
|--------------------------------------------------------------------------
| Akun: Checkout, Pembayaran, Hasil, Dashboard
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('checkout/{greeting}', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('checkout/{greeting}', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::post('checkout/{greeting}/promo', [CheckoutController::class, 'applyPromo'])->middleware('throttle:15,1')->name('checkout.promo.apply');
    Route::delete('checkout/{greeting}/promo', [CheckoutController::class, 'removePromo'])->name('checkout.promo.remove');

    Route::get('pesanan/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('pesanan/{order}/bukti', [PaymentProofController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('orders.proof.store');

    Route::get('hasil/{greeting}', [ResultController::class, 'show'])->name('result.show');
    Route::get('hasil/{greeting}/qr.png', GreetingQrController::class)->name('result.qr');

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::delete('dashboard/ucapan/{greeting}', [DashboardController::class, 'destroy'])->name('dashboard.greetings.destroy');
    Route::post('dashboard/ucapan/{greeting}/duplikat', [DashboardController::class, 'duplicate'])->name('dashboard.greetings.duplicate');
    Route::post('dashboard/notifikasi/dibaca', [NotificationController::class, 'markAllRead'])->name('notifications.read');
    Route::post('dashboard/pengingat', [ReminderController::class, 'store'])->name('reminders.store');
    Route::delete('dashboard/pengingat/{reminder}', [ReminderController::class, 'destroy'])->name('reminders.destroy');
    Route::delete('dashboard/video-reaksi/{reaction}', [RecipientVideoController::class, 'destroy'])->name('reaction-videos.destroy');

    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Penerima (tanpa akun, selamanya)
|--------------------------------------------------------------------------
*/

Route::prefix('u/{slug}')->name('recipient.')->group(function () {
    Route::get('/', [RecipientController::class, 'show'])->name('show');
    Route::post('buka', [RecipientController::class, 'open'])->middleware('throttle:20,1')->name('open');
    Route::post('balas', [RecipientReplyController::class, 'store'])->middleware('throttle:5,1')->name('reply');
    Route::post('reaksi', [RecipientReactionController::class, 'store'])->middleware('throttle:20,1')->name('react');
    Route::post('video-reaksi', [RecipientVideoController::class, 'store'])->middleware('throttle:3,1')->name('video');
});

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', EnsureUserIsAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn () => to_route('admin.orders.index'))->name('home');

    Route::get('pesanan', [Admin\OrderController::class, 'index'])->name('orders.index');
    Route::get('pesanan/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
    Route::post('pesanan/{order}/konfirmasi', [Admin\PaymentReviewController::class, 'confirm'])->name('orders.confirm');
    Route::post('pesanan/{order}/tolak', [Admin\PaymentReviewController::class, 'reject'])->name('orders.reject');

    Route::resource('kategori', Admin\CategoryController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->parameters(['kategori' => 'category'])
        ->names('categories');

    Route::resource('komponen', Admin\ComponentController::class)
        ->except(['show', 'destroy'])
        ->parameters(['komponen' => 'component'])
        ->names('components');

    Route::resource('promo', Admin\PromoCodeController::class)
        ->only(['index', 'store', 'update'])
        ->parameters(['promo' => 'promoCode'])
        ->names('promos');

    Route::resource('template', Admin\TemplateController::class)
        ->except(['show', 'destroy'])
        ->names('templates');
});

require __DIR__.'/auth.php';
