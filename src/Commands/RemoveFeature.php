<?php

namespace Gatekeeper\Commands;

use Illuminate\Console\Command;
use Gatekeeper\Facade\Gatekeeper;

class RemoveFeature extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gatekeeper:remove {feature}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes a specified feature flag';

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

        Gatekeeper::remove($feature);

        $this->line(
            sprintf(
                'Feature `%s` has been removed',
                $feature
            )
        );
    }
}
