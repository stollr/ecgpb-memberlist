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

        return $this->render('/MinistryCategory/index.html.twig', array(
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

        try {
            $em = $this->getDoctrine()->getManager();
            $clientMinistryCategories = json_decode($request->getContent(), true);

            $categories = $em->getRepository(Category::class)->findAll();
            /* @var $categories Category[] */

            // get all ids of already existing categories
            $existingCategoryIds = array();
            foreach ($clientMinistryCategories as $clientMinistryCategory) {
                if (!empty($clientMinistryCategory['id'])) {
                    $existingCategoryIds[] = $clientMinistryCategory['id'];
                }
            }

            // delete obsolete entities
            foreach ($categories as $category) {
                if (!in_array($category->getId(), $existingCategoryIds)) {
                    $em->remove($category);
                }
            }

            // create and update entities
            foreach ($clientMinistryCategories as $clientMinistryCategory) {
                if (empty($clientMinistryCategory['id'])) {
                    $category = new Category();
                    $categories[] = $category;
                } else {
                    $filtered = array_filter($categories, function($category) use ($clientMinistryCategory) {
                        return $category->getId() == $clientMinistryCategory['id'];
                    });
                    $category = reset($filtered);
                    $existingCategoryIds[] = $clientMinistryCategory['id'];
                }

                // cache old assignments
                $oldMinistries = $category->getMinistries()->toArray();
                $oldResponsibleAssignments = array();
                foreach ($category->getMinistries() as $ministry) {
                    $oldResponsibleAssignments[$ministry->getId()] = $ministry->getResponsibleAssignments()->toArray();
                }

                $form = $this->createForm(CategoryType::class, $category, array(
                    'csrf_protection' => false,
                ));
                $form->submit($clientMinistryCategory);

                // delete ministries
                foreach ($oldMinistries as $oldMinistry) {
                    if (!$category->getMinistries()->contains($oldMinistry)) {
                        $em->remove($oldMinistry);
                    }
                }

                // delete assignments, that have been removed by user
                foreach ($category->getMinistries() as $ministry) {
                    if (!$ministry->getId()) {
                        // for new ministries, there aren't any assignments
                        continue;
                    }
                    foreach ($oldResponsibleAssignments[$ministry->getId()] as $oldResponsibleAssignment) {
                        if (!$ministry->getResponsibleAssignments()->contains($oldResponsibleAssignment)) {
                            $em->remove($oldResponsibleAssignment);
                        }
                    }
                }

                if (!$form->isValid()) {
                    return new Response('Invalid entity', 400, array('Content-Type' => 'application/json'));
                } else if (!$category->getId()) {
                    $em->persist($category);
                }
            }

            $em->flush();

            // response
            $context = ['groups' => ['MinistryCategoryListing']];
            $categoriesJson = $serializer->serialize($categories, 'json', $context);

            return new Response($categoriesJson, 200, array('Content-Type' => 'application/json'));
        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage(), 401);
        }
    }
}
