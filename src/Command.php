<?php

namespace Khalyomede\LaravelBackendGenerator;

use Illuminate\Console\Command as BaseCommand;
use DB;

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
        $tables = DB::getDoctrineConnection()->getSchemaManager()->listTables();

        foreach( $tables as $table ) {
            $primaryKeys = $table->getPrimaryKey()->getColumns();
            $foreignKeys = [];
            
            foreach( $table->getForeignKeys() as $foreignKey ) {
                $foreignKeys[] = $foreignKey->getColumns()[0];
            }

            if( array_intersect($primaryKeys, $foreignKeys) == $primaryKeys) ) {
                // pivot table
            }
            else {
                $this->info(sprintf("table %s : creating model...", $table->getName()));

                $name = ucfirst(preg_replace('/\s/', '', ucwords(preg_replace('/_/', ' ', $table->getName()))));

                // $this->call('make:model', [
                //     'name' => $name,
                //     '--force' => true,
                //     '--quiet' => true
                // ]);

                $this->info(sprintf("table %s : creating controller...", $table->getName()));

                // $this->call('make:controller', [
                //     'name' => $name . 'Controller',
                //     '--model' => $name,
                //     '--resource' => true,
                //     '--quiet' => true
                // ]);
            }
        }
    }
}
