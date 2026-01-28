<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Jornada {{ $round }} - {{ $league->name }}</title>
    @php
        $logoPath = 'file://' . public_path('images/circular/logo-23-crop_280_300.png');
    @endphp
    <style>
        html, body {
            width: 794px;
            height: 1123px;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            font-family: "Helvetica Neue", Arial, sans-serif;
            color: #28243D;

        }
        body{
            position: relative;
        }
        h1{
            font-size: 50px;
            margin: 0;
            text-align: center;
        }
        table{
            border-collapse: separate;
            border-spacing: 0 1rem; /* 0 horizontal, 10px vertical */
            width: 100%;
            margin: 0 auto;
        }
        .schedule-container{
            width: 794px;
            height: 1123px;
            display: flex;
            position: relative;
            align-items: center;
        }
        .bg-pattern{
            position: absolute;
            top:0;
            left: 0;
            content: "";
            width: 100%;
            height: 100%;
            background-size: contain;
            opacity: .2;
            z-index: -1;
        }
        .schedule{
            flex: 1 0 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding: 0 32px 80px;
            box-sizing: border-box;
        }
        .schedule-header{
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            margin-top: 3rem;
        }
        .header-logo{
            width: 72px;
            height: 72px;
            object-fit: contain;
            margin-right: 16px;
        }
        {{-- END sections --}}

        .day{
            display: flex;
            margin: 0 auto;
            flex-direction: column;
            align-items: center;
            width: 100%;
        }
        .date {
            font-size: 1rem;
            font-weight: 900;
            text-transform: uppercase;
        }
        .team-name{
            display: inline-block;
            max-width: 150px;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
            font-size: 1.5rem;
            font-weight: bold;
            margin: 1rem auto;
        }
        .team-image{
            border: 1mm solid white;
            border-radius: 50%;
            margin: 0 1rem;
        }
        .game-details{
            background: #28243D;
            color: white;
            font-size: 1rem;
            font-weight: 900;
            border-radius: 5px;
            padding: 4px 8px;
            text-align: center;
        }
        .league-name{
            font-size: 1rem;
            font-weight: 900;
        }
        .league-details{
            font-weight: 400;
            display: inline-block;
            max-width: 200px;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
            margin-left: 1rem;
        }
        .bye-message{
            text-align: center;
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0.75rem 0 0;
        }
        .league-details-container{
            position: absolute;
            right: 32px;
            bottom: 32px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.5rem;
            max-width: 60%;
            text-align: right;
        }
    </style>
</head>
<body>
<div class="bg-pattern">
</div>
<main class="schedule-container">
    <section class="schedule">
        <div class="schedule-header">
            <img src="{{$logoPath}}" alt="futzo logo" class="header-logo">
            <h1>Jornada {{$round}}</h1>
        </div>
        {{-- Cuando el torneo tiene equipos impares, mostramos quién descansa esa jornada. --}}
        @if($byeTeam)
            <p class="bye-message">{{ $byeTeam->name }} descansa esta jornada.</p>
        @endif
        @foreach( $games->groupBy(fn($game) => \Carbon\Carbon::parse($game->match_date)->locale('es')->translatedFormat('l d F')) as $date => $games)
            <div class="day">
                <p class="date">{{$date}}</p>
                <table>
                    @foreach($games as $match)
                        <tr>
                            <td>
                                <span class="team-name">{{$match->homeTeam->name}}</span>
                            </td>
                            <td>
                                <img src="{{$match->homeTeam->image}}" class="team-image" alt="local team image" width="40" height="40">
                            </td>
                            <td style="text-align: center;">
                                <p class="game-details">{{$match->match_time}}</p>
                            </td>
                            <td>
                                <img src="{{$match->awayTeam->image}}" class="team-image" alt="local team image" width="40" height="40">
                            </td>
                            <td>
                                <span class="team-name">{{$match->awayTeam->name}}</span>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endforeach
        <footer class="league-details-container">
            <p class="league-name">{{$league->name}}</p> <span class="league-details">{{$tournament->name}}</span>
        </footer>
    </section>
</main>
</body>
</html>
