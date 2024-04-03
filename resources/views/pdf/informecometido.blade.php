<!doctype html>
<html lang="es">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{{ config('app.name') }} | Informe {{ $informe->codigo }}</title>
    <link rel="shortcut icon" href="{{ public_path('img/logo-sso.jpeg') }}">

    <style type="text/css">
        @page {
            margin: 1%;
        }

        body {
            margin: auto;
            padding: 0%;
            font-family: "Times New Roman", Times, serif;
            width: 100%;
        }

        .filtro {
            filter: grayscale(4%);
        }

        * {
            font-family: Verdana, Arial, sans-serif;
            font-size: 13px;
        }

        .information {
            background-color: #ffffff;
            color: black;
        }

        .information .logo {
            margin: 5px;
        }

        .datos {
            /* margin-left: 40rem; */
            padding: 0rem;
        }

        table,
        th,
        td {
            text-align: justify;
            width: 100%;
            margin-right: 10px;
        }

        table,
        th,
        td {
            line-height: 1.8;
            /* Agregar esta línea para el espaciado entre líneas generalizado */
        }

        .logo {
            position: relative;

            top: 0px;
            left: 20px;
            width: 50;
            height: 50;
            border-radius: 1%;
            margin: 30%;
            display: block;
        }

        .detalle {
            text-align: justify;
        }

        .title {
            text-align: left;
            font-weight: bold;
            font-size: 16px;
        }

        .section-borde {
            border: 1px solid #d1d1d1;
            margin-top: 20px;
        }

        .section-position {
            margin-left: 10px;
        }

        table.table-datos-contractuales,
        table.table-datos-contractuales th,
        table.table-datos-contractuales td {
            border: 0.3px solid black;
            border-collapse: collapse;
            border-color: #96D4D4;
            margin-bottom: 10px;
            margin-left: -5px;
        }

        @media print {
            .page-break {
                page-break-before: always;
            }
        }

        .spacing {
            line-height: 1.5;
        }

        footer {
            position: fixed;
            bottom: 0cm;
            left: 0cm;
            right: 0cm;
            height: 2cm;

            /** Estilos extra personales **/
            background-color: #0367b2;
            color: white;
            text-align: center;
            line-height: 1.5cm;
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

        .seccion {
            margin-bottom: 0px;
            display: flex;
            flex-wrap: wrap;
        }

        .column-firma {
            float: left;
            width: 48%;
            padding: 5px;
        }

        .firma-container {
            margin-bottom: 10px;
            position: relative;
            /* Puedes ajustar este valor según sea necesario */
        }

        .firma-container h5 {
            margin-bottom: 0;
            position: absolute;
            top: 25px;
        }
    </style>

<body>
    <div class="information">
        <table width="100%">
            <tr>
                <td align="left" style="width: 10%;">
                    <img class="center logo" src="{{ public_path('img/logo-sso.jpeg') }}">
                </td>
                <td align="left" style="width: 70%;">
                    <small style="font-size: 9px;" class="text-muted ml-4">SUBD. GESTIÓN Y DESARROLLO DE
                        PERSONAS</small><br>
                    <small style="font-size: 9px;" class="text-muted ml-4">DEPTO. GESTIÓN DE LAS PERSONAS</small>
                    </h6>
                </td>
            </tr>
        </table>
    </div>
    <div class="titulo">
        <h4 align="center">INFORME DE COMETIDO FUNCIONAL</h4>
    </div>
    <div class="datos">
        <section class="section-borde">
            <div class="section-position">
                <h3 class="title">Antecedentes personales de la funcionaria(o)</h3>
                <table>
                    <tbody>
                        <tr>
                            <th>RUT:</th>
                            <td>{{ $informe->solicitud->funcionario->rut_completo }}</td>
                        </tr>
                        <tr>
                            <th>NOMBRES:</th>
                            <td>{{ $informe->solicitud->funcionario->nombre_completo }}</td>
                        </tr>
                        <tr>
                            <th>CARGO:</th>
                            <td>{{ $informe->solicitud->cargo->nombre }}</td>
                        </tr>
                        <tr>
                            <th>GRADO:</th>
                            <td>{{ $informe->solicitud->grado->nombre }}</td>
                        </tr>
                        <tr>
                            <th>CALIDAD JURÍDICA:</th>
                            <td>{{ $informe->solicitud->calidad->nombre }}</td>
                        </tr>
                        <tr>
                            <th>ESTABLECIMIENTO:</th>
                            <td>{{ $informe->solicitud->establecimiento->nombre }}</td>
                        </tr>
                        <tr>
                            <th>DEPTO:</th>
                            <td>{{ $informe->solicitud->departamento->nombre }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
        <section class="section-borde">
            <div class="section-position">
                <h3 class="title">Datos del informe</h3>
                <table>
                    <tbody>
                        <tr>
                            <th>CÓDIGO INFORME:</th>
                            <td>{{ $informe->codigo }}</td>
                        </tr>
                        <tr>
                            <th>N° RESOLUCIÓN :</th>
                            <td>{{ $informe->solicitud->codigo }}</td>
                        </tr>
                        <tr>
                            <th>PERIODO:</th>
                            <td>{{ Carbon\Carbon::parse($informe->fecha_inicio)->format('d-m-Y') }} /
                                {{ Carbon\Carbon::parse($informe->fecha_termino)->format('d-m-Y') }}</td>
                        </tr>
                        <tr>
                            <th>HORA:</th>
                            <td>{{ Carbon\Carbon::parse($informe->hora_llegada)->format('H:i') }} /
                                {{ Carbon\Carbon::parse($informe->hora_salida)->format('H:i') }} hrs.</td>
                        </tr>
                        <tr>
                            <th>UTILIZA TRANSPORTE:</th>
                            <td>{{ $informe->utiliza_transporte ? 'Si' : 'No' }}</td>
                        </tr>
                        <tr>
                            <th>MOVILIZACIÓN UTILIZADA:</th>
                            <td>{{ $informe->transportes ? $informe->transportes->pluck('nombre')->implode(', ') : '' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
        <section class="section-borde">
            <div class="section-position">
                <h3 class="title">Actividades realizadas</h3>
                <p>{{ $informe->actividad_realizada }}</p>
            </div>
        </section>
        <div class="seccion">
            <h3>VERIFICACIONES</h3>
            <div class="row">
                <div class="column-firma">
                    <div class="firma-container">
                        <p>{{ $informe->firmaJefatura() ? $informe->firmaJefatura() : 'SIN FIRMA' }}</p>
                        <hr>
                        <h5>JEFATURA DIRECTA</h5>
                    </div>
                </div>
                <div class="column-firma">
                    <div class="firma-container">
                        <p>{{ $informe->firmaFuncionario() ? $informe->firmaFuncionario() : 'SIN FIRMA' }}</p>
                        <hr>
                        <h5>FUNCIONARIA(O)</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</head>
