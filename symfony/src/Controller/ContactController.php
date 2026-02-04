<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Repository\ContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ContactController extends AbstractController
{
    #[Route('/contacts', name: 'app_contacts', methods: ['GET'])]
    public function list(ContactRepository $contactRepository): Response
    {
        $contacts = $contactRepository->findByUser($this->getUser());
        return $this->render('contact/list.html.twig', ['contacts' => $contacts]);
    }

    #[Route('/contacts/new', name: 'app_contact_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $contact = new Contact();
        $contact->setUser($this->getUser());
        if ($request->isMethod('POST')) {
            $contact->setEmail($request->request->get('email', ''));
            $contact->setName($request->request->get('name', ''));
            $em->persist($contact);
            $em->flush();
            $this->addFlash('success', 'Contact ajouté.');
            return $this->redirectToRoute('app_contacts');
        }
        return $this->render('contact/form.html.twig', ['contact' => $contact]);
    }

    #[Route('/contacts/{id}/edit', name: 'app_contact_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, ContactRepository $contactRepository, EntityManagerInterface $em): Response
    {
        $contact = $contactRepository->find($id);
        if (!$contact || $contact->getUser() !== $this->getUser()) {
            throw $this->createNotFoundException();
        }
        if ($request->isMethod('POST')) {
            $contact->setEmail($request->request->get('email', ''));
            $contact->setName($request->request->get('name', ''));
            $em->flush();
            $this->addFlash('success', 'Contact modifié.');
            return $this->redirectToRoute('app_contacts');
        }
        return $this->render('contact/form.html.twig', ['contact' => $contact]);
    }

    #[Route('/contacts/{id}/delete', name: 'app_contact_delete', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function delete(int $id, Request $request, ContactRepository $contactRepository, EntityManagerInterface $em): Response
    {
        $contact = $contactRepository->find($id);
        if (!$contact || $contact->getUser() !== $this->getUser()) {
            throw $this->createNotFoundException();
        }
        if ($request->isMethod('POST') && $this->isCsrfTokenValid('delete' . $id, (string) $request->request->get('_token'))) {
            $em->remove($contact);
            $em->flush();
            $this->addFlash('success', 'Contact supprimé.');
            return $this->redirectToRoute('app_contacts');
        }
        return $this->render('contact/delete.html.twig', ['contact' => $contact]);
    }
}
