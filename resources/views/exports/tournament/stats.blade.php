<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <title>Tabla de pociones</title>
    <style>
        *{
            font-family: "Inter", sans-serif;
        }
        html, body {
            width: 794px;
            height: 1123px;
            margin: 0;
            padding: 0.5rem;
            overflow-x: hidden;
            font-family: "Inter", sans-serif;
            color: #28243D;

        }
        table > tbody > tr > td, table > tbody > tr > th, table > thead > tr > th {
            border: none !important;
        }
        table > tbody > tr > td{
            padding: 1rem 0;
        }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 .5rem; /* 0 horizontal, 10px vertical */
            border: 1px solid #EAECF0 !important;
            border-bottom-left-radius: 12px;
            border-bottom-right-radius: 12px;
        }
        table > thead >tr >th{
            padding: 1rem 0;
        }
        table > tbody > tr > td{
            border-bottom: 1px solid #EAECF0 !important;
        }
        header{
            padding: 0 1rem 1rem 1rem;
            display: flex;
            align-items: center;
            border: 1px solid #EAECF0;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
            background: #F9FAFB;
        }

        .header-image-container{
            padding: 0 1rem;
            flex: 0 1 auto;
        }
        .header-details-container{
            display: flex;
            flex-direction: column;
            flex: 1 0 auto;
        }
        p{
            margin: 0;
        }
        p.league-title{
            color: #101828;
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: .5rem;
        }
        p.league-subtitle{
            color: #475467;
            font-size: 1rem;
            margin-bottom: 4px;
        }
    </style>
</head>

<body>
<header>
    <div class="header-image-container">
        <img src="{{asset('images/circular/logo-23.png')}}" alt="futzo-logo" width="200" height="auto">
    </div>
    <div class="header-details-container">
        <p class="league-title">{{$leagueName}}</p>
        <p class="league-subtitle">{{$tournamentName}}</p>
        <p class="league-subtitle">Jornada  {{$currentRound}}</p>
        <p class="league-subtitle"> {{$currentDate}}</p>
    </div>
</header>
@include('components.standing.table')
</body>
</html>