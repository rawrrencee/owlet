<?php

use App\Constants\InventoryActivityCodes;
use App\Enums\TransactionChangeType;
use App\Enums\TransactionStatus;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\EmployeeStore;
use App\Models\InventoryLog;
use App\Models\PaymentMode;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductStore;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;

// Helpers to set up common test data

function createTestStore(array $overrides = []): Store
{
    return Store::factory()->create($overrides);
}

function createTestCurrency(array $overrides = []): Currency
{
    return Currency::factory()->create($overrides);
}

function createTestProduct(int $storeId, int $currencyId, string $unitPrice = '10.0000', string $costPrice = '5.0000'): Product
{
    $product = Product::factory()->create();

    $ps = ProductStore::create([
        'product_id' => $product->id,
        'store_id' => $storeId,
        'quantity' => 100,
        'is_active' => true,
    ]);

    // Use base price (not store price) for simplicity
    ProductPrice::create([
        'product_id' => $product->id,
        'currency_id' => $currencyId,
        'unit_price' => $unitPrice,
        'cost_price' => $costPrice,
    ]);

    return $product;
}

function createTestUser(): array
{
    $employee = Employee::factory()->create();
    $user = User::factory()->admin()->forEmployee($employee)->create();

    return [$user, $employee];
}

function getService(): TransactionService
{
    return app(TransactionService::class);
}

// --- Transaction Lifecycle ---

test('can create a draft transaction', function () {
    [$user, $employee] = createTestUser();
    $store = createTestStore();
    $currency = createTestCurrency();
    $store->currencies()->attach($currency->id);

    $this->actingAs($user);

    $service = getService();
    $transaction = $service->create($store->id, $employee->id, $currency->id, $user->id);

    expect($transaction)
        ->status->toBe(TransactionStatus::DRAFT)
        ->store_id->toBe($store->id)
        ->employee_id->toBe($employee->id)
        ->currency_id->toBe($currency->id);

    expect($transaction->transaction_number)->toStartWith('TXN-');
    expect($transaction->versions)->toHaveCount(1);
});

test('transaction number follows expected format', function () {
    [$user, $employee] = createTestUser();
    $store = createTestStore(['store_code' => 'TST']);
    $currency = createTestCurrency();

    $this->actingAs($user);

    $transaction = getService()->create($store->id, $employee->id, $currency->id, $user->id);

    $expected = 'TXN-TST-' . now()->format('Ymd') . '-0001';
    expect($transaction->transaction_number)->toBe($expected);
});

// --- Line Items ---

test('can add item to transaction', function () {
    [$user, $employee] = createTestUser();
    $store = createTestStore();
    $currency = createTestCurrency();

    $this->actingAs($user);

    $product = createTestProduct($store->id, $currency->id, '25.0000');

    $service = getService();
    $transaction = $service->create($store->id, $employee->id, $currency->id, $user->id);
    $transaction = $service->addItem($transaction, $product->id, 2, $user->id);

    expect($transaction->items)->toHaveCount(1);
    $item = $transaction->items->first();
    expect($item->product_id)->toBe($product->id);
    expect($item->quantity)->toBe(2);
    expect((float) $item->unit_price)->toBe(25.0);
    expect((float) $item->line_subtotal)->toBe(50.0);
    expect((float) $item->line_total)->toBeLessThanOrEqual(50.0);
    expect((float) $transaction->subtotal)->toBeGreaterThan(0);
});

test('adding same product increments quantity', function () {
    [$user, $employee] = createTestUser();
    $store = createTestStore();
    $currency = createTestCurrency();

    $this->actingAs($user);

    $product = createTestProduct($store->id, $currency->id, '10.0000');

    $service = getService();
    $transaction = $service->create($store->id, $employee->id, $currency->id, $user->id);
    $transaction = $service->addItem($transaction, $product->id, 1, $user->id);
    $transaction = $service->addItem($transaction, $product->id, 3, $user->id);

    expect($transaction->items)->toHaveCount(1);
    expect($transaction->items->first()->quantity)->toBe(4);
});

