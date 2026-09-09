<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateApiCredentials extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:create-credentials
                            {--username= : Username for API access}
                            {--password= : Password for API access}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create new API credentials (username and password) for BPJS webservice authentication';

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
        $this->info('==============================================');
        $this->info('   Create API Credentials for BPJS Webservice');
        $this->info('==============================================');
        $this->line('');

        // Get username
        $username = $this->option('username') ?: $this->ask('Enter username');

        if (empty($username)) {
            $this->error('Username cannot be empty!');
            return 1;
        }

        // Check if username already exists
        if (User::where('username', $username)->exists()) {
            $this->error("Username '{$username}' already exists!");

            if ($this->confirm('Do you want to update the password for this user?', false)) {
                return $this->updatePassword($username);
            }

            return 1;
        }

        // Get password
        $password = $this->option('password') ?: $this->secret('Enter password (min 6 characters)');

        if (empty($password)) {
            $this->error('Password cannot be empty!');
            return 1;
        }

        if (strlen($password) < 6) {
            $this->error('Password must be at least 6 characters!');
            return 1;
        }

        // Confirm password if not using option
        if (!$this->option('password')) {
            $passwordConfirm = $this->secret('Confirm password');

            if ($password !== $passwordConfirm) {
                $this->error('Passwords do not match!');
                return 1;
            }
        }

        try {
            // Create user
            $user = User::create([
                'username' => $username,
                'password' => Hash::make($password)
            ]);

            $this->line('');
            $this->info('✓ API Credentials created successfully!');
            $this->line('');
            $this->line('==============================================');
            $this->line('Credentials Details:');
            $this->line('==============================================');
            $this->line('Username: ' . $username);
            $this->line('Password: ' . $password);
            $this->line('User ID:  ' . $user->id);
            $this->line('==============================================');
            $this->line('');
            $this->comment('Use these credentials to authenticate:');
            $this->comment('GET /webservice/registrasionline/bpjs/getToken');
            $this->comment('Headers:');
            $this->comment('  x-username: ' . $username);
            $this->comment('  x-password: ' . $password);
            $this->line('');
            $this->warn('⚠ Please save these credentials securely!');
            $this->line('');

            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to create credentials: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Update password for existing user
     *
     * @param string $username
     * @return int
     */
    protected function updatePassword($username)
    {
        $password = $this->secret('Enter new password (min 6 characters)');

        if (empty($password)) {
            $this->error('Password cannot be empty!');
            return 1;
        }

        if (strlen($password) < 6) {
            $this->error('Password must be at least 6 characters!');
            return 1;
        }

        $passwordConfirm = $this->secret('Confirm new password');

        if ($password !== $passwordConfirm) {
            $this->error('Passwords do not match!');
            return 1;
        }

        try {
            User::where('username', $username)->update([
                'password' => Hash::make($password)
            ]);

            $this->line('');
            $this->info('✓ Password updated successfully!');
            $this->line('');
            $this->line('==============================================');
            $this->line('Updated Credentials:');
            $this->line('==============================================');
            $this->line('Username: ' . $username);
            $this->line('Password: ' . $password);
            $this->line('==============================================');
            $this->line('');
            $this->warn('⚠ Please save these credentials securely!');
            $this->line('');

            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to update password: ' . $e->getMessage());
            return 1;
        }
    }
}
