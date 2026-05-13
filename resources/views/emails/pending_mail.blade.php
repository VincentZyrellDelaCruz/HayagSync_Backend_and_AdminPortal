<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Incident Notification</title>
</head>
<body>
    <p>Hello {{$gender === 'Male' ? 'Mr' : 'Ms/Mrs.'}} {{ $lastname }},</p>

    @if ($status === 'Approved')
        <p>
            We are pleased to inform you that your registration has been approved. <br>
            You can now access your account using the generated default password: <br> <br>

            Default Password: <b>{{ $password }}</b> <br> <br>

            For security, please log in immediately and change your password. <br>

            Thank you, <br>
        </p>
    @elseif($status === 'Rejected')
        <p>
            We regret to inform you that your registration has been rejected after review. <br>
            You may contact the administrator for further details or to reapply. <br> <br>

            Thank you for your interest, <br>
        </p>
    @endif

    <p>
        HayagSync Team
    </p>
</body>
</html>
