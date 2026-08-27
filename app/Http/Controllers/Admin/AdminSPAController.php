<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class AdminSPAController extends Controller
{
    /**
     * The admin single-page shell.
     *
     * Served through Blade rather than as a static file in public/, so it goes
     * through the same Vite pipeline as layouts/admin.blade.php and can never
     * drift from the design system again.
     */
    public function index(): View
    {
        return view('admin.spa');
    }
}
