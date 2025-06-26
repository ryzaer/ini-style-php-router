<?php

class AboutController
{
    public function show($c,$params)
    {
        $c->set('title', 'Halaman Utama');
        $c->set('page_data_name', 'Selamat Datang di Situs Kami');
        $c->set('judul_article', 'dampak memasang baliho');
        $c->set('items', ['Andi', 'Budi', 'Cici','Dani','Ira']); // contoh array
        $c->set('title', 'User Profile');
        $c->set('template_file', 'header');
        $c->set('user', [
            'name' => 'Riza Borneo',
            'role' => 'Admin',
            'created_at' => '2024-12-31 15:45:00'
        ]);
        print $c->render('templates/layout.html');
    }
}
