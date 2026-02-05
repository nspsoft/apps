<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class PortalHelpController extends Controller
{
    public function index()
    {
        return Inertia::render('Portal/Help/Index');
    }
}
