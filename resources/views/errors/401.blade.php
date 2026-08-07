@section('title')
    | 401
@endsection


@include('frontend.index-top')


<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>401 Error Page</title>
</head>
<body>
    <div class="container mt-5 pt-5">
        <div class="alert alert-danger text-center">
            <h2 class="display-3">401</h2>
            <p class="display-5">Sorry , Unauthorized</p>
        </div>
    </div>
</body>


@include('frontend.index-footer')