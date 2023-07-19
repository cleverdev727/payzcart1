<?php

namespace App\Providers;
use App\Classes\DashboardUtils;
use App\Plugin\AccessControl\Utils\AccessControlUtils;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        view()->composer(
            'layout.master',
            function ($view) {
                if(Auth::check()) {
                    $alertData = DashboardUtils::renderPayoutAlert();
                    $view->with('alertData', $alertData);
                }
            }
        );

        Validator::extend('valid_ifsc', 'App\Classes\Utils\ValidationUtils@validateIFSC');
        Validator::extend('valid_vpa', 'App\Classes\Utils\ValidationUtils@validateVpa');
        Validator::extend('valid_account_number', 'App\Classes\Utils\ValidationUtils@validateAccountNumber');
        Validator::extend('customer_email', 'App\Classes\Utils\ValidationUtils@validateCustomerEmail');
        Validator::extend('customer_mobile', 'App\Classes\Utils\ValidationUtils@validateCustomerMobile');
        Validator::extend('valid_amount', 'App\Classes\Utils\ValidationUtils@validateAmount');
        Validator::extend('valid_url', 'App\Classes\Utils\ValidationUtils@validateURL');
        Validator::extend('valid_reference_id', 'App\Classes\Utils\ValidationUtils@validateMerchantReferenceId');
        Validator::extend('valid_udf', 'App\Classes\Utils\ValidationUtils@validateUDF');
    }
}
