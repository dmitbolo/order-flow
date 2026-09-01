<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    protected function perPage(Request $request, int $default): int
    {
        return max(1, min((int) $request->input('per_page', $default), 100));
    }
}
