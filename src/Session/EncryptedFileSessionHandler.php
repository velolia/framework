<?php

declare(strict_types=1);

namespace Velolia\Session;

use SessionHandlerInterface;
use Velolia\Encryption\Encrypter;
use Exception;

class EncryptedFileSessionHandler implements SessionHandlerInterface
{
    public function __construct(
        protected string $path,
        protected Encrypter $encrypter
    ) {}

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $file = $this->path . '/' . $id;

        if (file_exists($file)) {
            $contents = file_get_contents($file);

            if ($contents === '') {
                return '';
            }

            try {
                return $this->encrypter->decrypt($contents);
            } catch (Exception $e) {
                return '';
            }
        }

        return '';
    }

    public function write(string $id, string $data): bool
    {
        $file = $this->path . '/' . $id;

        try {
            $encryptedData = $this->encrypter->encrypt($data);
            return file_put_contents($file, $encryptedData) !== false;
        } catch (Exception $e) {
            return false;
        }
    }

    public function destroy(string $id): bool
    {
        $file = $this->path . '/' . $id;

        if (file_exists($file)) {
            unlink($file);
        }

        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        $files = glob($this->path . '/*');
        if ($files === false) return 0;

        $deleted = 0;

        foreach ($files as $file) {
            if (file_exists($file) && filemtime($file) + $max_lifetime < time()) {
                unlink($file);
                $deleted++;
            }
        }

        return $deleted;
    }
}
