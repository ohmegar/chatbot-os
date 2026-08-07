<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>System Unsupported</title>
    <link rel="shortcut icon" href="{{ asset('images/logo.png') }}">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@600&display=swap');
        * {
            -webkit-box-sizing: border-box;
            box-sizing: border-box;
        }
        body {
            padding: 0;
            margin: 0;
            font-family: "Noto Sans Thai", Arial, Helvetica, sans-serif;
        }
        a {
            text-decoration: none;
            border-bottom: solid 1px;
        }
        a:hover {
            color: green;
        }
        #notfound {
            position: relative;
            height: 100vh;
        }
        #notfound .notfound {
            position: absolute;
            left: 50%;
            top: 50%;
            -webkit-transform: translate(-50%, -50%);
            -ms-transform: translate(-50%, -50%);
            transform: translate(-50%, -50%);
        }
        .notfound {
            max-width: 1100px;
            width: 100%;
            line-height: 1.4;
            text-align: center;
        }
        .notfound h1 {
            font-size: 40px;
            font-weight: 500;
            font-style: normal;
            line-height: initial;
            font-optical-sizing: auto;
            font-variation-settings: "opsz"64;
        }
        .notfound h1 span {
            color: dodgerblue;
            font-size: 2rem;
        }
        .notfound h3 {
            font-size: 25px;
            color: green;
            font-weight: 500;
            font-style: normal;
        }
        @media only screen and (max-width: 480px) {
            .notfound h1 {
                display: flex;
                flex-direction: column;
                font-size: 25px;
            }
            .notfound h1 span {
                font-size: 1rem;
            }
            .notfound h3 {
                font-size: 18px;
            }
            .notfound p span {
                display: flex;
                flex-direction: column;
                margin-bottom: 8px;
            }
            .notfound p b {
                display: none;
            }
        }
    </style>

<body>
    <div id="notfound">
        <div class="notfound">
            <h1>ระบบนี้ไม่รองรับการใช้งานผ่านบราวเซอร์ <span>Internet Explorer (IE)</span> <br>ที่ท่านใช้อยู่ในปัจจุบัน</h1>
            <h3>เราขอแนะนำให้ใช้งานระบบนี้ผ่าน</h3>
            <p>
                <span>Google Chrome
                    <a href="https://www.google.com/intl/th/chrome/?brand=YTUH&gclid=CjwKCAjwve2TBhByEiwAaktM1El3mP9-hTtHQquWPb8IEtxayX8Z2XJnn2x2UVkE0HA8n05WK7dg7BoCmvUQAvD_BwE&gclsrc=aw.ds"
                        target="_blank">ดาวน์โหลด Chrome</a>
                </span>
                <b>|</b>
                <span>Firefox
                    <a href="https://www.mozilla.org/th/firefox/new/" target="_blank">ดาวน์โหลด firefox</a>
                </span>
                <b>|</b>
                <span>Edge
                    <a href="https://www.microsoft.com/th-th/edge" target="_blank">ดาวน์โหลด Edge</a>
                </span>
            </p>
        </div>
    </div>
</body>

</html>