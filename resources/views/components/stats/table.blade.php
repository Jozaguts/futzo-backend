<table style="border: 1px solid black; border-collapse: collapse;">
    <thead>
    @if($showDetails)
        <tr>
            <th colspan="2" style="text-align: center; border-bottom: 1px solid black; border-collapse: collapse; border-right: 1px solid black; ">Liga</th>
            <th colspan="11" style="font-weight: bold; text-align: right; border-bottom: 1px solid black; border-collapse: collapse; border-right: 1px solid black;">{{$leagueName}}</th>
        </tr>
        <tr>
            <th colspan="2" style="text-align: center; border-bottom: 1px solid black; border-collapse: collapse; border-right: 1px solid black;">Torneo </th>
            <th colspan="11" style="font-weight: bold; text-align: right; border-bottom: 1px solid black; border-collapse: collapse; border-right: 1px solid black;">{{$tournamentName}} </th>
        </tr>
        <tr>
            <th colspan="2" style="text-align: center; border-bottom: 1px solid black; border-collapse: collapse; border-right: 1px solid black; ">Fecha</th>
            <th colspan="11" style="font-weight: bold; text-align: right; border-bottom: 1px solid black; border-collapse: collapse; border-right: 1px solid black;">{{$currentDate}}</th>
        </tr>
        <tr>
            <th colspan="2" style="text-align: center; border-bottom: 1px solid black; border-collapse: collapse; border-right: 1px solid black; ">Jornada</th>
            <th colspan="11" style="font-weight: bold; text-align: right; border-bottom: 1px solid black; border-collapse: collapse; border-right: 1px solid black;">{{$currentRound}}</th>
        </tr>
        <tr></tr>
        <tr></tr>
        <tr>
            <th colspan="13" style="text-align: center; font-weight: bold;  border-collapse: collapse;">
                Líderes de estadísticas
            </th>
        </tr>
        <tr></tr>
    @endif
    </thead>
    <tbody>
        <tr>
            <th colspan="8" style="text-align: center; font-weight: bold; border: 1px solid black; border-collapse: collapse;">
                Goles
            </th>
        </tr>
        <tr>
            <th colspan="2"  style="text-align: center; border: 1px solid black; border-collapse: collapse; font-weight: bold;">Goles</th>
            <th colspan="3" style="text-align: center; border: 1px solid black; border-collapse: collapse; font-weight: bold;">Jugador</th>
            <th colspan="3" style="text-align: center; border: 1px solid black; border-collapse: collapse; font-weight: bold;">Equipo</th>
        </tr>
        @foreach($goals as $stat)
            <tr>
                <td colspan="2" style="text-align: center; border: 1px solid black; border-collapse: collapse;">
                    {{$stat->total}}
                </td>
                <td colspan="3" style="text-align: center; border: 1px solid black; border-collapse: collapse;">
                    {{$stat->player_name}}
                </td>
                <td colspan="3" style="text-align: center; border: 1px solid black; border-collapse: collapse;">
                    {{$stat->team_name}}
                </td>
            </tr>
        @endforeach
        <tr></tr>
        <tr>
            <th colspan="8" style="text-align: center; font-weight: bold; border: 1px solid black; border-collapse: collapse;">
                Asistencias
            </th>
        </tr>
        <tr>
            <th colspan="2"  style="text-align: center; border: 1px solid black; border-collapse: collapse; font-weight: bold;">Asistencias</th>
            <th colspan="3" style="text-align: center; border: 1px solid black; border-collapse: collapse; font-weight: bold;">Jugador</th>
            <th colspan="3" style="text-align: center; border: 1px solid black; border-collapse: collapse; font-weight: bold;">Equipo</th>
        </tr>
        @foreach($assistance as $stat)
            <tr>
                <td colspan="2" style="text-align: center; border: 1px solid black; border-collapse: collapse;">
                    {{$stat->total}}
                </td>
                <td colspan="3" style="text-align: center; border: 1px solid black; border-collapse: collapse;">
                    {{$stat->player_name}}
                </td>
                <td colspan="3" style="text-align: center; border: 1px solid black; border-collapse: collapse;">
                    {{$stat->team_name}}
                </td>
            </tr>
        @endforeach
        <tr></tr>
        <tr>
            <th colspan="8" style="text-align: center; font-weight: bold; border: 1px solid black; border-collapse: collapse;">
                Tarjetas Amarillas
            </th>
        </tr>
        <tr>
            <th colspan="2"  style="text-align: center; border: 1px solid black; border-collapse: collapse; font-weight: bold;">Tarjetas</th>
            <th colspan="3" style="text-align: center; border: 1px solid black; border-collapse: collapse; font-weight: bold;">Jugador</th>
            <th colspan="3" style="text-align: center; border: 1px solid black; border-collapse: collapse; font-weight: bold;">Equipo</th>
        </tr>
        @foreach($yellowCards as $stat)
            <tr>
                <td colspan="2" style="text-align: center; border: 1px solid black; border-collapse: collapse;">
                    {{$stat->total}}
                </td>
                <td colspan="3" style="text-align: center; border: 1px solid black; border-collapse: collapse;">
                    {{$stat->player_name}}
                </td>
                <td colspan="3" style="text-align: center; border: 1px solid black; border-collapse: collapse;">
                    {{$stat->team_name}}
                </td>
            </tr>
        @endforeach
        <tr></tr>
        <tr>
            <th colspan="8" style="text-align: center; font-weight: bold; border: 1px solid black; border-collapse: collapse;">
                Tarjetas Rojas
            </th>
        </tr>
        <tr>
            <th colspan="2"  style="text-align: center; border: 1px solid black; border-collapse: collapse; font-weight: bold;">Tarjetas</th>
            <th colspan="3" style="text-align: center; border: 1px solid black; border-collapse: collapse; font-weight: bold;">Jugador</th>
            <th colspan="3" style="text-align: center; border: 1px solid black; border-collapse: collapse; font-weight: bold;">Equipo</th>
        </tr>
        @foreach($redCards as $stat)
            <tr>
                <td colspan="2" style="text-align: center; border: 1px solid black; border-collapse: collapse;">
                    {{$stat->total}}
                </td>
                <td colspan="3" style="text-align: center; border: 1px solid black; border-collapse: collapse;">
                    {{$stat->player_name}}
                </td>
                <td colspan="3" style="text-align: center; border: 1px solid black; border-collapse: collapse;">
                    {{$stat->team_name}}
                </td>
            </tr>
        @endforeach
    </tbody>
</table>