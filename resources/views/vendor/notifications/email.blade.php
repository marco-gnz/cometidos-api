@component('mail::message')
# @lang('Estimada/o ')

@lang('Usted está recibiendo este correo porque se solicitó un restablecimiento de contraseña para su cuenta.')

@component('mail::button', ['url' => $actionUrl])
    @lang('Restablecer Contraseña')
@endcomponent

@lang('Este enlace de restablecimiento de contraseña expirará en :count minutos.', ['count' => config('auth.passwords.' . config('auth.defaults.passwords') . '.expire')])

@lang('Si no solicitó un restablecimiento de contraseña, favor ignorar este mensaje.')

@lang('Saludos'),

{{ config('app.name') }}

@isset($actionText)
    @slot('subcopy')
        @lang("Si tienes problemas haciendo clic en el botón, copia y pega la URL a continuación en tu navegador web:")
        [@lang('Restablecer contraseña')]({{ $actionUrl }})
    @endslot
@endisset
@endcomponent
