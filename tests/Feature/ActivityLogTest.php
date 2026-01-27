<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_is_logged_when_product_is_created()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::factory()->create([
            'name' => 'Test Product',
        ]);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Product::class,
            'subject_id' => $product->id,
            'event' => 'created',
            'causer_id' => $user->id,
        ]);
    }

    public function test_activity_is_logged_when_product_is_updated()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::factory()->create([
            'name' => 'Old Name',
        ]);

        $product->update(['name' => 'New Name']);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Product::class,
            'subject_id' => $product->id,
            'event' => 'updated',
            'causer_id' => $user->id,
        ]);

        $log = Activity::where('subject_id', $product->id)
            ->where('event', 'updated')
            ->first();

        $this->assertArrayHasKey('name', $log->properties['attributes']);
        $this->assertEquals('New Name', $log->properties['attributes']['name']);
        
        // Note: 'old' key might not be present by default unless configured or using a specific driver/setting, 
        // but spatie/laravel-activitylog usually logs 'attributes' (new) and 'old' (old) if configured.
        // We configured ->logOnlyDirty(), so it should capture changes.
    }
}
