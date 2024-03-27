<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} | Resolución {{ $solicitud->n_resolucion }}</title>

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
            bottom: 0;
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
                    <th>FECHA RECEPCIÓN:</th>
                    <td>{{ Carbon\Carbon::parse($solicitud->created_at)->format('d-m-Y') }}</td>
                </tr>
                <tr style="font-size: 8px;">
                    <th>HORA RECEPCIÓN:</th>
                    <td>{{ Carbon\Carbon::parse($solicitud->created_at)->format('H:i:s') }}</td>
                </tr>
                <tr style="font-size: 8px;">
                    <th>CÓDIGO SOLICITUD:</th>
                    <td>{{ $solicitud->codigo }}</td>
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
                    <p><strong>RESOLUCIÓN EXENTA N°</strong> {{ rand() }}</p>
                    <p><strong>OSORNO,</strong></p>
                </div>
            </div>
        </div>
        <div class="seccion">
            <p style="text-align: justify;">
                {{$solicitud->vistos}}
            </p>
        </div>
        <div class="seccion">
            <h3 class="seccion-title">RESOLUCIÓN:</h3>
            <p>ORDÉNESE el siguiente Cometido Funcional
                <strong>{{ $solicitud->derecho_pago ? 'con derecho' : 'sin derecho' }}</strong> a
                percibir viático, a funcionaria(o) que se indica:
            </p>
        </div>
        <div class="seccion">
            <h3>DATOS DEL COMETIDO</h3>
            <table class="table-datos-contractuales">
                <tr>
                    <td rowspan="9">
                        <h3>
                            <ol type="I" start="1">
                                <li>Sección Datos Personales
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
                    <td>{{$solicitud->calidad->nombre}}</td>
                </tr>
                <tr>
                    <td><strong>Cargo</strong></td>
                    <td>{{ $solicitud->cargo ? $solicitud->cargo->nombre : '--' }}</td>
                </tr>
                <tr>
                    <td><strong>Unidad o Servicio</strong></td>
                    <td>{{ $solicitud->departamento->nombre }}</td>
                </tr>
                <tr>
                    <td><strong>Establecimiento</strong></td>
                    <td>{{ $solicitud->establecimiento->nombre }}</td>
                </tr>

                <tr>
                    <td><strong>Grado / Horas</strong></td>
                    <td>{{ $solicitud->grado->nombre }} / {{ $solicitud->hora->nombre }}</td>
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
                    <td rowspan="3">
                        <h3>
                            <ol type="I" start="2">
                                <li>Sección Cargo y Destino
                            </ol>
                        </h3>
                    </td>
                    <td><strong>Lugar de cometido</strong></td>
                    <td>{{ $solicitud->lugares->pluck('nombre')->implode(', ') }}</td>
                </tr>
                <tr>
                    <td><strong>Total días cometido</strong></td>
                    <td>{{ $solicitud->total_dias_cometido }}</td>
                </tr>
                <tr>
                    <td><strong>Motivo</strong></td>
                    <td>{{ $solicitud->motivos->pluck('nombre')->implode(', ') }}</td>
                </tr>
                <tr>
                    <td rowspan="2">
                        <h3>
                            <ol type="I" start="3">
                                <li>Sección Viaje
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
                    <td rowspan="5">
                        <h3>
                            <ol type="I" start="4">
                                <li>Sección financiamiento
                            </ol>
                        </h3>
                    </td>
                    <td><strong>Financia gastos de alimentación</strong></td>
                    <td>{{ $solicitud->gastos_alimentacion ? 'Si' : 'No' }}</td>
                </tr>
                <tr>
                    <td><strong>Financia gastos de alojamiento</strong></td>
                    <td>{{ $solicitud->gastos_alojamiento ? 'Si' : 'No' }}</td>
                </tr>
                <tr>
                    <td><strong>Tipo de comisión</strong></td>
                    <td>{{ $solicitud->tipoComision->nombre }}</td>
                </tr>
                <tr>
                    <td><strong>Alimentación en red</strong></td>
                    <td>{{$solicitud->alimentacion_red ? 'Si' : 'No'}}</td>
                </tr>
                <tr>
                    <td><strong>Pernocta fuera del lugar de residencia</strong></td>
                    <td>{{ $solicitud->pernocta_lugar_residencia ? 'Si' : 'No' }}</td>
                </tr>
                <tr>
                    <td rowspan="1">
                        <h3>
                            <ol type="I" start="5">
                                <li>Sección periodo
                            </ol>
                        </h3>
                    </td>
                    <td><strong>Periodo cometido</strong></td>
                    <td>{{ Carbon\Carbon::parse($solicitud->fecha_inicio)->format('d-m-Y') }} / {{ Carbon\Carbon::parse($solicitud->fecha_termino)->format('d-m-Y') }}</td>
                </tr>
            </table>
        </div>
        <div class="seccion">
            <h3>DATOS ???</h3>
            <div class="row">
                <div class="column">
                    @if ($solicitud->ultimoCalculo)
                    <table class="table-1">
                        <thead>
                            <td colspan="3">
                                <p style="font-size: 10px; font-weight: bold;">MONTO VIGENTE APLICADO</p>
                            </td>
                        </thead>
                        <thead>
                            <th></th>
                            <th>Total Días</th>
                            <th>Total monto aplicado</th>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>40%</strong></td>
                                <td>{{ $solicitud->ultimoCalculo->n_dias_40 }}</td>
                                <td>{{ $solicitud->ultimoCalculo->monto_40 != null ? "$" . number_format($solicitud->ultimoCalculo->monto_40, 0, ',', '.') : 'N/A' }}
                                </td>
                            </tr>
                            <tr>
                                <td><strong>100%</strong></td>
                                <td>{{ $solicitud->ultimoCalculo->n_dias_100 }}</td>
                                <td>{{ $solicitud->ultimoCalculo->monto_100 != null ? "$" . number_format($solicitud->ultimoCalculo->monto_100, 0, ',', '.') : 'N/A' }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <td colspan="1"><strong>TOTAL</strong></td>
                            <td><strong>{{ $solicitud->ultimoCalculo->n_dias_40 + $solicitud->ultimoCalculo->n_dias_100 }}</strong>
                            </td>
                            <td><strong>{{ "$" . number_format($solicitud->ultimoCalculo->monto_total, 0, ',', '.') }}</strong>
                            </td>
                        </tfoot>
                    </table>
                    @endif

                </div>
                <div class="column">
                    @foreach ($solicitud->procesoRendicionGastos as $proceso)
                        <table class="table-1">
                            <thead>
                                <td colspan="2">
                                    <p style="font-size: 10px;"><strong>N° folio</strong>: {{ $proceso->n_folio }}</p>
                                </td>
                            </thead>
                            <thead>
                                <th>Concepto Pres.</th>
                                <th>Monto</th>
                            </thead>
                            <tbody>
                                @foreach ($proceso->rendicionesfinanzas() as $rendicion)
                                    <tr>
                                        <td>{{ $rendicion->actividad->nombre }}</td>
                                        <td>${{ number_format($rendicion->mount_real, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <td colspan="1"><strong>TOTAL</strong></td>
                                <td><strong>{{ $proceso->sumRendicionesAprobadas()}}</strong>
                                </td>
                            </tfoot>
                        </table>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="seccion">
            <h3>VERIFICACIONES</h3>
            <div class="row">
                <div class="column-firma">
                    <div class="firma-container">
                        <p>{{$solicitud->firmaJefatura() ? $solicitud->firmaJefatura() : 'SIN FIRMA'}}</p>
                        <hr>
                        <h5>JEFATURA DIRECTA</h5>
                    </div>
                </div>
                <div class="column-firma">
                    <div class="firma-container">
                        <p>{{$solicitud->firmaSubDirector() ? $solicitud->firmaSubDirector() : 'SIN FIRMA'}}</p>
                        <hr>
                        <h5>SUBDIRECTOR/A DEL ÁREA</h5>
                    </div>
                </div>
                <div class="column-firma">
                    <div class="firma-container">
                        <p>{{$solicitud->firmaJefePersonal() ? $solicitud->firmaJefePersonal() : 'SIN FIRMA'}}</p>
                        <hr>
                        <h5>JEFATURA SECCIÓN PERSONAL Y REGISTRO</h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="seccion-footer">
            <p>{{$solicitud->informeCometido() ? 'Con Informe de Cometido.' : 'Sin Informe de Cometido.'}}</p>
            <p>
                <strong>NOTA</strong>:
                El cumplimiento de este cometido, es de responsabilidad de la jefatura directa, solidariamente con la
                persona que disponga del pago.
            </p>
            <p>{{ Carbon\Carbon::now()->format('d-m-Y H:i:s e') }} </p>
        </div>
    </div>
</body>

</html>
