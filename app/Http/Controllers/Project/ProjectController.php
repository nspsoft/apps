<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::with(['manager', 'members'])->get()->map(function ($project) {
            $project->progress = $project->progress; // Trigger accessor
            return $project;
        });

        return Inertia::render('Project/Index', [
            'projects' => $projects
        ]);
    }

    public function create()
    {
        return Inertia::render('Project/Create', [
            'users' => User::all(['id', 'name'])
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'manager_id' => 'required|exists:users,id',
            'status' => 'required|string',
        ]);

        Project::create($validated);

        return redirect()->route('projects.index')->with('success', 'Project created successfully.');
    }

    public function show(Project $project)
    {
        $project->load(['manager', 'members', 'tasks.members', 'tasks.attachments.uploader']);
        
        return Inertia::render('Project/Show', [
            'project' => $project,
            'users' => User::all(['id', 'name'])
        ]);
    }

    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'manager_id' => 'required|exists:users,id',
            'status' => 'required|string',
        ]);

        $project->update($validated);

        return back()->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project)
    {
        $project->delete();
        return redirect()->route('projects.index')->with('success', 'Project deleted successfully.');
    }
}
