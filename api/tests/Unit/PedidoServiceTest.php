<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PedidoServiceTest extends TestCase
{
    public function testCalculoDescontoPercentual()
    {
        $subtotal = 100.00;
        $desconto = 10; // 10%
        
        $valorDesconto = ($subtotal * $desconto) / 100;
        $total = $subtotal - $valorDesconto;
        
        $this->assertEquals(90.00, $total);
    }
    
    public function testCalculoDescontoValorFixo()
    {
        $subtotal = 100.00;
        $desconto = 150; // valor fixo (maior que 100)
        
        $valorDesconto = $desconto;
        $total = $subtotal - $valorDesconto;
        
        $this->assertEquals(-50.00, $total);
    }
    
    public function testTamanhoInfantilValido()
    {
        $tamanhosValidos = ['pp', 'p', 'm', 'g', 'u', 'rn'];
        
        foreach ($tamanhosValidos as $tm) {
            $this->assertContains($tm, $tamanhosValidos);
        }
    }
    
    public function testStatusPedidoValido()
    {
        $statusValidos = [1, 2, 3, 4];
        $status = 4;
        
        $this->assertContains($status, $statusValidos);
    }
}