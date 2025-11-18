<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NotificationSubscriptionAmountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       // inside a seeder
        DB::table('notification_amount')->insert([
            ['type'=>'Small','amount'=>100,'price'=>199.00,'channel'=>'waba','created_at'=>now(),'updated_at'=>now()],
            ['type'=>'Medium','amount'=>500,'price'=>899.00,'channel'=>'waba','created_at'=>now(),'updated_at'=>now()],
            ['type'=>'Large','amount'=>2000,'price'=>2999.00,'channel'=>'waba','created_at'=>now(),'updated_at'=>now()],
            ['type'=>'Small','amount'=>100,'price'=>99.00,'channel'=>'text','created_at'=>now(),'updated_at'=>now()],
            ['type'=>'Medium','amount'=>500,'price'=>399.00,'channel'=>'text','created_at'=>now(),'updated_at'=>now()],
            ['type'=>'Email Basic','amount'=>1000,'price'=>499.00,'channel'=>'email','created_at'=>now(),'updated_at'=>now()],
        ]);

    }
}
