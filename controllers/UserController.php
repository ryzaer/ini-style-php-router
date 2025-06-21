<?php

class UserController
{
    public function show($self,$param)
    {
        echo "User detail for ID: " . htmlspecialchars($param->id);
    }
}
