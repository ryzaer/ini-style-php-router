<?php

class UserController
{
    public function show($self,$param)
    {
        // echo "User detail for ID: " . htmlspecialchars($param->id);
        $self->dbconnect('resta');
        $data = $self->prepare("select nama,nomor_identitas as ktp,alamat,nama_ibu as ibu,nama_ayah as ayah,nomor_telp as hp from tbbiodata where id_biodata=:id");
        // $data->bindValue(':id' ,$param->id);
        // $data->execute();
        $data->execute([':id' => $param->id]);
        // var_dump($data->fetchAll(PDO::FETCH_ASSOC));
        
        $self->api_response(200,$data->fetchAll(PDO::FETCH_ASSOC));
    }
}
