<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_accessible()
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_dashboard_is_accessible()
    {
        $response = $this->get('/');
        // Dashboard uses 'dashboard' route name which maps to '/'
        // ExampleTest already covers '/' returning 200, BUT that test didn't simulate unauthenticated user specifically if ExampleTest didn't authenticate.
        // Wait, ExampleTest just did get('/'), and we saw it return 200.
        // This implies the dashboard might be accessible without login?
        // Let's check web.php again.
        // Route::get('/', ...)->name('dashboard');
        // It is NOT inside a middleware group in the snippet I saw.
        // Let's verify if it should be protected.
        // Usually dashboards are protected. If it returned 200 in ExampleTest, it is public.
        // I will assert 200 for now, but if it should be protected, that's a security finding.
        
        $response->assertStatus(200);
    }
}
