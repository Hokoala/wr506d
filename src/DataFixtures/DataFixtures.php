<?php

namespace App\DataFixtures;


use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker;
use DateTimeImmutable;
use App\Entity\Actor;
use App\Entity\Category;
use App\Entity\Movie;

class DataFixtures extends Fixture
{
    public function load(ObjectManager $manager) : void
    {

        $fakerActors = \Faker\Factory::create();
        $fakerActors->addProvider(new \Xylis\FakerCinema\Provider\Person($fakerActors));

        $actors = $fakerActors->actors($gender = null, $count = 190, $duplicates = false);
        foreach ($actors as $item) {

            $actor = new Actor();
            $names = explode(' ', $item);
            $actor->setFirstName($names[0]);
            $actor->setLastName($names[1]);

            $actor->setBio($fakerActors->realText($maxNbChars = 100, $indexSize = 2));
            $actor->setDob($fakerActors->dateTimeThisCentury());
            $actor->setPhoto($fakerActors->imageUrl($maxNbChars = 100, $indexSize = 2));


            if ($fakerActors->boolean(45)) {
                $dob = $actor->getDob();
                $actor->setDod($fakerActors->dateTimeBetween($dob,'now'));
            }
            $manager->persist($actor);

        }


        $fakerDirector = \Faker\Factory::create();
        $fakerDirector->addProvider(new \Xylis\FakerCinema\Provider\Person($fakerDirector));

        $directors = $fakerDirector->directors($gender = 'female', $count = 30, $duplicates = false);
        $directorsArray = [];
        foreach ($directors as $item) {

            $director = new \App\Entity\Director();
            $names = explode(' ', $item);
            $director->setFirstName($names[0]);
            $director->setLastName($names[1]);
            $director->setDob($fakerDirector->dateTimeThisCentury());

            if ($fakerDirector->boolean(45)) {
                $dob = $director->getDob();
                $director->setDod($fakerDirector->dateTimeBetween($dob,'now'));
            }

            $directorsArray[] = $director;

            $manager->persist($director);


        }




        $fakerMovie = \Faker\Factory::create();
        $fakerMovie->addProvider(new \Xylis\FakerCinema\Provider\Movie($fakerMovie));


        $categoryArray = [];

        $movies = $fakerMovie->movies(199);
        foreach ($movies as $item) {
            $movie = new Movie();

            echo $item. ' ---- '.$fakerMovie->movieGenre."\n";

            $categoryName = $fakerMovie->movieGenre;
            if (!in_array($categoryName, $categoryArray)) {
                $category = new Category();
                $category->setName($categoryName);
                $manager->persist($category);
                $categoryArray[] = $categoryName;
            };




            $durationMin = 60 * 60;
            $durationMax = 270 * 60;


            $movie->setName($item);

            $movie->setDescription($fakerMovie->realText($maxNbChars = 100, $indexSize = 2));
            $movie->setDuration($fakerMovie->numberBetween($durationMin , $durationMax ));
            $movie->setReleaseDate($fakerMovie->dateTimeThisCentury());
            $movie->setImage($fakerMovie->imageUrl($width = 400, $height = 600));
            $movie->setUrl($fakerMovie->url($maxNbChars = 255 ));
            $movie->setBudget($fakerMovie->randomFloat($nbMaxDecimals = 2, $min = 1000, $max = 100000000));
            $movie->setNbEntries($fakerMovie->numberBetween($min = 0, $max = 10000));
            $movie->setDirector($directorsArray[array_rand($directorsArray)]);
            $manager->persist($movie);



        }





        $manager->flush();
    }


}
