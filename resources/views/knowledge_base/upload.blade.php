@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Subir PDF para Ingesta RAG</h2>
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form method="POST" action="{{ route('knowledge_base.upload') }}" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
            <label for="pdf" class="form-label">Archivo PDF</label>
            <input class="form-control" type="file" name="pdf" id="pdf" accept="application/pdf" required>
        </div>
        <button type="submit" class="btn btn-primary">Subir y Procesar</button>
    </form>
</div>
@endsection
