<!doctype html>
<html lang="es">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{{config('app.name')}} | Convenio de cometido</title>
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
            line-height: 1.8; /* Agregar esta línea para el espaciado entre líneas generalizado */
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

        .spacing{
            line-height:1.5;
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
    </style>

<body>
    <div class="information">
        <table width="100%">
            <tr>
                <td align="left" style="width: 10%;">
                    <img class="center logo" src="{{ public_path('img/logo-sso.jpeg') }}">
                </td>
                <td align="left" style="width: 60%;">
                    <h6 style="font-size: 11px;">DIRECCIÓN<br>
                        <small style="font-size: 9px;" class="text-muted ml-4">SUBDIRECCIÓN DE RECURSOS
                            HUMANOS</small><br>
                        <small style="font-size: 9px;" class="text-muted ml-4">DEPTO. GESTIÓN DE LAS PERSONAS</small>
                    </h6>
                </td>
            </tr>
        </table>
    </div>
    <div class="titulo">
        <h4 align="center">CONVENIO DE COMETIDO FUNCIONAL</h4>
        {{-- <h1 align="center">Documento emitido el {{now()->format('d-m-Y H:i:s')}}</h1> --}}
    </div>
    <div class="datos">
        <section class="section-borde">
            <div class="section-position">
                <h3 class="title">Antecedentes personales</h3>
                <table>
                    <tbody>
                        <tr>
                            <th>RUT:</th>
                            <td>{{ $convenio->funcionario->rut_completo }}</td>
                        </tr>
                        <tr>
                            <th>NOMBRES:</th>
                            <td>{{ $convenio->funcionario->nombre_completo }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
        <section class="section-borde">
            <div class="section-position">
                <h3 class="title">Datos del convenio</h3>
                <table>
                    <tbody>
                        <tr>
                            <th>ILUSTRE:</th>
                            <td>{{ $convenio->ilustre->nombre }}</td>
                        </tr>
                        <tr>
                            <th>RESOLUCIÓN:</th>
                            <td>N° {{ $convenio->n_resolucion }} / {{ Carbon\Carbon::parse($convenio->fecha_resolucion)->format('d-m-Y') }} </td>
                        </tr>
                        <tr>
                            <th>PERIODO VIGENCIA:</th>
                            <td>{{ Carbon\Carbon::parse($convenio->fecha_inicio)->format('d-m-Y') }} / {{ Carbon\Carbon::parse($convenio->fecha_termino)->format('d-m-Y') }}</td>
                        </tr>
                        <tr>
                            <th>TIPO DE CONVENIO:</th>
                            <td>VIÁTICOS</td>
                        </tr>
                        <tr>
                            <th>ESTAMENTO:</th>
                            <td>{{ $convenio->estamento->nombre }}</td>
                        </tr>
                        <tr>
                            <th>LEY:</th>
                            <td>{{ $convenio->ley->nombre }}</td>
                        </tr>
                        <tr>
                            <th>ESTABLECIMIENTO:</th>
                            <td>{{ $convenio->establecimiento->nombre }}</td>
                        </tr>
                        <tr>
                            <th>N° DE VIÁTICOS MENSUAL:</th>
                            <td>{{ $convenio->n_viatico_mensual }}</td>
                        </tr>
                        <tr>
                            <th>OBSERVACIÓN:</th>
                            <td>{{ $convenio->observacion ? $convenio->observacion  : '--' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</body>
</head>
