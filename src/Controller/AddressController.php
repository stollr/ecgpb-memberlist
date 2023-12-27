<?php

namespace App\Controller;

use App\Entity\Address;
use App\Form\AddressType;
use App\Helper\PersonHelper;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Address controller.
 */
#[Route(path: '/address')]
class AddressController extends AbstractController
{
    private $translator;
    
    private $paginator;

    private EntityManagerInterface $entityManager;

    public function __construct(
        TranslatorInterface $translator,
        PaginatorInterface $paginator,
        EntityManagerInterface $entityManager
    ) {
        $this->translator = $translator;
        $this->paginator = $paginator;
        $this->entityManager = $entityManager;
    }

    /**
     * Lists all Address entities.
     */
    #[Route(path: '/index', name: 'app.address.index', defaults: ['_locale' => 'de'])]
    public function index(Request $request, PersonHelper $personHelper): Response
    {
        $repo = $this->entityManager->getRepository(Address::class); /* @var $repo \App\Repository\AddressRepository */

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

        if ($pagination->count() === 1) {
            return $this->redirectToRoute('app.address.edit', [
                'id' => $pagination[0]->getId(),
            ]);
        }

        return $this->render('/address/index.html.twig', array(
            'pagination' => $pagination,
            'person_ids_without_photo' => $personHelper->getPersonIdsWithoutPhoto(),
            //'persons_with_picture' => $personsWithPicture,
        ));
    }

    /**
     * Displays a form to create a new Address entity.
     */
    #[Route(path: '/new', name: 'app.address.new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $address = new Address();
        $form = $this->createForm(AddressType::class, $address, ['method' => 'POST']);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $this->entityManager->persist($address);
                $this->entityManager->flush();

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
     */
    #[Route(path: '/{id}/edit', name: 'app.address.edit', methods: ['GET', 'POST'])]
    public function edit(Address $address, Request $request, PersonHelper $personHelper): Response
    {
        $form = $this->createForm(AddressType::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

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

        $repo = $this->entityManager->getRepository(Address::class);

        return $this->render('address/form.html.twig', [
            'entity' => $address,
            'form' => $form->createView(),
            'addressLogs' => $repo->findLogEntries($address),
            'personsLogs' => $repo->findPersonsLogEntries($address),
        ]);
    }

    /**
     * Deletes a Address entity.
     */
    #[Route(path: '/{id}/delete', name: 'app.address.delete')]
    public function delete(Address $address, Request $request): Response
    {

        foreach ($address->getPersons() as $person) {
            if ($person->getLeaderOf()) {
                $person->getLeaderOf()->setLeader(null);
            }
        }

        $this->entityManager->remove($address);
        $this->entityManager->flush();

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
