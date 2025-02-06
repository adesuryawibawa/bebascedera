<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 Forbidden</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f5f5f5;
        }
        .error-container {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 75%;
            max-width: 800px;
            text-align: center;
        }
        h1 {
            font-size: 50px;
            color: #ff0000;
            margin-bottom: 20px;
        }
        p {
            font-size: 20px;
            margin-bottom: 20px;
        }
        a {
            font-size: 18px;
            text-decoration: none;
            color: #1dbfc1;
            margin-bottom: 20px;
            display: inline-block;
        }
        a:hover {
            text-decoration: underline;
        }
        img {
            max-width: 100%;
            height: auto;
            margin-top: 20px;
            border-radius: 10px;
        }
    </style>
    <script>
        // Mencegah klik kanan di seluruh halaman
        document.addEventListener('contextmenu', function(event) {
            event.preventDefault();
        });
    </script>
</head>
<body>
    <div class="error-container">
        <img src="/assets/images/error.jpg" alt="Error Image">
        <a href="https://booking.bebascedera.com">Go back to the Homepage</a>
    </div>
</body>
</html>
