<?php

namespace App\Controller;

use App\Entity\Ministry\Category;
use App\Entity\Person;
use App\Form\Ministry\CategoryType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * App\Controller\MinistryCategoryController
 *
 * @Route("/ministry_category")
 * @/Security("has_role('ROLE_ADMIN')")
 */
class MinistryCategoryController extends Controller
{
    /**
     * Lists all Address entities.
     *
     * @Route(name="ecgpb.member.ministry_category.index", path="/", methods={"GET"})
     */
    public function indexAction(SerializerInterface $serializer)
    {
        $em = $this->getDoctrine()->getManager();

        $repo = $em->getRepository(Category::class); /* @var $repo \App\Repository\Ministry\CategoryRepository */
        $categories = $repo->findAllForListing();

        $personRepo = $em->getRepository(Person::class);
        $persons = $personRepo->findAllForMinistryListing();

        // serializations
        $context = ['groups' => ['MinistryCategoryListing']];
        $categoriesJson = $serializer->serialize($categories, 'json', $context);
        $personsJson = $serializer->serialize($persons, 'json', $context);

        return $this->render('/ministry_category/index.html.twig', array(
            'categories_json' => $categoriesJson,
            'persons_json' => $personsJson,
        ));
    }

    /**
     * Edits an existing Address entity.
     *
     * @Route(name="ecgpb.member.ministry_category.update", path="/", methods={"POST", "PUT"}, requirements={"_format" = "json"})
     */
    public function update(Request $request, SerializerInterface $serializer)
    {
        if ('json' != $request->getContentType()) {
            throw new \InvalidArgumentException('Wrong content type provided. JSON is expected.');
        }

        $em = $this->getDoctrine()->getManager();
        $clientMinistryCategories = json_decode($request->getContent(), true);

        $categories = $em->getRepository(Category::class)->findAll();
        /* @var $categories Category[] */

        $form = $this->createCategoriesForm($categories);
        $form->submit($clientMinistryCategories);

        if (!$form->isValid()) {
            return new JsonResponse('Invalid entity', JsonResponse::HTTP_BAD_REQUEST);
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
        $form = $this->createForm(\Symfony\Component\Form\Extension\Core\Type\CollectionType::class, $categories, [
            'entry_type' => CategoryType::class,
            'label' => false,
            'prototype' => true,
            'allow_add' => true,
            'by_reference' => false,
            'widget_add_btn' => ['label' => 'Add Ministry'],
            'allow_delete' => true,
            'horizontal_input_wrapper_class' => 'clearfix',
            'entry_options' => [
                'label' => false,
            ],
            'csrf_protection' => false,
        ]);
        return $form;
    }
}
