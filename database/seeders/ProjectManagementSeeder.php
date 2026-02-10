<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use Carbon\Carbon;

class ProjectManagementSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first();
        if (!$admin) return;

       // Cleanup existing project
        $existing = Project::where('name', 'ERP SYSTEM CORE UPGRADE')->first();
        if ($existing) {
            $existing->tasks()->delete();
            $existing->members()->detach();
            $existing->delete();
        }

        $project = Project::create([
            'name' => 'ERP SYSTEM CORE UPGRADE',
            'description' => 'Upgrading the core ERP matrix to support multi-node synchronization and AI-driven procurement logic.',
            'start_date' => Carbon::now()->startOfMonth(),
            'end_date' => Carbon::now()->startOfMonth()->addMonth(), // 1 Month
            'status' => 'active',
            'manager_id' => $admin->id,
        ]);

        $tasks = [
            [
                'name' => 'DATABASE_MIGRATION',
                'description' => 'Refactoring legacy schemas to modern atomic structures.',
                'start_date_plan' => Carbon::now()->startOfMonth(),
                'end_date_plan' => Carbon::now()->startOfMonth()->addDays(7),
                'progress' => 100,
                'status' => 'completed',
                'priority' => 'high',
            ],
            [
                'name' => 'FRONTEND_REFINE',
                'description' => 'Implementing futuristic HUD design patterns across all modules.',
                'start_date_plan' => Carbon::now()->startOfMonth()->addDays(7),
                'end_date_plan' => Carbon::now()->startOfMonth()->addDays(14),
                'progress' => 60,
                'status' => 'in_progress',
                'priority' => 'medium',
            ],
            [
                'name' => 'AI_LOGIC_INTEGRATION',
                'description' => 'Connecting neural networks to procurement and sales forecasting.',
                'start_date_plan' => Carbon::now()->startOfMonth()->addDays(14),
                'end_date_plan' => Carbon::now()->startOfMonth()->addDays(21),
                'progress' => 20,
                'status' => 'active',
                'priority' => 'high',
            ],
            [
                'name' => 'SYSTEM_STRESS_TEST',
                'description' => 'Simulating 1M concurrent transactions on the matrix.',
                'start_date_plan' => Carbon::now()->startOfMonth()->addDays(21),
                'end_date_plan' => Carbon::now()->startOfMonth()->addDays(28),
                'progress' => 0,
                'status' => 'todo',
                'priority' => 'urgent',
            ],
        ];

        foreach ($tasks as $taskData) {
            $project->tasks()->create($taskData);
        }

        $project->members()->attach($admin->id, ['role' => 'owner']);
    }
}
