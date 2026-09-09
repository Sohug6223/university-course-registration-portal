<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Password Reset OTP</title>
</head>

<body style="font-family: Arial, sans-serif; background: #f4f6f8; padding: 30px;">

    <div style="
        max-width: 500px;
        margin: auto;
        background: white;
        padding: 30px;
        border-radius: 10px;
        text-align: center;
    ">

        <h2 style="color: #123b63;">
            City University
        </h2>

        <p>
            We received a request to reset your password.
        </p>

        <p>
            Your One-Time Password (OTP) is:
        </p>

        <div style="
            font-size: 32px;
            font-weight: bold;
            letter-spacing: 8px;
            color: #123b63;
            margin: 25px 0;
        ">
            {{ $otp }}
        </div>

        <p>
            This OTP will expire in <strong>5 minutes</strong>.
        </p>

        <p style="color: #777; font-size: 13px;">
            If you did not request a password reset, please ignore this email.
        </p>

    </div>

</body>
</html>