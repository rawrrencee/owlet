<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Employee;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $type = $request->query('type', 'employees');

        if ($type === 'customers') {
            $users = Customer::query()
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->paginate(15)
                ->withQueryString();
        } else {
            $users = Employee::query()
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->paginate(15)
                ->withQueryString();
        }

        return Inertia::render('Users/Index', [
            'users' => $users,
            'type' => $type,
        ]);
    }
}
