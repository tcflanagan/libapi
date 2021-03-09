<?php

class Route {
    private static $routes = Array();
    private static $pathNotFound = null;
    private static $methodNotAllowed = null;

    public static function add($expression, $function, $method='get') {
        array_push(
            self::$routes,
            Array(
                'expression' => $expression,
                'function' => $function,
                'method' => $method
        ));
    }

    public static function setPathNotFound($function) {
        self::$pathNotFound = $function;
    }

    public static function setMethodNotAllowed($function) {
        self::$methodNotAllowed = $function;
    }

    public static function run($basePath = '/') {

        // Parse current URL
        $parsedUrl = parse_url($_SERVER['REQUEST_URI']);

        if (isset($parsedUrl['path'])) {
            $path = $parsedUrl['path'];
        }
        else {
            $path = '/';
        }

        // Get current request method
        $method = $_SERVER['REQUEST_METHOD'];

        $pathMatchFound = false;
        $routeMatchFound = false;

        foreach(self::$routes as $route) {
            
            // Add basePath to expression to match
            if ($basePath != '' && $basePath != "/") {
                $route['expression'] = "($basePath)" . $route['expression'];
            }

            // Add start and end regex markers
            $route['expression'] = '^' . $route['expression'] . '$';

            // Check for match
            if (preg_match('#' . $route['expression'] . '#', $path, $matches)) {
                $pathMatchFound = true;

                // Check method match
                if (strtolower($method) == strtolower($route['method'])) {
                    // Remove first element (contains whole string)
                    array_shift($matches);

                    // Remove the base path if it was added
                    if ($basePath != '' && $basePath != '/') {
                        array_shift($matches);
                    }

                    // Call function
                    call_user_func_array($route['function'], $matches);

                    $routeMatchFound = true;

                    break;
                }
            }
        }

        if (!$routeMatchFound) {
            if ($pathMatchFound) {
                // Path but not method was matched:
                if (strtolower($method) == strtolower('options')) {
                }
                else {
                    http_response_code(405); // Method not allowed
                    if (self::$methodNotAllowed) {
                        call_user_func_array(self::$methodNotAllowed, Array($path, $method));
                    }
                }
            }
            else {
                // Neither was matched
                http_response_code(404); // Not found
                if (self::$pathNotFound) {
                    call_user_func_array(self::$pathNotFound, Array($path));
                }
            }
        }
    }
}