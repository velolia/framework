<?php

declare(strict_types=1);

namespace Velolia\Http;

use InvalidArgumentException;

class RedirectResponse extends Response
{
    protected $targetUrl;
    
    public function __construct($url, int $status = 302, $headers = [])
    {
        parent::__construct('', $status, $headers);
        
        $this->setTargetUrl($url);
        
        if (!$this->isRedirect()) {
            throw new InvalidArgumentException(sprintf('The HTTP status code is not a redirect ("%s" given).', $status));
        }
        
        if (301 == $status && !array_key_exists('cache-control', array_change_key_case($headers, CASE_LOWER))) {
            $this->headers['cache-control'] = 'no-cache, must-revalidate';
        }
    }
    
    /**
     * Factory method untuk redirect
     */
    public static function create($url = '', int $status = 302, $headers = [])
    {
        return new static($url, $status, $headers);
    }
    
    /**
     * Get target URL
     */
    public function getTargetUrl()
    {
        return $this->targetUrl;
    }
    
    /**
     * Set target URL
     */
    public function setTargetUrl($url)
    {
        if (empty($url)) {
            throw new InvalidArgumentException('Cannot redirect to an empty URL.');
        }
        
        $this->targetUrl = $url;
        
        $this->setContent(
            sprintf('<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <meta http-equiv="refresh" content="0;url=%1$s" />
        <title>Redirecting to %1$s</title>
    </head>
    <body>
        Redirecting to <a href="%1$s">%1$s</a>.
    </body>
</html>', htmlspecialchars($url, ENT_QUOTES, 'UTF-8'))
        );
        
        $this->headers['location'] = $url;
        
        return $this;
    }
    
    /**
     * Check if response is redirect
     */
    public function isRedirect($location = null)
    {
        return in_array($this->statusCode, [201, 301, 302, 303, 307, 308]) && (null === $location ?: $location == $this->headers['location']);
    }

    public function with($key, $value)
    {
        session()->flash($key, $value);
        return $this;
    }

    public function withErrors($errors)
    {
        session()->flash('errors', $errors);
        return $this;
    }

    public function withInput()
    {
        session()->flash('_old_input', $_POST);
        return $this;
    }
}