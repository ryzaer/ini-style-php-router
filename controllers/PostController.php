<?php

class PostController
{
    public function comment($id, $comment_id)
    {
        echo "Post ID: " . htmlspecialchars($id) . ", Comment ID: " . htmlspecialchars($comment_id);
    }
}
