<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <title>{{ config('app.name') }} | GCF {{ $proceso_rendicion_gasto->n_folio }}</title>

    <style type="text/css">
        body {
            margin: -28;
            font-family: Arial, sans-serif;
        }

        .header-container {
            display: table;
            width: 100%;
            height: 40px;
            color: black;
            padding: 0px;
        }

        .logo,
        .titulo,
        .fecha {
            display: table-cell;
            padding: 5px;
            width: 33.33%;
            box-sizing: border-box;
            vertical-align: top;
        }

        .logo img {
            max-width: 30%;
            height: auto;
            padding: 0px;
            display: flex;
            flex-wrap: wrap;
        }

        .titulo {
            text-align: center;
            line-height: 1.5;
            font-weight: bold;
            padding-top: 20px;
        }

        .fecha {
            text-align: right;
            font-size: 11px;
            margin: 0;
        }

        .content-container {
            padding: 0px;
            font-size: 11px;
        }

        .seccion {
            border: 1px solid #d0d0d0;
            margin-bottom: 5px;
            padding: 5px;
            display: flex;
            flex-wrap: wrap;
        }

        .seccion-title {
            margin-top: -1px;
            flex-wrap: wrap;
        }

        .footer-container {
            background-color: #333;
            color: white;
            padding: 10px;
            text-align: center;
        }

        * {
            box-sizing: border-box;
        }

        .row {
            margin-left: -5px;
            margin-right: -5px;
        }

        .column {
            float: left;
            width: 50%;
            padding: 5px;
        }

        .column-firma {
            float: left;
            width: 49%;
            padding: 2px;
        }

        .firma-container {
            margin-bottom: 10px;
            position: relative;
            /* Puedes ajustar este valor según sea necesario */
        }

        .firma-container h5 {
            margin-bottom: 0;
            /* Establece el margen inferior de h5 a 0 para acercarlo a hr */
            position: absolute;
            top: 15px;
        }

        .row::after {
            content: "";
            clear: both;
            display: table;
        }

        table.table-1 {
            width: 90%;
            border-collapse: collapse;
            margin-bottom: 10px;
            margin-left: -5px;
        }

        table.table-1 th,
        table.table-1 td {
            text-align: left;
            padding: 3px;
            white-space: nowrap;
        }

        table.table-datos-contractuales,
        table.table-datos-contractuales th,
        table.table-datos-contractuales td {
            width: 100%;
            border: 0.3px solid black;
            border-collapse: collapse;
            border-color: #000000;
            padding: 2px;
            text-align: left;
        }

        table.table-datos-contractuales-2 th,
        table.table-datos-contractuales-2 td {
            text-align: left;
        }

        table.table-datos-contractuales-2,
        table.table-datos-contractuales-2 th,
        table.table-datos-contractuales-2 td {
            width: 95%;
            border: 0.3px solid black;
            border-collapse: collapse;
            border-color: #000000;
            padding: 2px;
        }

        .seccion-footer {
            position: fixed;
            left: 0;
            bottom: -25px;
            width: 100%;
            background-color: rgb(255, 255, 255);
            color: black;
            text-align: center;
        }
    </style>
</head>

