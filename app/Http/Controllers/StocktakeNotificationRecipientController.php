<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNotificationRecipientRequest;
use App\Http\Requests\UpdateNotificationRecipientRequest;
use App\Models\StocktakeNotificationRecipient;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class StocktakeNotificationRecipientController extends Controller
{
    /**
     * List recipients by store.
     */
    public function index(Request $request): InertiaResponse
    {
        $storeId = $request->query('store_id');

        $stores = Store::select('id', 'store_name', 'store_code')
            ->orderBy('store_name')
            ->get();

        $recipients = [];
        if ($storeId) {
            $recipients = StocktakeNotificationRecipient::where('store_id', $storeId)
                ->orderBy('name')
                ->get()
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'store_id' => $r->store_id,
                    'email' => $r->email,
                    'name' => $r->name,
                    'is_active' => $r->is_active,
                ]);
        }

        return Inertia::render('Management/Notifications/Index', [
            'stores' => $stores,
            'recipients' => $recipients,
            'filters' => [
                'store_id' => $storeId,
            ],
        ]);
    }

    /**
     * Add a new recipient.
     */
    public function store(StoreNotificationRecipientRequest $request): RedirectResponse
    {
        StocktakeNotificationRecipient::create($request->validated());

        return back()->with('success', 'Notification recipient added.');
    }

    /**
     * Update a recipient.
     */
    public function update(UpdateNotificationRecipientRequest $request, StocktakeNotificationRecipient $recipient): RedirectResponse
    {
        $recipient->update($request->validated());

        return back()->with('success', 'Notification recipient updated.');
    }

    /**
     * Remove a recipient.
     */
    public function destroy(Request $request, StocktakeNotificationRecipient $recipient): RedirectResponse
    {
        $recipient->delete();

        return back()->with('success', 'Notification recipient removed.');
    }
}
