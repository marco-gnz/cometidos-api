<?php

namespace App\Http\Controllers\pdf;

use App\Http\Controllers\Controller;
use App\Http\Resources\Rendicion\StatusRendicionResource;
use App\Models\Convenio;
use App\Models\Documento;
use App\Models\InformeCometido;
use App\Models\ProcesoRendicionGasto;
use App\Models\Solicitud;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Dompdf\Options;
use Dompdf\Dompdf;
use Illuminate\Support\Facades\View;

class PdfController extends Controller
{
    public function showConvenio($uuid)
    {
        try {
            $convenio = Convenio::where('uuid', $uuid)->firstOrFail();

            $pdf = \PDF::loadView(
                'pdf.convenio',
                [
                    'convenio'                   => $convenio
                ]
            );

            $pdf->setOptions([
                'chroot'  => public_path('/img/')
            ]);

            return $pdf->stream("CCF N° {$convenio->codigo}.pdf");
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function showInformeCometido($uuid)
    {
        try {
            $informe = InformeCometido::where('uuid', $uuid)->firstOrFail();

            $pdf = \PDF::loadView(
                'pdf.informecometido',
                [
                    'informe'                   => $informe
                ]
            );

            $pdf->setOptions([
                'chroot'  => public_path('/img/')
            ]);


            $pdf->output();
            $domPdf = $pdf->getDomPDF();

            $canvas = $domPdf->get_canvas();
            $canvas->page_text(500, 800, "Página {PAGE_NUM} de {PAGE_COUNT}", null, 10, [0, 0, 0]);

            return $pdf->stream("Informe N° {$informe->codigo}.pdf");
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function showDocumento($uuid)
    {
        try {

            $documento  = Documento::where('uuid', $uuid)->first();
            if (!$documento) {
                return response()->view('errors.404');
            }
            $filePath   = Storage::disk('public')->path($documento->url);

            if (!Storage::disk('public')->exists($documento->url)) {
                return response()->view('errors.404');
            }

            $content = file_get_contents($filePath);

            return response($content)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', "inline; filename={$documento->nombre}")
                ->header('Content-Disposition', "inline; filename={$documento->nombre}; filename*=UTF-8\'\'{$documento->nombre}");
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function showGastosCometidoFuncional($uuid)
    {
        try {
            $proceso_rendicion_gasto = ProcesoRendicionGasto::where('uuid', $uuid)->first();

            if (!$proceso_rendicion_gasto) {
                return response()->view('errors.404');
            }

            $rendiciones_particular = $proceso_rendicion_gasto->rendiciones()->where('rinde_gasto', true)->whereHas('actividad', function ($q) {
                $q->where('is_particular', true);
            })->get();

            $rendiciones_not_particular = $proceso_rendicion_gasto->rendiciones()->where('rinde_gasto', true)->whereHas('actividad', function ($q) {
                $q->where('is_particular', false);
            })->get();

            $rendiciones_finanzas = $proceso_rendicion_gasto->rendiciones()
                ->where('rinde_gasto', true)
                ->where('last_status', 1)
                ->get();

            $observaciones = [];

            foreach ($proceso_rendicion_gasto->rendiciones as $rendicion) {
                if ($rendicion->last_status === 2) {
                    $estados = $rendicion->estados()->where('status', 2)->get();
                    if (count($estados) > 0) {
                        foreach ($estados as $estado) {
                            $observaciones[] = [
                                'actividad'          => $estado->rendicionGasto->actividad->nombre,
                                'observacion'        => $estado->observacion,
                                'fecha_by_user'      => Carbon::parse($estado->fecha_by_user)->format('d-m-Y H:i:s'),
                                'user_by'            => $estado->userBy ? $estado->userBy->apellidos : null
                            ];
                        }
                    }
                }
            }

            $status_jefe_directo    = $proceso_rendicion_gasto->solicitud->estados()->where('s_role_id', 3)->first();

            $proceso_rendicion_gasto->{'rendiciones_particular'}                = $rendiciones_particular;
            $proceso_rendicion_gasto->{'rendiciones_particular_total'}          = $rendiciones_particular->sum('mount');
            $proceso_rendicion_gasto->{'rendiciones_finanzas'}                  = $rendiciones_finanzas;
            $proceso_rendicion_gasto->{'rendiciones_not_particular'}            = $rendiciones_not_particular;
            $proceso_rendicion_gasto->{'rendiciones_not_particular_total'}      = $rendiciones_not_particular->sum('mount');
            $proceso_rendicion_gasto->{'rendiciones_finanzas_total'}            = $rendiciones_finanzas->sum('mount_real');
            $proceso_rendicion_gasto->{'observaciones'}                         = $observaciones;
            $proceso_rendicion_gasto->{'status_jefe_directo'}                   = $status_jefe_directo;

            $pdf = \PDF::loadView(
                'pdf.comprobantecaja',
                [
                    'proceso_rendicion_gasto' => $proceso_rendicion_gasto
                ]
            );


            $pdf->setOptions([
                'isHtml5ParserEnabled'  => true,
                'chroot'                => public_path('/img/')
            ]);

            return $pdf->stream("GCF N° {$proceso_rendicion_gasto->n_folio}.pdf");
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function showResolucionCometidoFuncional($uuid)
    {
        try {

            $solicitud  = Solicitud::where('uuid', $uuid)->first();
            if (!$solicitud) {
                return response()->view('errors.404');
            }

            $solicitud->load(['procesoRendicionGastos.rendicionesfinanzas' => function ($query) {
                $query->where('rinde_gasto', true)->where('last_status', 1);
            }]);

            $ultimoCalculo = $solicitud->getLastCalculo();
            $solicitud->{'ultimoCalculo'} = $ultimoCalculo;


            $pdf = \PDF::loadView(
                'pdf.resolucion',
                [
                    'solicitud' => $solicitud
                ]
            );

            $pdf->setPaper('legal', 'portrait');

            $pdf->setOptions([
                'isHtml5ParserEnabled'  => true,
                'chroot'                => public_path('/img/')
            ]);

            return $pdf->stream("Cometido funcional N° {$solicitud->codigo}.pdf");
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }
}
