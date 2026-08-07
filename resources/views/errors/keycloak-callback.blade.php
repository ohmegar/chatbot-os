<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เซสชันหมดอายุ</title>

    <!-- ดึงฟอนต์ Prompt จาก Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        /* จัดวางตำแหน่งให้อยู่กึ่งกลางหน้าจอ */
        body, html {
            height: 100%;
            margin: 0;
            /* เปลี่ยนเป็นฟอนต์ Prompt เป็นหลัก */
            font-family: 'Prompt', system-ui, -apple-system, sans-serif;
            background-color: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* จำลองหน้า Dashboard เบลอๆ ไว้ด้านหลังแบบในรูป */
        .mock-bg {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: radial-gradient(circle at 80% 20%, #1e293b, #0f172a);
            filter: blur(20px);
            z-index: 1;
        }

        /* หน้าต่างแจ้งเตือนทรงกระจกใส (Glassmorphism) แบบดาร์กโทน */
        .modal-card {
            position: relative;
            z-index: 10;
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 3.5rem 2.5rem;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            text-align: center;
            max-width: 460px;
            width: 90%;
            animation: cubicPopup 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        /* หัวข้อ */
        .modal-card h2 {
            margin: 0 0 1.5rem 0;
            color: #ffffff;
            font-size: 1.75rem;
            font-weight: 600; /* ใช้ความหนา 600 ของ Prompt */
            letter-spacing: 0.5px;
        }

        /* วงแหวนไอคอนโล่เตือนภัยเรืองแสง */
        .icon-container {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: rgba(239, 68, 68, 0.1);
            border: 2px solid rgba(239, 68, 68, 0.4);
            box-shadow: 0 0 20px rgba(239, 68, 68, 0.2);
            margin-bottom: 2rem;
        }

        /* ไอคอนรูปโล่และเครื่องหมายอัศเจรีย์ (SVG) */
        .icon-shield {
            width: 42px;
            height: 42px;
            fill: #ef4444;
        }

        /* ข้อความแจ้งเตือน */
        .modal-card p {
            color: #94a3b8;
            font-size: 1.05rem;
            font-weight: 300; /* ใช้ความบาง 300 เพื่อความมินิมอลและอ่านง่าย */
            line-height: 1.7;
            margin: 0 0 2.5rem 0;
        }

        /* ปุ่มกดดีไซน์นีออนสีน้ำเงิน */
        .btn-redirect {
            display: block;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: #ffffff;
            text-decoration: none;
            padding: 1.1rem 2rem;
            border-radius: 14px;
            font-size: 1.1rem;
            font-weight: 500; /* ใช้ความหนาปานกลาง 500 */
            transition: all 0.2s ease;
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.4);
        }

        .btn-redirect:hover {
            background: linear-gradient(135deg, #60a5fa, #3b82f6);
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(37, 99, 235, 0.5);
        }

        .btn-redirect:active {
            transform: translateY(0);
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3);
        }

        /* อนิเมชั่นตอนเปิดหน้าจอให้ดูนุ่มนวล */
        @keyframes cubicPopup {
            0% {
                opacity: 0;
                transform: scale(0.92) translateY(10px);
            }
            100% {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
    </style>
</head>
<body>

    <!-- พื้นหลังจำลองความลึก -->
    <div class="mock-bg"></div>

    <!-- บล็อกแจ้งเตือน -->
    <div class="modal-card">

        <!-- ไอคอนโล่แจ้งเตือนแบบมินิมอล -->
        <div class="icon-container">
            <svg class="icon-shield" viewBox="0 0 24 24">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10zm0-15c.55 0 1 .45 1 1v4c0 .55-.45 1-1 1s-1-.45-1-1V8c0-.55.45-1 1-1zm-1 8h2v2h-2v-2z"/>
            </svg>
        </div>

        <h2>แจ้งเตือนจากระบบ</h2>

        <!-- ข้อความที่รับมาจาก Laravel -->
        <p>{{ $message }}</p>

        <!-- ปุ่มบังคับกด ย้ายหน้าด้วยมือผู้ใช้เองเท่านั้น ปลอดภัยแน่นอน -->
        <a href="{{ $loginUrl }}" class="btn-redirect">
            ตกลงและไปที่หน้าล็อกอิน
        </a>

    </div>

</body>
</html>
