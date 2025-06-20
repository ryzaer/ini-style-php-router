<?php

class UserController
{
    public function show($id)
    {
        echo "User detail for ID: " . htmlspecialchars($id);
    }
}
