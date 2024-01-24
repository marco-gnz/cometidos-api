<?php

namespace App\Http\Controllers\pdf;

use App\Http\Controllers\Controller;
use App\Models\Convenio;
use Illuminate\Http\Request;

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

            return $pdf->stream("Convenio de cometido funcional.pdf");
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }
}
