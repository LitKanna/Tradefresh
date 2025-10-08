<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

class AuditBuyerRoutes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'buyer:audit-routes {--fix : Automatically fix missing routes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit all buyer routes and identify missing ones from blade templates';

    /**
     * Routes found in templates
     */
    protected $templateRoutes = [];

    /**
     * Registered routes
     */
    protected $registeredRoutes = [];

    /**
     * Missing routes
     */
    protected $missingRoutes = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting comprehensive buyer route audit...');
        
        // Step 1: Scan all blade templates for route() calls
        $this->scanTemplates();
        
        // Step 2: Get all registered routes
        $this->getRegisteredRoutes();
        
        // Step 3: Compare and find missing routes
        $this->findMissingRoutes();
        
        // Step 4: Display results
        $this->displayResults();
        
        // Step 5: Fix missing routes if requested
        if ($this->option('fix')) {
            $this->fixMissingRoutes();
        }
        
        return Command::SUCCESS;
    }

    /**
     * Scan all blade templates for route() calls
     */
    protected function scanTemplates()
    {
        $this->info('Scanning blade templates for route() calls...');
        
        $viewPath = resource_path('views');
        $files = File::allFiles($viewPath);
        
        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $content = File::get($file->getPathname());
                
                // Find all route() calls
                preg_match_all('/route\([\'"]([^\'"]+)[\'"]/i', $content, $matches);
                
                foreach ($matches[1] as $route) {
                    if (str_starts_with($route, 'buyer.')) {
                        if (!isset($this->templateRoutes[$route])) {
                            $this->templateRoutes[$route] = [];
                        }
                        $this->templateRoutes[$route][] = str_replace(resource_path(), 'resources', $file->getPathname());
                    }
                }
            }
        }
        
        $this->info('Found ' . count($this->templateRoutes) . ' unique buyer routes in templates');
    }

    /**
     * Get all registered routes
     */
    protected function getRegisteredRoutes()
    {
        $this->info('Getting registered routes...');
        
        $routes = Route::getRoutes();
        
        foreach ($routes as $route) {
            $name = $route->getName();
            if ($name && str_starts_with($name, 'buyer.')) {
                $this->registeredRoutes[$name] = [
                    'uri' => $route->uri(),
                    'methods' => $route->methods(),
                    'action' => $route->getActionName(),
                ];
            }
        }
        
        $this->info('Found ' . count($this->registeredRoutes) . ' registered buyer routes');
    }

    /**
     * Find missing routes
     */
    protected function findMissingRoutes()
    {
        $this->info('Comparing routes...');
        
        foreach ($this->templateRoutes as $route => $files) {
            if (!isset($this->registeredRoutes[$route])) {
                $this->missingRoutes[$route] = $files;
            }
        }
        
        $this->info('Found ' . count($this->missingRoutes) . ' missing routes');
    }

    /**
     * Display audit results
     */
    protected function displayResults()
    {
        if (empty($this->missingRoutes)) {
            $this->info('✅ All routes are properly registered! Zero route errors found.');
            return;
        }
        
        $this->error('❌ Missing Routes Found:');
        $this->newLine();
        
        $table = [];
        foreach ($this->missingRoutes as $route => $files) {
            $table[] = [
                'Route' => $route,
                'Used In' => implode("\n", array_slice($files, 0, 3)) . (count($files) > 3 ? "\n..." : ''),
                'Count' => count($files)
            ];
        }
        
        $this->table(['Route Name', 'Used In Files', 'Usage Count'], $table);
        
        // Group missing routes by prefix for better organization
        $grouped = $this->groupMissingRoutes();
        
        $this->newLine();
        $this->info('Missing Routes by Category:');
        
        foreach ($grouped as $category => $routes) {
            $this->line("  $category:");
            foreach ($routes as $route) {
                $this->line("    - $route");
            }
        }
    }

    /**
     * Group missing routes by category
     */
    protected function groupMissingRoutes()
    {
        $grouped = [];
        
        foreach ($this->missingRoutes as $route => $files) {
            $parts = explode('.', $route);
            if (count($parts) > 2) {
                $category = $parts[1]; // Get the category (e.g., 'catalog', 'checkout', etc.)
            } else {
                $category = 'general';
            }
            
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $route;
        }
        
        return $grouped;
    }

    /**
     * Fix missing routes by adding them to routes file
     */
    protected function fixMissingRoutes()
    {
        if (empty($this->missingRoutes)) {
            return;
        }
        
        $this->info('Generating fixes for missing routes...');
        
        // Group routes by category
        $grouped = $this->groupMissingRoutes();
        
        // Generate route definitions
        $routeDefinitions = $this->generateRouteDefinitions($grouped);
        
        // Save to a separate file for review
        $fixFile = base_path('routes/buyer-fixes.php');
        File::put($fixFile, $routeDefinitions);
        
        $this->info("Route fixes generated in: $fixFile");
        $this->info("Please review and merge these routes into routes/buyer.php");
        
        // Also generate any missing controllers
        $this->generateMissingControllers($grouped);
    }

    /**
     * Generate route definitions for missing routes
     */
    protected function generateRouteDefinitions($grouped)
    {
        $content = "<?php\n\n";
        $content .= "// Missing routes found by audit on " . now()->format('Y-m-d H:i:s') . "\n";
        $content .= "// Add these to routes/buyer.php\n\n";
        
        foreach ($grouped as $category => $routes) {
            $content .= "\n// $category Routes\n";
            $content .= "Route::prefix('$category')->name('$category.')->group(function () {\n";
            
            foreach ($routes as $route) {
                $parts = explode('.', $route);
                $action = end($parts);
                $controllerName = ucfirst($category) . 'Controller';
                
                // Determine HTTP method and URI
                $method = 'get';
                $uri = '/';
                
                if (in_array($action, ['store', 'update', 'destroy', 'add', 'remove', 'save'])) {
                    $method = 'post';
                }
                
                if ($action !== 'index') {
                    $uri = '/' . str_replace('.', '/', substr($route, strlen("buyer.$category.")));
                }
                
                $content .= "    Route::$method('$uri', [\\App\\Http\\Controllers\\Buyer\\$controllerName::class, '$action'])->name('" . substr($route, strlen("buyer.$category.")) . "');\n";
            }
            
            $content .= "});\n";
        }
        
        return $content;
    }

    /**
     * Generate missing controllers
     */
    protected function generateMissingControllers($grouped)
    {
        $this->info('Checking for missing controllers...');
        
        $controllersToCreate = [];
        
        foreach ($grouped as $category => $routes) {
            $controllerName = ucfirst($category) . 'Controller';
            $controllerPath = app_path("Http/Controllers/Buyer/$controllerName.php");
            
            if (!File::exists($controllerPath)) {
                $controllersToCreate[$controllerName] = $this->extractActionsFromRoutes($routes);
            }
        }
        
        if (empty($controllersToCreate)) {
            $this->info('All required controllers exist.');
            return;
        }
        
        foreach ($controllersToCreate as $controllerName => $actions) {
            $this->createController($controllerName, $actions);
        }
    }

    /**
     * Extract action names from routes
     */
    protected function extractActionsFromRoutes($routes)
    {
        $actions = [];
        
        foreach ($routes as $route) {
            $parts = explode('.', $route);
            $action = end($parts);
            
            if (!in_array($action, $actions)) {
                $actions[] = $action;
            }
        }
        
        return $actions;
    }

    /**
     * Create a controller with placeholder methods
     */
    protected function createController($controllerName, $actions)
    {
        $namespace = 'App\\Http\\Controllers\\Buyer';
        $className = $controllerName;
        
        $content = "<?php\n\n";
        $content .= "namespace $namespace;\n\n";
        $content .= "use App\\Http\\Controllers\\Controller;\n";
        $content .= "use Illuminate\\Http\\Request;\n\n";
        $content .= "class $className extends Controller\n";
        $content .= "{\n";
        
        foreach ($actions as $action) {
            $content .= "    /**\n";
            $content .= "     * " . ucfirst(str_replace('-', ' ', $action)) . "\n";
            $content .= "     */\n";
            $content .= "    public function $action(Request \$request)\n";
            $content .= "    {\n";
            $content .= "        // TODO: Implement $action\n";
            $content .= "        return view('buyer." . strtolower(str_replace('Controller', '', $controllerName)) . ".$action');\n";
            $content .= "    }\n\n";
        }
        
        $content .= "}\n";
        
        $path = app_path("Http/Controllers/Buyer/$controllerName.php");
        File::put($path, $content);
        
        $this->info("Created controller: $controllerName");
    }
}