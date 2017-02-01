<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use JMS\Serializer\SerializationContext;
use AppBundle\Entity\Ministry\Category;
use AppBundle\Form\Ministry\CategoryType;

/**
 * AppBundle\Controller\MinistryCategoryController
 *
 * @/Security("has_role('ROLE_ADMIN')")
 */
class MinistryCategoryController extends Controller
{
    /**
     * Lists all Address entities.
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $repo = $em->getRepository('AppBundle:Ministry\Category'); /* @var $repo \AppBundle\Repository\Ministry\CategoryRepository */
        $categories = $repo->findAllForListing();

        $personRepo = $em->getRepository('AppBundle:Person');
        $persons = $personRepo->findAllForMinistryListing();

        $groupRepo = $em->getRepository('AppBundle:Ministry\Group');
        $groups = $groupRepo->findAll();

        // serializations
        $serializer = $this->get('jms_serializer');
        $categoriesJson = $serializer->serialize($categories, 'json', SerializationContext::create()->setGroups(array('MinistryCategoryListing')));
        $personsJson = $serializer->serialize($persons, 'json', SerializationContext::create()->setGroups(array('MinistryCategoryListing')));
        $groupsJson = $serializer->serialize($groups, 'json', SerializationContext::create()->setGroups(array('MinistryCategoryListing')));

        return $this->render('AppBundle:MinistryCategory:index.html.twig', array(
            'categories_json' => $categoriesJson,
            'persons_json' => $personsJson,
            'groups_json' => $groupsJson,
        ));
    }

    /**
     * Edits an existing Address entity.
     *
     */
    public function updateAction(Request $request)
    {
        if ('json' != $request->getContentType()) {
            throw new \InvalidArgumentException('Wrong content type provided. JSON is expected.');
        }

        try {
            $em = $this->getDoctrine()->getManager();
            $clientMinistryCategories = json_decode($request->getContent(), true);

            $categories = $em->getRepository('AppBundle:Ministry\Category')->findAll();
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

                $form = $this->createForm(new CategoryType(), $category, array(
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
            $serializer = $this->get('jms_serializer');
            $categoriesJson = $serializer->serialize($categories, 'json', SerializationContext::create()->setGroups(array('MinistryCategoryListing')));

            return new Response($categoriesJson, 200, array('Content-Type' => 'application/json'));
        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage(), 401);
        }
    }
}
