<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Password;
use App\Models\User;

class TestPasswordResetCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'password:test-reset {email : Email address of user to send password reset to}';

    /**
     * The console command description.
     */
    protected $description = 'Send a test password reset email to verify functionality';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');
        
        $this->info('Testing password reset email functionality...');
        $this->info("Sending password reset email to: {$email}");
        
        // Find user by email
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("❌ User with email {$email} not found.");
            $this->info("Available users:");
            User::all(['email', 'name'])->each(function ($user) {
                $this->line("  - {$user->email} ({$user->name})");
            });
            return Command::FAILURE;
        }
        
        try {
            // Send password reset notification
            $status = Password::sendResetLink(['email' => $email]);
            
            if ($status === Password::RESET_LINK_SENT) {
                $this->info('✅ Password reset email sent successfully!');
                $this->info('Check your inbox and spam folder.');
                $this->info('The reset link will be valid for 60 minutes.');
                
                return Command::SUCCESS;
            } else {
                $this->error('❌ Failed to send password reset email.');
                $this->error("Status: {$status}");
                
                return Command::FAILURE;
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Exception occurred while sending password reset email:');
            $this->error($e->getMessage());
            
            return Command::FAILURE;
        }
    }
}