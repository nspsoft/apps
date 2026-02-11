<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class BlueprintController extends Controller
{
    public function index()
    {
        return Inertia::render('Project/Blueprint/Index', [
            'project' => \App\Models\Project::first(), // Assuming single project context for now
        ]);
    }
}
