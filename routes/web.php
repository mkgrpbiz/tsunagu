<?php

use App\Http\Controllers\Admin\AdminManagerController;
use App\Http\Controllers\Admin\AgencyController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CollaborationPartnerController;
use App\Http\Controllers\Admin\CollaborationReferralController;
use App\Http\Controllers\Admin\CollaborationRewardController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DepositLinkController;
use App\Http\Controllers\Admin\HomeBlockController;
use App\Http\Controllers\Admin\HomePageContentController;
use App\Http\Controllers\Admin\InquiryController;
use App\Http\Controllers\Admin\LandingPageContentController;
use App\Http\Controllers\Admin\LegalDocumentController as AdminLegalDocumentController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\SalesMaterialController;
use App\Http\Controllers\Agency\AdditionalInfoController as AgencyAdditionalInfoController;
use App\Http\Controllers\Agency\AuthController as AgencyAuthController;
use App\Http\Controllers\Agency\CollaborationReferralController as AgencyCollaborationReferralController;
use App\Http\Controllers\Agency\ContractController as AgencyContractController;
use App\Http\Controllers\Agency\HomeController as AgencyHomeController;
use App\Http\Controllers\Agency\InquiryController as AgencyInquiryController;
use App\Http\Controllers\Agency\ProfileController as AgencyProfileController;
use App\Http\Controllers\Agency\ProjectController as AgencyProjectController;
use App\Http\Controllers\Public\AgencyRegistrationController;
use App\Http\Controllers\Public\ApplyController;
use App\Http\Controllers\Public\LegalDocumentController as PublicLegalDocumentController;
use App\Http\Controllers\Public\LineWebhookController;
use App\Http\Controllers\Public\OshigotoController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::get('apply/{inviteLink:token}', [ApplyController::class, 'show'])->name('apply.show');
Route::post('apply/{inviteLink:token}', [ApplyController::class, 'store'])->name('apply.store');

Route::get('oshigoto', [OshigotoController::class, 'index'])->name('oshigoto.index');

Route::post('line/webhook', [LineWebhookController::class, 'handle'])->name('line.webhook');

Route::pattern('type', 'terms|privacy|partner_agreement');