<body>
    <header class="header-container">
        <div class="logo">
            <img src="{{ public_path('img/logo-sso.jpeg') }}" alt="Logo" class="logo">
        </div>
        <div class="titulo">
            GASTOS DE COMETIDO FUNCIONAL
        </div>
        <div class="fecha">
            <table class="table-datos-contractuales">
                <tr style="font-size: 8px;">
                    <th>N° FOLIO:</th>
                    <td>{{ $proceso_rendicion_gasto->n_folio }}</td>
                </tr>
                <tr style="font-size: 8px;">
                    <th>N° RESOLUCION COMETIDO:</th>
                    <td>{{ $proceso_rendicion_gasto->solicitud->codigo }}</td>
                </tr>
            </table>
        </div>
    </header>

    <div class="content-container">
        <div class="seccion">
            <h2 class="seccion-title">Sección 1</h2>
            <div class="row">
                <div class="column">
                    <table class="table-1">
                        <tbody>
                            <tr>
                                <th>Nombre funcionario:</th>
                                <td>{{ $proceso_rendicion_gasto->solicitud->funcionario->nombre_completo }}</td>
                            </tr>
                            <tr>
                                <th>Rut:</th>
                                <td>{{ $proceso_rendicion_gasto->solicitud->funcionario->rut_completo }}</td>
                            </tr>
                            <tr>
                                <th>Establecimiento:</th>
                                <td>{{ $proceso_rendicion_gasto->solicitud->establecimiento->nombre }}</td>
                            </tr>
                            <tr>
                                <th>Unidad / Servicio / Depto:</th>
                                <td>{{ $proceso_rendicion_gasto->solicitud->departamento->nombre }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="column">
                    <table class="table-1">
                        <tbody>
                            <tr>
                                <th>Fecha de cometido:</th>
                                <td>{{ Carbon\Carbon::parse($proceso_rendicion_gasto->solicitud->fecha_inicio)->format('d-m-Y') }}
                                    /
                                    {{ Carbon\Carbon::parse($proceso_rendicion_gasto->solicitud->fecha_termino)->format('d-m-Y') }}
                                </td>
                            </tr>
                            <tr>
                                <th>Correo electrónico:</th>
                                <td>{{ $proceso_rendicion_gasto->solicitud->funcionario->email }}</td>
                            </tr>
                            <tr>
                                <th>Teléfono - Anexo:</th>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="row">
                <div class="column">
                    <table class="table-1">
                        <tbody>
                            <tr>
                                <th>Lugar de cometido:</th>
                                <td>
                                    @if (!$proceso_rendicion_gasto->solicitud->dentro_pais)
                                        {{ $proceso_rendicion_gasto->solicitud->lugares->pluck('nombre')->implode(', ') }}
                                    @else
                                        {{ $proceso_rendicion_gasto->solicitud->paises->pluck('nombre')->implode(', ') }}
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="row">
                <div class="column">
                    <table class="table-1">
                        <tbody>
                            <tr>
                                <th>Motivo del viaje:</th>
                                <td>{{ $proceso_rendicion_gasto->solicitud->motivos->pluck('nombre')->implode(', ') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <table class="table-datos-contractuales">
                <thead>
                    <th>N° cuenta bancaria</th>
                    <th>Tipo cuenta</th>
                    <th>Banco</th>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $proceso_rendicion_gasto->cuentaBancaria ? $proceso_rendicion_gasto->cuentaBancaria->n_cuenta : '--' }}
                        </td>
                        <td>{{ $proceso_rendicion_gasto->cuentaBancaria && $proceso_rendicion_gasto->cuentaBancaria->tipo_cuenta !== null ? App\Models\CuentaBancaria::TYPE_ACCOUNT_NOM[$proceso_rendicion_gasto->cuentaBancaria->tipo_cuenta] : '--' }}
                        </td>
                        <td>{{ $proceso_rendicion_gasto->cuentaBancaria ? $proceso_rendicion_gasto->cuentaBancaria->banco->nombre : '--' }}
                        </td>
                    </tr>
                </tbody>
            </table>
            <p>{{$proceso_rendicion_gasto->pagoHabilesMessage() ? $proceso_rendicion_gasto->pagoHabilesMessage() : ''}}</p>
            <div class="row">
                <div class="column">
                    <table class="table-1">
                        <tbody>
                            <tr>
                                <th>Item presupuestario funcionario:</th>
                                <td>{{ $proceso_rendicion_gasto->solicitud->itemPresupuestario ? $proceso_rendicion_gasto->solicitud->itemPresupuestario->nombre  : ''}}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="seccion">
            <h2 class="seccion-title">Sección 2 (Rendiciones solicitadas)</h2>
            <div class="row">
                <div class="column">
                    <table class="table-datos-contractuales-2">
                        <thead>
                            <th>Tipo de locomoción</th>
                            <th>Monto solicitado</th>
                        </thead>
                        <tbody>
                            @foreach ($proceso_rendicion_gasto->rendiciones_not_particular as $rendicion)
                                <tr>
                                    <td>{{ $rendicion->actividad->nombre }}</td>
                                    <td>{{ "$" . number_format($rendicion->mount, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <td><strong>TOTAL</strong></td>
                            <td><strong>{{ "$" . number_format($proceso_rendicion_gasto->rendiciones_not_particular_total, 0, ',', '.') }}</strong>
                            </td>
                        </tfoot>
                    </table>
                </div>
                <div class="column">
                    <table class="table-datos-contractuales-2">
                        <thead>
                            <th>Particular</th>
                            <th>Monto solicitado</th>
                        </thead>
                        <tbody>
                            @foreach ($proceso_rendicion_gasto->rendiciones_particular as $rendicion)
                                <tr>
                                    <td>{{ $rendicion->actividad->nombre }}</td>
                                    <td>{{ "$" . number_format($rendicion->mount, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <td><strong>TOTAL</strong></td>
                            <td><strong>{{ "$" . number_format($proceso_rendicion_gasto->rendiciones_particular_total, 0, ',', '.') }}</strong>
                            </td>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="row">
                <p>{{ $proceso_rendicion_gasto->observacion }}</p>
            </div>
        </div>
        <div class="seccion">
            <h2 class="seccion-title">Sección 3 (Rendiciones aprobadas)</h2>
            <p><strong>USO EXCLUSIVO DEPTO. FINANZAS</strong></p>
            @if (count($proceso_rendicion_gasto->rendiciones_finanzas) > 0)
                <table class="table-datos-contractuales">
                    <thead>
                        <th>Concepto Pres.</th>
                        <th>Item Pres.</th>
                        <th>Estado</th>
                        <th>Monto aprobado</th>
                    </thead>
                    <tbody>
                        @foreach ($proceso_rendicion_gasto->rendiciones_finanzas as $rendicion)
                            <tr>
                                <td>{{ $rendicion->actividad->nombre }}</td>
                                <td>{{ $rendicion->itemPresupuestario->nombre }}</td>
                                <td>{{ App\Models\RendicionGasto::STATUS_NOM[$rendicion->last_status] }}</td>
                                <td>{{ "$" . number_format($rendicion->mount_real, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <td colspan="3"><strong>TOTAL</strong></td>
                        <td><strong>{{ "$" . number_format($proceso_rendicion_gasto->rendiciones_finanzas_total, 0, ',', '.') }}</strong>
                        </td>
                    </tfoot>
                </table>
            @endif
        </div>
        <div class="seccion">
            <h2 class="seccion-title">Sección 4 (Rendiciones rechazadas)</h2> <i>Observaciones</i>
            @if (count($proceso_rendicion_gasto->observaciones) > 0)
                <table class="table-datos-contractuales">
                    <thead>
                        <th>Concepto Pres.</th>
                        <th>Observación</th>
                        <th>Usuario</th>
                        <th>Fecha</th>
                    </thead>
                    <tbody>
                        @foreach ($proceso_rendicion_gasto->observaciones as $observacion)
                            <tr>
                                <td>{{ $observacion['actividad'] }}</td>
                                <td>{{ $observacion['observacion'] }}</td>
                                <td>{{ $observacion['user_by'] }}</td>
                                <td>{{ $observacion['fecha_by_user'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
        <div class="seccion">
            <h2 class="seccion-title">Sección 5 (V° B°)</h2>
            <div class="row">
                <div class="column-firma">
                    <div class="firma-container">
                        <p>{{ $proceso_rendicion_gasto->firmaJefeDirecto() ? $proceso_rendicion_gasto->firmaJefeDirecto() : 'SIN FIRMA' }}
                        </p>
                        <hr>
                        <h5>JEFATURA DIRECTA</h5>
                    </div>
                </div>
                <div class="column-firma">
                    <div class="firma-container">
                        <p>{{ $proceso_rendicion_gasto->firmaSupervisorFinanzas() ? $proceso_rendicion_gasto->firmaSupervisorFinanzas() : 'SIN FIRMA' }}
                        </p>
                        <hr>
                        <h5>SUPERVISOR FINANZAS</h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="seccion-footer">
            <p>Fecha de impresión {{ Carbon\Carbon::now()->format('d-m-Y H:i:s') }} </p>
        </div>
    </div>
</body>

</html>
