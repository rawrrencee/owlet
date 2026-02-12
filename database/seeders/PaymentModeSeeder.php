<?php

namespace Database\Seeders;

use App\Models\PaymentMode;
use Illuminate\Database\Seeder;

class PaymentModeSeeder extends Seeder
{
    public function run(): void
    {
        $modes = [
            ['name' => 'PayNow', 'code' => 'PAYNOW'],
            ['name' => 'PayLah!', 'code' => 'PAYLAH'],
            ['name' => 'Cash', 'code' => 'CASH'],
            ['name' => 'Visa / Mastercard', 'code' => 'VISA_MC'],
            ['name' => 'American Express', 'code' => 'AMEX'],
            ['name' => 'NETS', 'code' => 'NETS'],
            ['name' => 'Alipay', 'code' => 'ALIPAY'],
            ['name' => 'WeChat Pay', 'code' => 'WECHAT'],
            ['name' => 'PayPal', 'code' => 'PAYPAL'],
            ['name' => 'Apple Pay', 'code' => 'APPLE_PAY'],
            ['name' => 'Google Pay', 'code' => 'GOOGLE_PAY'],
            ['name' => 'Samsung Pay', 'code' => 'SAMSUNG_PAY'],
            ['name' => 'ShopPay', 'code' => 'SHOPPAY'],
            ['name' => 'ShopeePay', 'code' => 'SHOPEEPAY'],
            ['name' => 'GrabPay', 'code' => 'GRABPAY'],
        ];

        foreach ($modes as $index => $mode) {
            PaymentMode::firstOrCreate(
                ['code' => $mode['code']],
                [
                    'name' => $mode['name'],
                    'sort_order' => $index,
                    'is_active' => true,
                ]
            );
        }
    }
}
