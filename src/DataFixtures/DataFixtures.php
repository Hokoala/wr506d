<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Actor;
use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Director;
use App\Entity\Movie;
use App\Entity\MediaObject;
use App\Entity\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Xylis\FakerCinema\Provider\Person as CinemaProvider;
use Xylis\FakerCinema\Provider\Movie as MovieProvider;

class DataFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $this->loadActors($manager);
        $directorsArray = $this->loadDirectors($manager);
        $moviesArray = $this->loadMoviesWithCategories($manager, $directorsArray);

        // NOUVEAU : Créer des utilisateurs et des commentaires
        $usersArray = $this->loadUsers($manager);
        $this->loadComments($manager, $moviesArray, $usersArray);

        $manager->flush();
    }

    private function loadActors(ObjectManager $manager): void
    {
        $fakerActors = \Faker\Factory::create();
        $fakerActors->addProvider(new CinemaProvider($fakerActors));

        $actorsData = $fakerActors->actors(null, 190, false);

        if ($actorsData !== null && is_array($actorsData)) {
            foreach ($actorsData as $item) {
                $actor = new Actor();

                $fullName = (string) $item;
                $names = explode(' ', $fullName);
                $actor->setFirstName($names[0]);
                $actor->setLastName($names[1] ?? '');

                $actor->setBio($fakerActors->realText(100, 2));
                $dob = $fakerActors->dateTimeThisCentury();
                $actor->setDob($dob);

                $mediaObject = new MediaObject();
                $mediaObject->filePath = 'actors/actor_' . $fakerActors->numberBetween(1, 1000) . '.jpg';
                $mediaObject->contentUrl = '/media/actors/actor_' . $fakerActors->numberBetween(1, 1000) . '.jpg';
                $manager->persist($mediaObject);

                $actor->setPhoto($mediaObject);

                if ($fakerActors->boolean(45)) {
                    $dod = $fakerActors->dateTimeBetween($dob, 'now');
                    $actor->setDod($dod);
                }

                $manager->persist($actor);
            }
        }
    }

    private function loadDirectors(ObjectManager $manager): array
    {
        $fakerDirector = \Faker\Factory::create();
        $fakerDirector->addProvider(new CinemaProvider($fakerDirector));

        $directorsData = $fakerDirector->directors('female', 30, false);
        $directorsArray = [];

        if ($directorsData !== null && is_array($directorsData)) {
            foreach ($directorsData as $item) {
                $director = new Director();

                $fullName = (string) $item;
                $names = explode(' ', $fullName);
                $director->setFirstName($names[0]);
                $director->setLastName($names[1] ?? '');

                $dob = $fakerDirector->dateTimeThisCentury();
                $director->setDob($dob);

                if ($fakerDirector->boolean(45)) {
                    $dod = $fakerDirector->dateTimeBetween($dob, 'now');
                    $director->setDod($dod);
                }

                $directorsArray[] = $director;
                $manager->persist($director);
            }
        }

        return $directorsArray;
    }

    private function loadMoviesWithCategories(ObjectManager $manager, array $directorsArray): array
    {
        $fakerMovie = \Faker\Factory::create();
        $fakerMovie->addProvider(new MovieProvider($fakerMovie));

        $categories = [];
        $moviesArray = []; // NOUVEAU : Pour retourner les films
        $moviesData = $fakerMovie->movies(199);

        if ($moviesData !== null && is_array($moviesData)) {
            foreach ($moviesData as $item) {
                $movie = new Movie();

                $categoryName = $fakerMovie->movieGenre();
                if (!isset($categories[$categoryName])) {
                    $category = new Category();
                    $category->setName($categoryName);
                    $manager->persist($category);
                    $categories[$categoryName] = $category;
                }

                $movieName = (string) $item;
                $movie->setName($movieName);
                $movie->setDescription($fakerMovie->realText(200, 2));
                $movie->setDuration($fakerMovie->numberBetween(60 * 60, 270 * 60));
                $movie->setReleaseDate($fakerMovie->dateTimeThisCentury());

                $movieMedia = new MediaObject();
                $movieMedia->filePath = 'movies/movie_' . $fakerMovie->numberBetween(1, 1000) . '.jpg';
                $movieMedia->contentUrl = '/media/movies/movie_' . $fakerMovie->numberBetween(1, 1000) . '.jpg';
                $manager->persist($movieMedia);

                $movie->setImage($movieMedia);

                $movie->setUrl($fakerMovie->url());
                $movie->setBudget($fakerMovie->randomFloat(2, 1000, 100000000));
                $movie->setNbEntries($fakerMovie->numberBetween(0, 10000));

                if (!empty($directorsArray)) {
                    $movie->setDirector($directorsArray[array_rand($directorsArray)]);
                }

                $movie->addCategory($categories[$categoryName]);

                $manager->persist($movie);
                $moviesArray[] = $movie; // NOUVEAU : Sauvegarder le film
            }
        }

        return $moviesArray; // NOUVEAU : Retourner les films
    }

    // NOUVEAU : Créer des utilisateurs
    private function loadUsers(ObjectManager $manager): array
    {
        $faker = \Faker\Factory::create('fr_FR');
        $usersArray = [];

        // Admin
        $admin = new User();
        $admin->setEmail('admin@cinema.com');
        $admin->setFirstname('Admin');
        $admin->setLastname('Cinéma');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $admin->setRoles(['ROLE_ADMIN']);
        $manager->persist($admin);
        $usersArray[] = $admin;

        // Utilisateurs normaux
        for ($i = 1; $i <= 20; $i++) {
            $user = new User();
            $user->setEmail($faker->email());
            $user->setFirstname($faker->firstName());
            $user->setLastname($faker->lastName());
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
            $user->setRoles(['ROLE_USER']);
            $manager->persist($user);
            $usersArray[] = $user;
        }

        return $usersArray;
    }

    // NOUVEAU : Créer des commentaires
    private function loadComments(ObjectManager $manager, array $moviesArray, array $usersArray): void
    {
        $faker = \Faker\Factory::create('fr_FR');

        $commentTemplates = [
            "Excellent film ! Je recommande vivement.",
            "Un chef-d'œuvre du cinéma moderne.",
            "Décevant, je m'attendais à mieux.",
            "Très bon divertissement, parfait pour une soirée.",
            "Le scénario est captivant du début à la fin.",
            "Les effets spéciaux sont impressionnants.",
            "Un peu long mais ça vaut le coup.",
            "Acteurs exceptionnels, mise en scène sublime.",
            "Pas convaincu par l'histoire.",
            "Un classique à voir absolument !",
            "Bande son magnifique qui accompagne parfaitement le film.",
            "Émotionnellement puissant, j'ai pleuré.",
            "Trop prévisible à mon goût.",
            "Une expérience cinématographique unique.",
            "Les dialogues sont brillants.",
            "Superbe réalisation, bravo au réalisateur.",
            "J'ai adoré du début à la fin.",
            "Un film qui fait réfléchir.",
            "Déçu par la fin, dommage.",
            "À voir en famille sans hésiter."
        ];

        // Ajouter 5-15 commentaires par film
        foreach ($moviesArray as $movie) {
            $numComments = $faker->numberBetween(5, 15);

            for ($i = 0; $i < $numComments; $i++) {
                $comment = new Comment();
                $comment->setContent($faker->randomElement($commentTemplates) . ' ' . $faker->sentence());
                $comment->setRating($faker->numberBetween(1, 5));
                $comment->setUser($faker->randomElement($usersArray));
                $comment->setMovie($movie);

                // Date aléatoire dans les 6 derniers mois
                $createdAt = \DateTimeImmutable::createFromMutable(
                    $faker->dateTimeBetween('-6 months', 'now')
                );
                $comment->setCreatedAt($createdAt);

                $manager->persist($comment);
            }
        }
    }
}
