<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;

class ProjectMemberController extends Controller
{
    public function store(Request $request, Project $project)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|string|max:255',
        ]);

        // Prevent duplicate attachment
        if (!$project->members()->where('user_id', $validated['user_id'])->exists()) {
            $project->members()->attach($validated['user_id'], ['role' => $validated['role']]);
            return back()->with('success', 'Member added successfully.');
        }

        return back()->with('error', 'User is already a member of this project.');
    }

    public function destroy(Project $project, User $user)
    {
        $project->members()->detach($user->id);
        return back()->with('success', 'Member removed successfully.');
    }
}
