<?php

namespace App\Http\Controllers\pdf;

use App\Http\Controllers\Controller;
use App\Http\Resources\Rendicion\StatusRendicionResource;
use App\Models\Convenio;
use App\Models\Documento;
use App\Models\EstadoInformeCometido;
use App\Models\EstadoProcesoRendicionGasto;
use App\Models\InformeCometido;
use App\Models\InstitucionalDocumento;
use App\Models\ProcesoRendicionGasto;
use App\Models\Solicitud;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Dompdf\Options;
use Dompdf\Dompdf;
use Illuminate\Support\Facades\View;
use App\Traits\StatusSolicitudTrait;

class PdfController extends Controller
{
    use StatusSolicitudTrait;

    private function paginatePdf($pdf)
    {
        $pdf->output();
        $domPdf = $pdf->getDomPDF();

        $canvas = $domPdf->get_canvas();
        return $canvas->page_text(500, 800, "Página {PAGE_NUM} de {PAGE_COUNT}", null, 10, [0, 0, 0]);
    }

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

            $this->paginatePdf($pdf);

            return $pdf->stream("CCF N° {$convenio->codigo}.pdf");
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function showInformeCometido($uuid)
    {
        try {
            $informe = InformeCometido::where('uuid', $uuid)->firstOrFail();
            $this->authorize('view', $informe);
            $pdf = \PDF::loadView(
                'pdf.informecometido',
                [
                    'informe' => $informe
                ]
            );

            $pdf->setOptions([
                'chroot'  => public_path('/img/')
            ]);


            $this->paginatePdf($pdf);

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
                return response()->view('errors.notfile', compact('documento'));
            }

            $content = Storage::disk('public')->get($documento->url);
            $safeFileName = str_replace([',', ';', ' '], '_', $documento->nombre);

            return response($content)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "inline; filename={$safeFileName}; filename*=UTF-8''{$safeFileName}");
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function showDocumentoInstitucional($uuid)
    {
        try {
            $documento  = InstitucionalDocumento::where('uuid', $uuid)->first();
            if (!$documento) {
                return response()->view('errors.404');
            }
            $filePath   = Storage::disk('public')->path($documento->url);

            if (!Storage::disk('public')->exists($documento->url)) {
                return response()->view('errors.404');
            }

            $content = Storage::disk('public')->get($documento->url);
            $safeFileName = str_replace([',', ';', ' '], '_', $documento->nombre);

            return response($content)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', "inline; filename={$safeFileName}; filename*=UTF-8''{$safeFileName}");
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function showGastosCometidoFuncional($uuid)
    {
        try {
            $proceso_rendicion_gasto = ProcesoRendicionGasto::where('uuid', $uuid)->with('cuentaBancaria')->first();

            if (!$proceso_rendicion_gasto) {
                return response()->view('errors.404');
            }

            if (($proceso_rendicion_gasto) && ($proceso_rendicion_gasto->solicitud->status === Solicitud::STATUS_ANULADO)) {
                return response()->view('errors.401');
            }

            if (($proceso_rendicion_gasto) && ($proceso_rendicion_gasto->status !== EstadoProcesoRendicionGasto::STATUS_APROBADO_N && $proceso_rendicion_gasto->status !== EstadoProcesoRendicionGasto::STATUS_APROBADO_S)) {
                return response()->view('errors.401');
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
                                'user_by'            => $estado->userBy ? $estado->userBy->abreNombres() : null
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

            $this->paginatePdf($pdf);

            return $pdf->stream(env('APP_NAME') . "GCF N° {$proceso_rendicion_gasto->n_folio}.pdf");
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

            if (($solicitud) && ($solicitud->status === Solicitud::STATUS_ANULADO)) {
                return response()->view('errors.401');
            }

            /* $solicitud->load(['procesoRendicionGastos.rendicionesfinanzas' => function ($query) {
                $query->where('rinde_gasto', true)->where('last_status', 1);
            }]); */

            $ultimoCalculo = $solicitud->getLastCalculo();

            $solicitud->{'navStatus'}    = $this->navStatusSolicitud($solicitud);
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

            $pdf->output();
            $domPdf = $pdf->getDomPDF();

            $canvas = $domPdf->get_canvas();
            $canvas->page_text(520, 990, "Pagina {PAGE_NUM} de {PAGE_COUNT}", null, 8, [0, 0, 0]);

            return $pdf->stream(env('APP_NAME') . "Resolución N° {$solicitud->codigo}.pdf");
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }
}
