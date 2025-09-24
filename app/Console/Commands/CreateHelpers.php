<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class CreateHelpers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "app:make-helper {folderAndName}";

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
        $helperName = array_pop($parts);
        $folderPath = implode(DIRECTORY_SEPARATOR, $parts);

        $helperDirectory = app_path(
            "Helpers" . DIRECTORY_SEPARATOR . $folderPath
        );
        $helperPath =
            $helperDirectory . DIRECTORY_SEPARATOR . "{$helperName}.php";

        if (!$this->files->exists($helperDirectory)) {
            $this->files->makeDirectory($helperDirectory, 0755, true);
        }

        if ($this->files->exists($helperPath)) {
            $this->error("The helper class already exists: {$helperPath}");
            return Command::FAILURE;
        }

        $this->files->put(
            $helperPath,
            $this->buildHelperStub($helperName, $folderPath)
        );

        $relativePath = str_replace(base_path(), "", $helperPath);
        $this->info("Resource [{$relativePath}] created successfully.");

        return Command::SUCCESS;
    }

    protected function buildHelperStub($name, $folderPath = null)
    {
        $namespace = "App\Helpers";
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
