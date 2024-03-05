<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

        .seccion-title{
            margin-top:-1px;
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
            <p><strong>{{ $proceso_rendicion_gasto->n_folio }}</strong></p>
            <p>{{ rand() }}</p>
            <p>{{ now()->format('d M Y') }}</p>
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
                                <th>Unidad o Servicio:</th>
                                <td>{{ $proceso_rendicion_gasto->solicitud->departamento->nombre }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
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
                            <tr>
                                <th>Fecha de cometido:</th>
                                <td>{{ Carbon\Carbon::parse($proceso_rendicion_gasto->solicitud->fecha_inicio)->format('d-m-Y') }}
                                    /
                                    {{ Carbon\Carbon::parse($proceso_rendicion_gasto->solicitud->fecha_termino)->format('d-m-Y') }}
                                </td>
                            </tr>
                            <tr>
                                <th>Motivo del viaje:</th>
                                <td>{{ $proceso_rendicion_gasto->solicitud->motivos->pluck('nombre')->implode(', ') }}
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
            <table class="table-datos-contractuales">
                <thead>
                    <th>N° cuenta bancaria</th>
                    <th>Tipo cuenta</th>
                    <th>Banco</th>
                </thead>
                <tbody>
                    <tr>
                        <td>--</td>
                        <td>--</td>
                        <td>--</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="seccion">
            <h2 class="seccion-title">Sección 2</h2>
            <div class="row">
                <div class="column">
                    <table class="table-datos-contractuales-2">
                        <thead>
                            <th>Tipo de locomoción</th>
                            <th>Monto</th>
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
                            <th>Tipo de locomoción (Particular)</th>
                            <th>Monto</th>
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
        </div>
        <div class="seccion">
            <h2 class="seccion-title">Sección 3</h2>
            <p><strong>USO EXCLUSIVO DEPTO. FINANZAS</strong></p>
            @if (count($proceso_rendicion_gasto->rendiciones_finanzas) > 0)
                <table class="table-datos-contractuales">
                    <thead>
                        <th>Concepto Pres.</th>
                        <th>Item Pres.</th>
                        <th>Estado</th>
                        <th>Monto</th>
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
            <h2 class="seccion-title">Sección 4</h2> <i>Observaciones</i>
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
            <h2 class="seccion-title">Sección 5</h2>
            <p><strong>V° B° JEFATURA DIRECTA</strong>:</p>
            @if ($proceso_rendicion_gasto->status_jefe_directo)
            <p>{{ $proceso_rendicion_gasto->status_jefe_directo->firma->funcionario->nombre_completo }}
                {{ $proceso_rendicion_gasto->status_jefe_directo->created_at }}</p>
            @endif
        </div>
    </div>
    {{-- <footer class="footer-container">
        {{ now()->format('d-m-Y') }}
    </footer> --}}
</body>

</html>
