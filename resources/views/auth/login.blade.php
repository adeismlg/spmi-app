<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk — SPMI</title>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family:'Public Sans',sans-serif; background:#f5f5f9; }
        .card { border:0; box-shadow:0 .25rem 1rem rgba(161,172,184,.45); border-radius:.6rem; }
        .btn-primary { --bs-btn-bg:#696cff; --bs-btn-border-color:#696cff; --bs-btn-hover-bg:#5f61e6; --bs-btn-hover-border-color:#5f61e6; }
    </style>
</head>
<body class="d-flex align-items-center min-vh-100">
<div class="container" style="max-width:420px">
    <div class="card p-4">
        <h4 class="fw-bold text-center mb-1" style="color:#696cff">SPMI</h4>
        <p class="text-center text-muted mb-4">Sistem Penjaminan Mutu Internal — siklus PPEPP</p>

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required autofocus>
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                <label class="form-check-label" for="remember">Ingat saya</label>
            </div>
            <button class="btn btn-primary w-100">Masuk</button>
        </form>
    </div>
</div>
</body>
</html>
