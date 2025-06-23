<?php

class UserController
{
    public function show($self,$param)
    {
        echo "User detail for ID: " . htmlspecialchars($param->id);
        $self->dbconnect('root','123');
        $data = $self->prepare("select nama,nomor_identitas,alamat,nama_ibu,nama_ayah,nomor_telp from dbident.tbbiodata where id_biodata=:id");
        // $data->bindValue(':id' ,$param->id);
        // $data->execute();
        $data->execute([':id' => $param->id]);
        var_dump($data->fetchAll(PDO::FETCH_ASSOC));

    }
}
