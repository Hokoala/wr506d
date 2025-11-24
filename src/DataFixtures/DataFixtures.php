<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Actor;
use App\Entity\Category;
use App\Entity\Director;
use App\Entity\Movie;
use App\Entity\MediaObject;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Xylis\FakerCinema\Provider\Person as CinemaProvider;
use \Xylis\FakerCinema\Provider\Movie as MovieProvider;

class DataFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // ----- ACTEURS -----
        $fakerActors = \Faker\Factory::create();
        $fakerActors->addProvider(new CinemaProvider($fakerActors));

        $actorsData = $fakerActors->actors(null, 190, false);

        // Vérification que actorsData n'est pas null
        if ($actorsData !== null && is_array($actorsData)) {
            foreach ($actorsData as $item) {
                $actor = new Actor();

                // Conversion en string et explode
                $fullName = (string) $item;
                $names = explode(' ', $fullName);
                $actor->setFirstName($names[0]);
                $actor->setLastName($names[1] ?? '');

                $actor->setBio($fakerActors->realText(100, 2));
                $dob = $fakerActors->dateTimeThisCentury();
                $actor->setDob($dob);

                // Créer un MediaObject pour la photo
                $mediaObject = new MediaObject();
                $mediaObject->filePath = 'actors/actor_' . $fakerActors->numberBetween(1, 1000) . '.jpg';
                $mediaObject->contentUrl = '/media/actors/actor_' . $fakerActors->numberBetween(1, 1000) . '.jpg';
                $manager->persist($mediaObject);

                $actor->setPhoto($mediaObject);

                // Date de décès après la date de naissance
                if ($fakerActors->boolean(45)) {
                    $dod = $fakerActors->dateTimeBetween($dob, 'now');
                    $actor->setDod($dod);
                }

                $manager->persist($actor);
            }
        }

        // ----- RÉALISATEURS -----
        $fakerDirector = \Faker\Factory::create();
        $fakerDirector->addProvider(new CinemaProvider($fakerDirector));

        $directorsData = $fakerDirector->directors('female', 30, false);
        $directorsArray = [];

        // Vérification que directorsData n'est pas null
        if ($directorsData !== null && is_array($directorsData)) {
            foreach ($directorsData as $item) {
                $director = new Director();

                // Conversion en string et explode
                $fullName = (string) $item;
                $names = explode(' ', $fullName);
                $director->setFirstName($names[0]);
                $director->setLastName($names[1] ?? '');

                $dob = $fakerDirector->dateTimeThisCentury();
                $director->setDob($dob);

                // Date de décès après la date de naissance
                if ($fakerDirector->boolean(45)) {
                    $dod = $fakerDirector->dateTimeBetween($dob, 'now');
                    $director->setDod($dod);
                }

                $directorsArray[] = $director;
                $manager->persist($director);
            }
        }

        // ----- FILMS -----
        $fakerMovie = \Faker\Factory::create();
        $fakerMovie->addProvider(new MovieProvider($fakerMovie));

        $categories = [];
        $moviesData = $fakerMovie->movies(199);

        // Vérification que moviesData n'est pas null
        if ($moviesData !== null && is_array($moviesData)) {
            foreach ($moviesData as $item) {
                $movie = new Movie();

                $categoryName = $fakerMovie->movieGenre();
                // Création de la catégorie si elle n'existe pas déjà
                if (!isset($categories[$categoryName])) {
                    $category = new Category();
                    $category->setName($categoryName);
                    $manager->persist($category);
                    $categories[$categoryName] = $category;
                }

                $movieName = (string) $item;
                $movie->setName($movieName);
                $movie->setDescription($fakerMovie->realText(200, 2));
                $movie->setDuration($fakerMovie->numberBetween(60 * 60, 270 * 60)); // entre 1h et 4h30
                $movie->setReleaseDate($fakerMovie->dateTimeThisCentury());

                // Créer un MediaObject pour l'image du film
                $movieMedia = new MediaObject();
                $movieMedia->filePath = 'movies/movie_' . $fakerMovie->numberBetween(1, 1000) . '.jpg';
                $movieMedia->contentUrl = '/media/movies/movie_' . $fakerMovie->numberBetween(1, 1000) . '.jpg';
                $manager->persist($movieMedia);

                $movie->setImage($movieMedia);

                $movie->setUrl($fakerMovie->url());
                $movie->setBudget($fakerMovie->randomFloat(2, 1000, 100000000));
                $movie->setNbEntries($fakerMovie->numberBetween(0, 10000));

                // Attribution d'un réalisateur aléatoire (si des réalisateurs existent)
                if (!empty($directorsArray)) {
                    $movie->setDirector($directorsArray[array_rand($directorsArray)]);
                }

                // Attribution de la catégorie correspondante
                $movie->setCategory($categories[$categoryName]);

                $manager->persist($movie);
            }
        }

        $manager->flush();
    }
}
