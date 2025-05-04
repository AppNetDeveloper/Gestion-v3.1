<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $subjectText }}</title>
    <style>
        /* Estilos simples para que se vea como un correo tradicional */
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background: #ffffff;
            color: #333;
        }
        h1 {
            font-size: 18px;
            margin-bottom: 10px;
        }
        p {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <h1>{{ $subjectText }}</h1>
    {!! $content !!}
    <p>{{ config('app.name') }}</p>
</body>
</html>


{{-- para modificar el  envio de auto formateo a html tienes que modificar eñl archivo Mail/SendCustomMail.php --}}
