<?php


namespace App\EofficeApp\Performance\Enums;


final class PerformancePeriods
{
    const QUARTERS = [
        1 => 'first_quarter',
        2 => 'second_quarter',
        3 => 'third_quarter',
        4 => 'fourth_quarter',
    ];

    const HALF_YEARS = [
        1 => 'first_half_of_the_year',
        2 => 'the_next_half_of_the_year'
    ];

}
