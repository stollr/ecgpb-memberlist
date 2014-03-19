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

        $categories = $em->getRepository('EcgpbMemberBundle:Ministry\Category')->findBy(array(
            'id' => array_map(function($element) {
                return isset($element['id']) ? $element['id'] : 0;

            },
            $clientMinistryCategories
        )));
        /* @var $categories Category[] */

        foreach ($clientMinistryCategories as $clientMinistryCategory) {
            if (empty($clientMinistryCategory['id'])) {
                $category = new Category();
                $categories[] = $category;
            } else {
                $filtered = array_filter($categories, function($category) use ($clientMinistryCategory) {
                    return $category->getId() == $clientMinistryCategory['id'];
                });
                $category = reset($filtered);
            }
            $form = $this->createForm(new \Ecgpb\MemberBundle\Form\Ministry\CategoryType(), $category, array(
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
        return new Response(
            $serializer->serialize($categories, 'json', SerializationContext::create()->setGroups(array('MinistryCategoryListing'))),
            200,
            array('Content-Type' => 'application/json')
        );
    }

    /**
    * Creates a form to create a Address entity.
    *
    * @param Address $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createMinistryCategoryForm(Address $entity)
    {
        $url = $entity->getId() > 0
            ? $this->generateUrl('ecgpb.member.address.update', array('id' => $entity->getId()))
            : $this->generateUrl('ecgpb.member.address.create')
        ;
        $form = $this->createForm(new AddressType(), $entity, array(
            'action' => $url,
            'method' => 'POST',
            'attr' => array(
                'enctype' => 'multipart/form-data',
                'class' => 'form-horizontal',
                'role' => 'form',
            ),
        ));

        $form->add('submit', 'submit', array('label' => 'Save'));

        return $form;
    }
}
