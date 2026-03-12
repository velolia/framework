<?php

declare(strict_types=1);

namespace Velolia\Support;

use Velolia\Core\Application;

abstract class ServiceProvider
{
    /**
     * Aplikasi Velolia (Container).
     */
    protected Application $app;

    /**
     * Menandakan apakah provider ini dimuat secara malas (lazy/deferred).
     */
    protected bool $defer = false;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Daftarkan binding/layanan ke dalam kontainer (DI).
     * Wajib diimplementasikan oleh turunan.
     */
    abstract public function register(): void;

    /**
     * Fungsi opsional untuk bootstrapping layanan.
     */
    public function boot(): void
    {
        // 
    }

    /**
     * Tentukan apakah provider ini adalah "deferred" provider.
     */
    public function isDeferred(): bool
    {
        return $this->defer;
    }

    /**
     * Dapatkan daftar layanan yang disediakan/di-bind oleh provider ini.
     * Hanya digunakan jika $defer = true.
     */
    public function provides(): array
    {
        return [];
    }
}
