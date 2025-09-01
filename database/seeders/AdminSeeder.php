<?php

namespace Database\Seeders;

use App\Models\User;
use App\Traits\Truncatable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    use Truncatable;

    public function run(): void
    {
        $this->truncateTables(['users']);

        User::create([
            'username' => 'bwazikdeveloper',
            'name' => ['en' => 'Abdullah Mohamed Fathy', 'ar' => 'عبدالله محمد فتحي'],
            'phone' => '01098617164',
            'email' => 'bwazik@outlook.com',
            'password' => Hash::make('125.5from140@aA'),
        ]);
    }
}
