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
        <tr>
            <th colspan="13" style="text-align: center; font-weight: bold; border: 1px solid black; border-collapse: collapse;">
                Tabla de Posiciones
            </th>
        </tr>
    @endif
    <tr>
        <th colspan="2"  style="text-align: center; border: 1px solid black; border-collapse: collapse; font-weight: bold;">Posición</th>
        <th colspan="2" style="text-align: center; border: 1px solid black; border-collapse: collapse; font-weight: bold;">Equipo</th>
        <th style="text-align: center; border: 1px solid black; border-collapse: collapse; font-weight: bold;">PJ</th>
        <th style="text-align: center; border: 1px solid black; border-collapse: collapse; font-weight: bold;">PG</th>
        <th style="text-align: center; border: 1px solid black; border-collapse: collapse; font-weight: bold;">PE</th>
        <th style="text-align: center; border: 1px solid black; border-collapse: collapse; font-weight: bold;">PP</th>
        <th style="text-align: center; border: 1px solid black; border-collapse: collapse; font-weight: bold;">GF</th>
        <th style="text-align: center; border: 1px solid black; border-collapse: collapse; font-weight: bold;">GC</th>
        <th style="text-align: center; border: 1px solid black; border-collapse: collapse; font-weight: bold;">DG</th>
        <th style="text-align: center; border: 1px solid black; border-collapse: collapse; font-weight: bold;">Pts.</th>
        <th style="text-align: center; border: 1px solid black; border-collapse: collapse; font-weight: bold;">Últimos 5</th>
    </tr>
    </thead>
    <tbody>
    @foreach($standing as $stat)
        <tr>
            <td colspan="2" style="text-align: center; border: 1px solid black; border-collapse: collapse;">
                {{$stat['rank']}}
            </td>
            <td colspan="2" style="text-align: center; border: 1px solid black; border-collapse: collapse;">
                {{$stat['team']['name']}}
            </td>
            <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{$stat['matches_played']}}</td>
            <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{$stat['wins']}}</td>
            <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{$stat['draws']}}</td>
            <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{$stat['losses']}}</td>
            <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{$stat['goals_for']}}</td>
            <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{$stat['goals_against']}}</td>
            <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{$stat['goal_difference']}}</td>
            <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{$stat['points']}}</td>
            <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{$stat['last_5']}}</td>
        </tr>
    @endforeach
    </tbody>
    @if($showDetails)
        <tfoot >
        <tr></tr>
        <tr style=" border-top: 1px solid black; border-collapse: collapse;">
            <td colspan="11"></td>
            <th colspan="2"   style="text-align: center; font-weight: bold; border-collapse: collapse; border: 1px solid black;" >Últimos 5</th>
        </tr>
        <tr style=" border-left: 1px solid black; border-collapse: collapse;">
            <td colspan="11"></td>
            <th colspan="1" style="text-align: center; border-left: 1px solid black;  border-bottom: 1px solid black; border-collapse: collapse; " >W</th>
            <th colspan="1" style="text-align: left;  border-right: 1px solid black; border-bottom: 1px solid black; border-collapse: collapse;" >Ganó</th>
        </tr>
        <tr>
            <td colspan="11"></td>
            <th colspan="1" style="text-align: center;  border-left: 1px solid black; border-collapse: collapse; border-bottom: 1px solid black;" >D</th>
            <th colspan="1" style="text-align: left;  border-right: 1px solid black; border-collapse: collapse; border-bottom: 1px solid black;" >Empató</th>
        </tr>
        <tr>
            <td colspan="11"></td>
            <th colspan="1" style="text-align: center;  border-left: 1px solid black; border-collapse: collapse; border-bottom: 1px solid black;" >L</th>
            <th colspan="1" style="text-align: left;  border-right: 1px solid black; border-collapse: collapse; border-bottom: 1px solid black;" >Perdió</th>
        </tr>
        <tr>
            <td colspan="11"></td>
            <th colspan="1" style="text-align: center;  border-left: 1px solid black; border-bottom: 1px solid black;  border-collapse: collapse;" >-</th>
            <th colspan="1" style="text-align: left;  border-right: 1px solid black; border-bottom: 1px solid black; border-right: 1px solid black; border-collapse: collapse;" >No jugó</th>
        </tr>
        </tfoot>
    @endif
</table>