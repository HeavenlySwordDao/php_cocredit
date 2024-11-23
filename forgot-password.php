<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #e9ecef;
            margin: 0;
            font-family: Arial, sans-serif;
        }
        .container {
            width: 100%;
            max-width: 400px;
            padding: 30px;
            background-color: #f4f4f4;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        h1 {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
        }
        .input-group {
            position: relative;
            margin-bottom: 20px;
            text-align: left;
        }
        .input-group label {
            position: absolute;
            top: 12px;
            left: 10px;
            font-size: 14px;
            color: #555;
            background-color: #f4f4f4;
            padding: 0 4px;
            transition: 0.3s;
            pointer-events: none;
        }
        .input-group input {
            width: 100%;
            padding: 12px 10px 12px 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            outline: none;
        }
        .input-group input:focus,
        .input-group input:not(:placeholder-shown) {
            border-color: #28a745;
        }
        .input-group input:focus + label,
        .input-group input:not(:placeholder-shown) + label {
            top: -8px;
            font-size: 12px;
            color: #28a745;
        }
        .btn {
            width: 100%;
            padding: 10px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
            margin-top: 15px;
        }
        .btn:hover {
            background-color: #218838;
        }
        .message {
            color: red;
            margin-top: 10px;
            font-size: 14px;
        }
        .success {
            color: green;
            font-size: 14px;
        }
        .links {
            font-size: 14px;
            color: #28a745;
            text-align: justify;
            text-decoration: none;
            transition: color 0.3s ease;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Forgot Password</h1>
    <form method="post" action="send-password-reset.php">
        <div class="input-group">
            <input type="email" name="email" id="email" placeholder=" " required>
            <label for="email">Email</label>
        </div>
        <button class="btn" type="submit">Send</button>

        <div class="links"  style="margin-left: 180px">
                 <a href="login.html">Back_to_Login</a>
            </div> 
    </form>
</div>

</body>
</html>
