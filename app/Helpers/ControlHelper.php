<?php

namespace App\Helpers;



class ControlHelper
{
    // to ignore checking permission in middle then register the controller here
    public static function getPermIgnoreController()
    {
        return ['FileUploadController'];
    }

    public static function getControllerName()
    {
        $routeArray = app('request')->route()->getAction();
        $controllerAction = class_basename($routeArray['controller']);
        $controller = explode('@', $controllerAction);
        return $controller[0];
    }

    public static function getControllerMethod()
    {
        $routeArray = app('request')->route()->getAction();
        $controllerAction = class_basename($routeArray['controller']);
        $controller = explode('@', $controllerAction);
        return $controller[1];
    }
}
