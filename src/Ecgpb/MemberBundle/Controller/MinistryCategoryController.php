<?php

namespace Ecgpb\MemberBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use JMS\Serializer\SerializationContext;
use Ecgpb\MemberBundle\Entity\Ministry\Category;
use Ecgpb\MemberBundle\Form\Ministry\CategoryType;

/**
 * Ecgpb\MemberBundle\Controller\MinistryCategoryController
 *
 * @Security("has_role('ROLE_ADMIN')")
 */
class MinistryCategoryController extends Controller
{
    /**
     * Lists all Address entities.
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $repo = $em->getRepository('EcgpbMemberBundle:Ministry\Category'); /* @var $repo \Ecgpb\MemberBundle\Repository\Ministry\CategoryRepository */
        $categories = $repo->findAllForListing();

        $personRepo = $em->getRepository('EcgpbMemberBundle:Person');
        $persons = $personRepo->findAllForMinistryListing();

        $groupRepo = $em->getRepository('EcgpbMemberBundle:Ministry\Group');
        $groups = $groupRepo->findAll();

        // serializations
        $serializer = $this->get('jms_serializer');
        $categoriesJson = $serializer->serialize($categories, 'json', SerializationContext::create()->setGroups(array('MinistryCategoryListing')));
        $personsJson = $serializer->serialize($persons, 'json', SerializationContext::create()->setGroups(array('MinistryCategoryListing')));
        $groupsJson = $serializer->serialize($groups, 'json', SerializationContext::create()->setGroups(array('MinistryCategoryListing')));

        return $this->render('EcgpbMemberBundle:MinistryCategory:index.html.twig', array(
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

        $em = $this->getDoctrine()->getManager();
        $clientMinistryCategories = json_decode($request->getContent(), true);

        $categories = $em->getRepository('EcgpbMemberBundle:Ministry\Category')->findAll();
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
            $form = $this->createForm(new CategoryType(), $category, array(
                'csrf_protection' => false,
            ));
            $form->submit($clientMinistryCategory);

            if ($form->isValid()) {
                $em->persist($category);
            } else {
                return new Response('Invalid entity', 400, array('Content-Type' => 'application/json'));
            }
        }

        $em->flush();

        // response
        $serializer = $this->get('jms_serializer');
        $categoriesJson = $serializer->serialize($categories, 'json', SerializationContext::create()->setGroups(array('MinistryCategoryListing')));
        
        return new Response($categoriesJson, 200, array('Content-Type' => 'application/json'));
    }
}
