<?php
namespace Database\Factories;

use App\Models\masterData\ProductName\ProductNameModel;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductNameModelFactory extends Factory
{
    protected $model = ProductNameModel::class;

    public function definition(): array
    {
         return [
            'name' => $this->faker->word,
            'shortName' => strtoupper($this->faker->lexify('???')),
            'productType' => $this->faker->randomElement(['TypeA', 'TypeB', 'TypeC']),
            'active' => $this->faker->boolean(),
            'code' => 'PRD' . $this->faker->unique()->numberBetween(100000, 999999),
        ];
    }
}
?>