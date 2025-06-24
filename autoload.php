<?php
spl_autoload_register(function($class) {  
    $root = preg_replace('~[\\\]~','/',__DIR__);
    $file = $root."/classes";
    $find = preg_split('~[\\\]~',$class);     
    $make = $find[count($find)-1];      
    unset($find[count($find)-1]);   
    $newcc = implode('/',$find); 
    $space = $file.($newcc ? '/'.$newcc : null);  
    if(!is_dir($space)){
        mkdir($space, 0755, true);
    }    
    if(!file_exists("$space/$make.php")){
        file_put_contents("$space/$make.php","<?php".($find? "\nnamespace ".implode('\\',$find).";" : null)."\n\nclass $make {\n\n\tprivate \$static;\n\n\tpublic function __construct(\$args=[]){\n\t\t// start here ..\n\t}\n}");
		chmod("$space/$make.php", 0644); 
    }
    require_once "$space/$make.php";   
});