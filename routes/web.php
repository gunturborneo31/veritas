<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContributionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GreetingMediaController;
use App\Http\Controllers\GreetingQrController;
use App\Http\Controllers\GreetingSlugController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvitationCheckInController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\InvitationEventController;
use App\Http\Controllers\InvitationGiftController;
use App\Http\Controllers\InvitationGuestController;
use App\Http\Controllers\InvitationRevisionController;
use App\Http\Controllers\InvitationWishController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ManualPaymentController;
use App\Http\Controllers\MediaFileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PreviewController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QrisPaymentController;
use App\Http\Controllers\QrisWebhookController;
use App\Http\Controllers\RecipientController;
use App\Http\Controllers\RecipientReactionController;
use App\Http\Controllers\RecipientReplyController;
use App\Http\Controllers\RecipientRsvpController;
use App\Http\Controllers\RecipientVideoController;
use App\Http\Controllers\RecipientWishController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\SongController;
use App\Http\Controllers\StudioContributionController;
use App\Http\Controllers\StudioController;
use App\Http\Controllers\StudioStyleController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\TemplateDemoController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
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

Route::middleware('throttle:30,1')->prefix('lokasi')->name('locations.')->group(function () {
    Route::get('cari', [LocationController::class, 'search'])->name('search');
    Route::get('alamat', [LocationController::class, 'reverse'])->name('reverse');
    Route::get('link', [LocationController::class, 'link'])->name('link');
});

Route::middleware('throttle:30,1')->prefix('lagu')->name('songs.')->group(function () {
    Route::get('cari', [SongController::class, 'search'])->name('search');
    Route::get('link', [SongController::class, 'link'])->name('link');
});

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

// Foto/audio ucapan: URL bertanda tangan & kedaluwarsa, tetap berlaku setelah file dikompres.
Route::get('media/{media}', MediaFileController::class)->middleware('signed')->name('media.file');

/*
| Kelola undangan. Tanpa middleware auth karena acara & rekening sudah bisa diisi saat draf anonim;
| kepemilikan dicek di controller (akun atau cookie draf). Model anak di-scope ke undangannya.
*/

Route::prefix('undangan/{greeting}')->name('invitations.')->scopeBindings()->group(function () {
    Route::get('/', [InvitationController::class, 'show'])->name('show');
    Route::put('pengaturan', [InvitationController::class, 'update'])->name('settings.update');
    Route::put('revisi', InvitationRevisionController::class)->name('revise');

    Route::post('acara', [InvitationEventController::class, 'store'])->name('events.store');
    Route::put('acara/{invitationEvent}', [InvitationEventController::class, 'update'])->name('events.update');
    Route::delete('acara/{invitationEvent}', [InvitationEventController::class, 'destroy'])->name('events.destroy');

    Route::post('amplop', [InvitationGiftController::class, 'store'])->name('gifts.store');
    Route::put('amplop/{giftAccount}', [InvitationGiftController::class, 'update'])->name('gifts.update');
    Route::delete('amplop/{giftAccount}', [InvitationGiftController::class, 'destroy'])->name('gifts.destroy');

    Route::post('tamu', [InvitationGuestController::class, 'store'])->name('guests.store');
    Route::post('tamu/impor', [InvitationGuestController::class, 'import'])->middleware('throttle:10,1')->name('guests.import');
    Route::put('tamu/{guest}', [InvitationGuestController::class, 'update'])->name('guests.update');
    Route::delete('tamu/{guest}', [InvitationGuestController::class, 'destroy'])->name('guests.destroy');
    Route::post('tamu/{guest}/terkirim', [InvitationGuestController::class, 'markInvited'])->name('guests.invited');
    Route::get('tamu/{guest}/qr.png', [InvitationGuestController::class, 'qr'])->name('guests.qr');
    Route::post('check-in', InvitationCheckInController::class)->middleware('throttle:120,1')->name('check-in');

    Route::patch('ucapan-doa/{wish}', [InvitationWishController::class, 'toggle'])->name('wishes.toggle');
});

Route::get('patungan/{token}', [ContributionController::class, 'show'])->name('contributions.show');
Route::post('patungan/{token}', [ContributionController::class, 'store'])->middleware('throttle:10,1')->name('contributions.store');

/*
| Webhook InterActive QRIS (tanpa sesi/CSRF; token rahasia di URL, status selalu dicek ulang ke API).
*/

Route::post('webhook/telegram', TelegramWebhookController::class)
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->middleware('throttle:120,1')
    ->name('webhooks.telegram');

Route::post('webhook/qris/{token}', QrisWebhookController::class)
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->middleware('throttle:120,1')
    ->name('webhooks.qris');

/*
|--------------------------------------------------------------------------
| Akun: Checkout, Pembayaran, Hasil, Dashboard
|--------------------------------------------------------------------------
*/

/*
| Checkout terbuka untuk pemilik draf anonim (cookie): akun dibuat & login otomatis saat membayar.
*/

Route::get('checkout/{greeting}', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('checkout/{greeting}', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');
Route::get('checkout/{greeting}/cek-link', [GreetingSlugController::class, 'check'])->middleware('throttle:60,1')->name('checkout.slug.check');

Route::middleware('auth')->group(function () {
    Route::post('checkout/{greeting}/promo', [CheckoutController::class, 'applyPromo'])->middleware('throttle:15,1')->name('checkout.promo.apply');
    Route::delete('checkout/{greeting}/promo', [CheckoutController::class, 'removePromo'])->name('checkout.promo.remove');

    Route::get('pesanan/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('pesanan/{order}/qris', [QrisPaymentController::class, 'refresh'])->middleware('throttle:10,1')->name('orders.qris.refresh');
    Route::post('pesanan/{order}/qris/cek', [QrisPaymentController::class, 'check'])->middleware('throttle:20,1')->name('orders.qris.check');
    Route::get('pesanan/{order}/qris.png', [QrisPaymentController::class, 'image'])->name('orders.qris.image');
    Route::post('pesanan/{order}/qris/simulasi', [QrisPaymentController::class, 'simulate'])->name('orders.qris.simulate');
    Route::post('pesanan/{order}/sudah-bayar', [ManualPaymentController::class, 'claim'])->middleware('throttle:10,1')->name('orders.manual.claim');

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
    Route::post('rsvp', [RecipientRsvpController::class, 'store'])->middleware('throttle:10,1')->name('rsvp');
    Route::post('ucapan-doa', [RecipientWishController::class, 'store'])->middleware('throttle:5,1')->name('wish');
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

    Route::post('template/{template}/duplikat', [Admin\TemplateController::class, 'duplicate'])->name('templates.duplicate');

    Route::resource('template', Admin\TemplateController::class)
        ->except(['show', 'destroy'])
        ->names('templates');
});

require __DIR__.'/auth.php';
