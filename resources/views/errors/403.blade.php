@section('title')
    | 403
@endsection

@include('frontend.index-top')

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 Error Page</title>
</head>


<body>


    <div style="text-align: center" class="card-body">
        <h1>ท่านไม่มีสิทธิ์เข้าใช้งานระบบนี้<br> ระบบนี้เฉพาะเจ้าหน้าที่เท่านั้น <br>หรือติดต่อ ทส.สท. โทร 7516
        </h1><br>
        <p id="counter" class="text-danger"></p><br>
        หรือคลิกที่ปุ่มนี้<br>
        <a href="{{ url('/logout') }}" class="btn btn-primary"><i class="fa fa-home"></i> กลับหน้าหลัก</a>
    </div>


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
                title: 'ท่านไม่มีสิทธิ์เข้าใช้งานระบบนี้',
            });

        });
    </script>
    <script>
        function startCountdown() {
            let counter = 1;
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

</body>

@include('frontend.index-footer')