test('can update item quantity', function () {
    [$user, $employee] = createTestUser();
    $store = createTestStore();
    $currency = createTestCurrency();

    $this->actingAs($user);

    $product = createTestProduct($store->id, $currency->id, '15.0000');

    $service = getService();
    $transaction = $service->create($store->id, $employee->id, $currency->id, $user->id);
    $transaction = $service->addItem($transaction, $product->id, 2, $user->id);

    $itemId = $transaction->items->first()->id;
    $transaction = $service->updateItem($transaction, $itemId, ['quantity' => 5], $user->id);

    expect($transaction->items->first()->quantity)->toBe(5);
    expect((float) $transaction->items->first()->line_subtotal)->toBe(75.0);
});

test('can remove item from transaction', function () {
    [$user, $employee] = createTestUser();
    $store = createTestStore();
    $currency = createTestCurrency();

    $this->actingAs($user);

    $product1 = createTestProduct($store->id, $currency->id, '10.0000');
    $product2 = createTestProduct($store->id, $currency->id, '20.0000');

    $service = getService();
    $transaction = $service->create($store->id, $employee->id, $currency->id, $user->id);
    $transaction = $service->addItem($transaction, $product1->id, 1, $user->id);
    $transaction = $service->addItem($transaction, $product2->id, 1, $user->id);

    expect($transaction->items)->toHaveCount(2);

    $itemId = $transaction->items->where('product_id', $product1->id)->first()->id;
    $transaction = $service->removeItem($transaction, $itemId, $user->id);

    expect($transaction->items)->toHaveCount(1);
    expect($transaction->items->first()->product_id)->toBe($product2->id);
});

// --- Customer ---

test('can set customer on transaction', function () {
    [$user, $employee] = createTestUser();
    $store = createTestStore();
    $currency = createTestCurrency();

    $this->actingAs($user);

    $customer = Customer::factory()->create();

    $service = getService();
    $transaction = $service->create($store->id, $employee->id, $currency->id, $user->id);
    $transaction = $service->setCustomer($transaction, $customer->id, $user->id);

    expect($transaction->customer_id)->toBe($customer->id);
});

test('can clear customer from transaction', function () {
    [$user, $employee] = createTestUser();
    $store = createTestStore();
    $currency = createTestCurrency();

    $this->actingAs($user);

    $customer = Customer::factory()->create();

    $service = getService();
    $transaction = $service->create($store->id, $employee->id, $currency->id, $user->id);
    $transaction = $service->setCustomer($transaction, $customer->id, $user->id);
    $transaction = $service->setCustomer($transaction, null, $user->id);

    expect($transaction->customer_id)->toBeNull();
});

// --- Payments ---

test('can add payment to transaction', function () {
    [$user, $employee] = createTestUser();
    $store = createTestStore();
    $currency = createTestCurrency();
    $paymentMode = PaymentMode::factory()->cash()->create();

    $this->actingAs($user);

    $product = createTestProduct($store->id, $currency->id, '100.0000');

    $service = getService();
    $transaction = $service->create($store->id, $employee->id, $currency->id, $user->id);
    $transaction = $service->addItem($transaction, $product->id, 1, $user->id);
    $transaction = $service->addPayment($transaction, $paymentMode->id, '100.0000', null, $user->id);

    expect($transaction->payments)->toHaveCount(1);
    expect((float) $transaction->payments->first()->amount)->toBe(100.0);
    expect((float) $transaction->amount_paid)->toBe(100.0);
    expect((float) $transaction->balance_due)->toBeLessThanOrEqual(0);
});

test('can split payment across multiple methods', function () {
    [$user, $employee] = createTestUser();
    $store = createTestStore();
    $currency = createTestCurrency();
    $cash = PaymentMode::factory()->cash()->create();
    $card = PaymentMode::factory()->card()->create();

    $this->actingAs($user);

    $product = createTestProduct($store->id, $currency->id, '100.0000');

    $service = getService();
    $transaction = $service->create($store->id, $employee->id, $currency->id, $user->id);
    $transaction = $service->addItem($transaction, $product->id, 1, $user->id);
    $transaction = $service->addPayment($transaction, $cash->id, '60.0000', null, $user->id);
    $transaction = $service->addPayment($transaction, $card->id, '40.0000', null, $user->id);

    expect($transaction->payments)->toHaveCount(2);
    expect((float) $transaction->amount_paid)->toBe(100.0);
});

