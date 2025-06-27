<?php

class HomeController
{
    public function index($self,$params)
    {
        $self->set('title', 'Dashboard');
        $self->set('user', [
            'name' => 'JoHn dOe',
            'role' => 'Admin',
            'created_at' => '2024-12-31 12:00:00'
        ]);
        // or set array assoc data
        $self->set([
            'path_code' => 'other_openscript',
            'catalog' => [
                'VivoBook',
                'Galaxy A52',
                'Thinkpad E280',
                'WH-1000XM4'
            ]
        ]);        
        echo $self->render('templates/home.html');
    }
}
