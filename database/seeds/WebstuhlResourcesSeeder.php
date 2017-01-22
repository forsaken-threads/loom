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

        $namespace = Webstuhl::getResourceNamespace();
        $resources = [
            "$namespace\\User",
            "$namespace\\WebstuhlResource",
        ];
        foreach ($resources as $resource) {
            $option = app("$namespace\\WebstuhlResource");
            $option->name = $resource;
            $option->url = Webstuhl::getResourceUrl(class_basename($resource));
            $option->save();
        }
    }
}
