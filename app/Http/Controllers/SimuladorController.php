<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SebastianBergmann\Environment\Console;

class SimuladorController extends Controller
{
    private $dadosSimulador;
    private $simulacao = [];

    public function simular(Request $request)
    {
        $this->carregarArquivoDadosSimulador()
             ->simularEmprestimo($request->valor_emprestimo)
             ->filtrarInstituicao($request->instituicoes)
             ->filtrarConvenios($request->convenios)
             ->filtrarParcelas($request->parcelas)
        ;
        return \response()->json($this->simulacao);
    }

    private function carregarArquivoDadosSimulador() : self
    {
        $this->dadosSimulador = json_decode(\File::get(storage_path("app/public/simulador/taxas_instituicoes.json")));
        return $this;
    }

    private function simularEmprestimo(float $valorEmprestimo) : self
    {
        foreach ($this->dadosSimulador as $dados) {
            $this->simulacao[$dados->instituicao][] = [
                "taxa"            => $dados->taxaJuros,
                "parcelas"        => $dados->parcelas,
                "valor_parcela"    => $this->calcularValorDaParcela($valorEmprestimo, $dados->coeficiente),
                "convenio"        => $dados->convenio,
            ];
        }
        return $this;
    }

    private function calcularValorDaParcela(float $valorEmprestimo, float $coeficiente) : float
    {
        return round($valorEmprestimo * $coeficiente, 2);
    }

    private function filtrarInstituicao(array $instituicoes) : self
    {
        if (\count($instituicoes))
        {
            $arrayAux = [];
            foreach ($instituicoes AS $key => $instituicao)
            {
                if (\array_key_exists($instituicao, $this->simulacao))
                {
                     $arrayAux[$instituicao] = $this->simulacao[$instituicao];
                }
            }
            $this->simulacao = $arrayAux;
        }
        return $this;
    }

    private function filtrarConvenios(array $convenios) : self
    {
        if (\count($convenios))
        {
            $arrayAux = [];
            foreach ($this->simulacao AS $key => $value)
            {
                $arrayAux2 = [];
                foreach ($value AS $key2 => $value2)
                {
                    if(\in_array($value2["convenio"], $convenios)) {
                        \array_push($arrayAux2 ,$value2);
                    }
                }

                if (\count($arrayAux2)){
                    $arrayAux[$key] = $arrayAux2;
                }
            }
            $this->simulacao = $arrayAux;
        }
        return $this;
    }

    private function filtrarParcelas(int $parcela) : self
    {
        $arrayAux = [];
        foreach ($this->simulacao AS $key => $value)
        {
            $arrayAux2 = [];
            foreach ($value AS $key2 => $value2)
            {
                if($value2["parcelas"] == $parcela) {
                    \array_push($arrayAux2 ,$value2);
                }
            }

            if (\count($arrayAux2)){
                $arrayAux[$key] = $arrayAux2;
            }
        }
        $this->simulacao = $arrayAux;
        return $this;
    }
}