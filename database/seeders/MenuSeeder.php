<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        DB::table('menus')->insert([
            ['name' => 'Phở bò', 'price' => 50000, 'image' => 'pho.jpg', 'description' => 'Phở bò truyền thống'],
            ['name' => 'Bún chả', 'price' => 45000, 'image' => 'buncha.jpg', 'description' => 'Bún chả Hà Nội'],
            ['name' => 'Cơm tấm', 'price' => 40000, 'image' => 'comtam.jpg', 'description' => 'Cơm tấm sườn bì chả'],
            ['name' => 'Phở bò', 'price' => 50000, 'image' => 'pho.jpg', 'description' => 'Phở bò truyền thống'],
            ['name' => 'Bún chả', 'price' => 45000, 'image' => 'buncha.jpg', 'description' => 'Bún chả Hà Nội'],
            ['name' => 'Cơm tấm', 'price' => 40000, 'image' => 'comtam.jpg', 'description' => 'Cơm tấm sườn bì chả'],
            ['name' => 'Phở bò', 'price' => 50000, 'image' => 'pho.jpg', 'description' => 'Phở bò truyền thống'],
            ['name' => 'Bún chả', 'price' => 45000, 'image' => 'buncha.jpg', 'description' => 'Bún chả Hà Nội'],
            ['name' => 'Cơm tấm', 'price' => 40000, 'image' => 'comtam.jpg', 'description' => 'Cơm tấm sườn bì chả'],
            ['name' => 'Phở bò', 'price' => 50000, 'image' => 'pho.jpg', 'description' => 'Phở bò truyền thống'],
            ['name' => 'Bún chả', 'price' => 45000, 'image' => 'buncha.jpg', 'description' => 'Bún chả Hà Nội'],
            ['name' => 'Cơm tấm', 'price' => 40000, 'image' => 'comtam.jpg', 'description' => 'Cơm tấm sườn bì chả'],
            ['name' => 'Phở bò', 'price' => 50000, 'image' => 'pho.jpg', 'description' => 'Phở bò truyền thống'],
            ['name' => 'Bún chả', 'price' => 45000, 'image' => 'buncha.jpg', 'description' => 'Bún chả Hà Nội'],
            ['name' => 'Cơm tấm', 'price' => 40000, 'image' => 'comtam.jpg', 'description' => 'Cơm tấm sườn bì chả'],
            ['name' => 'Phở bò', 'price' => 50000, 'image' => 'pho.jpg', 'description' => 'Phở bò truyền thống'],
            ['name' => 'Bún chả', 'price' => 45000, 'image' => 'buncha.jpg', 'description' => 'Bún chả Hà Nội'],
            ['name' => 'Cơm tấm', 'price' => 40000, 'image' => 'comtam.jpg', 'description' => 'Cơm tấm sườn bì chả'],
            ['name' => 'Phở bò', 'price' => 50000, 'image' => 'pho.jpg', 'description' => 'Phở bò truyền thống'],
            ['name' => 'Bún chả', 'price' => 45000, 'image' => 'buncha.jpg', 'description' => 'Bún chả Hà Nội'],
            ['name' => 'Cơm tấm', 'price' => 40000, 'image' => 'comtam.jpg', 'description' => 'Cơm tấm sườn bì chả'],
        ]);
    }

}
