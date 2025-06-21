<?php

class ErrorController
{
    public function handle($self,$param,$http_code)
    {
        print "ERROR ! $http_code";
    }
}
