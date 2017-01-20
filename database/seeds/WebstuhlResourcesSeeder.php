<?php

use Illuminate\Database\Seeder;

class WebstuhlResourcesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('webstuhl_resources')->truncate();

        $resources = [
            \App\Resources\User::class,
            \App\Resources\WebstuhlResource::class
        ];
        foreach ($resources as $resource) {
            $option = new \App\Resources\WebstuhlResource();
            $option->name = $resource;
            $option->save();
        }
    }
}
