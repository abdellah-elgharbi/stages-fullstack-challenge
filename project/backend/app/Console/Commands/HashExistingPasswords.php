<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class HashExistingPasswords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hash:existing-passwords';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hash existing plain text passwords in the database';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting password hashing process...');

        $count = 0;
        $skipped = 0;

        // Process users in chunks
        User::chunkById(100, function ($users) use (&$count, &$skipped) {
            foreach ($users as $user) {
                // Get raw attribute to check current value
                $currentPassword = $user->getAttributes()['password']; 
                
                // Identify if it's likely already a Bcrypt hash
                $isHash = preg_match('/^\$2y\$/', $currentPassword) && strlen($currentPassword) === 60;

                if (!$isHash) {
                    // Force update the password attribute to trigger the mutator (or manual hash)
                    // Since we added the mutator, setting the property works.
                    // But to be absolutely safe and avoid double hashing if mutator logic is complex,
                    // we can just set it and save.
                    
                    $user->password = $currentPassword; // Triggers setPasswordAttribute -> Hash::make()
                    $user->save();
                    
                    $this->line("Hashed password for user: {$user->email}");
                    $count++;
                } else {
                    $skipped++;
                }
            }
        });

        $this->info("Finished. Hashed {$count} passwords. Skipped {$skipped} already hashed passwords.");

        return 0;
    }
}
