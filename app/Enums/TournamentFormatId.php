<?php

namespace App\Enums;

enum TournamentFormatId: int
{
    case League = 1;
    case LeagueAndElimination = 2;
    case GroupAndElimination = 3;
    case Elimination = 4;
    case Swiss = 5;
}
