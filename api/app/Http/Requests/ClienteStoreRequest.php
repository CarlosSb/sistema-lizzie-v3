<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClienteStoreRequest extends FormRequest
{
    public function rules()
    {
        return [
            'razao_social' => 'required|string|max:255',
            'cnpj' => 'required|string|max:18',
            'email' => 'nullable|email',
            'telefone' => 'nullable|string|max:20',
            'status' => 'required|boolean',
        ];
    }

    public function messages()
    {
        return [
            'razao_social.required' => 'O Razão Social é obrigatório.',
            'cnpj.required' => 'O CNPJ é obrigatório.',
            'cnpj.max' => 'O CNPJ deve ter no máximo 18 caracteres.',
            'status' => 'O status deve ser informado.',
        ];
    }

    public function authorize()
    {
        // Only allow 'admin' to create clientes
        return auth()->user() && auth()->user()->controle_acesso === 'admin';
    }
}