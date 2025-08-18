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
            <th colspan="8" style="text-align: center; font-weight: bold;  border-collapse: collapse;">
                Líderes de estadísticas
            </th>
        </tr>
        <tr></tr>
    @endif
    </thead>
    <tbody>


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
                    @if($showImages)
                        <div style="display: flex; justify-content: center; align-items: center;  width: 100%;">
                            <img src="{{$stat->user_image}}" alt="{{$stat->player_name}} image" style="border-radius: 50%; flex: 0 0 30px; margin-right: 8px;" width="30px">
                            <span style="display: inline-block; white-space: nowrap;overflow: hidden; text-overflow: ellipsis; max-width: 120px; flex: 0 0 100%;">  {{$stat->player_name}}</span>
                        </div>
                    @else
                        <span style="display: inline-block; white-space: nowrap;overflow: hidden; text-overflow: ellipsis; max-width: 120px; flex: 0 0 100%;">  {{$stat->player_name}}</span>
                    @endif
                </td>
                <td colspan="3" style="text-align: center; border: 1px solid black; border-collapse: collapse;">
                    @if($showImages)
                        <div style="display: flex; justify-content: center; align-items: center; width: 100%;">
                            <img src="{{$stat->team_image}}" alt="{{$stat->team_name}} image"  style="border-radius: 50%; flex: 0 0 30px; margin-right: 8px;" width="30px">
                            <span style="display: inline-block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 110px; flex: 0 0 100%;">{{$stat->team_name}}</span>
                        </div>
                    @else
                        <span style="display: inline-block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 110px; flex: 0 0 100%;">{{$stat->team_name}}</span>
                    @endif
                </td>
            </tr>
        @endforeach
        <tr></tr>
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
                    @if($showImages)
                        <div style="display: flex; justify-content: center; align-items: center;  width: 100%;">
                            <img src="{{$stat->user_image}}" alt="{{$stat->player_name}} image" style="border-radius: 50%; flex: 0 0 30px; margin-right: 8px;" width="30px">
                            <span style="display: inline-block; white-space: nowrap;overflow: hidden; text-overflow: ellipsis; max-width: 120px; flex: 0 0 100%;">  {{$stat->player_name}}</span>
                        </div>
                    @else
                        <span style="display: inline-block; white-space: nowrap;overflow: hidden; text-overflow: ellipsis; max-width: 120px; flex: 0 0 100%;">  {{$stat->player_name}}</span>
                    @endif
                </td>
                <td colspan="3" style="text-align: center; border: 1px solid black; border-collapse: collapse;">
                    @if($showImages)
                        <div style="display: flex; justify-content: center; align-items: center; width: 100%;">
                            <img src="{{$stat->team_image}}" alt="{{$stat->team_name}} image"  style="border-radius: 50%; flex: 0 0 30px; margin-right: 8px;" width="30px">
                            <span style="display: inline-block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 110px; flex: 0 0 100%;">{{$stat->team_name}}</span>
                        </div>
                    @else
                        <span style="display: inline-block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 110px; flex: 0 0 100%;">{{$stat->team_name}}</span>
                    @endif
                </td>
            </tr>
        @endforeach
        <tr>
            <th colspan="2"  style="text-align: center; border: 1px solid black; border-collapse: collapse; font-weight: bold;"> Tarjetas Amarillas</th>
            <th colspan="3" style="text-align: center; border: 1px solid black; border-collapse: collapse; font-weight: bold;">Jugador</th>
            <th colspan="3" style="text-align: center; border: 1px solid black; border-collapse: collapse; font-weight: bold;">Equipo</th>
        </tr>
        @foreach($yellowCards as $stat)
            <tr>
                <td colspan="2" style="text-align: center; border: 1px solid black; border-collapse: collapse;">
                    {{$stat->total}}
                </td>
                <td colspan="3" style="text-align: center; border: 1px solid black; border-collapse: collapse;">
                    @if($showImages)
                        <div style="display: flex; justify-content: center; align-items: center;  width: 100%;">
                            <img src="{{$stat->user_image}}" alt="{{$stat->player_name}} image" style="border-radius: 50%; flex: 0 0 30px; margin-right: 8px;" width="30px">
                            <span style="display: inline-block; white-space: nowrap;overflow: hidden; text-overflow: ellipsis; max-width: 120px; flex: 0 0 100%;">  {{$stat->player_name}}</span>
                        </div>
                    @else
                        <span style="display: inline-block; white-space: nowrap;overflow: hidden; text-overflow: ellipsis; max-width: 120px; flex: 0 0 100%;">  {{$stat->player_name}}</span>
                    @endif
                </td>
                <td colspan="3" style="text-align: center; border: 1px solid black; border-collapse: collapse;">
                    @if($showImages)
                        <div style="display: flex; justify-content: center; align-items: center; width: 100%;">
                            <img src="{{$stat->team_image}}" alt="{{$stat->team_name}} image"  style="border-radius: 50%; flex: 0 0 30px; margin-right: 8px;" width="30px">
                            <span style="display: inline-block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 110px; flex: 0 0 100%;">{{$stat->team_name}}</span>
                        </div>
                    @else
                        <span style="display: inline-block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 110px; flex: 0 0 100%;">{{$stat->team_name}}</span>
                    @endif
                </td>
            </tr>
        @endforeach
        <tr></tr>
        <tr>
            <th colspan="2"  style="text-align: center; border: 1px solid black; border-collapse: collapse; font-weight: bold;">Tarjetas Rojas</th>
            <th colspan="3" style="text-align: center; border: 1px solid black; border-collapse: collapse; font-weight: bold;">Jugador</th>
            <th colspan="3" style="text-align: center; border: 1px solid black; border-collapse: collapse; font-weight: bold;">Equipo</th>
        </tr>
        @foreach($redCards as $stat)
            <tr>
                <td colspan="2" style="text-align: center; border: 1px solid black; border-collapse: collapse;">
                    {{$stat->total}}
                </td>
                <td colspan="3" style="text-align: center; border: 1px solid black; border-collapse: collapse;">
                    @if($showImages)
                        <div style="display: flex; justify-content: center; align-items: center;  width: 100%;">
                            <img src="{{$stat->user_image}}" alt="{{$stat->player_name}} image" style="border-radius: 50%; flex: 0 0 30px; margin-right: 8px;" width="30px">
                            <span style="display: inline-block; white-space: nowrap;overflow: hidden; text-overflow: ellipsis; max-width: 120px; flex: 0 0 100%;">  {{$stat->player_name}}</span>
                        </div>
                    @else
                        <span style="display: inline-block; white-space: nowrap;overflow: hidden; text-overflow: ellipsis; max-width: 120px; flex: 0 0 100%;">  {{$stat->player_name}}</span>
                    @endif
                </td>
                <td colspan="3" style="text-align: center; border: 1px solid black; border-collapse: collapse;">
                    @if($showImages)
                        <div style="display: flex; justify-content: center; align-items: center; width: 100%;">
                            <img src="{{$stat->team_image}}" alt="{{$stat->team_name}} image"  style="border-radius: 50%; flex: 0 0 30px; margin-right: 8px;" width="30px">
                            <span style="display: inline-block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 110px; flex: 0 0 100%;">{{$stat->team_name}}</span>
                        </div>
                    @else
                        <span style="display: inline-block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 110px; flex: 0 0 100%;">{{$stat->team_name}}</span>
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>