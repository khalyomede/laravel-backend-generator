<?php

namespace Khalyomede\LaravelBackendGenerator;

use Illuminate\Console\Command as BaseCommand;
use DB;
use PhpParser\ParserFactory;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\PrettyPrinter;

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

    private $startTimestamp;
    private $endTimestamp;
    private $elapsedTimeMilliseconds;
    private $doctrineConnection;
    private $schemaManager;
    private $tables;
    private $table;
    private $tableName;
    private $tablePrimaryKeysNames;
    private $tableForeignKeys;
    private $tableForeignKeysNames;
    private $isJoinTable;
    private $modelName;
    private $routesFilePath;
    private $routesFileContent;
    private $doesRoutesFileExists;
    private $routesFileIsWritable;
    private $larvel;
    private $laravelVersion;
    private $parser;
    private $abstractSyntaxticTree;
    private $nodeTraverser;
    private $resourceName;
    private $controllerName;
    private $prettyPrinter;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->startTimestamp = microtime(true);
        $this->endTimestamp = $this->startTimestamp;
        $this->elapsedTimeMilliseconds = 0;
        $this->doctrineConnection = null;
        $this->schemaManager = null;
        $this->tables = [];
        $this->table = null;
        $this->tableName = null;
        $this->tablePrimaryKeysNames = [];
        $this->tableForeignKeys = [];
        $this->tableForeignKeysNames = [];
        $this->isJoinTable = false;
        $this->modelName = null;
        $this->routesFilePath = null;
        $this->routesFIleContent = null;
        $this->doesRoutesFileExists = false;
        $this->routesFileIsWritable = false;
        $this->laravel = app();
        $this->laravelVersion = $this->laravel::VERSION;
        $this->parser = null;
        $this->abstractSyntaxticTree = null;
        $this->nodeTraverser = null;
        $this->resourceName = null;
        $this->controllerName = null;
        $this->prettyPrinter = null;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->setDoctrineConnection();
        $this->setSchemaManager();
        $this->setTables();
        $this->setRoutesFilePath();
        $this->setDoesRoutesFileExists();
        $this->setRoutesFileIsWritable();

        $this->doesRoutesFileExists or throwException('routes file does not exists');
        $this->routesFileIsWritable or throwException('routes file is already opened in another program or processus');

        $this->setRoutesFileContent();

        foreach( $this->tables as $this->table ) {
            $this->setTableName();
            $this->setTablePrimaryKeysNames();
            $this->setTableForeignKeys();
            $this->setTableForeignKeysNames();
            $this->setIsJoinTable();

            if( ! $this->isJoinTable ) {
                echo $this->tableName . PHP_EOL;
                $this->setModelName();
                $this->createModelFile();
                $this->updateRoutesFile();
            }
        }

        echo $this->routesFileContent;
    }

    private function setDoctrineConnection() {
        $this->doctrineConnection = DB::getDoctrineConnection();
    }

    private function setSchemaManager() {
        $this->schemaManager = $this->doctrineConnection->getSchemaManager();
    }

    private function setTables() {
        $this->tables = $this->schemaManager->listTables();
    }

    private function setTableName() {
        $this->tableName = $this->table->getName();
    }

    private function setTablePrimaryKeysNames() {
        $this->tablePrimaryKeysNames = [];

        $this->tablePrimaryKeysNames = $this->table->getPrimaryKey()->getColumns();
    }

    private function setTableForeignKeys() {
        $this->tableForeignKeys = [];

        $foreignKeys = $this->table->getForeignKeys();

        foreach( $foreignKeys as $foreignKey ) {
            $foreignTableName = $foreignKey->getForeignTableName();
            $foreignColumnName = $foreignKey->getForeignColumns()[0];
            $localColumnName = $foreignKey->getLocalColumns()[0];

            $this->tableForeignKeys[] = [
                'localColumnName' => $localColumnName,
                'foreignColumnName' => $foreignColumnName,
                'foreignTableName' => $foreignTableName
            ];
        }
    }

    private function setTableForeignKeysNames() {
        $this->tableForeignKeysNames = [];

        foreach( $this->tableForeignKeys as $foreignKey ) {
            $this->tableForeignKeysNames[] = $foreignKey['localColumnName'];
        }
    }

    private function setIsJoinTable() {
        $this->isJoinTable = array_intersect( $this->tablePrimaryKeysNames, $this->tableForeignKeysNames ) === $this->tablePrimaryKeysNames;
    }

    public function __destruct() {
        $this->endTimestamp = microtime(true);
        $this->elapsedTimeMilliseconds = floor(($this->endTimestamp - $this->startTimestamp) * 1000);

        $this->info('finished after ' . $this->elapsedTimeMilliseconds . ' ms');
    }

    public function setModelName() {
        $this->modelName = ucfirst(camel_case( $this->tableName ));
    }

    private function createModelFile() {
        $res = $this->call('make:model', [
            'name' => $this->modelName,
            '--quiet' => true,
            '--force' => true,
            '--controller' => true,
            '--resource' => true
        ]);
    }

    private function updateRoutesFile() {        
        $this->setResourceName();
        $this->setControllerName();
        $this->setParser();
        $this->setAbstractSyntaxicTree();
        $this->setPrettyPrinter();
        $this->setNodeTraverser();
        $this->setNodeTraverserVisitor();

        $stmts = $this->nodeTraverser->traverse( $this->abstractSyntaxticTree );

        $this->routesFileContent = $this->prettyPrinter->prettyPrintFile($stmts);
    }

    private function setRoutesFilePath() {
        if( version_compare($this->laravelVersion, '5.3.*', '>=') ) {
            $this->routesFilePath = base_path() . '/routes/web.php';
        }
        else {
            $this->routesFilePath = base_path() . '/app/Http/routes.php';
        }
    }

    private function setDoesRoutesFileExists() {
        $this->doesRoutesFileExists = file_exists( $this->routesFilePath );
    }

    private function setRoutesFileIsWritable() {
        $this->routesFileIsWritable = is_writable( $this->routesFilePath );
    }

    private function setRoutesFileContent() {
        $this->routesFileContent = file_get_contents( $this->routesFilePath );
    }

    private function setResourceName() {
        $this->resourceName = kebab_case(camel_case($this->tableName));
    }

    private function setControllerName() {
        $this->controllerName = $this->modelName . 'Controller';
    }

    private function setParser() {
        $this->parser = (new ParserFactory)->create( ParserFactory::PREFER_PHP7 );
    }

    private function setAbstractSyntaxicTree() {
        $this->abstractSyntaxticTree = $this->parser->parse( $this->routesFileContent );
    }

    private function setNodeTraverser() {
        $this->nodeTraverser = new NodeTraverser;
    }

    private function setPrettyPrinter() {
        $this->prettyPrinter = new PrettyPrinter\Standard;
    }

    private function setNodeTraverserVisitor() {
        $this->nodeTraverser->addVisitor( new NodeVisitorRoute( $this->resourceName, $this->controllerName ) );
    }

    function throwException( $message = '', $code = 0 ) {
        throw new Exception( $message, $code );

        return true;
    }
}

