<?php

class AboutController
{
    public function show()
    {
        $layout = new Layout('templates/layout.html');
        $layout->set('title', 'Halaman Utama');
        $layout->set('page_data_name', 'Selamat Datang di Situs Kami');
        $layout->set('judul_article', 'dampak memasang baliho');
        $layout->set('items', ['Andi', 'Budi', 'Cici']); // contoh array
        $layout->set('title', 'User Profile');
        $layout->set('template_file', 'header');
        $layout->set('user', [
            'name' => 'Riza Borneo',
            'role' => 'Admin',
            'created_at' => '2024-12-31 15:45:00'
        ]);
        print $layout->render();
    }
}
