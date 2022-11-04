<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This creates a super admin account.';

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
        $name = $this->ask('What is your name?');
        $email = $this->ask('What is your email?');
        if (User::where('email', $email)->count()) {
            $this->error('The email address is already in use.');
            return;
        }
        $password = $this->secret('What is the password (You need at least 6 characters)?');
        if (strlen($password) < 6) {
            $this->error('You need at least 6 characters for your password.');
            return;
        }
        /** @var User $user */
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now()
        ]);
        $user->assign(User::ROLE_SUPER_ADMIN);

        $this->info('You have successfully created the user with the ID: ' . $user->id);

        //TODO: Notify slack / etc that a new super admin has been created as security measure
    }
}
