<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Set all existing and soft-deleted siswa emails to null
        User::withTrashed()->where('role', 'siswa')->update(['email' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: emails for students are intentionally not used
    }
};
