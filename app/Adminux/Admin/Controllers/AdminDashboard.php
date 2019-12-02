<?php

namespace App\Adminux\Admin\Controllers;

use App\Http\Controllers\Controller;

class AdminDashboard extends Controller
{
    /**
     * Display admin dashboard
     *
     * @return \Illuminate\Http\Response
     */
    public function dashboard()
    {
        return view('adminux.dashboard');
    }
}