test('can remove payment', function () {
    [$user, $employee] = createTestUser();
    $store = createTestStore();
    $currency = createTestCurrency();
    $paymentMode = PaymentMode::factory()->cash()->create();

    $this->actingAs($user);

    $product = createTestProduct($store->id, $currency->id, '50.0000');

    $service = getService();
    $transaction = $service->create($store->id, $employee->id, $currency->id, $user->id);
    $transaction = $service->addItem($transaction, $product->id, 1, $user->id);
    $transaction = $service->addPayment($transaction, $paymentMode->id, '50.0000', null, $user->id);

    $paymentId = $transaction->payments->first()->id;
    $transaction = $service->removePayment($transaction, $paymentId, $user->id);

    expect($transaction->payments)->toHaveCount(0);
    expect((float) $transaction->amount_paid)->toBe(0.0);
});

test('overpayment calculates change amount', function () {
    [$user, $employee] = createTestUser();
    $store = createTestStore();
    $currency = createTestCurrency();
    $paymentMode = PaymentMode::factory()->cash()->create();

    $this->actingAs($user);

    $product = createTestProduct($store->id, $currency->id, '80.0000');

    $service = getService();
    $transaction = $service->create($store->id, $employee->id, $currency->id, $user->id);
    $transaction = $service->addItem($transaction, $product->id, 1, $user->id);
    $transaction = $service->addPayment($transaction, $paymentMode->id, '100.0000', null, $user->id);

    expect((float) $transaction->change_amount)->toBe(20.0);
    expect((float) $transaction->balance_due)->toBe(0.0);
});

// --- Complete ---

test('can complete a fully paid transaction', function () {
    [$user, $employee] = createTestUser();
    $store = createTestStore();
    $currency = createTestCurrency();
    $paymentMode = PaymentMode::factory()->cash()->create();

    $this->actingAs($user);

    $product = createTestProduct($store->id, $currency->id, '50.0000');

    $service = getService();
    $transaction = $service->create($store->id, $employee->id, $currency->id, $user->id);
    $transaction = $service->addItem($transaction, $product->id, 2, $user->id);
    $transaction = $service->addPayment($transaction, $paymentMode->id, '100.0000', null, $user->id);
    $transaction = $service->complete($transaction, $user->id);

    expect($transaction->status)->toBe(TransactionStatus::COMPLETED);
    expect($transaction->checkout_date)->not->toBeNull();
});

test('completing a transaction decrements inventory', function () {
    [$user, $employee] = createTestUser();
    $store = createTestStore();
    $currency = createTestCurrency();
    $paymentMode = PaymentMode::factory()->cash()->create();

    $this->actingAs($user);

    $product = createTestProduct($store->id, $currency->id, '10.0000');

    $productStore = ProductStore::where('product_id', $product->id)
        ->where('store_id', $store->id)
        ->first();
    $initialQty = $productStore->quantity;

    $service = getService();
    $transaction = $service->create($store->id, $employee->id, $currency->id, $user->id);
    $transaction = $service->addItem($transaction, $product->id, 3, $user->id);
    $transaction = $service->addPayment($transaction, $paymentMode->id, '30.0000', null, $user->id);
    $transaction = $service->complete($transaction, $user->id);

    $productStore->refresh();
    expect($productStore->quantity)->toBe($initialQty - 3);

    // Check inventory log created with SI code
    $log = InventoryLog::where('transaction_id', $transaction->id)
        ->where('activity_code', InventoryActivityCodes::SOLD_ITEM)
        ->first();
    expect($log)->not->toBeNull();
    expect($log->quantity_out)->toBe(3);
});

// --- Suspend & Resume ---

test('can suspend and resume a transaction', function () {
    [$user, $employee] = createTestUser();
    $store = createTestStore();
    $currency = createTestCurrency();

    $this->actingAs($user);

    $product = createTestProduct($store->id, $currency->id, '10.0000');

    $service = getService();
    $transaction = $service->create($store->id, $employee->id, $currency->id, $user->id);
    $transaction = $service->addItem($transaction, $product->id, 1, $user->id);

    $transaction = $service->suspend($transaction, $user->id);
    expect($transaction->status)->toBe(TransactionStatus::SUSPENDED);

    $transaction = $service->resume($transaction, $user->id);
    expect($transaction->status)->toBe(TransactionStatus::DRAFT);
});

// --- Void ---

