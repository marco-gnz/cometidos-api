<?php

namespace App\Http\Controllers\Admin\Configuration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuration\UpdateConfigurationRequest;
use App\Models\Configuration;
use Illuminate\Http\Request;

class ConfigurationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function getConfiguration($establecimiento_id)
    {
        try {
            $configurations = Configuration::where('establecimiento_id', $establecimiento_id)->get();

            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => null,
                    'message'       => null,
                    'data'          => $configurations
                )
            );
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function updateConfiguration($id, UpdateConfigurationRequest $request)
    {
        try {
            $configuration = Configuration::find($id);

            if($configuration){
                $update = $configuration->update(['valor' => $request->valor]);
                if($update){
                    $configuration = $configuration->fresh();
                    return response()->json(
                        array(
                            'status'        => 'success',
                            'title'         => "Configuración modificada con éxito.",
                            'message'       => null,
                            'data'          => $configuration
                        )
                    );
                }

            }

        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }
}