class NodeVisitorRoute extends NodeVisitorAbstract {
    private $resourceName;

    public function __construct( $resourceName, $controllerName ) {
        $this->resourceName = (string) $resourceName;
        $this->controllerName = (string) $controllerName;
    }

    public function beforeTraverse( array $nodes ) {
        $found = false;

        foreach( $nodes as $node ) {
            if( $node instanceof \PhpParser\Node\Expr\StaticCall && 
                $node->class == 'Route' && 
                $node->name == 'resource' &&
                isset( $node->args[0] ) &&
                $node->args[0] instanceof \PhpParser\Node\Arg &&
                $node->args[0]->value instanceof \PhpParser\Node\Scalar\String_ &&
                $node->args[0]->value->value == $this->resourceName &&
                isset( $node->args[1] ) &&
                $node->args[1] instanceof \PhpParser\Node\Arg &&
                $node->args[1]->value instanceof \PhpParser\Node\Scalar\String_ &&
                $node->args[1]->value->value == $this->controllerName ) {

                $found = true;

                break;
            }
        }

        if( ! $found ) {
            $nodes[] = new \PhpParser\Node\Expr\StaticCall( new \PhpParser\Node\Name('Route'), 'resource', [
                new \PhpParser\Node\Arg( new \PhpParser\Node\Scalar\String_( $this->resourceName ) ),
                new \PhpParser\Node\Arg( new \PhpParser\Node\Scalar\String_( $this->controllerName ) )
            ]);          
        }

        return $nodes;
    }
}
