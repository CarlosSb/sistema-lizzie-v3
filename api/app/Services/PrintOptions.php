<?php

namespace App\Services;

class PrintOptions
{
    public string $templateId;
    public string $format = 'pdf';
    public bool $includeQR = false;
    public string $paperSize = 'a4';
    public string $orientation = 'portrait';
    public int $copies = 1;
    public string $mode = 'complete';

    public function __construct(string $templateId)
    {
        $this->templateId = $templateId;
    }
}
