<?php

namespace App\Http\Controllers;

use App\Enums\NotificationEventType;
use App\Http\Requests\StoreNotificationRecipientRequest;
use App\Http\Requests\UpdateNotificationRecipientRequest;
use App\Models\NotificationRecipient;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class NotificationRecipientController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $storeId = $request->query('store_id');
        $eventType = $request->query('event_type', NotificationEventType::Stocktake->value);

        $stores = Store::select('id', 'store_name', 'store_code')
            ->orderBy('store_name')
            ->get();

        $eventTypes = collect(NotificationEventType::cases())->map(fn ($type) => [
            'value' => $type->value,
            'label' => $type->label(),
        ]);

        $recipients = [];
        if ($storeId) {
            $recipients = NotificationRecipient::where('store_id', $storeId)
                ->where('event_type', $eventType)
                ->orderBy('name')
                ->get()
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'event_type' => $r->event_type->value,
                    'store_id' => $r->store_id,
                    'email' => $r->email,
                    'name' => $r->name,
                    'is_active' => $r->is_active,
                ]);
        }

        return Inertia::render('Management/Notifications/Index', [
            'stores' => $stores,
            'recipients' => $recipients,
            'eventTypes' => $eventTypes,
            'filters' => [
                'store_id' => $storeId,
                'event_type' => $eventType,
            ],
        ]);
    }

    public function store(StoreNotificationRecipientRequest $request): RedirectResponse
    {
        NotificationRecipient::create($request->validated());

        return back()->with('success', 'Notification recipient added.');
    }

    public function update(UpdateNotificationRecipientRequest $request, NotificationRecipient $recipient): RedirectResponse
    {
        $recipient->update($request->validated());

        return back()->with('success', 'Notification recipient updated.');
    }

    public function destroy(Request $request, NotificationRecipient $recipient): RedirectResponse
    {
        $recipient->delete();

        return back()->with('success', 'Notification recipient removed.');
    }
}
