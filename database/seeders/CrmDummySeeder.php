<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CRM\Lead;
use App\Models\CRM\Opportunity;
use App\Models\CRM\Campaign;
use Carbon\Carbon;

class CrmDummySeeder extends Seeder
{
    public function run()
    {
        // 1. Create Campaigns
        $campaigns = [
            ['name' => 'Q1 Growth Burst', 'type' => 'email', 'status' => 'active', 'budget' => 50000000, 'start' => Carbon::now()->subDays(15), 'end' => Carbon::now()->addDays(15)],
            ['name' => 'Tech Expo 2026', 'type' => 'event', 'status' => 'planned', 'budget' => 120000000, 'start' => Carbon::now()->addDays(20), 'end' => Carbon::now()->addDays(23)],
            ['name' => 'Social Media Outreach', 'type' => 'social', 'status' => 'active', 'budget' => 15000000, 'start' => Carbon::now()->subDays(45), 'end' => Carbon::now()->addDays(45)],
            ['name' => 'Customer Loyalty Program', 'type' => 'email', 'status' => 'completed', 'budget' => 25000000, 'start' => Carbon::now()->subDays(60), 'end' => Carbon::now()->subDays(10)],
        ];

        foreach ($campaigns as $camp) {
            Campaign::firstOrCreate(
                ['name' => $camp['name']],
                [
                    'type' => $camp['type'],
                    'status' => $camp['status'],
                    'budget' => $camp['budget'],
                    'start_date' => $camp['start'],
                    'end_date' => $camp['end'],
                ]
            );
        }

        // 2. Create Leads
        $sources = ['LinkedIn', 'Website', 'Referral', 'Cold Call', 'Exhibition'];
        $statuses = ['new', 'contacted', 'qualified', 'lost'];
        $companies = [
            'Stellar Dynamics', 'Quantum Industries', 'Cybernetic Solutions', 'Aethelred Systems', 
            'Nebula Corp', 'Titan Construction', 'Omega Logistics', 'Vanguard Energy',
            'Blue Horizon Tech', 'Apex Manufacturing', 'Orion Group', 'Zenith Global'
        ];

        for ($i = 0; $i < 50; $i++) {
            Lead::create([
                'name' => 'Contact ' . fake()->name(),
                'company' => fake()->randomElement($companies) . ' ' . fake()->suffix(),
                'email' => fake()->email(),
                'phone' => fake()->phoneNumber(),
                'status' => fake()->randomElement($statuses),
                'source' => fake()->randomElement($sources),
                'created_at' => fake()->dateTimeBetween('-3 months', 'now'),
            ]);
        }

        // 3. Create Opportunities (linked to Leads)
        $leads = Lead::where('status', '!=', 'lost')->get();
        $stages = ['prospecting', 'negotiation', 'closed_won', 'closed_lost'];

        foreach ($leads as $lead) {
            // Not all leads become opportunities
            if (rand(0, 100) > 30) { 
                $stage = fake()->randomElement($stages);
                $amount = rand(10, 500) * 1000000; // 10jt to 500jt
                
                // Probability logic based on stage
                $prob = match($stage) {
                    'prospecting' => rand(10, 30),
                    'negotiation' => rand(40, 80),
                    'closed_won' => 100,
                    'closed_lost' => 0,
                };

                Opportunity::create([
                    'name' => 'Deal for ' . $lead->company,
                    'lead_id' => $lead->id,
                    'amount' => $amount,
                    'stage' => $stage,
                    'probability' => $prob,
                    'close_date' => fake()->dateTimeBetween('now', '+3 months'),
                    'created_at' => fake()->dateTimeBetween('-2 months', 'now'),
                ]);
            }
        }
    }
}
