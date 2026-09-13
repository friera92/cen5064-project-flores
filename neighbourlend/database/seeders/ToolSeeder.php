<?php

namespace Database\Seeders;

use App\Domain\Models\Tool;
use App\Domain\Models\User;
use App\Domain\States\ToolCondition;
use App\Domain\States\ToolStatus;
use Illuminate\Database\Seeder;

class ToolSeeder extends Seeder
{
    public function run(): void
    {
        $carlos = User::where('email', 'carlos@example.com')->first();
        $maria  = User::where('email', 'maria@example.com')->first();

        $tools = [
            [
                'owner_id'            => $carlos->id,
                'category_id'         => 1, // Power Tools
                'title'               => 'DeWalt 20V Max Brushless Cordless Drill',
                'description'         => 'Includes 2 batteries, charger, and standard driver bits.',
                'daily_rate'          => 15.00,
                'availability_status' => ToolStatus::AVAILABLE->value,
                'condition'           => ToolCondition::NEW->value,
                'picture'             => 'https://placehold.co/600x400?text=DeWalt+Drill',
            ],
            [
                'owner_id'            => $carlos->id,
                'category_id'         => 4, // Heavy Equipment
                'title'               => 'Simpson 3400 PSI Gas Pressure Washer',
                'description'         => 'High-pressure surface cleaner attachment included. Uses unleaded fuel.',
                'daily_rate'          => 45.00,
                'availability_status' => ToolStatus::AVAILABLE->value,
                'condition'           => ToolCondition::GOOD->value,
                'picture'             => 'https://placehold.co/600x400?text=Pressure+Washer',
            ],
            [
                'owner_id'            => $maria->id,
                'category_id'         => 2, // Lawn & Garden
                'title'               => 'Stihl MS 170 Gas Chainsaw',
                'description'         => '16-inch bar. Chain oil provided. Eye protection required.',
                'daily_rate'          => 30.00,
                'availability_status' => ToolStatus::IN_USE->value,
                'condition'           => ToolCondition::GOOD->value,
                'picture'             => 'https://placehold.co/600x400?text=Stihl+Chainsaw',
            ],
            [
                'owner_id'            => $maria->id,
                'category_id'         => 1, // Power Tools
                'title'               => 'Makita 7-1/4 Circular Saw',
                'description'         => 'Corded heavy-duty saw with 24T carbide tipped framing blade.',
                'daily_rate'          => 20.00,
                'availability_status' => ToolStatus::AVAILABLE->value,
                'condition'           => ToolCondition::FAIR->value,
                'picture'             => 'https://placehold.co/600x400?text=Makita+Saw',
            ],
        ];

        foreach ($tools as $data) {
            Tool::updateOrCreate(
                ['title' => $data['title'], 'owner_id' => $data['owner_id']],
                $data
            );
        }
    }
}
