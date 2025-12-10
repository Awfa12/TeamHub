<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Channel;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'channel_id' => Channel::factory(),
            'user_id' => User::factory(),
            'body' => $this->faker->sentence(),
            'file_name' => null,
            'file_path' => null,
            'file_size' => null,
            'edited_at' => null,
        ];
    }
}
