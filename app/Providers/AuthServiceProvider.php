<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;

use App\Models\ContactMessage;
use App\Models\Customer;
use App\Models\CustomerLevel;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Models\Installment;
use App\Models\InstallmentPayment;
use App\Models\InstallmentPlan;
use App\Models\Product;
use App\Models\SchoolUnit;
use App\Models\Template;
use App\Models\TemplateSection;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Village;
use App\Policies\ContactMessagePolicy;
use App\Policies\CustomerPolicy;
use App\Policies\CustomerLevelPolicy;
use App\Policies\EmailLogPolicy;
use App\Policies\EmailTemplatePolicy;
use App\Policies\ExceptionPolicy;
use App\Policies\InstallmentPaymentPolicy;
use App\Policies\InstallmentPlanPolicy;
use App\Policies\InstallmentPolicy;
use App\Policies\ProductPolicy;
use App\Policies\SchoolUnitPolicy;
use App\Policies\TemplatePolicy;
use App\Policies\TemplateSectionPolicy;
use App\Policies\TransactionPolicy;
use App\Policies\VillagePolicy;
use BezhanSalleh\FilamentExceptions\Models\Exception;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;


class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Exception::class => ExceptionPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::policy(Exception::class, ExceptionPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(Transaction::class, TransactionPolicy::class);
        Gate::policy(SchoolUnit::class, SchoolUnitPolicy::class);
        Gate::policy(CustomerLevel::class, CustomerLevelPolicy::class);
        Gate::policy(InstallmentPlan::class, InstallmentPlanPolicy::class);
        Gate::policy(Installment::class, InstallmentPolicy::class);
        Gate::policy(InstallmentPayment::class, InstallmentPaymentPolicy::class);
        Gate::policy(EmailTemplate::class, EmailTemplatePolicy::class);
        Gate::policy(TemplateSection::class, TemplateSectionPolicy::class);
        Gate::policy(Template::class, TemplatePolicy::class);
        Gate::policy(ContactMessage::class, ContactMessagePolicy::class);
        Gate::policy(EmailLog::class, EmailLogPolicy::class);
        Gate::policy(Village::class, VillagePolicy::class);

        Gate::before(function (User $user, $ability) {
            return $user->is_super_user ? true : null;  // Pastikan mengembalikan null jika bukan superuser
        });
    }
}
