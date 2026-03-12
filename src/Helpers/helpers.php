<?php

if (! function_exists('app')) {
    function app($abstract = null, array $parameters = [])
    {
        if (is_null($abstract)) {
            return Velolia\DI\Container::getInstance();
        }
        return Velolia\DI\Container::getInstance()->make($abstract, $parameters);
    }
}

if (! function_exists('base_path')) {
    function base_path(string $path = '')
    {
        return app()->basePath($path);
    }
}

if (!function_exists('e')) {
    function e($string): string
    {
        if ($string === null) {
            return '';
        }
        if ($string instanceof \Velolia\View\HtmlString) {
            return $string->toHtml();
        }
        return htmlspecialchars((string) $string, ENT_QUOTES, 'UTF-8', false);
    }
}

if (! function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return app('env')->get($key, $default);
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
    function view(string $view, array $data = [])
    {
        return app('view')->make($view, $data);
    }
}

if (! function_exists('session')) {
    function session($key = null, $default = null)
    {
        $session = app('session');
        if (is_null($key)) {
            return $session;
        }
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $session->put($k, $v);
            }
            return $session;
        }
        return $session->get($key, $default);
    }
}

if (! function_exists('redirect')) {
    function redirect(string $url = '', int $status = 302)
    {
        return new \Velolia\Http\RedirectResponse($url, $status);
    }
}

if (! function_exists('back')) {
    function back(int $status = 302, array $headers = [])
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        return new \Velolia\Http\RedirectResponse($referer, $status, $headers);
    }
}

if (! function_exists('route')) {
    function route(string $name, mixed $params = [])
    {
        if (!is_array($params)) {
            $params = [$params];
        }
        return app('router')->getRouteUrl($name, $params);
    }
}

if (! function_exists('to_route')) {
    function to_route(string $name, array $params = [], int $status = 302)
    {
        return redirect(route($name, $params), $status);
    }
}

if (! function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return app(\Velolia\Security\CsrfManager::class)->token();
    }
}

if (! function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . csrf_token() . '">';
    }
}

if (! function_exists('url')) {
    function url(string $path = '')
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return rtrim($protocol . $host, '/') . '/' . ltrim($path, '/');
    }
}

if (! function_exists('asset')) {
    function asset(string $path)
    {
        return url($path);
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
    function response($content = '', $status = 200, $headers = [])
    {
        return app()->make(\Velolia\Http\Response::class, ['content' => $content, 'statusCode' => $status, 'headers' => $headers]);
    }
}

if (! function_exists('old')) {
    function old(?string $key = null, mixed $default = null): mixed
    {
        $oldInput = session('_old_input', []);

        if (is_null($key)) {
            return $oldInput;
        }

        return $oldInput[$key] ?? $default;
    }
}

if (! function_exists('errors')) {
    function errors(): array
    {
        return session('errors', []);
    }
}

if (! function_exists('error')) {
    function error(string $field): ?string
    {
        $errors = errors();
        if (!isset($errors[$field])) {
            return null;
        }
        
        return is_array($errors[$field]) ? $errors[$field][0] : $errors[$field];
    }
}

if (! function_exists('has_error')) {
    function has_error(?string $field = null): bool
    {
        $errors = errors();

        if (is_null($field)) {
            return !empty($errors);
        }

        return isset($errors[$field]) && !empty($errors[$field]);
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

if (! function_exists('abort')) {
    function abort(int $code, string $message = '')
    {
        throw new \Velolia\Exceptions\HttpException($code, $message);
    }
}

if (! function_exists('encrypt')) {
    function encrypt(mixed $value): string
    {
        return app('encrypter')->encrypt($value);
    }
}

if (! function_exists('decrypt')) {
    function decrypt(string $payload): mixed
    {
        return app('encrypter')->decrypt($payload);
    }
}
if (! function_exists('logger')) {
    function logger(?string $message = null, string $level = 'info', array $context = [])
    {
        $log = app('log');
        if (is_null($message)) {
            return $log;
        }
        return $log->log($level, $message, $context);
    }
}

if (! function_exists('auth')) {
    function auth()
    {
        return app('auth');
    }
}

if (! function_exists('aura')) {
    /**
     * Get the Velolia Aura API token authentication manager.
     *
     * @return \Velolia\Auth\Aura\AuraManager
     */
    function aura()
    {
        return app('aura');
    }
}

if (! function_exists('can')) {
    function can(string $ability, $arguments = []): bool
    {
        return \Velolia\Support\Facades\Gate::allows($ability, $arguments);
    }
}

if (! function_exists('cannot')) {
    function cannot(string $ability, $arguments = []): bool
    {
        return \Velolia\Support\Facades\Gate::denies($ability, $arguments);
    }
}

if (! function_exists('class_basename')) {
    function class_basename($class)
    {
        $class = is_object($class) ? get_class($class) : $class;
        return basename(str_replace('\\', '/', $class));
    }
}

if (! function_exists('bcrypt')) {
    function bcrypt($pass)
    {
        return password_hash($pass, PASSWORD_BCRYPT);
    }
}

