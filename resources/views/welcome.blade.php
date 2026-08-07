<!DOCTYPE html>
<html lang="th" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chatbot OS - ระบบผู้ช่วยอัจฉริยะภายใน</title>

    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">


    <!-- Google Fonts: Prompt -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS (ผ่าน Vite) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Prompt', sans-serif;
        }

        .glow-effect:hover {
            box-shadow: 0 0 25px rgba(70, 95, 255, 0.4);
        }
    </style>
</head>

<body class="h-full bg-gray-50 text-gray-900 flex flex-col justify-between selection:bg-brand-500 selection:text-white">

    <!-- Background Decorative Gradients -->
    <div class="absolute inset-0 -z-10 overflow-hidden pointer-events-none">
        <div
            class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[500px] bg-gradient-to-tr from-brand-100/60 to-blue-light-100/40 blur-3xl rounded-full opacity-70">
        </div>
    </div>

    <!-- Header / Navbar เล็กๆ ด้านบน -->
    <header class="w-full max-w-7xl mx-auto px-6 py-6 flex justify-between items-center">
        <div class="flex items-center gap-3">
            <div
                class="w-10 h-10 rounded-xl bg-brand-500 flex items-center justify-center text-white shadow-md shadow-brand-500/20">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z">
                    </path>
                </svg>
            </div>
            <span class="font-bold text-lg tracking-wide text-gray-800">Chatbot OS</span>
        </div>
        <div>
            <span
                class="text-xs font-medium px-3 py-1.5 rounded-full bg-brand-50 text-brand-600 border border-brand-100">
                Internal Service
            </span>
        </div>
    </header>

    <!-- Main Content (Hero Section) -->
    <main class="w-full max-w-4xl mx-auto px-6 py-12 text-center flex-1 flex flex-col justify-center items-center">

        <!-- Badge หัวข้อรอง -->
        <div
            class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white border border-gray-200 shadow-sm mb-8 animate-bounce-subtle">
            <span class="w-2 h-2 rounded-full bg-success-500"></span>
            <span class="text-xs font-medium text-gray-600">ระบบปัญญาประดิษฐ์และบริการสารสนเทศภายในองค์กร</span>
        </div>

        <!-- หัวข้อหลัก -->
        <h1 class="text-4xl sm:text-6xl font-bold tracking-tight text-gray-900 leading-tight mb-6">
            ผู้ช่วยอัจฉริยะ <br class="hidden sm:inline">
            <span class="bg-gradient-to-r from-brand-600 to-blue-light-500 bg-clip-text text-transparent">
                พร้อมสนับสนุนการทำงานของคุณ
            </span>
        </h1>

        <!-- คำอธิบายสั้นๆ -->
        <p class="text-base sm:text-lg text-gray-600 max-w-2xl mb-10 leading-relaxed">
            ค้นหาข้อมูลระเบียบปฏิบัติ ตอบคำถามอัตโนมัติ
            และบริการบุคลากรภาครัฐด้วยมาตรฐานความปลอดภัยสูงสุด
        </p>

        <!-- ปุ่ม Login (ชี้ไปที่ Route สำหรับเรียก Keycloak) -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 w-full">
            <a href="{{ url('/login') }}"
                class="w-full sm:w-auto inline-flex items-center justify-center gap-3 px-8 py-4 rounded-xl bg-brand-500 text-white font-medium text-base shadow-lg shadow-brand-500/25 glow-effect transition-all duration-200 hover:bg-brand-600 active:scale-95">
                {{-- <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1">
                    </path>
                </svg> --}}
                เข้าสู่ระบบด้วย Keycloak (DSS Account)
            </a>
        </div>

    </main>

    <!-- Footer -->
    <footer class="w-full max-w-7xl mx-auto px-6 py-6 text-center text-xs text-gray-400 border-t border-gray-100">
        &copy; 2026 Department of Science Service (กรมวิทยาศาสตร์บริการ). All rights reserved.
    </footer>

</body>

</html>
