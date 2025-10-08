<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestEmailMail;

class TestEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'email:test {email : Email address to send test email to}';

    /**
     * The console command description.
     */
    protected $description = 'Send a test email to verify email configuration';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');
        
        $this->info('Testing email configuration...');
        $this->info("Sending test email to: {$email}");
        
        try {
            Mail::raw(
                "This is a test email from Sydney Markets B2B marketplace.\n\n" .
                "If you received this email, your email configuration is working correctly!\n\n" .
                "Mail Configuration:\n" .
                "- Mailer: " . config('mail.default') . "\n" .
                "- Host: " . config('mail.mailers.' . config('mail.default') . '.host') . "\n" .
                "- Port: " . config('mail.mailers.' . config('mail.default') . '.port') . "\n" .
                "- From: " . config('mail.from.address') . "\n\n" .
                "Timestamp: " . now()->toDateTimeString(),
                function ($message) use ($email) {
                    $message->to($email)
                           ->subject('Sydney Markets B2B - Email Test')
                           ->from(config('mail.from.address'), config('mail.from.name'));
                }
            );
            
            $this->info('✅ Test email sent successfully!');
            $this->info('Check your inbox and spam folder.');
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('❌ Failed to send test email:');
            $this->error($e->getMessage());
            
            return Command::FAILURE;
        }
    }
}