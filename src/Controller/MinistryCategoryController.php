<?php

namespace App\Controller;

use App\Entity\Ministry\Category;
use App\Form\Ministry\CategoryType;
use App\Repository\Ministry\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * App\Controller\MinistryCategoryController
 */
#[Route(path: '/ministry_category')]
class MinistryCategoryController extends AbstractController
{
    /**
     * Lists all Address entities.
     */
    #[Route('/', name: 'app.ministry_category.index', methods: ['GET'])]
    public function index(CategoryRepository $repo): Response
    {
        $ministryCategories = $repo->findBy([], ['position' => 'asc']);

        return $this->render('/ministry_category/index.html.twig', array(
            'ministryCategories' => $ministryCategories,
        ));
    }

    #[Route('/create', name: 'app.ministry_category.create')]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        $category = new Category();

        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($category);
            $em->flush();

            $this->addFlash('success', 'The entry has been created.');

            return $this->redirectToRoute('app.ministry_category.edit', ['id' => $category->getId()]);
        }

        return $this->render('ministry_category/form.html.twig', [
            'category' => $category,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{id}/edit', name: 'app.ministry_category.edit')]
    public function edit(Category $category, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'All changes have been saved.');

            return $this->redirectToRoute('app.ministry_category.edit', ['id' => $category->getId()]);
        }

        return $this->render('ministry_category/form.html.twig', [
            'category' => $category,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{id}/delete', name: 'app.ministry_category.delete', methods: ['DELETE'])]
    public function delete(Category $category, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('delete_ministry_category', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid token.');
        }

        $em->remove($category);
        $em->flush();

        $this->addFlash('success', 'The entry has been deleted.');

        return $this->redirectToRoute('app.ministry_category.index');
    }

    /**
     * Edits an existing Address entity.
     */
    #[Route('/', name: 'app.ministry_category.update', methods: ['POST'], requirements: ['_format' => 'json'])]
    public function update(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        CategoryRepository $repo,
    ): Response {
        if ('json' != $request->getContentType()) {
            throw new \InvalidArgumentException('Wrong content type provided. JSON is expected.');
        }

        $clientMinistryCategories = $request->toArray();

        $ministryNames = [];
        foreach ($clientMinistryCategories as $c) {
            foreach ($c['ministries'] as $m) {
                if (isset($ministryNames[$m['name']]) && $m['name'] != 'Materialbüro') {
                    throw new \InvalidArgumentException('ministry ' . $m['name'] . ' doppelt.');
                }
                $ministryNames[$m['name']] = $m['name'];
            }
        }

        $categories = $repo->findAll();

        $form = $this->createCategoriesForm($categories);
        $form->submit($clientMinistryCategories);

        if (!$form->isValid()) {
            return new JsonResponse('Invalid entity', JsonResponse::HTTP_BAD_REQUEST);
        }

        $ministryNames = [];
        foreach ($categories as $c) {
            foreach ($c->getMinistries() as $m) {
                if (isset($ministryNames[$m->getName()]) && $m->getName() != 'Materialbüro') {
                    throw new \InvalidArgumentException('ministry ' . $m->getName() . ' doppelt, nach form mapping.');
                }
                $ministryNames[$m->getName()] = $m->getName();
            }
        }

        foreach ($form->getData() as $category) {
            $em->persist($category);
        }
        $em->flush();

        // response
        $context = ['groups' => ['MinistryCategoryListing']];
        $data = $serializer->normalize($categories, 'json', $context);

        return new JsonResponse($data);
    }


    private function createCategoriesForm(array $categories)
    {
        $form = $this->createForm(CollectionType::class, $categories, [
            'entry_type' => CategoryType::class,
            'label' => false,
            'prototype' => true,
            'allow_add' => true,
            'by_reference' => false,
            'allow_delete' => true,
            'entry_options' => [
                'label' => false,
            ],
            'csrf_protection' => false,
        ]);
        return $form;
    }
}
