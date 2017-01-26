<?php

use Illuminate\Database\Seeder;

class LoomResourcesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('loom_resources')->truncate();

        $namespace = Loom::getResourceNamespace();
        $resources = [
            "$namespace\\User",
            "$namespace\\LoomResource",
        ];
        foreach ($resources as $resource) {
            $option = app("$namespace\\LoomResource");
            $option->name = $resource;
            $option->url = Loom::getResourceUrl(class_basename($resource));
            $option->save();
        }
    }
}
