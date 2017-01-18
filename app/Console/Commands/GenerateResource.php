<?php

namespace App\Console\Commands;

use Closure;
use Illuminate\Console\Command;

class GenerateResource extends Command
{

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Interactive command to build a Jumbled or Eloquent repository.';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:resource';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $resourceName = $this->getInput('What is the name for the new resource?', true, function ($answer) {
            if (empty($answer)) {
                $this->error('You must enter a name for the new resource.');
                return false;
            }
            return true;
        });

        $resourceGroup = $this->getInput('Under what grouping (if any) will this resource reside? (blank if none) ',
            true, function ($answer) {
                if (empty($answer)) {
                    return true;
                }
                $directory = app_path(app_path('Resources/') . $answer);
                if (is_dir($directory)) {
                    return true;
                } elseif ($this->confirm('That grouping does not exist.  Would you like to create it? [yes|no]')) {
                    if (mkdir($directory)) {
                        return true;
                    } else {
                        $this->error('There was a problem creating that grouping.');
                        return false;
                    }
                } else {
                    return false;
                }
            });

        $this->info("Building a resource named $resourceName" . $resourceGroup ? " within the $resourceGroup group." : '.');

        $this->createEloquentModel($resourceName, $resourceGroup);
        $this->createResourceController($resourceName, $resourceGroup);

        return true;
    }

    protected function getInput($question, $die_on_failure, Closure $callback)
    {
        do {
            $answer = $this->ask($question);
            if ($callback($answer)) {
                return $answer;
            }
        } while ($this->confirm('Would you like to try again? [yes|no]'));

        if ($die_on_failure) {
            exit;
        }
        return false;
    }

    private function createEloquentModel($name, $group)
    {
        $this->info('Building Eloquent model');

        $data = [
            'name' => $name,
            'group' => $group ? '\\' . $group : '',
        ];
        $content = view('commands.generate-resource.eloquent-model', $data)->__toString();

        $file = $name . '.php';
        if ($group) {
            $file = "$group/$file";
        }

        if (!file_put_contents(app_path('Resources/' . $file), $content)) {
            $this->error('There was an error building the eloquent model.');
            return false;
        }

        return true;
    }

    private function createResourceController($name, $group)
    {
        $this->info('Building resource controller');

        $data = [
            'name' => $name,
            'group' => $group ? '\\' . $group : '',
        ];
        $content = view('commands.generate-resource.resource-controller', $data)->__toString();

        $file = $name . '.php';
        if ($group) {
            $file = "$group/$file";
        }

        if (!file_put_contents(app_path('Http/Controllers/Resources/' . $file), $content)) {
            $this->error('There was an error building the resource controller.');
            return false;
        }

        return true;
    }

}