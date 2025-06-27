<?php

class PostController
{
    public function comment($self,$params)
    {   
        echo "Post ID: " . htmlspecialchars($params->id) . ", Hash : " . htmlspecialchars($params->hash)."<br><br>";
        // example
        $pdo = $self->dbconnect('local1');
        // $pdo->create("tbblob",[
        //     'id'=>'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY',
        //     'name'=>'varchar(20) NOT NULL',
        //     'item'=>'varchar(50) NOT NULL',
        //     'filedata'=>'longblob NOT NULL'
        // ]);
        // $rsl = $pdo->blob('pdf|jpg')->insert('tbblob', ['name' => 'doraemon', 'item' => $params->id.$params->hash,'filedata'=>'./doraemon.gif']);
        // echo "Last Insert ID: $rsl";
        // $pdo->blob()->update('tbblob', ['filedata'=>'./doraemon.gif'],['id'=>'1']);
        // $pdo->delete('tbblob',['id'=>'2']);        
        $rsl = $pdo->select('tbblob[~id,name,filedata as file~](~id DESC~){~1,5~}',['name'=>'dora'],true);
        echo $self->getMimeFile($rsl[1]['file'],false);
        // echo finfo_buffer($finfo, $rsl[1]['file']);
        // $img = 'data:image/gif;base64,'.base64_encode($rsl[0]['file']);
        // echo "<img src=\"$img\" width=\"100px\" />";

        // var_dump($self->getExtension('image/png'));
    }
}
