<?php

declare(strict_types=1);

namespace Velolia\Database;

use IteratorAggregate;
use ArrayIterator;
use Traversable;

class Paginator implements IteratorAggregate
{
    protected array $items;
    protected int $total;
    protected int $perPage;
    protected int $currentPage;
    protected int $lastPage;

    public function __construct(array $items, int $total, int $perPage, int $currentPage)
    {
        $this->items = $items;
        $this->total = $total;
        $this->perPage = $perPage;
        $this->currentPage = $currentPage;
        $this->lastPage = (int) ceil($total / $perPage);
    }

    public function items(): array
    {
        return $this->items;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function total(): int
    {
        return $this->total;
    }

    public function perPage(): int
    {
        return $this->perPage;
    }

    public function currentPage(): int
    {
        return $this->currentPage;
    }

    public function lastPage(): int
    {
        return $this->lastPage;
    }

    public function hasPages(): bool
    {
        return $this->lastPage > 1;
    }

    public function links(): string
    {
        if (!$this->hasPages()) {
            return '';
        }

        $html = '<nav aria-label="Page navigation"><ul class="pagination">';

        // Previous Link
        if ($this->currentPage <= 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
        } else {
            $url = $this->url($this->currentPage - 1);
            $html .= '<li class="page-item"><a class="page-link" href="' . $url . '">Previous</a></li>';
        }

        // Page Numbers
        for ($i = 1; $i <= $this->lastPage; $i++) {
            $active = ($i === $this->currentPage) ? ' active' : '';
            $url = $this->url($i);
            $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $url . '">' . $i . '</a></li>';
        }

        // Next Link
        if ($this->currentPage >= $this->lastPage) {
            $html .= '<li class="page-item disabled"><span class="page-link">Next</span></li>';
        } else {
            $url = $this->url($this->currentPage + 1);
            $html .= '<li class="page-item"><a class="page-link" href="' . $url . '">Next</a></li>';
        }

        $html .= '</ul></nav>';

        return $html;
    }

    protected function url(int $page): string
    {
        $params = $_GET;
        $params['page'] = $page;
        $queryString = http_build_query($params);
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        
        return $path . '?' . $queryString;
    }
}
