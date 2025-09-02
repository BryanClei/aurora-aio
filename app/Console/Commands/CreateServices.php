<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class CreateServices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "app:make-service {folderAndName}";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Create a new service class in the Services directory";

    protected $files;

    public function __construct()
    {
        parent::__construct();
        $this->files = new Filesystem();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $folderAndName = $this->argument("folderAndName");
        $folderAndName = str_replace("/", DIRECTORY_SEPARATOR, $folderAndName);

        $parts = explode(DIRECTORY_SEPARATOR, $folderAndName);
        $serviceName = array_pop($parts);
        $folderPath = implode(DIRECTORY_SEPARATOR, $parts);

        $serviceDirectory = app_path(
            "Services" . DIRECTORY_SEPARATOR . $folderPath
        );
        $servicePath =
            $serviceDirectory . DIRECTORY_SEPARATOR . "{$serviceName}.php";

        if (!$this->files->exists($serviceDirectory)) {
            $this->files->makeDirectory($serviceDirectory, 0755, true);
        }

        if ($this->files->exists($servicePath)) {
            $this->error("The service class already exists: {$servicePath}");
            return Command::FAILURE;
        }

        $this->files->put(
            $servicePath,
            $this->buildServiceStub($serviceName, $folderPath)
        );

        $relativePath = str_replace(base_path(), "", $servicePath);
        $this->info("Resource [{$relativePath}] created successfully.");

        return Command::SUCCESS;
    }

    protected function buildServiceStub($name, $folderPath = null)
    {
        $namespace = "App\Services";
        if (!empty($folderPath)) {
            $namespace .=
                "\\" . str_replace(DIRECTORY_SEPARATOR, "\\", $folderPath);
        }

        return "<?php

namespace {$namespace};

class {$name}
{
    public static function exampleMethod()
    {
        // Example logic
        return 'Example method logic';
    }
}";
    }
}
