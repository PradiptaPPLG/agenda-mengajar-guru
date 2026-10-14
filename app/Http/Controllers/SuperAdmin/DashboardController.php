<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return app(AdminDashboardController::class)->index();
    }
}
