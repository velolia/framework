<?php

declare(strict_types=1);

namespace Velolia\Http;

use InvalidArgumentException;

class RedirectResponse extends Response
{
    public function __construct(string $url, int $status = 302, array $headers = [])
    {
        parent::__construct('', $status, array_merge($headers, ['Location' => $url]));
    }

    public function with(string $key, $value): self
    {
        session()->flash($key, $value);
        return $this;
    }

    public function withErrors($errors): self
    {
        app('session')->flash('errors', $errors);
        return $this;
    }

    public function withInput(): self
    {
        app('session')->flash('_old_input', $_POST);
        return $this;
    }

    public function route(string $name, array $params = []): self
    {
        $url = app('router')->getRouteUrl($name, $params);
        $this->headers['Location'] = $url;
        return $this;
    }

    public function back(): self
    {
        $url = $_SERVER['HTTP_REFERER'] ?? '/';
        $this->headers['Location'] = $url;
        return $this;
    }

    public function intended(string $default = '/'): self
    {
        $url = session()->get('url.intended', $default);
        session()->delete('url.intended');
        
        $this->headers['Location'] = $url;
        return $this;
    }
}
