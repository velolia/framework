<?php

if (! function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        return rtrim(dirname(__DIR__, 5), '/') . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (! function_exists('app')) {
    function app($abstract = null, array $parameters = [])
    {
        if (is_null($abstract)) {
            return Velolia\DI\Container::getInstance();
        }
        return Velolia\DI\Container::getInstance()->make($abstract, $parameters);
    }
}

if (! function_exists('config')) {
    function config($key = null, $default = null)
    {
        if (is_null($key)) {
            return app('config');
        }

        if (is_array($key)) {
            app('config')->set($key);
            return null;
        }

        return app('config')->get($key, $default);
    }
}

if (! function_exists('view')) {
    function view($view, $data = [])
    {
        return app('view')->render($view, $data);
    }
}

if (!function_exists('e')) {
    /**
     * Escape HTML special characters in a string.
     *
     * @param  string  $string
     * @return string
     */
    function e($string) {
        if ($string === null) {
            return '';
        }
        return htmlspecialchars((string) $string, ENT_QUOTES, 'UTF-8', false);
    }
}

if (! function_exists('session')) {
    function session($key = null, $default = null): mixed
    {
        if (is_null($key)) {
            return app('session');
        }

        if (is_array($key)) {
            return app('session')->set($key);
        }

        return app('session')->get($key, $default);
    }
}

if (! function_exists('old')) {
    function old(string $key, mixed $value = null): mixed
    {
        $oldInput = session()->getFlash('_old_input', []);

        if (is_null($key)) {
            return $oldInput;
        }

        return $oldInput[$key] ?? $value;
    }
}

if (! function_exists('get_flash')) {
    function get_flash(string $key, mixed $default = null): mixed
    {
        return session()->getFlash($key, $default);
    }
}

if (! function_exists('has_flash')) {
    function has_flash(string $key): bool
    {
        return session()->hasFlash($key);
    }
}

if (! function_exists('errors')) {
    function errors()
    {
        return session()->getFlash('errors', []);
    }
}

if (! function_exists('error')) {
    function error(string $field)
    {
        $errors = errors();
        return $errors[$field][0] ?? null;
    }
}

if (! function_exists('has_error')) {
    function has_error(?string $field = null)
    {
        $errors = errors();
        if (is_null($field)) {
            return !empty($errors);
        }
        return isset($errors[$field]) && !empty($errors[$field]);
    }
}

if (! function_exists('request')) {
    function request(?string $key = null, mixed $default = null)
    {
        $request = app('request');

        if (is_null($key)) {
            return $request;
        }

        if (is_array($key)) {
            return $request->only($key);
        }

        return $request->input($key, $default);
    }
}

if (! function_exists('response')) {
    function response(mixed $content = '', int $status = 200, array $headers = [])
    {
        return app('response');
    }
}

if (! function_exists('redirect')) {
    function redirect(string $url = '', int $status = 302, array $headers = [])
    {
        return new \Velolia\Http\RedirectResponse($url, $status, $headers);
    }
}

if (! function_exists('back')) {
    function back(int $status = 302, array $headers = [])
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        return new \Velolia\Http\RedirectResponse($referer, $status, $headers);
    }
}

if (! function_exists('to_route')) {
    function to_route(string $name, array $parameters = [], bool $absolute = true)
    {
        return redirect(route($name, $parameters, $absolute));
    }
}

if (!function_exists('url')) {
    function url(string $path = '', bool $absolute = true): string
    {
        return app('url')->to($path, [], $absolute);
    }
}

if (! function_exists('route')) {
    function route(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        return app('router')->route($name, $parameters, $absolute);
    }
}

if (!function_exists('bcrypt')) {
    function bcrypt($pass)
    {
        return password_hash($pass, PASSWORD_BCRYPT);
    }
}

if (!function_exists('collect')) {
    function collect(array $value = [])
    {
        return new \Velolia\Support\Collection($value);
    }
}

if (!function_exists('now')) {
    function now(): int
    {
        return (int)microtime(true);
    }
}

if (!function_exists('class_basename')) {
    function class_basename($class)
    {
        $class = is_object($class) ? get_class($class) : $class;
        return basename(str_replace('\\', '/', $class));
    }
}

if (!function_exists('storage_path')) {
    function storage_path(string $path = ''): string
    {
        return base_path('storage' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }
}

if (! function_exists('value')) {
    function value(mixed $value, ...$args): mixed
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }
}

if (! function_exists('abort')) {
    function abort(int $code, string $message = ''): void
    {
        throw new Velolia\ErrorHandler\HttpException($code, $message);
    }
}
