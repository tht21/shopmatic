<?php

use App\Models\User;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Illuminate\Database\Seeder;
use Silber\Bouncer\Database\Role;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     * @throws Exception
     */
    public function run()
    {
        Bouncer::allow(User::ROLE_SUPER_ADMIN)->everything();

        Bouncer::allow(User::ROLE_ADMIN)->everything();
        Bouncer::forbid(User::ROLE_ADMIN)->toManage(Role::class);

        Bouncer::refresh();
    }
}
