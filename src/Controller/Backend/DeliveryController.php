<?php

namespace App\Controller\Backend;

use App\Entity\Delivery\Delivery;
use App\Form\DeliveryType;
use App\Repository\Delivery\DeliveryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/deliveries', name: 'admin.deliveries')]
class DeliveryController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private DeliveryRepository $deliveryRepo
    ) {
    }

    #[Route('', name: '.index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('Backend/Deliveries/index.html.twig', [
            'deliveries' => $this->deliveryRepo->findAll(),
        ]);
    }

    #[Route('/create', name: '.create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response|RedirectResponse
    {
        $delivery = new Delivery;

        $form = $this->createForm(DeliveryType::class, $delivery);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($delivery);
            $this->em->flush();

            $this->addFlash('success', 'Méthode de livraison créée avec succès');

            return $this->redirectToRoute('admin.deliveries.index');
        }

        return $this->render('Backend/Deliveries/create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: '.edit', methods: ['GET', 'POST'])]
    public function edit(?Delivery $delivery, Request $request): Response|RedirectResponse
    {
        if (!$delivery) {
            $this->addFlash('error', 'Méthode de livraison non trouvée');

            return $this->redirectToRoute('admin.deliveries.index');
        }

        $form = $this->createForm(DeliveryType::class, $delivery);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($delivery);
            $this->em->flush();

            $this->addFlash('success', 'La méthode a bien été mise à jour');

            return $this->redirectToRoute('admin.deliveries.index');
        }

        return $this->render('Backend/Deliveries/edit.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: '.delete', methods: ['POST'])]
    public function delete(?Delivery $delivery, Request $request): RedirectResponse
    {
        if (!$delivery) {
            $this->addFlash('error', 'Méthode de livraison non trouvée');

            return $this->redirectToRoute('admin.deliveries.index');
        }

        if ($this->isCsrfTokenValid('delete' . $delivery->getId(), $request->request->get('token'))) {
            $this->em->remove($delivery);
            $this->em->flush();

            $this->addFlash('success', 'Méthode supprimée avec succès');
        } else {
            $this->addFlash('error', 'Token invalide');
        }

        return $this->redirectToRoute('admin.deliveries.index');
    }
}
