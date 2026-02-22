<?php

namespace App\Http\Controllers;

use App\Enums\NotificationEventType;
use App\Http\Requests\StoreNotificationRecipientRequest;
use App\Http\Requests\UpdateNotificationRecipientRequest;
use App\Models\NotificationRecipient;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class NotificationRecipientController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $storeId = $request->query('store_id', 'all');
        $eventType = $request->query('event_type', NotificationEventType::Stocktake->value);

        $stores = Store::select('id', 'store_name', 'store_code')
            ->orderBy('store_name')
            ->get();

        $eventTypes = collect(NotificationEventType::cases())->map(fn ($type) => [
            'value' => $type->value,
            'label' => $type->label(),
        ]);

        $query = NotificationRecipient::where('event_type', $eventType)
            ->with('store:id,store_name,store_code');

        if ($storeId && $storeId !== 'all') {
            $query->where('store_id', $storeId);
        }

        $recipients = $query->orderBy('name')
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'event_type' => $r->event_type->value,
                'store_id' => $r->store_id,
                'store_name' => $r->store?->store_name,
                'store_code' => $r->store?->store_code,
                'email' => $r->email,
                'name' => $r->name,
                'is_active' => $r->is_active,
            ]);

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

    public function batchStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'event_type' => ['required', Rule::enum(NotificationEventType::class)],
            'store_ids' => ['required', 'array', 'min:1'],
            'store_ids.*' => ['required', 'exists:stores,id'],
            'email' => ['required', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $created = 0;
        foreach ($validated['store_ids'] as $storeId) {
            $recipient = NotificationRecipient::firstOrCreate(
                [
                    'event_type' => $validated['event_type'],
                    'store_id' => $storeId,
                    'email' => $validated['email'],
                ],
                [
                    'name' => $validated['name'] ?? null,
                    'is_active' => true,
                ]
            );
            if ($recipient->wasRecentlyCreated) {
                $created++;
            }
        }

        return back()->with('success', "Recipient added to {$created} store(s).");
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
