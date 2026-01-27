<!doctype html>
<title>Archivo no encontrado</title>
<style>
    body {
        text-align: center;
        padding: 100px;
        max-width: 100%;
        height: auto;
    }

    h1 {
        font-size: 50px;
    }

    body {
        font: 20px Helvetica, sans-serif;
        color: #333;
    }

    article {
        display: block;
        text-align: left;
        width: 650px;
        margin: 0 auto;
    }

    a {
        color: #dc8100;
        text-decoration: none;
    }

    a:hover {
        color: #333;
        text-decoration: none;
    }

    .logo-sso {
        width: 140px;
        height: 130px;
        /* margin-top: -20px; */
        image-rendering: pixelated;
        text-align: center;
    }
</style>

<a href="{{config('app.frontend_url')}}"><img src="{{ asset('img/gob.svg') }}" src="img/logo-sso.jpeg" alt="Logo SSO"
        class="logo"></a>
<article>
    <h1>Archivo no localizado</h1>
    <div>
        <p style="text-align: justify;">Lo sentimos, el archivo <strong>{{ $documento->nombre }}</strong> no ha sido localizado en el
            servidor. Le solicitamos, por favor, editar la {{App\Models\Documento::MODEL_NOM[$documento->model]}} y adjuntar nuevamente los archivos
            correspondientes. Para dudas contactarse con {{App\Models\Documento::MODEL_DEPTO[$documento->model]}}.<br>
        <p>&mdash; {{ config('app.name') }}</p>
    </div>
</article>
