<?php

class UserController
{
    public function show($param)
    {
        echo "User detail for ID: " . htmlspecialchars($param->id);
    }
}
