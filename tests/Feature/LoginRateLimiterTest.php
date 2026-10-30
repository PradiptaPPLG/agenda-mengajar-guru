<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginRateLimiterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('test@sekolah.sch.id|127.0.0.1');
    }

    public function test_login_throttles_after_five_failed_attempts(): void
    {
        $user = User::factory()->create([
            'email' => 'test@sekolah.sch.id',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        // 5 percobaan salah
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post(route('login.post'), [
                'identifier' => 'test@sekolah.sch.id',
                'password' => 'wrongpassword',
            ]);
            $response->assertSessionHasErrors('identifier');
        }

        // Percobaan ke-6 harus ditolak oleh RateLimiter
        $responseSixth = $this->post(route('login.post'), [
            'identifier' => 'test@sekolah.sch.id',
            'password' => 'wrongpassword',
        ]);

        $responseSixth->assertSessionHasErrors([
            'identifier' => fn ($message) => str_contains((string) $message, 'Terlalu banyak percobaan login'),
        ]);
    }

    public function test_successful_login_clears_rate_limiter(): void
    {
        $user = User::factory()->create([
            'email' => 'test@sekolah.sch.id',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Gagal 2 kali
        for ($i = 0; $i < 2; $i++) {
            $this->post(route('login.post'), [
                'identifier' => 'test@sekolah.sch.id',
                'password' => 'wrongpassword',
            ]);
        }

        // Login sukses
        $response = $this->post(route('login.post'), [
            'identifier' => 'test@sekolah.sch.id',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
    }
}
