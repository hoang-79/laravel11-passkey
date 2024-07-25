<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Code for Email Verification</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
<div class="bg-white p-8 rounded-lg shadow-lg w-96">
    <h1 class="text-2xl font-bold mb-4">OTP Code for Email Verification</h1>
    <p class="text-lg">Your OTP Code is:</p>
    <div class="text-center my-6">
        <span class="text-4xl font-bold text-indigo-600">{{ $otp }}</span>
    </div>
    <p class="text-gray-600">This code is needed to verify your email address. Please enter it in the required field to continue.</p>
</div>
</body>
</html>
