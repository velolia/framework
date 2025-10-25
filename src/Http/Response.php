<?php

declare(strict_types=1);

namespace Velolia\Http;

use InvalidArgumentException;

class Response
{
    protected $content;
    protected $statusCode;
    protected $headers;
    
    public function __construct($content = '', $status = 200, array $headers = [])
    {
        $this->headers = [];
        $this->setContent($content);
        $this->setStatusCode($status);
        
        foreach ($headers as $key => $value) {
            $this->headers[strtolower($key)] = $value;
        }
    }
    
    /**
     * Factory method untuk JSON response
     */
    public static function json($data = null, $status = 200, array $headers = [], $options = 0)
    {
        $response = new static('', $status, $headers);
        
        return $response->setJson($data, $options);
    }
    
    /**
     * Set JSON data
     */
    public function setJson($data = null, $options = 0)
    {
        $json = json_encode($data, $options);
        
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidArgumentException(json_last_error_msg());
        }
        
        return $this->setContent($json)->header('Content-Type', 'application/json; charset=UTF-8');
    }
    
    /**
     * Set content
     */
    public function setContent($content)
    {
        if (is_array($content) || is_object($content)) {
            return $this->setJson($content);
        }
        
        $this->content = (string) $content;
        
        return $this;
    }
    
    /**
     * Get content
     */
    public function getContent()
    {
        return $this->content;
    }
    
    /**
     * Set status code
     */
    public function setStatusCode($code)
    {
        $this->statusCode = $code = (int) $code;
        
        if ($this->isInvalid()) {
            throw new InvalidArgumentException(sprintf('The HTTP status code "%s" is not valid.', $code));
        }
        
        return $this;
    }
    
    /**
     * Get status code
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }
    
    /**
     * Set header
     */
    public function header($key, $value, $replace = true)
    {
        $key = strtolower($key);
        
        if ($replace || !isset($this->headers[$key])) {
            $this->headers[$key] = $value;
        }
        
        return $this;
    }
    
    /**
     * Get header
     */
    public function getHeader($key, $default = null)
    {
        $key = strtolower($key);
        
        return isset($this->headers[$key]) ? $this->headers[$key] : $default;
    }
    
    /**
     * Get all headers
     */
    public function getHeaders()
    {
        return $this->headers;
    }
    
    /**
     * Send response
     */
    public function send()
    {
        $this->sendHeaders();
        $this->sendContent();
        
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } elseif ('cli' !== PHP_SAPI) {
            static::closeOutputBuffers(0, true);
        }
        
        return $this;
    }
    
    /**
     * Send headers
     */
    public function sendHeaders()
    {
        if (headers_sent()) {
            return $this;
        }
        
        http_response_code($this->statusCode);
        
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value, false);
        }
        
        return $this;
    }
    
    /**
     * Send content
     */
    public function sendContent()
    {
        echo $this->content;
        return $this;
    }

    /**
     * Check if response is invalid
     */
    public function isInvalid()
    {
        return $this->statusCode < 100 || $this->statusCode >= 600;
    }
    
    /**
     * Close output buffers
     */
    public static function closeOutputBuffers($targetLevel, $flush)
    {
        $status = ob_get_status(true);
        $level = count($status);
        $flags = PHP_OUTPUT_HANDLER_REMOVABLE | ($flush ? PHP_OUTPUT_HANDLER_FLUSHABLE : PHP_OUTPUT_HANDLER_CLEANABLE);
        
        while ($level-- > $targetLevel && ($s = $status[$level]) && (!isset($s['del']) ? !isset($s['flags']) || ($s['flags'] & $flags) === $flags : $s['del'])) {
            if ($flush) {
                ob_end_flush();
            } else {
                ob_end_clean();
            }
        }
    }
    
    /**
     * Convert response to string
     */
    public function __toString()
    {
        $headers = [];
        foreach ($this->headers as $name => $value) {
            $headers[] = $name . ': ' . $value;
        }
        
        return
            sprintf('HTTP/1.1 %s %s', $this->statusCode) . "\r\n" .
            implode("\r\n", $headers) . "\r\n\r\n" .
            $this->getContent();
    }
}