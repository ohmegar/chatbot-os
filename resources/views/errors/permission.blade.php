@section('title')
    | 405 ข้อมูลไม่ครบ
@endsection

@include('frontend.index-top')


<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>permission</title>
</head>
<br><br><br><br>

<body>

    @if (session('status') == 405)
        <div style="text-align: center" class="card-body">
            <h1>{{ session('msg') }} <br>โปรดติดต่อ ศบก.สลก. โทร 7519</h1><br>
            <p id="counter" class="text-danger"></p><br>
            หรือคลิกที่ปุ่มนี้<br>
            <a href="{{ url('/logout') }}" class="btn btn-primary"><i class="fa fa-home"></i> กลับหน้าหลัก</a>
        </div>
    @endif

    @if (session('status') == 405)
        <script type="text/javascript">
            $(function() {
                const Toast = Swal.mixin({
                    toast: false,
                    position: 'center',
                    showConfirmButton: false,
                    timer: 2000
                });
                Toast.fire({
                    icon: 'error',
                    title: '{{ session('msg') }}',
                });

            });
        </script>
        <script>
            function startCountdown() {
                let counter = 10;
                const interval = setInterval(() => {
                    counter--;

                    document.getElementById('counter').innerHTML = `ระบบจะดำเนินการกลับหน้าหลักในอีก ${counter} วินาที`

                    if (counter < 0) {
                        document.getElementById('counter').innerHTML = '';
                        clearInterval(interval);
                        window.location.href = "{{ url('/logout') }}";
                    }
                }, 1000);
            }
            startCountdown();
        </script>
    @endif


</body>


@include('frontend.index-footer')