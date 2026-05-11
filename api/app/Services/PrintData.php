<?php

namespace App\Services;

use DateTime;

class PrintData
{
    public $pedido;
    public $cliente;
    public $vendedor;
    public array $itens = [];
    public array $pagamentos = [];
    public ?string $qrCodeUrl;
    public DateTime $generatedAt;
    public ?string $printedBy;
    public array $calculo = [];

    public function __construct()
    {
        $this->generatedAt = new DateTime();
    }
}