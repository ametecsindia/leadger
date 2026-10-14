<!DOCTYPE html>
<html>
<head>
    <title>Agreement PDF</title>
    <style>
        body { font-family: sans-serif; }
        .section { margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1>Agreement Summary</h1>
    <div class="section"><strong>Advertiser Name:</strong> {{ $agreement->advertiser_name }}</div>
    <div class="section"><strong>Calls Per Week:</strong> {{ $agreement->calls_per_week }}</div>
    <div class="section"><strong>Calls Per Month:</strong> {{ $agreement->calls_per_month }}</div>
    <div class="section"><strong>Preferred Calling Hours:</strong> {{ $agreement->preferred_calling_hours }}</div>
    <div class="section"><strong>Support Person Name:</strong> {{ $agreement->support_person_name }}</div>
    <div class="section"><strong>Contact Details:</strong> {{ $agreement->contact_details }}</div>
    <div class="section"><strong>Start Date:</strong> {{ $agreement->start_date }}</div>
    <div class="section"><strong>End Date:</strong> {{ $agreement->end_date }}</div>
    <div class="section"><strong>Terms Agreed:</strong> Yes</div>
</body>
</html>
