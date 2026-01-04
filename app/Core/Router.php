<?php
namespace App\Core;

/**
 * Router compatible con PHP 7.4 (sin union types ni propiedades tipadas).
 */
class Router {
    /** @var array */
    private $routes;

    public function __construct() {
        $this->routes = ['GET' => [], 'POST' => []];
    }

    /** @param mixed $handler callable o [Clase::class, 'metodo'] */
    public function get($path, $handler) { $this->routes['GET'][$path] = $handler; }

    /** @param mixed $handler callable o [Clase::class, 'metodo'] */
    public function post($path, $handler) { $this->routes['POST'][$path] = $handler; }

    /** @return array [handler, params] */
    private function match($method, $uri) {
        $uri = rtrim(parse_url($uri, PHP_URL_PATH) ?: '/', '/') ?: '/';
        $table = isset($this->routes[$method]) ? $this->routes[$method] : [];

        foreach ($table as $pattern => $handler) {
            $regex = '#^' . preg_replace('#\\{([a-zA-Z_][a-zA-Z0-9_]*)\\}#', '(?P<$1>[^/]+)', rtrim($pattern,'/')) . '$#';
            if (preg_match($regex, rtrim($uri,'/'), $m)) {
                $params = array();
                foreach ($m as $k => $v) if (is_string($k)) $params[$k] = $v;
                return array($handler, $params);
            }
        }
        return array(null, array());
    }

    public function dispatch() {
        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
        $uri    = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';

        list($handler, $params) = $this->match($method, $uri);
        if (!$handler) { http_response_code(404); echo "404 - No encontrado"; return; }

        if (is_array($handler)) {
            $class = $handler[0];
            $methodName = $handler[1];
            $obj = new $class();
            call_user_func_array(array($obj, $methodName), $params);
        } else {
            if (!empty($params)) {
                call_user_func($handler, $params);
            } else {
                call_user_func($handler);
            }
        }
    }
}
