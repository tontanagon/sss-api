<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>ยืนยันคำสั่งซื้อ #55</title>
    {{-- <title>ยืนยันคำสั่งซื้อ #{{ $order->id }}</title> --}}
</head>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
<link href='https://fonts.googleapis.com/css?family=Noto Sans Thai' rel='stylesheet'>

<body style="font-family: Noto Sans Thai; background:#ffffff; margin:0; padding:20px;">
    <div class="container">
        @php
            $test = [['name' => 'test'], ['name' => 'test'], ['name' => 'test']];
        @endphp
        <div class="border-top border-bottom border-primary d-flex flex-row align-items-center ">
            <img src="{{ asset('storage/images/logo.png') }}" alt="My Image" style="height: 70px; weight: 70px;">
            <div class="ms-3" style="color:#264981;">
                <span class="d-flex flex-row">
                    Smart Store <span class="" style="color: #9D76B3;">System</span>
                </span>
                <span class="">
                    Faculty of Associated Medical Sciences
                </span>
            </div>
        </div>

        <div>
            การอนุมัติรายการจองของคุณเสร็จสมบูรณ์แล้ว
        </div>
        <div>
            สวัสดีคุณ $user->name, รายการจองของคุณ "รหัส : #booking_number" ได้รับการอนุมัติเรียบร้อยแล้ว
            {{-- คุณสามารถเข้ามาตรวจสอบรายละเอียดเพิ่มเติมได้ที่ --}}
        </div>
        <div class="d-flex flex-column">
            <span>รายการ #booking_number</span><span>สถานะ #status</span>
            @foreach ($test as $item)
                <div class="d-flex justify-items-between border-top border-secondary">
                    <div class="col"><img>รูป</div>
                    <div class="col">ชื่อวัสดุ

                    </div>
                    <div class="col"></div>
                    <div class="col flex-grow-1">
                        <span>
                            QTE: 1
                        </span>
                    </div>
                </div>
            @endforeach
            <span class="flex-fill border-top border-secondary text-end">รวมทั้งหมด 1 รายการ 2 ชิ้น</span>
        </div>
        <button>
            ดูรายระเอียดเพิ่มเติม
        </button>

    </div>


    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
        integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js"
        integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous">
    </script>
</body>

</html>
