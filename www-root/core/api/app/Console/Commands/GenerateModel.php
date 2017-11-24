<?php namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use DB;

class GenerateModel extends Command {

	/**
     * The console command name.
     *
     * @var string
     */
	protected $name = 'generate:models';

	/**
     * The console command description.
     *
     * @var string
     */
	protected $description = 'Create Models based off of the db';

    protected $schema_name; // = 'olab_database';

	/**
     * Create a new command instance.
     *
     * @return void
     */
	public function __construct()
	{
		parent::__construct();
	}

    public function getArguments()
    {
        return array( array( 'schemaname', InputArgument::REQUIRED, "Schema name" ) );
    }

	/**
     * Execute the console command.
     *
     * @return mixed
     */
	public function fire()
	{
        $this->schema_name = $this->argument('schemaname');

        $model_dir = /*__DIR__ .*/  '/tmp/';
        $model_namespace = 'App\\Models\\Olab';
        $model_base = 'BaseModel';
        $model_base_namespace = 'App\\Models\\BaseModel';

        $all_tables = [];
        $tables = DB::connection($this->schema_name)->select('SHOW tables');
        foreach($tables as $table) {
            foreach($table as $key => $val) {
                $all_tables[] = $val;
            }
        }

        $migrate_key = array_search('migrations',$all_tables);
        if($migrate_key) {
            unset($all_tables[$migrate_key]);
        }

        foreach($all_tables as $table) {
            $newName = $this->modelName($table);

            $modelFile = $model_dir . $newName . '.php';
            if(!is_file($modelFile)) {
                $columns = DB::connection($this->schema_name)->select("DESCRIBE `{$table}`");
                $this->info($modelFile);

                $soft_delete = false;
                foreach($columns as $column) {
                    if($column->Field == 'deleted_at') {
                        $soft_delete = true;
                        break;
                    }
                }

                $content = "<?php
namespace {$model_namespace};

use {$model_base_namespace};
";

                if($soft_delete) {
                    $content .= "use Illuminate\Database\Eloquent\SoftDeletes;\n";
                }

                $content .= "
/**
";

                $column_names = [];
                $validations = "";

                $relationships = "";
                $db_name = DB::connection($this->schema_name)->select('SELECT DATABASE()');
                $schema = (array)$db_name[0];
                $schema_name = end($schema);

                //get all models that are connected
                $query = "select TABLE_NAME,COLUMN_NAME,CONSTRAINT_NAME, REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME
from INFORMATION_SCHEMA.KEY_COLUMN_USAGE
where REFERENCED_TABLE_NAME = '{$table}' and TABLE_SCHEMA='{$schema_name}'";
                $keys = DB::connection($this->schema_name)->select($query);
                foreach($keys as $key) {
                    if(!empty($key->TABLE_NAME)) {
                        $modelname = $this->modelName($key->TABLE_NAME);
                        $relationships .= "    public function {$modelname}() {\n";
                        $relationships .= "        return \$this->hasMany('{$model_namespace}\\{$modelname}');\n    }\n";


                    }
                }

                //get all models this connects to
                $query = "select TABLE_NAME,COLUMN_NAME,CONSTRAINT_NAME, REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME
from INFORMATION_SCHEMA.KEY_COLUMN_USAGE
where TABLE_NAME = '{$table}'";
                $keys = DB::connection($this->schema_name)->select($query);
                foreach($keys as $key) {
                    if(!empty($key->REFERENCED_TABLE_NAME)) {
                        $modelname = $this->modelName($key->REFERENCED_TABLE_NAME);
                        $relationships .= "    public function {$modelname}() {\n";
                        $relationships .= "        return \$this->belongsTo('{$model_namespace}\\{$modelname}');\n    }\n";

                        foreach($columns as $column) {
                            if($column->Field == $key->COLUMN_NAME) {
                                $column->validation = "exists:{$key->REFERENCED_TABLE_NAME},{$key->REFERENCED_COLUMN_NAME}";
                                break;
                            }
                        }
                    }
                }

                foreach($columns as $column) {
                    if($column->Field != 'created_at' && $column->Field != 'updated_at' && $column->Field != 'id' && $column->Field != 'deleted_at') {
                        $validators = [];
                        if(isset($column->validation)) {
                            $validators[] = $column->validation;
                        }
                        if(strpos($column->Type,'int(') !== false) {
                            $validators[] = 'integer';
                            if(strpos($column->Type,'unsigned') !== false) {
                                $validators[] = 'min:0';
                            }
                        }
                        if($column->Null == 'NO') {
                            if(strpos($column->Type,'varchar(') === false && strpos($column->Type,'text') === false) {
                                $validators[] = 'required';
                            }
                        }

                        if(strpos($column->Type,'varchar(') !== false) {
                            $length = str_replace(')','',str_replace('varchar(', '', $column->Type));
                            $validators[] = "max:$length";
                            $validators[] = "string";
                        }
                        if(strpos($column->Type,'text') !== false) {
                            $validators[] = "string";
                        }
                        if($column->Type == 'date' || $column->Type == 'datetime' || $column->Type == 'timestamp') {
                            $validators[] = 'date';
                        }
                        if(strpos($column->Type,'decimal(') !== false || strpos($column->Type,'double(') !== false || strpos($column->Type,'float(') !== false) {
                            $validators[] = 'numeric';
                        }

                        $validation = '';
                        if(count($validators) > 0) {
                            $validation = implode('|',$validators);
                        }

                        $column_names[] = $column->Field;
                        if(count($column_names) > 1) {
                            $validations .= "\n                            ";
                        }
                        $validations .= "'{$column->Field}' => '{$validation}',";
                    }
                    if(substr($column->Type,0,3) == 'int') {
                        $content .= " * @property integer \$" . $column->Field . "\n";
                    } else {
                        $content .= " * @property string \$" . $column->Field . "\n";
                    }
                }
                $fillable = implode("','",$column_names);
                $validations = substr($validations,0,-1);

                $content .= " */

class {$newName} extends {$model_base} {
";

                if($soft_delete) {
                    $content .= "\n    use SoftDeletes;\n";
                }

                $content .= "
    protected \$table = '{$table}';
    protected \$fillable = ['{$fillable}'];
    protected \$validations = [{$validations}];

$relationships";

                $content .= "\n}";

                $file = fopen($modelFile,'w');
                fwrite($file, $content);
                fclose($file);
            }
        }
	}
    public function modelName($tablename) {
        $pieces = explode('_',$tablename);
        $newName = '';
        foreach($pieces as $piece) {
            $newName .= ucfirst($piece);
        }
        return $newName;
    }
}

