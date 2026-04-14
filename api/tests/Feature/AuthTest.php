<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class AuthTest extends TestCase
{
    public function testLoginSemCredenciais()
    {
        $response = $this->call('POST', '/api/auth/login', []);
        
        $this->assertEquals(422, $response->status());
        $this->assertJson($response->getContent());
    }

    public function testLoginUsuarioInvalido()
    {
        $response = $this->call('POST', '/api/auth/login', [
            'usuario' => 'invalido_xyz',
            'senha' => 'senha123'
        ]);
        
        $this->assertEquals(401, $response->status());
    }

    public function testApiRotaPublica()
    {
        $response = $this->call('GET', '/');
        
        $this->assertEquals(200, $response->status());
    }
}