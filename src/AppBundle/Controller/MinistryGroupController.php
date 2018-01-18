<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
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
    public function indexAction(SerializerInterface $serializer)
    {
        $em = $this->getDoctrine()->getManager();

        $repo = $em->getRepository('AppBundle:Ministry\Group'); /* @var $repo \AppBundle\Repository\Ministry\GroupRepository */
        $groups = $repo->findAllForListing();

        $personRepo = $em->getRepository('AppBundle:Person');
        $persons = $personRepo->findAllForMinistryListing();

        // serializations
        $context = ['groups' => ['MinistryGroupListing']];
        $groupsJson = $serializer->serialize($groups, 'json', $context);
        $personsJson = $serializer->serialize($persons, 'json', $context);

        return $this->render('AppBundle:MinistryGroup:index.html.twig', array(
            'persons_json' => $personsJson,
            'groups_json' => $groupsJson,
        ));
    }

    /**
     * Edits an existing ministry group entity.
     *
     */
    public function updateAction(Request $request, SerializerInterface $serializer)
    {
        if ('json' != $request->getContentType()) {
            throw new \InvalidArgumentException('Wrong content type provided. JSON is expected.');
        }

        try {
            $em = $this->getDoctrine()->getManager();
            $clientMinistryGroups = json_decode($request->getContent(), true);

            $groups = $em->getRepository(Group::class)->findAll();
            /* @var $groups Group[] */

            // Remove groups
            foreach ($groups as $group) {
                foreach ($clientMinistryGroups as $clientMinistryGroup) {
                    if ($group->getId() == $clientMinistryGroup['id']) {
                        continue 2;
                    }
                }
                $em->remove($group);
            }

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
                $form = $this->createForm(GroupType::class, $group, array(
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
            $context = ['groups' => ['MinistryGroupListing']];
            $json = $serializer->serialize($groups, 'json', $context);

            return new Response($json, 200, array('Content-Type' => 'application/json'));
        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage(), 401);
        }
    }
}
