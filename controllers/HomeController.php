<?php

class HomeController
{
    public function index($c,$p)
    {
        $c->set('title', 'Halaman Awal');
        $c->set('user', [
            'name' => 'Riza Borneo',
            'role' => 'Admin',
            'created_at' => '2024-12-31 15:45:00'
        ]);

        var_dump($c->get('database.name'));
                
        print $c->render('templates/home.html');
    }
}
