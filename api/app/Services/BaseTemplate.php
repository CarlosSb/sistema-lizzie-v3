<?php

namespace App\Services;

abstract class BaseTemplate
{
    protected function formatCurrency(float $value): string
    {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }

    protected function formatDate(string $date): string
    {
        return date('d/m/Y', strtotime($date));
    }

    protected function formatDateTime(\DateTime $date): string
    {
        return $date->format('d/m/Y H:i:s');
    }

    abstract public function render(PrintData $data, PrintOptions $options): string;
}