test('voiding a completed transaction restores inventory', function () {
    [$user, $employee] = createTestUser();
    $store = createTestStore();
    $currency = createTestCurrency();
    $paymentMode = PaymentMode::factory()->cash()->create();

    $this->actingAs($user);

    $product = createTestProduct($store->id, $currency->id, '10.0000');

    $productStore = ProductStore::where('product_id', $product->id)
        ->where('store_id', $store->id)
        ->first();
    $initialQty = $productStore->quantity;

    $service = getService();
    $transaction = $service->create($store->id, $employee->id, $currency->id, $user->id);
    $transaction = $service->addItem($transaction, $product->id, 5, $user->id);
    $transaction = $service->addPayment($transaction, $paymentMode->id, '50.0000', null, $user->id);
    $transaction = $service->complete($transaction, $user->id);

    $productStore->refresh();
    expect($productStore->quantity)->toBe($initialQty - 5);

    $transaction = $service->void($transaction, $user->id, 'Test void');
    expect($transaction->status)->toBe(TransactionStatus::VOIDED);

    $productStore->refresh();
    expect($productStore->quantity)->toBe($initialQty);
});

// --- Tax ---

test('exclusive tax is calculated correctly', function () {
    [$user, $employee] = createTestUser();
    $store = createTestStore(['include_tax' => false, 'tax_percentage' => 7.00]);
    $currency = createTestCurrency();

    $this->actingAs($user);

    $product = createTestProduct($store->id, $currency->id, '100.0000');

    $service = getService();
    $transaction = $service->create($store->id, $employee->id, $currency->id, $user->id);
    $transaction = $service->addItem($transaction, $product->id, 1, $user->id);

    expect((float) $transaction->tax_percentage)->toBe(7.0);
    expect((bool) $transaction->tax_inclusive)->toBeFalse();
    expect((float) $transaction->tax_amount)->toBe(7.0);
    expect((float) $transaction->total)->toBe(107.0);
});

test('inclusive tax is calculated correctly', function () {
    [$user, $employee] = createTestUser();
    $store = createTestStore(['include_tax' => true, 'tax_percentage' => 7.00]);
    $currency = createTestCurrency();

    $this->actingAs($user);

    $product = createTestProduct($store->id, $currency->id, '107.0000');

    $service = getService();
    $transaction = $service->create($store->id, $employee->id, $currency->id, $user->id);
    $transaction = $service->addItem($transaction, $product->id, 1, $user->id);

    expect((bool) $transaction->tax_inclusive)->toBeTrue();
    expect((float) $transaction->total)->toBe(107.0);
    expect((float) $transaction->tax_amount)->toBeGreaterThan(0);
    expect((float) $transaction->tax_amount)->toBeLessThan(107.0);
});

// --- Refund ---

test('can refund items from completed transaction', function () {
    [$user, $employee] = createTestUser();
    $store = createTestStore();
    $currency = createTestCurrency();
    $paymentMode = PaymentMode::factory()->cash()->create();

    $this->actingAs($user);

    $product = createTestProduct($store->id, $currency->id, '20.0000');

    $productStore = ProductStore::where('product_id', $product->id)
        ->where('store_id', $store->id)
        ->first();
    $initialQty = $productStore->quantity;

    $service = getService();
    $transaction = $service->create($store->id, $employee->id, $currency->id, $user->id);
    $transaction = $service->addItem($transaction, $product->id, 3, $user->id);
    $transaction = $service->addPayment($transaction, $paymentMode->id, '60.0000', null, $user->id);
    $transaction = $service->complete($transaction, $user->id);

    $itemId = $transaction->items->first()->id;
    $transaction = $service->processRefund($transaction, [
        ['item_id' => $itemId, 'quantity' => 2, 'reason' => 'Defective'],
    ], $user->id);

    // Check refund was processed
    expect((float) $transaction->refund_amount)->toBeGreaterThan(0);

    // Check inventory restored for refunded quantity
    $productStore->refresh();
    expect($productStore->quantity)->toBe($initialQty - 3 + 2); // sold 3, refunded 2

    // Check inventory log with RI code
    $refundLog = InventoryLog::where('transaction_id', $transaction->id)
        ->where('activity_code', InventoryActivityCodes::REFUND_ITEM)
        ->first();
    expect($refundLog)->not->toBeNull();
});

// --- Version History ---

