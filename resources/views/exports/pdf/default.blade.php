<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Jornada 1 - Liga mx</title>
{{--    <title>Jornada {{ $round->number }} - {{ $league->name }}</title>--}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <style>
        @page { margin: 30px; }
        body {
            font-family: "Inter", sans-serif;
            font-optical-sizing: auto;
            font-style: normal;
            color: #28243D;
            position: relative;
            overflow-x: hidden;
            width: 100%;
            height: 100%;
            max-width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 0;
        }
        .bg-patter{
            width: 100%;
            height: 100%;
            position: absolute;
            background-image: url("{{asset('images/vector-bg.jpg')}}");
            background-size: cover;
            opacity: 0.2;
            z-index: -1;
        }
        .schedule-container{
            max-width: 100%;
            height: 100%;
            margin:  0 auto;
            display: flex;
        }
        .drawer {
            position: relative;
            width: 20%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .bg-drawer {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' version='1.1' xmlns:xlink='http://www.w3.org/1999/xlink' xmlns:svgjs='http://svgjs.dev/svgjs' width='300' height='1200' preserveAspectRatio='none' viewBox='0 0 300 1200'%3e%3cg mask='url(%26quot%3b%23SvgjsMask1118%26quot%3b)' fill='none'%3e%3crect width='300' height='1200' x='0' y='0' fill='url(%26quot%3b%23SvgjsLinearGradient1119%26quot%3b)'%3e%3c/rect%3e%3cpath d='M300 0L224.44 0L300 14.45z' fill='rgba(255%2c 255%2c 255%2c .1)'%3e%3c/path%3e%3cpath d='M224.44 0L300 14.45L300 435.86L171.68 0z' fill='rgba(255%2c 255%2c 255%2c .075)'%3e%3c/path%3e%3cpath d='M171.68 0L300 435.86L300 794.81L75.85000000000001 0z' fill='rgba(255%2c 255%2c 255%2c .05)'%3e%3c/path%3e%3cpath d='M75.85000000000002 0L300 794.81L300 976.02L27.61000000000002 0z' fill='rgba(255%2c 255%2c 255%2c .025)'%3e%3c/path%3e%3cpath d='M0 1200L102.05 1200L0 935.5z' fill='rgba(0%2c 0%2c 0%2c .1)'%3e%3c/path%3e%3cpath d='M0 935.5L102.05 1200L163.78 1200L0 491.44z' fill='rgba(0%2c 0%2c 0%2c .075)'%3e%3c/path%3e%3cpath d='M0 491.44000000000005L163.78 1200L174.17000000000002 1200L0 337.85z' fill='rgba(0%2c 0%2c 0%2c .05)'%3e%3c/path%3e%3cpath d='M0 337.85L174.17000000000002 1200L199.13000000000002 1200L0 308.42z' fill='rgba(0%2c 0%2c 0%2c .025)'%3e%3c/path%3e%3c/g%3e%3cdefs%3e%3cmask id='SvgjsMask1118'%3e%3crect width='300' height='1200' fill='white'%3e%3c/rect%3e%3c/mask%3e%3clinearGradient x1='100%25' y1='50%25' x2='0%25' y2='50%25' gradientUnits='userSpaceOnUse' id='SvgjsLinearGradient1119'%3e%3cstop stop-color='%230e2a47' offset='0'%3e%3c/stop%3e%3cstop stop-color='rgba(71%2c 84%2c 103%2c 1)' offset='1'%3e%3c/stop%3e%3c/linearGradient%3e%3c/defs%3e%3c/svg%3e");
            background-repeat: repeat;
            width: 100%;
            height: 100%;
            min-height: 297mm;
            z-index: 10;
            border-bottom-right-radius: 60px;
            border-top-right-radius: 60px;
        }
        .drawer-img-container {
            position: absolute;
            top: 0;
            left: 0;
            transform: translate(-60px, calc(50% + 280px));
            z-index: 20;
            width: 280px;
            height: 280px;
            object-fit: cover;
        }
        h1{
            font-size: 5rem;
            margin-bottom: 1rem;
        }
        .schedule{
            width: 80%;
            text-align: center;
        }
        .day{
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .date {
            font-size: 1rem;
            font-weight: 900;
            text-transform: uppercase;
        }
        .game{
            display: flex;
            width: 100%;
            justify-content: center;
            align-items: center;
            margin: 1rem 0 .5rem 0;
        }
        .game:first-child{
            margin-top: 0;
        }
        .team{
            display: flex;
            align-items: center;
            font-weight: 900;
            text-transform: capitalize;
        }
        .team-image{
            border: 1px solid white;
            border-radius: 50%;
            margin: 0 1rem;
        }
        .game-details{
            background: #28243D;
            color: white;
            font-weight: 900;
            border-radius: 5px;
            padding: 4px 8px;
        }
        .league-details-container{
            margin: 1rem  auto 0 auto;
        }
        .league-name{
            font-weight: 900;
        }
        .league-details{
            font-weight: 400;
        }
    </style>
</head>
<body>
<div class="bg-patter"></div>
<div class="schedule-container">
    <aside class="drawer">
        <div class="bg-drawer"></div>
        <div class="drawer-img-container">
            <img src="{{asset('images/circular/logo-24.png')}}" alt="futzo logo" width="100%">
        </div>
    </aside>
    <main class="schedule">
        <h1>Matchweek 1</h1>
        <div class="day">
            <p class="date">friday 16 august</p>
            <div class="game">
                <div class="team local">
                    <span class="team-name">Man Utd</span>
                    <img src="" class="team-image" alt="local team image" width="40" height="40">
                </div>
                <div class="game-details">
                    <span>12:30</span>
                </div>
                <div class="team away">
                    <img src="" class="team-image" alt="local team image" width="40" height="40">
                    <span class="team-name">fullham</span>
                </div>
            </div>
        </div>
        <div class="day">
            <p class="date">saturday 17 august</p>
            <div class="game">
                <div class="team local">
                    <span class="team-name">Man Utd</span>
                    <img src="" class="team-image" alt="local team image" width="40" height="40">
                </div>
                <div class="game-details">
                    <span>12:30</span>
                </div>
                <div class="team away">
                    <img src="" class="team-image" alt="local team image" width="40" height="40">
                    <span class="team-name">fullham</span>
                </div>
            </div>
            <div class="game">
                <div class="team local">
                    <span class="team-name">Man Utd</span>
                    <img src="" class="team-image" alt="local team image" width="40" height="40">
                </div>
                <div class="game-details">
                    <span>12:30</span>
                </div>
                <div class="team away">
                    <img src="" class="team-image" alt="local team image" width="40" height="40">
                    <span class="team-name">fullham</span>
                </div>
            </div>
            <div class="game">
                <div class="team local">
                    <span class="team-name">Man Utd</span>
                    <img src="" class="team-image" alt="local team image" width="40" height="40">
                </div>
                <div class="game-details">
                    <span>12:30</span>
                </div>
                <div class="team away">
                    <img src="" class="team-image" alt="local team image" width="40" height="40">
                    <span class="team-name">fullham</span>
                </div>
            </div>
            <div class="game">
                <div class="team local">
                    <span class="team-name">Man Utd</span>
                    <img src="" class="team-image" alt="local team image" width="40" height="40">
                </div>
                <div class="game-details">
                    <span>12:30</span>
                </div>
                <div class="team away">
                    <img src="" class="team-image" alt="local team image" width="40" height="40">
                    <span class="team-name">fullham</span>
                </div>
            </div>
            <div class="game">
                <div class="team local">
                    <span class="team-name">Man Utd</span>
                    <img src="" class="team-image" alt="local team image" width="40" height="40">
                </div>
                <div class="game-details">
                    <span>12:30</span>
                </div>
                <div class="team away">
                    <img src="" class="team-image" alt="local team image" width="40" height="40">
                    <span class="team-name">fullham</span>
                </div>
            </div>
            <div class="game">
                <div class="team local">
                    <span class="team-name">Man Utd</span>
                    <img src="" class="team-image" alt="local team image" width="40" height="40">
                </div>
                <div class="game-details">
                    <span>12:30</span>
                </div>
                <div class="team away">
                    <img src="" class="team-image" alt="local team image" width="40" height="40">
                    <span class="team-name">fullham</span>
                </div>
            </div>
        </div>
        <div class="day">
            <p class="date">sunday 18 august</p>
            <div class="game">
                <div class="team local">
                    <span class="team-name">Man Utd</span>
                    <img src="" class="team-image" alt="local team image" width="40" height="40">
                </div>
                <div class="game-details">
                    <span>12:30</span>
                </div>
                <div class="team away">
                    <img src="" class="team-image" alt="local team image" width="40" height="40">
                    <span class="team-name">fullham</span>
                </div>
            </div>
            <div class="game">
                <div class="team local">
                    <span class="team-name">Man Utd</span>
                    <img src="" class="team-image" alt="local team image" width="40" height="40">
                </div>
                <div class="game-details">
                    <span>12:30</span>
                </div>
                <div class="team away">
                    <img src="" class="team-image" alt="local team image" width="40" height="40">
                    <span class="team-name">fullham</span>
                </div>
            </div>
        </div>
        <div class="day">
            <p class="date">monday 119 august</p>
            <div class="game">
                <div class="team local">
                    <span class="team-name">Man Utd</span>
                    <img src="" class="team-image" alt="local team image" width="40" height="40">
                </div>
                <div class="game-details">
                    <span>12:30</span>
                </div>
                <div class="team away">
                    <img src="" class="team-image" alt="local team image" width="40" height="40">
                    <span class="team-name">fullham</span>
                </div>
            </div>
        </div>
        <footer class="league-details-container">
            <p class="league-name">Premier League <span class="league-details">2024/2025 partidos</span></p>
        </footer>
    </main>

</div>
</body>
</html>
