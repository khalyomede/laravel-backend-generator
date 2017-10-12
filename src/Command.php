<?php

namespace Khalyomede\LaravelBackendGenerator;

use Illuminate\Console\Command as BaseCommand;

class Command extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backend:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Database first model/controller/route generator';

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
     */
    public function handle()
    {
        //
    }
}
