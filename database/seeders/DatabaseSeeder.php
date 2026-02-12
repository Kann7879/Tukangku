<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Setting;
use App\Models\TukangProfile; 
use App\Models\Category; 

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call(RolePermissionSeeder::class);

        $this->call(CategorySeeder::class);

        $tukang = User::create([
            'username' => 'Tukang',
            'name' => 'Tukang Ac',
            'email' => 'tukang@gmail.com',
            'email_verified_at' => '2022-08-16 20:57:19',
            'password' => Hash::make('tukang123')
        ]);

        $tukang->assignRole('Tukang');

        TukangProfile::create([
            'user_id' => $tukang->id,
            'foto' => 'no_image.jpg',
            'deskripsi' => 'Tukang profesional dan berpengalaman'
        ]);

        $user = User::create([
            'username' => 'Pelanggan',
            'name' => 'Pelanggan 1',
            'email' => 'pelanggan@gmail.com',
            'email_verified_at' => '2022-08-16 20:57:19',
            'password' => Hash::make('12345')
        ]);

        $user->assignRole('Pelanggan');

        Setting::create([
            'key' => 'title',
            'value' => 'TukangKu',
            'serialize' => 0,
        ]);

        Setting::create([
            'key' => 'keyword',
            'value' => 'Tukang, Jasa, Service, Ledeng, Listrik',
            'serialize' => 0,
        ]);

        Setting::create([
            'key' => 'description',
            'value' => 'Marketplace jasa tukang terpercaya',
            'serialize' => 0,
        ]);

        Setting::create([
            'key'   => 'favicon',
            'value' => asset('/assets/images/favicon.png'),
            'serialize' => 0,
        ]);

        Setting::create([
            'key'   => 'author',
            'value' => 'TukangKu Indonesia',
            'serialize' => 0,
        ]);

        $this->call(MenuSeeder::class);
    }
}
