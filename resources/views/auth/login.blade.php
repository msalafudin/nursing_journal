<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Nursing Journal</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-white text-midnight">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="w-full max-w-[400px]">
            <!-- Logo -->
            <h1 class="font-sf-display text-heading-lg font-semibold text-center text-midnight mb-2">Nursing Journal</h1>
            <p class="text-body text-cloud text-center mb-10">RSI Muhammadiyah 2 Kendal</p>

            <!-- Login Card -->
            <div class="card">
                @if ($errors->any())
                    <div class="alert alert-error mb-5">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                @if (session('success'))
                    <div class="alert alert-success mb-5">
                        {{ session('success') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login.post') }}">
                    @csrf

                    <div class="mb-5">
                        <label for="username" class="form-label">Username</label>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            value="{{ old('username') }}"
                            class="form-input"
                            placeholder="Masukkan username"
                            required
                            autofocus
                        >
                    </div>

                    <div class="mb-7">
                        <label for="password" class="form-label">Password</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-input"
                            placeholder="Masukkan password"
                            required
                        >
                    </div>

                    <button type="submit" class="btn btn-primary w-full">
                        Masuk
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
