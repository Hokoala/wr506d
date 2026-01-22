<?php

namespace App\Controller;

use App\Service\SlugifyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProductController extends AbstractController
{
    #[Route('/products', name: 'app_product_list')]
    public function index(): Response
    {
        return $this->render('product/index.html.twig', [
            'listProducts' => 'Liste des produits',
        ]);
    }


    #[Route('/product/{id}', name: 'app_product_view')]
    public function view($productId, SlugifyService $slugifyService): Response
    {
        return $this->render('product/view.html.twig', [
            'listProducts' => 'Liste des produits',
            'productId' => $productId,
            'slug' => $slugifyService->slugify("T-Shirt d'Été !"),
        ]);
    }
}
