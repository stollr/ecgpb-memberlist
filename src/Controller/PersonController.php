<?php

namespace App\Controller;

use App\Entity\Person;
use App\Form\PersonType;
use App\Helper\PersonHelper;
use App\PdfGenerator\MemberListGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Person controller.
 *
 * @/Security("is_granted('ROLE_ADMIN')")
 * @Route("/person")
 */
class PersonController extends Controller
{
    private $personHelper;

    public function __construct(PersonHelper $personHelper)
    {
        $this->personHelper = $personHelper;
    }

    /**
     * Displays a form to edit an existing Person entity.
     *
     * @Route(name="ecgpb.member.person.edit", path="/{id}/edit")
     */
    public function edit(Person $person, Request $request)
    {
        $form = $this->createPersonForm($person);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            // person photo file
            if ($file = $request->files->get('person-picture-file')) {
                /* @var $file UploadedFile */
                $filename = $this->personHelper->getPersonPhotoFilename($person);
                $file->move($this->personHelper->getPersonPhotoPath(), $filename);
            }

            $this->addFlash('success', 'All changes have been saved.');

            return $this->redirectToRoute('ecgpb.member.person.edit', ['id' => $person->getId()]);
        }

        return $this->render('person/form.html.twig', [
            'person' => $person,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Creates a form to edit a Person person.
     */
    private function createPersonForm(Person $person): \Symfony\Component\Form\Form
    {
        $form = $this->createForm(PersonType::class, $person, [
            'method' => 'PUT',
            'attr' => [
                'enctype' => 'multipart/form-data',
            ],
            'add_address_field' => true,
        ]);

        return $form;
    }

    /**
     * Deletes a Person entity.
     *
     * @Route(name="ecgpb.member.person.delete", path="/{id}/delete")
     */
    public function delete(Person $person)
    {
        $em = $this->getDoctrine()->getManager();

        if ($person->getLeaderOf()) {
            $person->getLeaderOf()->setLeader(null);
        }

        $em->remove($person);
        $em->flush();

        $this->addFlash('success', 'The entry has been deleted.');

        return $this->redirectToRoute('ecgpb.member.address.index');
    }

    /**
     * Generate and return the optimized member picture.
     *
     * @Route(name="ecgpb.member.person.optimized_member_picture", path="/{id}/optimized_member_picture")
     */
    public function optimizedMemberPicture(Request $request, Person $person, MemberListGenerator $generator)
    {
        $options = new \Tcpdf\Extension\Attribute\BackgroundFormatterOptions(
            null,
            MemberListGenerator::GRID_PICTURE_CELL_WIDTH,
            MemberListGenerator::GRID_ROW_MIN_HEIGHT
        );
        $formatter = $generator->getPersonPictureFormatter($person);
        $formatter($options);
        $filename = $options->getImage();

        // Create cacheable response
        $response = new BinaryFileResponse($filename, 200, [
            'Content-Type' => 'image/jpeg',
        ]);
        $response->setAutoLastModified();
        $response->isNotModified($request);

        return $response;
    }
}
