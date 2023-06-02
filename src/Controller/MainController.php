<?php

namespace App\Controller;

use Doctrine\Persistence\ManagerRegistry;
use JetBrains\PhpStorm\NoReturn;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Services\XMLHandler;
use App\Entity\Item;
use App\Form\ItemType;

class MainController extends AbstractController
{
    /**
     * @param LoggerInterface $logger
     * @param ManagerRegistry $doctrine
     * @return Response
     * @Route("/", name="app_main")
     */
    public function index(LoggerInterface $logger, ManagerRegistry $doctrine): Response
    {
        $item = new Item();
        $form = $this->createForm(ItemType::class, $item, [
            'action' => $this->generateUrl('add'),
        ]);
        $items = $doctrine->getRepository(Item::class)->findAllForMain();
        return $this->render('main/index.html.twig', [
            'controller_name' => 'MainController',
            'items' => $items,
            'add_form' => $form,
        ]);
    }

    /**
     * @param LoggerInterface $logger
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @param XMLHandler $handler
     * @return Response
     * @Route("/download", name="download")
     */
    public function download(LoggerInterface $logger, EntityManagerInterface $entityManager, Request $request, XMLHandler $handler): Response
    {
        $file = $request->files->get('file');
        $handler->writeFromFile($entityManager, $logger, $file);
        return $this->redirectToRoute('app_main');
    }

    /**
     * @param ManagerRegistry $doctrine
     * @param EntityManagerInterface $entityManager
     * @param int $id
     * @return Response
     * @Route("/remove/{id}", name="remove")
     */
    public function remove(
        LoggerInterface $logger,
        ManagerRegistry $doctrine,
        EntityManagerInterface $entityManager,
        int $id
    ): Response
    {
        $item = $doctrine->getRepository(Item::class)->find($id);
        if (!$item) {
            throw $this->createNotFoundException(
                'No item found for id '.$id
            );
        }
        $entityManager->remove($item);
        $entityManager->flush();
        return $this->redirectToRoute('app_main');
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @return RedirectResponse
     * @Route("/add", name="add")
     */
    public function add(EntityManagerInterface $entityManager, Request $request): Response
    {
        $item = new Item();
        $form = $this->createForm(ItemType::class, $item);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $value =  $form->getData()->getValue();
            $item->setValue($value);
            $entityManager->persist($item);
            $entityManager->flush();
        }
        return $this->redirectToRoute('app_main');
    }

    /**
     * @param ManagerRegistry $doctrine
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @param int $id
     * @return Response
     * @Route("/update/{id}", name="update")
     */
    public function update(ManagerRegistry $doctrine, EntityManagerInterface $entityManager, Request $request, int $id): Response
    {
        $item = $doctrine->getRepository(Item::class)->find($id);
        if (!$item) {
            throw $this->createNotFoundException(
                'No item found for id '.$id
            );
        }
        $form = $this->createForm(ItemType::class, $item, [
            'action' => '/update' . '/' . $id,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $value =  $form->getData()->getValue();
            $item->setValue($value);
            $entityManager->flush();
        }
        return $this->render('main/update.html.twig', [
            'item' => $item,
            'add_form' => $form,
        ]);
    }

    /**
     * @param ManagerRegistry $doctrine
     * @param Request $request
     * @return Response
     * @Route("/search", name="search")
     */
    public function search(ManagerRegistry $doctrine, Request $request): Response
    {
        $query = $request->query->get('q');
        $items = $doctrine->getRepository(Item::class)->searchByQuery($query);

        return $this->render('main/search.html.twig', [
            'items' => $items
        ]);
    }
}
