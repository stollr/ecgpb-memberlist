<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use JMS\Serializer\SerializationContext;
use AppBundle\Entity\Ministry\Group;
use AppBundle\Form\Ministry\GroupType;

/**
 * AppBundle\Controller\MinistryGroupController
 *
 * @/Security("has_role('ROLE_ADMIN')")
 */
class MinistryGroupController extends Controller
{
    /**
     * Lists all Address entities.
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $repo = $em->getRepository('EcgpbMemberBundle:Ministry\Group'); /* @var $repo \AppBundle\Repository\Ministry\GroupRepository */
        $groups = $repo->findAllForListing();

        $personRepo = $em->getRepository('EcgpbMemberBundle:Person');
        $persons = $personRepo->findAllForMinistryListing();

        // serializations
        $serializer = $this->get('jms_serializer');
        $groupsJson = $serializer->serialize($groups, 'json', SerializationContext::create()->setGroups(array('MinistryGroupListing')));
        $personsJson = $serializer->serialize($persons, 'json', SerializationContext::create()->setGroups(array('MinistryGroupListing')));

        return $this->render('EcgpbMemberBundle:MinistryGroup:index.html.twig', array(
            'persons_json' => $personsJson,
            'groups_json' => $groupsJson,
        ));
    }

    /**
     * Edits an existing ministry group entity.
     *
     */
    public function updateAction(Request $request)
    {
        if ('json' != $request->getContentType()) {
            throw new \InvalidArgumentException('Wrong content type provided. JSON is expected.');
        }

        try {
            $em = $this->getDoctrine()->getManager();
            $clientMinistryGroups = json_decode($request->getContent(), true);

            $groups = $em->getRepository('EcgpbMemberBundle:Ministry\Group')->findBy(array(
                'id' => array_map(function($element) {
                    return isset($element['id']) ? $element['id'] : 0;

                },
                $clientMinistryGroups
            )));
            /* @var $groups Group[] */

            foreach ($clientMinistryGroups as $clientMinistryGroup) {
                if (empty($clientMinistryGroup['id'])) {
                    $group = new Group();
                    $groups[] = $group;
                } else {
                    $filtered = array_filter($groups, function($group) use ($clientMinistryGroup) {
                        return $group->getId() == $clientMinistryGroup['id'];
                    });
                    $group = reset($filtered);
                }
                $form = $this->createForm(new GroupType(), $group, array(
                    'csrf_protection' => false,
                ));
                $form->submit($clientMinistryGroup);

                if ($form->isValid()) {
                    $em->persist($group);
                } else {
                    return new Response('Invalid entity', 400, array('Content-Type' => 'application/json'));
                }
            }

            $em->flush();

            // response
            $serializer = $this->get('jms_serializer');
            $json = $serializer->serialize($groups, 'json', SerializationContext::create()->setGroups(array('MinistryGroupListing')));
            return new Response($json, 200, array('Content-Type' => 'application/json'));
        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage(), 401);
        }
    }
}
