<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Jornada {{ $round }} - {{ $league->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <style>
        html, body {
            width: 794px;
            height: 1123px;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            font-family: "Inter", sans-serif;
            color: #28243D;

        }
        body{
            position: relative;
        }
        h1{
            font-size: 50px;
            margin: 3rem 0 0 0;
            text-align: center;
        }
        table{
            border-collapse: separate;
            border-spacing: 0 1rem; /* 0 horizontal, 10px vertical */
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
            background-image: url("{{asset('images/vector-bg.jpg')}}");
            background-size: contain;
            opacity: .2;
            z-index: -1;
        }
        .drawer {
            flex: 1 0 20%;
            display: flex;
            width: 100%;
            height: 100%;
            justify-content: center;
            align-items: center;
            background-image: url("{{asset('images/circular/download.svg')}}");
            background-size: cover;
            background-repeat: no-repeat;
            border-top-right-radius: 120px;
            border-bottom-right-radius: 120px;
        }
        .drawer-img-container {
            width: 90%;
            object-fit: contain;
        }
        .schedule{
            flex: 0 0 80%;
        }
        .league-details-container{
            display: flex;
            justify-items: center;
            align-items: center;
            justify-content: center;
        }
        {{-- END sections --}}

        .day{
            display: flex;
            margin: 0 auto;
            flex-direction: column;
            align-items: center;
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
    </style>
</head>
<body>
<div class="bg-pattern">
</div>
<main class="schedule-container">
    <aside class="drawer">
        <div class="drawer-img-container">
            <img src="{{asset('images/circular/logo-24-crop.png')}}" alt="futzo logo" width="100%">
        </div>
    </aside>
    <section class="schedule">
        <h1>Jornada {{$round}}</h1>
        @if($byeTeam)
            <p class="bye-message">{{ $byeTeam->name }} descansa esta jornada.</p>
        @endif
        @foreach( $games->groupBy(fn($game) => \Carbon\Carbon::parse($game->match_date)->format('l d F')) as $date => $games)
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
            <p class="league-name">{{$league->name}}</p> <span class="league-details">{{$tournament->name}} partidos</span>
        </footer>
    </section>
</main>
</body>
</html>
