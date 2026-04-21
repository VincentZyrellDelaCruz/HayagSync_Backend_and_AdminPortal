<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Incident Notification</title>
</head>
<body>
    <p>Dear {{ $recipient }},</p>

    @if ($status === 'Scheduled')
        <p>
            The incident reported involving your child requires further discussion. We have scheduled a meeting on [Date/Time] to be held either at the school office or online via Zoom. <br>

            Your presence is important to ensure clarity and support for your child. Please confirm your availability.
        </p>
    @elseif($status === 'Cancelled')
        <p>
            We wish to inform you that the incident reported involving your child <b>has been cancelled</b>. After reviewing the evidences and context, the matter has been resolved without further action.

            You can reappeal or resubmit your incident report with strong informationa and substantial evidence if there's mistakes in your previous submission.

            We appreciate your cooperation and understanding throughout this process.
        </p>
    @endif

    <p>Please log in to the mobile app for full details.</p>

    <p>
        Regards,<br>
        {{ $sender->first_name . ' ' . $sender->last_name }} <br>
        {{ $sender->staff?->positions()->orderByDesc('staff_position.assigned_at')->first()->position_name }} <br>
        {{ $sender->staff->department }}
    </p>
</body>
</html>
