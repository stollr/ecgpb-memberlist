<?php

namespace App\Controller;

use App\Entity\Address;
use App\Form\AddressType;
use App\Helper\PersonHelper;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Address controller.
 *
 * @Route("/address")
 * @/Security("has_role('ROLE_ADMIN')")
 */
class AddressController extends AbstractController
{
    private $translator;
    
    private $paginator;

    public function __construct(TranslatorInterface $translator, PaginatorInterface $paginator)
    {
        $this->translator = $translator;
        $this->paginator = $paginator;
    }

    /**
     * Lists all Address entities.
     *
     * @Route("/index", name="app.address.index", defaults={"_locale"="de"})
     */
    public function index(Request $request, PersonHelper $personHelper): Response
    {
        $em = $this->getDoctrine()->getManager();

        $repo = $em->getRepository(Address::class); /* @var $repo \App\Repository\AddressRepository */

        $filter = $request->get('filter', array());
        if (!empty($filter['no-photo'])) {
            $filter['no-photo'] = $personHelper->getPersonIdsWithoutPhoto();
        }

        $pagination = $this->paginator->paginate(
            $repo->getListFilterQb($filter),
            $request->query->get('page', 1)/*page number*/,
            15, /*limit per page*/
            array(
                'wrap-queries' => true,
                'defaultSortFieldName' => array('address.familyName', 'person.dob'),
                'defaultSortDirection' => 'asc',
            )
        );

        return $this->render('/address/index.html.twig', array(
            'pagination' => $pagination,
            'person_ids_without_photo' => $personHelper->getPersonIdsWithoutPhoto(),
            //'persons_with_picture' => $personsWithPicture,
        ));
    }

    /**
     * Displays a form to create a new Address entity.
     *
     * @Route("/new", name="app.address.new", methods={"GET", "POST"})
     */
    public function new(Request $request): Response
    {
        $address = new Address();
        $form = $this->createForm(AddressType::class, $address, ['method' => 'POST']);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($address);
                $em->flush();

                $this->addFlash('success', 'The entry has been created.');

                return $this->redirectToRoute('app.address.edit', ['id' => $address->getId()]);
            }

            $this->addFlash('error', 'The submitted data is invalid. Please check your inputs.');
        }

        return $this->render('address/form.html.twig', [
            'entity' => $address,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing Address entity.
     *
     * @Route("/{id}/edit", name="app.address.edit", methods={"GET", "PUT"})
     */
    public function edit(Address $address, Request $request, PersonHelper $personHelper): Response
    {
        $form = $this->createForm(AddressType::class, $address, ['method' => 'PUT']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            // person picture file
            foreach ($request->files->get('person-picture-file', []) as $index => $file) {
                /** @var UploadedFile $file */
                $person = $address->getPersons()->get($index);

                if (!$file) {
                    continue;
                }

                if ($this->isJpegImage($file)) {
                    $file->move($personHelper->getPersonPhotoPath(), $personHelper->getPersonPhotoFilename($person));
                } else {
                    $this->addFlash('warning', $this->translator->trans(
                        'The uploaded photo for "%name%" is not a valid JPEG file.',
                        ['%name%' => $person->getFirstname()]
                    ));
                }
            }

            $this->addFlash('success', 'All changes have been saved.');

            return $this->redirectToRoute('app.address.edit', [
                'id' => $address->getId(),
                'referrer' => $request->query->get('referrer'),
            ]);
        }

        return $this->render('address/form.html.twig', [
            'entity' => $address,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Deletes a Address entity.
     *
     * @Route("/{id}/delete", name="app.address.delete")
     */
    public function delete(Request $request, $id): Response
    {
        $em = $this->getDoctrine()->getManager();
        $address = $em->getRepository(Address::class)->find($id);
        /* @var $address Address */

        if (!$address) {
            throw $this->createNotFoundException('Unable to find Address entity.');
        }

        foreach ($address->getPersons() as $person) {
            if ($person->getLeaderOf()) {
                $person->getLeaderOf()->setLeader(null);
            }
        }

        $em->remove($address);
        $em->flush();

        $this->addFlash('success', 'The entry has been deleted.');

        if ($referrer = $request->query->get('referrer')) {
            return $this->redirect($referrer);
        }

        return $this->redirectToRoute('app.address.index');
    }

    /**
     * Check if an uploaded file is real jpeg image.
     */
    private static function isJpegImage(UploadedFile $file): bool
    {
        $res = @imagecreatefromjpeg($file->getPathname());

        return $res !== false;
    }
}
