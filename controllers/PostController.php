<?php

class PostController
{
    public function comment($params,$self)
    {   
        // var_dump($self);
        echo "Post ID: " . htmlspecialchars($params->id) . ", Comment ID: " . htmlspecialchars($params->comment_id);
    }
}
