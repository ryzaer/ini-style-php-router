<?php

class PostController
{
    public function comment($self,$params)
    {   
        // var_dump($self);
        echo "Post ID: " . htmlspecialchars($params->id) . ", Comment ID: " . htmlspecialchars($params->comment_id);

        // $self->fn->test('data');
    }
}
