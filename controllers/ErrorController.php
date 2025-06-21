<?php

class ErrorController
{
    public function handle($param,$self,$http_code)
    {
        print "ERROR ! $http_code";
    }
}
