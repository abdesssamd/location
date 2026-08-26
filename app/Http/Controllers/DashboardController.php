<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Routing\Controller;

class DashboardController extends Controller
{
    public function __invoke()
    {
        if (auth()->user()->is_super_admin) {
            return redirect()->route('admin.index');
        }

        $stats = [
            'products' => Product::count(),
        ];

        return view('dashboard', compact('stats'));
    }
}