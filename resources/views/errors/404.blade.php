@extends('errors.illustrated-layout')

@section('code', '404')
@section('title', __('Página no encontrada'))

@section('image')
<style>
    #apartado-derecho{
        text-align:center;
    }
    ul{
        text-decoration: none !important;
        list-style: none;
        color: black;
        font-weight: bold;
    }
</style>

@endsection

@section('message', __('No hemos encontrado la página que buscas.'))