Route::get('legal/{type}', [PublicLegalDocumentController::class, 'show'])->name('legal.show');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.attempt');

    Route::middleware('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::redirect('/', '/admin/projects');

        Route::resource('admins', AdminManagerController::class)->except('show');
        Route::post('admins/{admin}/reset-password', [AdminManagerController::class, 'resetPassword'])->name('admins.reset-password');

        Route::middleware('menu:dashboard')->group(function () {
            Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
        });

        Route::middleware('menu:categories')->group(function () {
            Route::post('categories/reorder', [CategoryController::class, 'reorder'])->name('categories.reorder');
            Route::resource('categories', CategoryController::class)->except('show');
        });

        Route::middleware('menu:projects')->group(function () {
            Route::post('projects/reorder', [ProjectController::class, 'reorder'])->name('projects.reorder');
            Route::post('projects/{project}/duplicate', [ProjectController::class, 'duplicate'])->name('projects.duplicate');
            Route::resource('projects', ProjectController::class)->except('show');
        });

        Route::middleware('menu:agencies')->group(function () {
            Route::patch('agencies/{agency}/status', [AgencyController::class, 'updateStatus'])->name('agencies.update-status');
            Route::patch('agencies/{agency}/collaboration-partner', [AgencyController::class, 'toggleCollaborationPartner'])->name('agencies.toggle-collaboration-partner');
            Route::resource('agencies', AgencyController::class);
        });

        Route::middleware('menu:collaboration_partners')->group(function () {
            Route::get('collaboration-partners', [CollaborationPartnerController::class, 'index'])->name('collaboration-partners.index');
        });

        Route::middleware('menu:inquiries')->group(function () {
            Route::get('inquiries', [InquiryController::class, 'index'])->name('inquiries.index');
            Route::patch('inquiries/{inquiry}/toggle-lost', [InquiryController::class, 'toggleLost'])->name('inquiries.toggle-lost');
        });

        Route::middleware('menu:deposit_links')->group(function () {
            Route::get('deposit-links', [DepositLinkController::class, 'index'])->name('deposit-links.index');
            Route::post('deposit-links/{inquiry}', [DepositLinkController::class, 'store'])->name('deposit-links.store');
        });

        Route::middleware('menu:payments')->group(function () {
            Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
            Route::patch('payments/{contract}', [PaymentController::class, 'update'])->name('payments.update');
            Route::patch('payments/{contract}/revert', [PaymentController::class, 'revert'])->name('payments.revert');
            Route::patch('payments/referral-commissions/{referralCommission}', [PaymentController::class, 'updateReferralCommission'])->name('payments.referral-commissions.update');
            Route::patch('payments/referral-commissions/{referralCommission}/revert', [PaymentController::class, 'revertReferralCommission'])->name('payments.referral-commissions.revert');
            Route::patch('payments/collaboration-rewards/{collaborationReward}', [PaymentController::class, 'updateCollaborationReward'])->name('payments.collaboration-rewards.update');
            Route::patch('payments/collaboration-rewards/{collaborationReward}/revert', [PaymentController::class, 'revertCollaborationReward'])->name('payments.collaboration-rewards.revert');
        });

        Route::middleware('menu:announcements')->group(function () {
            Route::resource('announcements', AnnouncementController::class)->except('show');
        });

        Route::middleware('menu:collaboration_referrals')->group(function () {
            Route::get('collaboration-referrals', [CollaborationReferralController::class, 'index'])->name('collaboration-referrals.index');
            Route::patch('collaboration-referrals/{collaborationReferral}/toggle-status', [CollaborationReferralController::class, 'toggleStatus'])->name('collaboration-referrals.toggle-status');
        });

        Route::middleware('menu:collaboration_rewards')->group(function () {
            Route::get('collaboration-rewards', [CollaborationRewardController::class, 'index'])->name('collaboration-rewards.index');
            Route::put('collaboration-rewards/{collaborationReward}', [CollaborationRewardController::class, 'update'])->name('collaboration-rewards.update');
        });

        Route::middleware('menu:home')->group(function () {
            Route::post('home-blocks/reorder', [HomeBlockController::class, 'reorder'])->name('home-blocks.reorder');
            Route::resource('home-blocks', HomeBlockController::class)->except('show');

            Route::resource('sales-materials', SalesMaterialController::class)->except('show');

            Route::get('home-content', [HomePageContentController::class, 'edit'])->name('home-content.edit');
            Route::put('home-content', [HomePageContentController::class, 'update'])->name('home-content.update');
        });

        Route::middleware('menu:landing_page_content')->group(function () {
            Route::get('landing-page-content', [LandingPageContentController::class, 'edit'])->name('landing-page-content.edit');
            Route::put('landing-page-content', [LandingPageContentController::class, 'update'])->name('landing-page-content.update');
        });

        Route::middleware('menu:legal_documents')->group(function () {
            Route::get('legal-documents', [AdminLegalDocumentController::class, 'index'])->name('legal-documents.index');
            Route::get('legal-documents/{type}/edit', [AdminLegalDocumentController::class, 'edit'])->name('legal-documents.edit');
            Route::put('legal-documents/{type}', [AdminLegalDocumentController::class, 'update'])->name('legal-documents.update');
            Route::get('legal-documents/{type}/history', [AdminLegalDocumentController::class, 'history'])->name('legal-documents.history');
        });
    });
});

Route::prefix('agency')->name('agency.')->group(function () {
    Route::get('login', [AgencyAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AgencyAuthController::class, 'login'])->name('login.attempt');

    Route::get('register', [AgencyRegistrationController::class, 'landing'])->name('register');
    Route::get('register/form', [AgencyRegistrationController::class, 'form'])->name('register.form');
    Route::post('register/form', [AgencyRegistrationController::class, 'store'])->name('register.store');

    Route::middleware('auth:agency')->group(function () {
        Route::post('logout', [AgencyAuthController::class, 'logout'])->name('logout');
        Route::redirect('/', '/agency/home');

        Route::get('profile', [AgencyProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [AgencyProfileController::class, 'update'])->name('profile.update');

        Route::middleware('agency.password_changed')->group(function () {
            Route::get('home', [AgencyHomeController::class, 'index'])->name('home');
            Route::get('inquiries', [AgencyInquiryController::class, 'index'])->name('inquiries.index');
            Route::get('contracts', [AgencyContractController::class, 'index'])->name('contracts.index');

            Route::get('additional-info', [AgencyAdditionalInfoController::class, 'edit'])->name('additional-info.edit');
            Route::put('additional-info', [AgencyAdditionalInfoController::class, 'update'])->name('additional-info.update');

            Route::middleware(['agency.approved', 'agency.consents_submitted'])->group(function () {
                Route::get('projects', [AgencyProjectController::class, 'index'])->name('projects.index');

                Route::get('collaboration-referrals/create', [AgencyCollaborationReferralController::class, 'create'])->name('collaboration-referrals.create');
                Route::post('collaboration-referrals', [AgencyCollaborationReferralController::class, 'store'])->name('collaboration-referrals.store');
            });
        });
    });
});
