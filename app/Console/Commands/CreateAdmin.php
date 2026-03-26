<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CreateAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
   public function handle() {
    $name = $this->argument('name');
    $email = $this->argument('email');
    $pass = $this->argument('password');

    $exists = \DB::table('users')->where('email',$email)->exists();
    if (!$exists) {
        \DB::table('users')->insert([
            'name'=>$name,
            'email'=>$email,
            'password'=>bcrypt($pass),
            'role'=>0,
            'created_at'=>now(),
            'updated_at'=>now()
        ]);
        $this->info("Admin created successfully.");
    } else {
        $this->warn("Admin already exists.");
    }
}



}
