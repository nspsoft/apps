<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectTask;
use Illuminate\Http\Request;

class ProjectTaskController extends Controller
{
    public function store(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date_plan' => 'nullable|date',
            'end_date_plan' => 'nullable|date',
            'priority' => 'required|string',
            'status' => 'required|string',
            'progress' => 'nullable|numeric|min:0|max:100',
            'parent_id' => 'nullable|exists:project_tasks,id',
            'members' => 'nullable|array',
            'members.*' => 'exists:users,id',
        ]);

        $task = $project->tasks()->create(\Illuminate\Support\Arr::except($validated, ['members']));

        if ($request->has('members')) {
            $task->members()->sync($request->members);
        }

        return back()->with('success', 'Task created successfully.');
    }

    public function update(Request $request, ProjectTask $task)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date_plan' => 'nullable|date',
            'end_date_plan' => 'nullable|date',
            'start_date_actual' => 'nullable|date',
            'end_date_actual' => 'nullable|date',
            'progress' => 'required|numeric|min:0|max:100',
            'status' => 'required|string',
            'priority' => 'required|string',
            'members' => 'nullable|array',
            'members.*' => 'exists:users,id',
        ]);

        $task->update(\Illuminate\Support\Arr::except($validated, ['members']));

        if ($request->has('members')) {
            $task->members()->sync($request->members);
        }

        return back()->with('success', 'Task updated successfully.');
    }

    public function destroy(ProjectTask $task)
    {
        $task->delete();
        return back()->with('success', 'Task deleted successfully.');
    }

    public function storeAttachment(Request $request, ProjectTask $task)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
        ]);

        $file = $request->file('file');
        $path = $file->store('project-attachments', 'public');

        $task->attachments()->create([
            'user_id' => auth()->id(),
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);

        return back()->with('success', 'File uploaded successfully.');
    }

    public function destroyAttachment(\App\Models\ProjectTaskAttachment $attachment)
    {
        // Optional: Check if user is authorized to delete
        // if ($attachment->user_id !== auth()->id()) abort(403);

        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($attachment->file_path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($attachment->file_path);
        }
        
        $attachment->delete();
        return back()->with('success', 'Attachment removed.');
    }
}
