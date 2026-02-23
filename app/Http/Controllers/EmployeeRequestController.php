<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitEmployeeRequestRequest;
use App\Models\Country;
use App\Models\SiteSetting;
use App\Services\EmployeeRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class EmployeeRequestController extends Controller
{
    public function __construct(
        private readonly EmployeeRequestService $service
    ) {}

    public function show(Request $request): InertiaResponse|\Illuminate\Http\RedirectResponse
    {
        $enabled = SiteSetting::get('signup_enabled', '0');

        if ($enabled !== '1') {
            return Inertia::render('EmployeeRequests/Apply', [
                'enabled' => false,
                'requiresCode' => false,
                'codeVerified' => false,
                'countries' => [],
            ]);
        }

        $accessCode = SiteSetting::get('signup_access_code');
        $codeVerified = ! $accessCode || $request->session()->get('employee_request_access_verified', false);

        return Inertia::render('EmployeeRequests/Apply', [
            'enabled' => true,
            'requiresCode' => (bool) $accessCode,
            'codeVerified' => $codeVerified,
            'countries' => $codeVerified
                ? Country::active()->ordered()->get(['id', 'name', 'nationality_name'])
                : [],
        ]);
    }

    public function verifyCode(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'access_code' => ['required', 'string'],
        ]);

        $storedHash = SiteSetting::get('signup_access_code');

        if (! $storedHash || ! Hash::check($request->input('access_code'), $storedHash)) {
            return back()->withErrors(['access_code' => 'Invalid access code.']);
        }

        $request->session()->put('employee_request_access_verified', true);

        return redirect('/apply');
    }

    public function store(SubmitEmployeeRequestRequest $request): \Illuminate\Http\RedirectResponse
    {
        $enabled = SiteSetting::get('signup_enabled', '0');

        if ($enabled !== '1') {
            abort(403, 'Applications are currently disabled.');
        }

        $data = $request->validated();

        // Handle profile picture upload
        if ($request->hasFile('profile_picture')) {
            $data['profile_picture'] = $request->file('profile_picture')
                ->store('employee-requests/photos', 'local');
        }

        $this->service->submit($data);

        return back()->with('success', true);
    }
}
