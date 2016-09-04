<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // $this->call(UsersTableSeeder::class);
        DB::table('pages')->insert([
            'title' => "Main Page",
            'slug' => 'Main_Page',
            'content' => 'This is your main page. Edit this page to enter your own content.',
        ]);
    }
}
