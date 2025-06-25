<?php

class PostController
{
    public function comment($self,$params)
    {   
        echo "Post ID: " . htmlspecialchars($params->id) . ", Hash : " . htmlspecialchars($params->hash)."<br><br>";
        // example
        // $pdo = $self->dbconnect('resta');
        // $pdo->create("tbblob",[
        //     'id'=>'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY',
        //     'name'=>'varchar(20) NOT NULL',
        //     'filedata'=>'longblob NOT NULL'
        // ]);
        // $rsl = $pdo->blob('pdf|jpg')->insert('tbblob', ['name' => $params->id, 'item' => $params->hash,'filedata'=>'./2344343.jpg']);
        // echo "Last Insert ID: $rsl";
        // $self->blob('jpg')->update('tbblob', ['filedata'=>'./clips.jpg'],['id'=>'1']);
        // $self->delete('tbblob',['id'=>'2']);        
    }
}
