<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        Model::unguard();
        factory(\App\Subdivision::class,100)->create();
        factory(App\User::class, 10)->create();
        $this->call(AdsourcesSeeder::class);
        $this->call(EducationSeeder::class);
        $this->call(CandidatRegionsSeeder::class);
        $this->call(RegionSeeder::class);
        $this->call(CitiesSeeder::class);
        $this->call(DebtorGroupSeeder::class);
        $this->call(EmailsMessagesSeeder::class);
        $this->call(LoanGoalsSeeder::class);
        $this->call(MaritalTypesSeeder::class);
        $this->call(OrderTypesSeeder::class);
        $this->call(RolesSeeder::class);
        $this->call(PermissionSeeder::class);

        factory(\App\Customer::class, 15)->create();
        factory(\App\Card::class,100)->create();
        factory(\App\about_client::class,15)->create();
        factory(\App\Passport::class,15)->create();
        factory(\App\Order::class,15)->create();
        factory(\App\Claim::class,15)->create();
        factory(\App\Debtor::class,10)->create();
        factory(\App\DebtorEvent::class,100)->create();
    }

}
