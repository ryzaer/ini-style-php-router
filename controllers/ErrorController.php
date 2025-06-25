<?php

class ErrorController
{
    public function handle($self,$param)
    {
        print "ERROR ! {$param->http_code}: the page is not associate to this app";
    }
}
