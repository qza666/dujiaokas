<?php

namespace App\Providers;

use App\Http\Controllers\UnifiedPaymentController;
use App\PaymentGateways\PaymentManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * 支付服务提供者
 * 负责注册支付服务和动态路由
 */
class PaymentServiceProvider extends ServiceProvider
{
    /**
     * 注册服务
     */
    public function register()
    {
        $this->app->singleton(PaymentManager::class, function ($app) {
            return new PaymentManager();
        });

        // 注册支付管理器别名
        $this->app->alias(PaymentManager::class, 'payment.manager');
    }

    /**
     * 启动服务
     */
    public function boot()
    {
        $this->registerPaymentRoutes();
        $this->registerPaymentDrivers();
    }

    /**
     * 注册支付路由
     */
    protected function registerPaymentRoutes()
    {
        Route::macro('paymentRoutes', function () {
            $paymentManager = app(PaymentManager::class);

            foreach ($paymentManager->getRegisteredDrivers() as $driver) {
                Route::group([
                    'prefix' => "pay/{$driver}",
                    'middleware' => ['dujiaoka.pay_gate_way']
                ], function () use ($driver) {
                    Route::get('{payway}/{orderSN}', function (string $payway, string $orderSN) use ($driver) {
                        return app(UnifiedPaymentController::class)
                            ->gateway($driver, $payway, $orderSN);
                    })->name("payment.{$driver}.gateway");

                    Route::post('notify_url', function (Request $request) use ($driver) {
                        return app(UnifiedPaymentController::class)
                            ->notify($request, $driver);
                    })->name("payment.{$driver}.notify");

                    Route::get('return_url', function (Request $request) use ($driver) {
                        return app(UnifiedPaymentController::class)
                            ->returnUrl($request, $driver);
                    })->name("payment.{$driver}.return");
                });
            }
        });
    }

    /**
     * 注册默认支付驱动
     */
    protected function registerPaymentDrivers()
    {
        $paymentManager = app(PaymentManager::class);
        
        // 可以在这里手动注册额外的驱动
        // $paymentManager->registerDriver('custom_payment', CustomPaymentDriver::class);
    }
}