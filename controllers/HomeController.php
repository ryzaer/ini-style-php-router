<?php

class HomeController
{
    public function index()
    {
        $layout = new Layout('templates/home.html');
        $layout->set('title', 'Halaman Awal');
        $layout->set('user', [
            'name' => 'Riza Borneo',
            'role' => 'Admin',
            'created_at' => '2024-12-31 15:45:00'
        ]);
        print $layout->render();
    }
}
