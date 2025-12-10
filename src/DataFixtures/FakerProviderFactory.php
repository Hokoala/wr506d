<?php

namespace App\DataFixtures;

use Xylis\FakerCinema\Provider\Person;
use Xylis\FakerCinema\Provider\Movie;

class FakerProviderFactory
{
    public function createCinemaProvider($faker): Person
    {
        return new Person($faker);
    }

    public function createMovieProvider($faker): Movie
    {
        return new Movie($faker);
    }
}

