<?php

use App\Http\Controllers\Frontend\AccountController;
use App\Http\Controllers\Frontend\Auth\ForgotPasswordController;
use App\Http\Controllers\Frontend\Auth\LoginController;
use App\Http\Controllers\Frontend\Auth\LogoutController;
use App\Http\Controllers\Frontend\Auth\RegisterController;
use App\Http\Controllers\Frontend\Auth\ResetPasswordController;
use App\Http\Controllers\Frontend\BlogController;
use App\Http\Controllers\Frontend\CartController;
use App\Http\Controllers\Frontend\CheckoutController;
use App\Http\Controllers\Frontend\ContactController;
use App\Http\Controllers\Frontend\FaqController;
use App\Http\Controllers\Frontend\FlashsaleController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\NewsletterController;
use App\Http\Controllers\Frontend\OrderController;
use App\Http\Controllers\Frontend\PageController;
use App\Http\Controllers\Frontend\ProductController;
use App\Http\Controllers\Frontend\ProductReviewController;
use App\Http\Controllers\Frontend\VoucherController;
use App\Http\Controllers\Frontend\WishlistController;
use App\Http\Controllers\Frontend\InstallmentController;
use App\Http\Controllers\PaymentWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/login', function () {
    return redirect()->route('frontend.login');
})->name('login');

Route::name('frontend.')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');

    // Guest routes for customers - login only (disabled for fully private)
    Route::middleware('auth.customer.guest')->group(function () {
        Route::get('/register-closed', fn() => \Inertia\Inertia::render('Auth/RegistrationClosed'))
            ->name('registration-closed');

        Route::get('/register', RegisterController::class)->name('signup');
        Route::post('/register', [RegisterController::class, 'register'])->name('signup.post');
        Route::get('/forgot-password', ForgotPasswordController::class)->name('forgot-password');
        Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])->name('forgot-password.send');
        Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('reset-password');
        Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('reset-password.update');

        Route::get('/login', LoginController::class)->name('login');
        Route::post('/login', [LoginController::class, 'login'])->name('login.post');
    });

    Route::get('/contact', [ContactController::class, 'index'])->name('contact');
    Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

    Route::get('/flash-sale', FlashsaleController::class)->name('flashsales');

    // Public content routes
    Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
    Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
    Route::get('/page/{slug}', [PageController::class, 'show'])->name('page.show');
    Route::get('/faq', FaqController::class)->name('faq');

    // All routes require authentication (fully private)
    Route::middleware('auth.customer')->group(function () {
        Route::get('/account', AccountController::class)->name('account');
        Route::post('/account/update', [AccountController::class, 'updateProfile'])->name('account.update');

        Route::post('/account/address', [AccountController::class, 'storeAddress'])->name('account.address.store');
        Route::patch('/account/address/{address}', [AccountController::class, 'updateAddress'])->name('account.address.update');
        Route::delete('/account/address/{address}', [AccountController::class, 'deleteAddress'])->name('account.address.delete');

        Route::get('/regions/districts/{province}', [AccountController::class, 'getDistricts'])->name('regions.districts');
        Route::get('/regions/sub-districts/{district}', [AccountController::class, 'getSubDistricts'])->name('regions.sub-districts');
        Route::get('/regions/villages/{subDistrict}', [AccountController::class, 'getVillages'])->name('regions.villages');

        Route::post('/logout', LogoutController::class)->name('logout');
        Route::get('/checkout', CheckoutController::class)->name('checkout');
        Route::get('/checkout/shipping-costs', [CheckoutController::class, 'getShippingCosts'])->name('checkout.shipping-costs');
        Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
        Route::get('/orders', [OrderController::class, 'index'])->name('orders');
        Route::get('/orders/{transaction}', [OrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{transaction}/pay', [OrderController::class, 'pay'])->name('orders.pay');
        Route::post('/orders/{transaction}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
        Route::post('/orders/{transaction}/products/{transactionProduct}/review', [ProductReviewController::class, 'store'])->name('orders.products.review.store');
        Route::post('/orders/{transaction}/reviews', [ProductReviewController::class, 'storeBatch'])->name('orders.reviews.store');

        Route::get('/installments', [InstallmentController::class, 'index'])->name('installments');
        Route::get('/installments/{uuid}', [InstallmentController::class, 'show'])->name('installments.show');

        Route::get('/cart', CartController::class)->name('cart');
        Route::post('/cart/add', [CartController::class, 'store'])->name('cart.store');
        Route::patch('/cart/{item}', [CartController::class, 'update'])->name('cart.update');
        Route::delete('/cart/{item}', [CartController::class, 'destroy'])->name('cart.destroy');

        Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist');
        Route::post('/wishlist/toggle', [WishlistController::class, 'toggle'])->name('wishlist.toggle');

        Route::get('/vouchers', [VoucherController::class, 'index'])->name('vouchers');
        Route::post('/vouchers/apply', [VoucherController::class, 'apply'])->name('vouchers.apply');
        Route::post('/vouchers/remove', [VoucherController::class, 'remove'])->name('vouchers.remove');
    });

    Route::get('/products', ProductController::class)->name('products');
    Route::get('/products/{product}/reviews', [ProductReviewController::class, 'index'])->name('products.reviews');
    Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');
    Route::post('/newsletter/send-test', [NewsletterController::class, 'sendTest'])->name('newsletter.send-test');
    Route::get('/newsletter/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');
    // Webhook routes (no auth - webhook handles its own auth)
    Route::post('/webhooks/payment/{gateway}', PaymentWebhookController::class)->name('webhooks.payment');

    // Wildcard for product detail - MUST BE LAST
    Route::get('{product}', [ProductController::class, 'show'])->name('product-detail');
});
