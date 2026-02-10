<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;

class SalesModuleImplementationSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first();
        if (!$admin) return;

        // Cleanup existing project to avoid duplicates
        $existing = Project::where('name', 'SALES MODULE OVERHAUL')->first();
        if ($existing) {
            $existing->tasks()->delete();
            $existing->members()->detach();
            $existing->delete();
        }

        $project = Project::create([
            'name' => 'SALES MODULE OVERHAUL',
            'description' => 'End-to-end implementation of the new Sales CRM, Quotation system, and Order processing matrix. Focusing on high-velocity transaction handling.',
            'start_date' => Carbon::now()->subDays(7),
            'end_date' => Carbon::now()->addDays(23), // Total 1 month
            'status' => 'in_progress',
            'manager_id' => $admin->id,
        ]);

        $tasks = [
            [
                'name' => 'REQUIREMENTS_ANALYSIS',
                'description' => 'Mapping out current sales workflows and identifying bottlenecks in the legacy system.',
                'start_date_plan' => Carbon::now()->subDays(7),
                'end_date_plan' => Carbon::now()->subDays(2),
                'start_date_actual' => Carbon::now()->subDays(7),
                'end_date_actual' => Carbon::now()->subDays(3),
                'progress' => 100,
                'status' => 'completed',
                'priority' => 'high',
            ],
            [
                'name' => 'DATABASE_SCHEMA_V2',
                'description' => 'Designing the new schema for quotations, orders, and customer interactions.',
                'start_date_plan' => Carbon::now()->subDays(2),
                'end_date_plan' => Carbon::now()->addDays(5),
                'start_date_actual' => Carbon::now()->subDays(2),
                'progress' => 90,
                'status' => 'in_progress',
                'priority' => 'urgent',
            ],
            [
                'name' => 'API_GATEWAY_DEV',
                'description' => 'Building restful endpoints for mobile app integration and external partners.',
                'start_date_plan' => Carbon::now()->addDays(5),
                'end_date_plan' => Carbon::now()->addDays(12),
                'progress' => 15,
                'status' => 'active',
                'priority' => 'high',
            ],
            [
                'name' => 'UI_HUD_DESIGN',
                'description' => 'Implementing the new neon-glassmorphism design language for the Sales Dashboard.',
                'start_date_plan' => Carbon::now()->addDays(12),
                'end_date_plan' => Carbon::now()->addDays(18),
                'progress' => 0,
                'status' => 'todo',
                'priority' => 'medium',
            ],
            [
                'name' => 'UAT_STRESS_TEST',
                'description' => 'User Acceptance Testing with 50 concurrent sales agents.',
                'start_date_plan' => Carbon::now()->addDays(19),
                'end_date_plan' => Carbon::now()->addDays(23),
                'progress' => 0,
                'status' => 'todo',
                'priority' => 'high',
            ]
        ];

        foreach ($tasks as $taskData) {
            $project->tasks()->create($taskData);
        }

        $project->members()->attach($admin->id, ['role' => 'Project Lead']);
        
        // Add some random members if available, otherwise just admin
        $randomUsers = User::where('id', '!=', $admin->id)->inRandomOrder()->limit(3)->get();
        foreach ($randomUsers as $user) {
            $roles = ['Frontend Dev', 'Backend Dev', 'QA Specialist', 'System Architect'];
            $project->members()->attach($user->id, ['role' => $roles[array_rand($roles)]]);
        }
    }
}
