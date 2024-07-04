<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} | Resolución {{ $solicitud->codigo }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <style type="text/css">
        body {
            margin: -28;
            font-family: Arial, sans-serif;
            font-size: 10px;
        }

        .header-container {
            display: table;
            width: 100%;
            height: 40px;
            color: black;
            padding: 0px;
        }

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
            font-size: 10px;
            margin: 0;
        }

        .content-container {
            padding: 0px;
            /* font-size: 11px; */
        }

        .seccion {
            margin-bottom: 0px;
            display: flex;
            flex-wrap: wrap;
        }

        .seccion-title {
            flex-wrap: wrap;
            text-align: center;
        }

        .footer-container {
            background-color: #333;
            color: white;
            padding: 10px;
            text-align: center;
        }

        .seccion-footer {
            position: fixed;
            left: 0;
            width: 100%;
            background-color: rgb(255, 255, 255);
            color: black;
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

        .column-2 {
            float: left;
            width: 100%;
            padding: 5px;
        }

        .row-1 {
            margin-left: -5px;
            margin-right: -5px;
        }

        .column-1 {
            float: center;
            width: 100%;
            padding: 0px;
        }

        .header-logo {
            margin-left: 85px;
            margin-top: -2px;
            font-size: 10px;
            line-height: 1;
        }

        .column-firma {
            float: left;
            width: 30%;
            padding: 5px;
            padding-top: 100px;
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
            border: 0.3px solid black;
            border-collapse: collapse;
            border-color: #000000;
        }

        table.table-datos-contractuales,
        table.table-datos-contractuales th,
        table.table-datos-contractuales td {
            width: 100%;
            border: 0.3px solid black;
            border-collapse: collapse;
            border-color: #000000;
            padding: 3px;
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

        table.table-general {
            border-collapse: collapse;
            width: 100%;
        }

        table.table-general th,
        td {
            border: 1px solid black;
            padding: 5px;
        }

        table.table-general th {
            text-align: center;
        }
    </style>
</head>

<body>
    <header class="header-container">
        <div class="logo">
            <div class="row-1">
                <div class="column-1">
                    <img src="{{ public_path('img/logo-sso.jpeg') }}" alt="Logo" class="logo">
                </div>
                <div class="column-1">
                    <div class="header-logo">
                        <p><strong>DIRECCIÓN</strong></p>
                        <p>SUBD. GESTIÓN Y DESARROLLO DE PERSONAS</p>
                        <p>DEPTO. GESTION DE LAS PERSONAS</p>
                    </div>
                </div>
            </div>

        </div>
        <div class="titulo">

        </div>
        <div class="fecha">
            <table class="table-datos-contractuales">
                <thead>
                    <td colspan="2">
                        <p style="font-size: 8px; font-weight: bold;">USO EXCLUSIVO DEPTO. GESTIÓN DE LAS PERSONAS</p>
                    </td>
                </thead>
                <tr style="font-size: 8px;">
                    <th>FECHA RECEPCIÓN</th>
                    <td>{{ Carbon\Carbon::parse($solicitud->created_at)->format('d-m-Y') }}</td>
                </tr>
                <tr style="font-size: 8px;">
                    <th>HORA RECEPCIÓN</th>
                    <td>{{ Carbon\Carbon::parse($solicitud->created_at)->format('H:i:s') }}</td>
                </tr>
                <tr style="font-size: 8px;">
                    <th>ÚLTIMA APROBACIÓN</th>
                    <td>{{ $solicitud->lastEstadoAprobado() ? $solicitud->lastEstadoAprobado()->funcionario->abreNombres() : '' }}
                    </td>
                </tr>
                <tr style="font-size: 8px;">
                    <th>ÍTEM PRESUPUESTARIO</th>
                    <td>{{ $solicitud->itemPresupuestario ? $solicitud->itemPresupuestario->nombre : '' }}
                    </td>
                </tr>
            </table>
        </div>
    </header>

    <div class="content-container">
        <div class="seccion">
            <div class="row">
                <div class="column">
                </div>
                <div class="column">
                    <p><strong>RESOLUCIÓN {{ App\Models\Solicitud::RESOLUCION_NOM[$solicitud->tipo_resolucion] }} N°</strong> {{ $solicitud->codigo }}/{{ Carbon\Carbon::parse($solicitud->created_at)->format('d-m-Y') }}</p>
                    <p><strong>OSORNO,</strong></p>
                </div>
            </div>
        </div>
        <div class="seccion">
            <p style="text-align: justify;">
                {{ $solicitud->vistos }}
            </p>
        </div>
        <div class="seccion">
            <h3 class="seccion-title">RESOLUCIÓN:</h3>
            <p>1.- ORDÉNESE el siguiente Cometido Funcional
                <strong>{{ $solicitud->derecho_pago ? 'con derecho' : 'sin derecho' }}</strong> a
                percibir viático, a funcionaria(o) que se indica:
            </p>
        </div>
        <div class="seccion">
            <h3>DATOS DEL COMETIDO</h3>
            <table class="table-datos-contractuales">
                <tr>
                    <td rowspan="10">
                        <h3>
                            <ol type="I" start="1">
                                <li>Datos Personales
                            </ol>
                        </h3>
                    </td>
                    <td><strong>Apellidos y Nombres</strong></td>
                    <td>{{ $solicitud->funcionario->apellidos }} {{ $solicitud->funcionario->nombres }}</td>
                </tr>
                <tr>
                    <td><strong>Cédula de Identidad</strong></td>
                    <td>{{ $solicitud->funcionario->rut_completo }}</td>
                </tr>
                <tr>
                    <td><strong>Calidad Jurídica</strong></td>
                    <td>{{ $solicitud->calidad->nombre }}</td>
                </tr>
                <tr>
                    <td><strong>Cargo</strong></td>
                    <td>{{ $solicitud->cargo ? $solicitud->cargo->nombre : '--' }}</td>
                </tr>
                <tr>
                    <td><strong>Estamento</strong></td>
                    <td>{{ $solicitud->estamento ? $solicitud->estamento->nombre : '--' }}</td>
                </tr>
                <tr>
                    <td><strong>Unidad / Servicio / Depto.</strong></td>
                    <td>{{ $solicitud->departamento->nombre }}</td>
                </tr>
                <tr>
                    <td><strong>Establecimiento</strong></td>
                    <td>{{ $solicitud->establecimiento->nombre }}</td>
                </tr>

                <tr>
                    <td><strong>Grado / Horas</strong></td>
                    <td>{{ $solicitud->grado ? $solicitud->grado->nombre : 'Sin grado' }} /
                        {{ $solicitud->hora->nombre }}</td>
                </tr>
                <tr>
                    <td><strong>Correo electrónico</strong></td>
                    <td>{{ $solicitud->funcionario->email }}</td>
                </tr>
                <tr>
                    <td><strong>Teléfono / Anexo</strong></td>
                    <td>-- / --</td>
                </tr>
                <tr>
                    <td rowspan="2">
                        <h3>
                            <ol type="I" start="2">
                                <li>Periodo
                            </ol>
                        </h3>
                    </td>
                    <td><strong>Periodo cometido</strong></td>
                    <td>{{ Carbon\Carbon::parse($solicitud->fecha_inicio)->format('d-m-Y') }} /
                        {{ Carbon\Carbon::parse($solicitud->fecha_termino)->format('d-m-Y') }}</td>
                </tr>
                <tr>
                    <td><strong>Total días cometido</strong></td>
                    <td>{{ $solicitud->total_dias_cometido }}</td>
                </tr>
                <tr>
                    <td rowspan="3">
                        <h3>
                            <ol type="I" start="3">
                                <li>Viaje
                            </ol>
                        </h3>
                    </td>
                    <td><strong>Derecho pasajes</strong></td>
                    <td>{{ $solicitud->utiliza_transporte ? 'Si' : 'No' }}</td>
                </tr>
                <tr>
                    <td><strong>Medios de transporte</strong></td>
                    <td>{{ $solicitud->transportes->pluck('nombre')->implode(', ') }}</td>
                </tr>
                <tr>
                    <td><strong>Viaja con funcionaria(o) de la Institución</strong></td>
                    <td>No</td>
                </tr>
                <tr>
                    <td rowspan="5">
                        <h3>
                            <ol type="I" start="4">
                                <li>Financiamiento
                            </ol>
                        </h3>
                    </td>
                    <td><strong>Gastos de alimentación</strong></td>
                    <td>{{ $solicitud->gastos_alimentacion ? 'Si' : 'No' }}</td>
                </tr>
                <tr>
                    <td><strong>Gastos de alojamiento</strong></td>
                    <td>{{ $solicitud->gastos_alojamiento ? 'Si' : 'No' }}</td>
                </tr>
                <tr>
                    <td><strong>Tipo de comisión</strong></td>
                    <td>{{ $solicitud->tipoComision->nombre }}</td>
                </tr>
                <tr>
                    <td><strong>Alimentación en establecimiento de la red</strong></td>
                    <td>{{ $solicitud->alimentacion_red ? 'Si' : 'No' }}</td>
                </tr>
                <tr>
                    <td><strong>Pernocta fuera del lugar de residencia</strong></td>
                    <td>{{ $solicitud->pernocta_lugar_residencia ? 'Si' : 'No' }}</td>
                </tr>
                <tr>
                    <td rowspan="2">
                        <h3>
                            <ol type="I" start="5">
                                <li>Destino
                            </ol>
                        </h3>
                    </td>
                    <td><strong>Lugar de cometido</strong></td>
                    <td>{{ $solicitud->lugares->pluck('nombre')->implode(', ') }}</td>
                </tr>
                <tr>
                    <td><strong>Motivo</strong></td>
                    <td>{{ $solicitud->motivos->pluck('nombre')->implode(', ') }}</td>
                </tr>
                <tr>
                    <td rowspan="2">
                        <h3>
                            <ol type="I" start="6">
                                <li>Convenio
                            </ol>
                        </h3>
                    </td>
                    <td><strong>Afecta</strong></td>
                    <td>{{ $solicitud->afectaConvenio() }}</td>
                </tr>
                <tr>
                    <td><strong>Datos</strong></td>
                    <td>
                        @if ($solicitud->afecta_convenio)
                            {{ $solicitud->convenio->codigo }} / Año {{ $solicitud->convenio->anio }}
                        @else
                            --
                        @endif
                    </td>
                </tr>

            </table>
        </div>
        <div class="seccion">
            <br>
            <div class="row">
                <div class="column">
                    <h4>Valorización de cometido</h4>
                    @if ($solicitud->ultimoCalculo)
                        <table class="table-1">
                            <thead>
                                <th></th>
                                <th>40 %</th>
                                <th>100 %</th>
                                <th>Total</th>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="1"><strong>Días</strong></td>
                                    <td>{{ $solicitud->ultimoCalculo->n_dias_40 != null ? $solicitud->ultimoCalculo->n_dias_40 : 'N/A' }}
                                    </td>
                                    <td>{{ $solicitud->ultimoCalculo->n_dias_100 != null ? $solicitud->ultimoCalculo->n_dias_100 : 'N/A' }}
                                    </td>
                                    <td>{{$solicitud->ultimoCalculo->n_dias_40  + $solicitud->ultimoCalculo->n_dias_100  }}
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="1"><strong>Escala de valores</strong></td>
                                    <td>{{ $solicitud->ultimoCalculo->monto_40 != null ? "$" . number_format($solicitud->ultimoCalculo->monto_40, 0, ',', '.') : 'N/A' }}
                                    </td>
                                    <td>{{ $solicitud->ultimoCalculo->monto_100 != null ? "$" . number_format($solicitud->ultimoCalculo->monto_100, 0, ',', '.') : 'N/A' }}
                                    </td>
                                    <td>{{ $solicitud->ultimoCalculo->monto_total != null ? "$" . number_format($solicitud->ultimoCalculo->monto_total, 0, ',', '.') : 'N/A' }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="1"><strong>Total ajustes</strong></td>
                                    <td><strong>{{ $solicitud->ultimoCalculo->valorizacionTotalAjusteMonto()->total_40 }}</strong>
                                    </td>
                                    <td><strong>{{ $solicitud->ultimoCalculo->valorizacionTotalAjusteMonto()->total_100 }}</strong>
                                    </td>
                                    <td><strong>{{ $solicitud->ultimoCalculo->valorizacionTotalAjusteMonto()->total_monto_ajustes }}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3"><strong>TOTAL VALORIZACION</strong></td>
                                    <td><strong>{{ $solicitud->ultimoCalculo->valorizacionTotalAjusteMonto()->total_valorizacion }}</strong>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    @else
                    <p>Sin valorización de cometido.</p>
                    @endif
                    @if ($solicitud->cuentaBancaria)
                        <table class="table-1">
                            <thead>
                                <th>N° cuenta bancaria</th>
                                <th>Tipo cuenta</th>
                                <th>Banco</th>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ $solicitud->cuentaBancaria ? $solicitud->cuentaBancaria->n_cuenta : '--' }}
                                    </td>
                                    <td>{{ $solicitud->cuentaBancaria && $solicitud->cuentaBancaria->tipo_cuenta !== null ? App\Models\CuentaBancaria::TYPE_ACCOUNT_NOM[$solicitud->cuentaBancaria->tipo_cuenta] : '--' }}
                                    </td>
                                    <td>{{ $solicitud->cuentaBancaria ? $solicitud->cuentaBancaria->banco->nombre : '--' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    @endif
                </div>
                <div class="column">
                    <h4>Rendiciones de gastos aprobadas Depto. Finanzas</h4>
                    <table class="table-1">
                        <thead>
                            <th>N° total de rendiciones</th>
                            <th>Monto total de rendiciones</th>
                        </thead>
                        <tbody>
                            <tr>
                                <td>{{ $solicitud->totalProcesosRendiciones() }}</td>
                                <td>{{ $solicitud->sumTotalProcesosRendiciones() }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="seccion">
            <div class="row">
                <div class="column-2">
                    <h4>Verificaciones</h4>
                    <table class="table-1">
                        <thead>
                            <th>N°</th>
                            <th>Nombres</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                        </thead>
                        <tbody>
                            @foreach ($solicitud->navStatus as $nav)
                                <tr>
                                    <td>{{ $nav->posicion_firma }}</td>
                                    <td>{{ $nav->nombres_firmante }} {{ $nav->is_subrogancia ? '(S)' : '' }}</td>
                                    <td>{{ $nav->perfil }}</td>
                                    <td>{{ $nav->status_nom }}</td>
                                    <td>{{ $nav->status_date }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="seccion-footer">
            <p><strong>{{ $solicitud->informeCometido() ? 'Con Informe de Cometido.' : 'Sin Informe de Cometido.' }}</strong>
            </p>
            <p>Fecha de impresión {{ Carbon\Carbon::now()->format('d-m-Y H:i:s') }} </p>
        </div>
    </div>
</body>

</html>
