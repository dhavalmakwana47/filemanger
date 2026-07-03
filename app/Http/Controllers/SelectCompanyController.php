<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SelectCompanyController extends Controller
{
    public function show()
    {
        $companies = auth()->user()->companies()->get();

        if ($companies->count() <= 1) {
            return redirect()->route('dashboard');
        }

        return view('auth.select-company', compact('companies'));
    }

    public function store(Request $request)
    {
        $request->validate(['company_id' => 'required|integer']);

        $company = auth()->user()->companies()->find($request->company_id);

        if (! $company) {
            return back()->withErrors(['company_id' => 'Invalid company selected.']);
        }

        session(['active_company' => $company->id]);

        return redirect()->intended(route('dashboard'));
    }
}
