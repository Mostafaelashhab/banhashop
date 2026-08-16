<?php

namespace Database\Factories;

use App\Enums\SellerStatus;
use App\Enums\UserRole;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Seller> */
class SellerFactory extends Factory
{
    protected $model = Seller::class;

    public function definition(): array
    {
        $name = 'متجر '.$this->faker->unique()->word();

        return [
            'user_id' => User::factory()->state(['role' => UserRole::Seller]),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(6),
            'status' => SellerStatus::Active,
            'is_verified' => false,
        ];
    }

    public function suspended(): static
    {
        return $this->state(['status' => SellerStatus::Suspended]);
    }
}
