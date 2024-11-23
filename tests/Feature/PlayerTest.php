<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Position;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\InitUser;
use Tests\TestCase;

class PlayerTest extends TestCase
{
    use RefreshDatabase, InitUser;

    public function test_player_store(): void
    {
        $this->initUser();
        Storage::fake('public');
        $image = UploadedFile::fake()->image('logo-test.jpg')->mimeType('image/jpeg');
        $category = Category::factory()->create();
        $team = Team::factory()->create();
        $team->categories()->attach($category->id);
        $team->save();
        $response = $this->json('POST', '/api/v1/admin/players', [
            'basic' => [
                'name' => fake()->name,
                'last_name' => fake()->lastName,
                'birthdate' => fake()->date,
                'nationality' => fake()->country,
                'team_id' => $team->id,
                'category_id' => $team->category()->first()->id,
                'image' => $image,
            ],
            'details' => [
                'position_id' => Position::first()->id,
                'number' => fake()->randomNumber([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]),
                'height' => fake()->numberBetween(160, 210),
                'weight' => fake()->numberBetween(60, 140),
                'dominant_foot' => fake()->randomElement(['derecha', 'izquierda']),
                'medical_notes' => fake()->text,
            ],
            'contact' => [
                'phone' => fake()->phoneNumber,
                'email' => fake()->email,
                'notes' => fake()->text,
            ],
        ]);
        $response->assertStatus(201);
    }
}
