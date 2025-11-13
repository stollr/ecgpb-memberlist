<?php

namespace App\Controller;

use App\Entity\Address;
use App\Form\AddressType;
use App\Helper\PersonHelper;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
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
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly PaginatorInterface $paginator,
        private readonly EntityManagerInterface $entityManager,
        private readonly FormFactoryInterface $formFactory
    ) {
    }

    /**
     * Lists all Address entities.
     */
    #[Route(path: '/index', name: 'app.address.index', defaults: ['_locale' => 'de'])]
    public function index(Request $request, PersonHelper $personHelper): Response
    {
        $repo = $this->entityManager->getRepository(Address::class); /* @var $repo \App\Repository\AddressRepository */
        $filterForm = $this->createFilterForm([
            'sort' => 'address.familyName',
        ]);
        $filterForm->handleRequest($request);

        $filter = $filterForm->getData();//$request->query->all('filter', []);
        if (!empty($filter['noPhoto'])) {
            $filter['noPhoto'] = $personHelper->getPersonIdsWithoutPhoto();
        }

        $pagination = $this->paginator->paginate(
            $repo->getListFilterQb($filter),
            $request->query->get('page', 1)/*page number*/,
            15, /*limit per page*/
            [
                'wrap-queries' => true,
                PaginatorInterface::DEFAULT_SORT_FIELD_NAME => [$filter['sort'], 'person.dob'],
                PaginatorInterface::DEFAULT_SORT_DIRECTION => 'asc',
            ]
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
            'filterForm' => $filterForm,
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

    private function createFilterForm(array $data): FormInterface
    {
        $data['sortBy'] ??= 'address.familyName';

        $builder = $this->formFactory->createNamedBuilder(
            name: '',
            data: $data,
            options: [
                'block_prefix' => 'filter',
                'method' => 'get',
                'csrf_protection' => false,
            ]
        );

        $builder->add('term', TextType::class, [
            'required' => false,
            'attr' => [
                'placeholder' => 'Search Term',
            ],
        ]);

        $builder->add('hasEmail', CheckboxType::class, [
            'required' => false,
            'label' => 'Has Email Address',
        ]);

        $builder->add('noPhoto', CheckboxType::class, [
            'required' => false,
            'label' => 'Without Photo'
        ]);

        $builder->add('sort', ChoiceType::class, [
            'choices' => [
                'Name' => 'address.familyName',
                'Creation date' => 'person.createdAt',
            ],
        ]);

        $builder->add('direction', ChoiceType::class, [
            'choices' => [
                'ascending' => 'asc',
                'descending' => 'desc',
            ],
        ]);

        return $builder->getForm();
    }
}
