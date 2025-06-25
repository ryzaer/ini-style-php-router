<?php

class PostController
{
    public function comment($self,$params)
    {   
        // var_dump($self);
        echo "Post ID: " . htmlspecialchars($params->id) . ", Hash : " . htmlspecialchars($params->hash)."<br><br>";
        $self->dbconnect('resta');
        // $rsl = $self->blob('pdf|jpg')->insert('tbblob', ['name' => $params->id, 'item' => $params->hash,'filedata'=>'./234434.jpg']);
        // echo "Last Insert ID: $rsl";

        // $rsl = $self->blob()->update('tbblob', ['filedata'=>'./clips.jpg'],['id'=>'2']);
        $self->delete('tbblob',['id'=>'2']);
    }
}