test('version history tracks all changes', function () {
    [$user, $employee] = createTestUser();
    $store = createTestStore();
    $currency = createTestCurrency();
    $paymentMode = PaymentMode::factory()->cash()->create();

    $this->actingAs($user);

    $product = createTestProduct($store->id, $currency->id, '10.0000');

    $service = getService();
    $transaction = $service->create($store->id, $employee->id, $currency->id, $user->id);
    // Version 1: created

    $transaction = $service->addItem($transaction, $product->id, 1, $user->id);
    // Version 2: item_added

    $transaction = $service->addPayment($transaction, $paymentMode->id, '10.0000', null, $user->id);
    // Version 3: payment_added

    $transaction = $service->complete($transaction, $user->id);
    // Version 4: completed

    $transaction->load('versions');
    expect($transaction->versions)->toHaveCount(4);

    $types = $transaction->versions->pluck('change_type')->map(fn ($t) => $t->value)->toArray();
    expect($types)->toContain('created');
    expect($types)->toContain('item_added');
    expect($types)->toContain('payment_added');
    expect($types)->toContain('completed');
});

test('version snapshots contain items and totals', function () {
    [$user, $employee] = createTestUser();
    $store = createTestStore();
    $currency = createTestCurrency();

    $this->actingAs($user);

    $product = createTestProduct($store->id, $currency->id, '25.0000');

    $service = getService();
    $transaction = $service->create($store->id, $employee->id, $currency->id, $user->id);
    $transaction = $service->addItem($transaction, $product->id, 2, $user->id);

    $transaction->load('versions');
    $latestVersion = $transaction->versions->sortByDesc('version_number')->first();

    expect($latestVersion->snapshot_items)->toBeArray();
    expect($latestVersion->snapshot_items)->toHaveCount(1);
    expect($latestVersion->snapshot_totals)->toBeArray();
    expect($latestVersion->snapshot_totals)->toHaveKey('total');
});

// --- Customer Discount ---

test('customer discount is applied to items', function () {
    [$user, $employee] = createTestUser();
    $store = createTestStore();
    $currency = createTestCurrency();

    $this->actingAs($user);

    $customer = Customer::factory()->withDiscount(10)->create();
    $product = createTestProduct($store->id, $currency->id, '100.0000');

    $service = getService();
    $transaction = $service->create($store->id, $employee->id, $currency->id, $user->id);
    $transaction = $service->setCustomer($transaction, $customer->id, $user->id);
    $transaction = $service->addItem($transaction, $product->id, 1, $user->id);

    $item = $transaction->items->first();
    expect((float) $item->customer_discount_amount)->toBeGreaterThan(0);
    expect((float) $transaction->customer_discount)->toBeGreaterThan(0);
});

// --- Multiple Products ---

test('totals are correct with multiple products', function () {
    [$user, $employee] = createTestUser();
    $store = createTestStore();
    $currency = createTestCurrency();

    $this->actingAs($user);

    $product1 = createTestProduct($store->id, $currency->id, '30.0000');
    $product2 = createTestProduct($store->id, $currency->id, '20.0000');

    $service = getService();
    $transaction = $service->create($store->id, $employee->id, $currency->id, $user->id);
    $transaction = $service->addItem($transaction, $product1->id, 2, $user->id); // 60
    $transaction = $service->addItem($transaction, $product2->id, 3, $user->id); // 60

    expect($transaction->items)->toHaveCount(2);
    expect((float) $transaction->subtotal)->toBe(120.0);
    expect((float) $transaction->total)->toBe(120.0); // no tax on this store
});

// --- Edge Cases ---

test('cannot complete transaction with no items', function () {
    [$user, $employee] = createTestUser();
    $store = createTestStore();
    $currency = createTestCurrency();

    $this->actingAs($user);

    $service = getService();
    $transaction = $service->create($store->id, $employee->id, $currency->id, $user->id);

    expect(fn () => $service->complete($transaction, $user->id))
        ->toThrow(\Exception::class);
});

test('cannot add item without price', function () {
    [$user, $employee] = createTestUser();
    $store = createTestStore();
    $currency = createTestCurrency();

    $this->actingAs($user);

    // Product without any price
    $product = Product::factory()->create();
    ProductStore::create([
        'product_id' => $product->id,
        'store_id' => $store->id,
        'quantity' => 100,
        'is_active' => true,
    ]);

    $service = getService();
    $transaction = $service->create($store->id, $employee->id, $currency->id, $user->id);

    expect(fn () => $service->addItem($transaction, $product->id, 1, $user->id))
        ->toThrow(\Exception::class);
});
