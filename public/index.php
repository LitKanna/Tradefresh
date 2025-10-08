<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * @package  Laravel
 * @author   Taylor Otwell <taylor@laravel.com>
 */

// Check if autoloader exists
$autoloadPath = __DIR__.'/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require $autoloadPath;
    
    // Bootstrap Laravel
    $app = require_once __DIR__.'/../bootstrap/app.php';
    
    // Handle the request
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $response = $kernel->handle(
        $request = Illuminate\Http\Request::capture()
    )->send();
    
    $kernel->terminate($request, $response);
} else {
    // Fallback if Laravel is not installed
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sydney Markets B2B Platform</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gradient-to-br from-green-50 to-blue-50 min-h-screen">
        <div class="container mx-auto px-4 py-16">
            <div class="max-w-4xl mx-auto">
                <div class="bg-white rounded-lg shadow-2xl overflow-hidden">
                    <div class="bg-gradient-to-r from-green-600 to-blue-600 text-white p-8">
                        <h1 class="text-4xl font-bold mb-2">🏪 Sydney Markets B2B Platform</h1>
                        <p class="text-green-100">Wholesale Produce Marketplace</p>
                    </div>
                    
                    <div class="p-8">
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700">
                                        <strong>Laravel packages not installed yet!</strong><br>
                                        Run <code class="bg-yellow-100 px-1">composer install</code> to complete setup.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="grid md:grid-cols-3 gap-6 mb-8">
                            <div class="text-center p-6 bg-green-50 rounded-lg">
                                <div class="text-3xl mb-2">👥</div>
                                <h3 class="font-semibold text-green-800">For Vendors</h3>
                                <p class="text-sm text-gray-600 mt-2">Receive RFQs, submit quotes, manage inventory</p>
                            </div>
                            
                            <div class="text-center p-6 bg-blue-50 rounded-lg">
                                <div class="text-3xl mb-2">🛒</div>
                                <h3 class="font-semibold text-blue-800">For Buyers</h3>
                                <p class="text-sm text-gray-600 mt-2">Post RFQs, compare quotes, track orders</p>
                            </div>
                            
                            <div class="text-center p-6 bg-purple-50 rounded-lg">
                                <div class="text-3xl mb-2">📱</div>
                                <h3 class="font-semibold text-purple-800">WhatsApp Bot</h3>
                                <p class="text-sm text-gray-600 mt-2">Order via WhatsApp messages</p>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 rounded-lg p-6">
                            <h2 class="text-xl font-semibold mb-4">✅ PHP Status</h2>
                            <table class="w-full text-sm">
                                <tr class="border-b">
                                    <td class="py-2 font-medium">PHP Version:</td>
                                    <td class="text-green-600"><?php echo PHP_VERSION; ?></td>
                                </tr>
                                <tr class="border-b">
                                    <td class="py-2 font-medium">Server:</td>
                                    <td class="text-green-600"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'PHP Built-in Server'; ?></td>
                                </tr>
                                <tr class="border-b">
                                    <td class="py-2 font-medium">Port:</td>
                                    <td class="text-green-600"><?php echo $_SERVER['SERVER_PORT'] ?? '8000'; ?></td>
                                </tr>
                                <tr>
                                    <td class="py-2 font-medium">Status:</td>
                                    <td class="text-green-600 font-semibold">✓ Server Running</td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="mt-8 p-6 bg-blue-50 rounded-lg">
                            <h3 class="font-semibold mb-3">📦 Complete Setup Instructions:</h3>
                            <ol class="list-decimal list-inside space-y-2 text-sm text-gray-700">
                                <li>Open Command Prompt in this directory</li>
                                <li>Run: <code class="bg-white px-2 py-1 rounded">C:\xampp\php\php.exe composer.phar install</code></li>
                                <li>Run: <code class="bg-white px-2 py-1 rounded">C:\xampp\php\php.exe artisan key:generate</code></li>
                                <li>Run: <code class="bg-white px-2 py-1 rounded">C:\xampp\php\php.exe artisan migrate</code></li>
                                <li>Refresh this page</li>
                            </ol>
                        </div>
                        
                        <div class="mt-8 text-center text-gray-500">
                            <p>Sydney Markets B2B Platform &copy; 2024</p>
                            <p class="text-xs mt-2">Built with Laravel, Livewire, and 💚</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>