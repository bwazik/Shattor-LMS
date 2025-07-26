<?php

namespace Database\Seeders;

use App\Models\Teacher;
use App\Traits\Truncatable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class TeacherSeeder extends Seeder
{
    use Truncatable;

    public function run()
    {
        $this->truncateTables(['teachers']);

        $fakerEn = Faker::create('en_US');
        $fakerAr = Faker::create('ar_SA');

        for ($i = 0; $i < 15; $i++) {
            Teacher::create([
                'username' => $fakerEn->unique()->userName,
                'password' => Hash::make('123456789'),
                'name' => ['en' => $fakerEn -> name, 'ar' => $fakerAr -> name],
                'phone' => '01' . $fakerEn->randomElement([0, 1, 2, 5]) . $fakerEn->numerify('########'),
                'email' => $fakerEn->unique()->safeEmail,
                'subject_id' => rand(1, 14),
                'plan_id' => null,
                'is_active' => $fakerEn->boolean(75),
                'average_rating' => $fakerEn->randomFloat(2, 0, 10),
                'balance' => $fakerEn->randomFloat(2, 0, 1000),
            ]);
        }
    }
}
