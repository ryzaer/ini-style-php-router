<?php

class HomeController
{
    public function index()
    {
        $layout = new Layout('templates/home.html');
        $layout->set('title', 'Halaman Awal');
        print $layout->render();
    }
}
