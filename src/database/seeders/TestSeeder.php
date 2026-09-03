<?php

namespace Database\Seeders;

use App\Models\UserLoginHistory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TestSeeder extends Seeder
{
    // docker compose exec app php artisan db:seed --class=TestSeeder
    public function run(): void
    {
        // Just for demonstrating login OTP
        UserLoginHistory::truncate();
    }
}
