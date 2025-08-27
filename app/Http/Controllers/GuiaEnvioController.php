<?php

namespace App\Http\Controllers;

use App\Models\GuiaEnvio;
use App\Services\GuiaEnvioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GuiaEnvioController extends Controller
{
    private $guiaenvioService;

    public function __construct(GuiaEnvioService $guiaenvioService)
    {
        $this->guiaenvioService = $guiaenvioService;
    }

    public function index()
    {
        $guias = GuiaEnvio::with('unidadesEmpaque')
            ->delUsuario(Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('guias-index', compact('guias'));
    }

    public function create()
    {
        return view('crear-guia-envio');
    }

    public function store(Request $request)
    {
        $request->validate([
            'des_ciudad' => 'required|string|max:255',
            'des_direccion' => 'required|string',
            'nom_contacto' => 'required|string|max:255',
            'des_departamento_destino' => 'required|string|max:255',
            'des_dice_contener' => 'required|string',
            'num_valor_declarado_total' => 'required|numeric|min:0',
            'des_telefono' => 'nullable|string|max:50',
            'des_correo_electronico' => 'nullable|email',
            'referencia_cliente' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string',
            
            // Validación para unidades de empaque
            'unidades_empaque' => 'required|array|min:1',
            'unidades_empaque.*.num_alto' => 'required|numeric|min:0.1',
            'unidades_empaque.*.num_ancho' => 'required|numeric|min:0.1',
            'unidades_empaque.*.num_largo' => 'required|numeric|min:0.1',
            'unidades_empaque.*.num_peso' => 'required|numeric|min:0.1',
            'unidades_empaque.*.num_cantidad' => 'required|integer|min:1',
            'unidades_empaque.*.des_dice_contener' => 'required|string'
        ]);

        try {
            // Crear la guía principal
            $guia = GuiaEnvio::create([
                'user_id' => Auth::id(),
                'des_ciudad' => $request->des_ciudad,
                'des_direccion' => $request->des_direccion,
                'nom_contacto' => $request->nom_contacto,
                'des_departamento_destino' => $request->des_departamento_destino,
                'des_dice_contener' => $request->des_dice_contener,
                'num_valor_declarado_total' => $request->num_valor_declarado_total,
                'des_telefono' => $request->des_telefono,
                'des_correo_electronico' => $request->des_correo_electronico,
                'referencia_cliente' => $request->referencia_cliente,
                'observaciones' => $request->observaciones,
                'num_piezas' => array_sum(array_column($request->unidades_empaque, 'num_cantidad'))
            ]);

            // Crear las unidades de empaque
            foreach ($request->unidades_empaque as $unidad) {
                $guia->unidadesEmpaque()->create([
                    'num_alto' => $unidad['num_alto'],
                    'num_ancho' => $unidad['num_ancho'],
                    'num_largo' => $unidad['num_largo'],
                    'num_peso' => $unidad['num_peso'],
                    'num_cantidad' => $unidad['num_cantidad'],
                    'des_dice_contener' => $unidad['des_dice_contener'],
                    'nom_unidad_empaque' => 'generico',
                    'des_unidad_longitud' => 'cm',
                    'des_unidad_peso' => 'kg',
                    'des_id_archivo_origen' => '0'
                ]);
            }

            // Generar la guía con Servientrega
            $resultado = $this->guiaenvioService->generarGuia($guia);

            if ($resultado['success']) {
                return redirect()->route('show', $guia)
                    ->with('success', 'Guía generada exitosamente. Número: ' . $resultado['numero_guia']);
            } else {
                return back()->withErrors('Error al generar la guía en Servientrega')
                    ->withInput();
            }

        } catch (\Exception $e) {
            return back()->withErrors('Error: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(GuiaEnvio $guia)
    {
        $this->authorize('view', $guia);
        
        $guia->load('unidadesEmpaque');

        return view('show', compact('guia'));
    }

    public function regenerar(GuiaEnvio $guia)
    {
        $this->authorize('update', $guia);

        try {
            $resultado = $this->guiaenvioService->generarGuia($guia);

            if ($resultado['success']) {
                return back()->with('success', 'Guía regenerada exitosamente. Número: ' . $resultado['numero_guia']);
            } else {
                return back()->withErrors('Error al regenerar la guía');
            }

        } catch (\Exception $e) {
            return back()->withErrors('Error: ' . $e->getMessage());
        }
    }
}