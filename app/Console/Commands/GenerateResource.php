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
    protected $description = 'Interactive command to build a Loom resource.';

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
        $resourceName = $this->getInput(trans('commands/generate-resource.ask-for-name'), function ($answer) {
            if (empty($answer)) {
                $this->error(trans('commands/generate-resource.ask-for-name-error'));
                return false;
            }
            if (!preg_match('/^[A-Z]\w*$/', $answer)) {
                $this->error(trans('commands/generate-resource.name-validation-error'));
                return false;
            }
            return true;
        });

        if ($resourceName === false) {
            return;
        }

        $resourceGroup = $this->getInput(trans('commands/generate-resource.ask-for-group'), function ($answer) {
                if ($answer === '_') {
                    return true;
                }
                if (!preg_match('/^[A-Z]\w*$/', $answer)) {
                    $this->error(trans('commands/generate-resource.group-validation-error'));
                    return false;
                }
                $directory = \Loom::getResourceBasePath($answer);
                if (is_dir($directory)) {
                    return true;
                } elseif ($this->confirm(trans('commands/generate-resource.group-confirm-create'))) {
                    return true;
                } else {
                    return false;
                }
            });

        if ($resourceGroup === false) {
            return;
        } elseif ($resourceGroup === '_') {
            $resourceGroup = null;
        }

        if (\Loom::resourceExists($resourceName, $resourceGroup)) {
            $this->error(trans('commands/generate-resource.resource-exists', ['group' => $resourceGroup ? ' and group' : '']));
            return;
        }

        if (\Loom::resourceControllerExists($resourceName, $resourceGroup)) {
            $this->error(trans('commands/generate-resource.resource-controller-exists', ['group' => $resourceGroup ? ' and group' : '']));
            return;
        }

        $this->info(trans('commands/generate-resource.building-resource', [
            'name' => $resourceName,
            'group' => $resourceGroup
                ? ' ' . trans('commands/generate-resource.within-group', ['group' => $resourceGroup])
                : '',
        ]));

        if (!\Loom::createResource($resourceName, $resourceGroup)) {
            $this->error(trans('commands/generate-resource.resource-error'));
        } elseif (!\Loom::createResourceController($resourceName, $resourceGroup)) {
            $this->error(trans('commands/generate-resource.resource-controller-error'));
        }

        return;
    }

    protected function getInput($question, Closure $callback)
    {
        do {
            $answer = $this->ask($question);
            if ($callback($answer)) {
                return $answer;
            }
        } while ($answer !== false && $this->confirm(trans('commands/generate-resource.try-again')));

        return false;
    }
}