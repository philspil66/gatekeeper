<?php

namespace Gatekeeper\Commands;

use Illuminate\Console\Command;
use Gatekeeper\Facade\Gatekeeper;

class AddFeature extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gatekeeper:add {feature}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adds a specified feature flag';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws \ErrorException
     */
    public function handle()
    {
        $feature = $this->argument('feature');

        if (is_null($feature) || is_array($feature)) {
            throw new \ErrorException('Feature argument must be a string');
        }

        Gatekeeper::add($feature);

        $this->line(
            sprintf(
                'Feature `%s` has been added',
                $feature
            )
        );
    }
}
