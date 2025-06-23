<?php

class UserController
{
    public function show($self,$param)
    {
        echo "User detail for ID: " . htmlspecialchars($param->id);
        $self->dbconnect('resta');
        $data = $self->prepare("select nama,nomor_identitas,alamat,nama_ibu,nama_ayah,nomor_telp from db_ident.tbbiodata where id_biodata=:id");
        // $data->bindValue(':id' ,$param->id);
        // $data->execute();
        $data->execute([':id' => $param->id]);
        var_dump($data->fetchAll(PDO::FETCH_ASSOC));
    }
}
