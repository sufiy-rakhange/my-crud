<?php

namespace Sufiyan\CrudGenerator;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class CrudGenerator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sufiy:crud-generator {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'A laravel package to create a CRUD functionality for any module. This will create a Route, Controller, Model, Migration, and Blade files.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = strtolower($this->argument('name'));

        $pluralName = Str::plural($name);

        Artisan::call('make:model', ['name' => ucfirst($name)]);

        Artisan::call('make:migration', [
            'name' => 'create_' . $pluralName . '_table',
            '--create' => $pluralName
        ]);

        $controllerName = ucfirst($name) . 'Controller';

        Artisan::call('make:controller', [
            'name' => $controllerName,
            '--resource' => true
        ]);

        // Get the contents of the web.php file
        $routeContent = File::get(base_path('routes/web.php'));

        $functionStart = strpos($routeContent, 'use Illuminate\Support\Facades\Route;');
        $functionEnd = strpos($routeContent, '/*', $functionStart);

        $routeContent = substr_replace(
            $routeContent,
            "\nuse App\\Http\Controllers\\$controllerName;\n",
            $functionEnd,
            0
        );

        File::put('routes/web.php', $routeContent);

        // Define the new route
        $newRoute = "\nRoute::resource('{$pluralName}', $controllerName::class);\n";

        // Add the new route to the web.php file
        File::append(base_path('routes/web.php'), $newRoute);

        // Get the path to the controller file
        $controllerPath = app_path('Http/Controllers/' . $controllerName . '.php');

        // Read the contents of the controller file
        $controllerContents = File::get($controllerPath);

        $controllerContents = $this->addCustomCode($name, $pluralName, $controllerContents);

        // Write the modified contents back to the controller file
        File::put($controllerPath, $controllerContents);

        // Create the directory if does not exist
        File::ensureDirectoryExists(resource_path('views/' . $pluralName));

        $this->createBladeFiles($pluralName);

        // Displaying messages after creation
        $this->info('Follwing files has generated successfully:');

        $this->info('Controller file generated: App/Http/Controllers/' . $controllerName . '.php');

        $this->info('Model file generated: App/Models/' . ucfirst($name) . '.php');

        $this->info("Blade file for index generated: " . $pluralName . "/index.blade.php");

        $this->info("Blade file for create generated: " . $pluralName . "/create.blade.php");

        $this->info("Blade file for edit generated: " . $pluralName . "/edit.blade.php");

        $this->info("Blade file for show generated: " . $pluralName . "/show.blade.php");
    }

    // Customising all the resource functions
    private function addCustomCode($name, $pluralName, $controllerContents)
    {
        $functionNames = ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'];

        $ucfname = ucfirst($name);

        $functionStart = strpos($controllerContents, 'use Illuminate\Http\Request;');
        $functionEnd = strpos($controllerContents, 'class', $functionStart);

        $controllerContents = substr_replace($controllerContents, "use App\Models\\$ucfname;\n\n", $functionEnd, 0);

        foreach ($functionNames as $functionName) {
            // Find the start and end positions of the function code
            $functionStart = strpos($controllerContents, 'public function ' . $functionName . '(');
            $functionEnd = strpos($controllerContents, '    }', $functionStart);

            // Add your custom code to the function
            $customCode = "";
            switch ($functionName) {
                case 'index':
                    $customCode = "       return view('{$pluralName}.index', ['{$pluralName}' => $ucfname::all()]);\n";
                    break;

                case 'create':
                    $customCode = "       return view('{$pluralName}.create');\n";
                    break;

                case 'store':
                    $customCode = "       \$validatedData = \$request->validate([\$request->all()]);
                    \n      $ucfname::create(\$validatedData);
                    \n      return redirect()->route('{$pluralName}.index');\n";
                    break;

                    case 'show':
                    $customCode = "       return view('{$pluralName}.show', ['{$pluralName}' => $ucfname::findOrFail(\$id)]);\n";
                    break;

                case 'edit':
                    $customCode = "       return view('{$pluralName}.edit', ['{$pluralName}' => $ucfname::findOrFail(\$id)]);\n";
                    break;

                case 'update':
                    $customCode = "     \$validatedData = \$request->validate([\$request->all()]);
                    \n      $ucfname::findOrFail(\$id)->update(\$validatedData);
                    \n      return redirect()->route('{$pluralName}.index');\n";
                    break;

                case 'destroy':
                    $customCode = "       $ucfname::findOrFail(\$id)->delete();
                    \n      return redirect()->route('{$pluralName}.index');\n";
                    break;
            }

            $controllerContents = substr_replace($controllerContents, $customCode, $functionEnd, 0);
        }

        return $controllerContents;
    }

    private function createBladeFiles($name)
    {
        $bladeFiles = ['index', 'create', 'edit', 'show'];

        foreach ($bladeFiles as $bladeFile) {
            // Create the Blade view files
            $bladePath = resource_path('views/' . $name . '/' . $bladeFile . '.blade.php');

            File::put($bladePath, '');

            // Read the current contents of the file
            // $contents = File::get($bladePath);

            $contents = "{{-- Blade file $bladeFile generated by sufiyan crud generator --}}\n";

            // Write the modified contents back to the file
            File::put($bladePath, $contents);
        }
    }
}
