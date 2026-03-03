<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code - {{ $table->nama_meja }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            background: #fff;
            padding: 50px;
        }
        .container {
            border: 2px dashed #000;
            padding: 40px;
            display: inline-block;
            border-radius: 10px;
        }
        .qr-image {
            width: 300px;
            height: 300px;
            margin: 20px 0;
        }
        h1 {
            font-size: 32px;
            margin-bottom: 5px;
        }
        p {
            font-size: 18px;
            color: #555;
            margin-bottom: 20px;
        }
        .btn-print {
            padding: 10px 20px;
            font-size: 16px;
            background-color: #4F46E5;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 20px;
        }
        @media print {
            .btn-print {
                display: none;
            }
            body {
                padding: 0;
            }
            .container {
                border: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Scan untuk Pesan</h1>
        <p>Meja: <strong>{{ $table->nama_meja }}</strong></p>
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=500x500&data={{ urlencode($orderUrl) }}" alt="QR Code {{ $table->nama_meja }}" class="qr-image">
        <p style="font-size: 14px; color: #888;">{{ config('app.name', 'SmartKasir Pro') }}</p>
    </div>
    <br>
    <button class="btn-print" onclick="window.print()">Cetak QR Code</button>
</body>
</html>
