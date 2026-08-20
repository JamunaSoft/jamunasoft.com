<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HostingController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\QuotationViewController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SolutionController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
Route::get('/services/{service}', [ServiceController::class, 'show'])->name('services.show');

Route::get('/solutions', [SolutionController::class, 'index'])->name('solutions.index');
Route::get('/solutions/{solution}', [SolutionController::class, 'show'])->name('solutions.show');

Route::get('/portfolio', [PortfolioController::class, 'index'])->name('portfolio.index');
Route::get('/portfolio/{portfolio}', [PortfolioController::class, 'show'])->name('portfolio.show');

Route::get('/hosting', HostingController::class)->name('hosting');

Route::get('/domains', [DomainController::class, 'index'])
    ->middleware('throttle:30,1')
    ->name('domains.index');
Route::post('/domains/order', [DomainController::class, 'order'])
    ->middleware('throttle:10,1')
    ->name('domains.order');
Route::get('/domains/order/{reference}', [DomainController::class, 'status'])->name('domains.order.status');

Route::get('/packages', PackageController::class)->name('packages.index');

Route::get('/about', AboutController::class)->name('about');

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/category/{category}', [BlogController::class, 'category'])->name('blog.category');
Route::get('/blog/tag/{tag}', [BlogController::class, 'tag'])->name('blog.tag');
Route::get('/blog/{post}', [BlogController::class, 'show'])->name('blog.show');

Route::get('/contact', [ContactController::class, 'create'])->name('contact.form');
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('contact.submit');

Route::get('/request-a-quotation', [QuotationController::class, 'create'])->name('quote.create');
Route::post('/request-a-quotation', [QuotationController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('quote.store');
Route::get('/request-a-quotation/thank-you', [QuotationController::class, 'thanks'])->name('quote.thanks');

Route::get('/quotation/{reference}/{token}', [QuotationViewController::class, 'show'])->name('quotation.show');
Route::post('/quotation/{reference}/{token}', [QuotationViewController::class, 'respond'])
    ->middleware('throttle:10,1')
    ->name('quotation.respond');

Route::post('/newsletter', [NewsletterController::class, 'subscribe'])
    ->middleware('throttle:5,1')
    ->name('newsletter.subscribe');
Route::get('/newsletter/confirm/{token}', [NewsletterController::class, 'confirm'])->name('newsletter.confirm');
Route::get('/newsletter/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');

Route::get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

Route::get('/page/{page}', [PageController::class, 'show'])->name('page.show');
