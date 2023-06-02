<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Entity\Item;

class XMLHandler
{
    /**
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface $logger
     * @param UploadedFile $file
     * @return void
     */
    public function writeFromFile(EntityManagerInterface $entityManager, LoggerInterface $logger, UploadedFile $file): void
    {
        try {
            $document = new \SimpleXMLElement($file->getContent());
            $items = (array) $document->children()->item;
            foreach ($items as $item) {
                $createItem = new Item();
                $createItem->setValue($item);
                $entityManager->persist($createItem);
                $entityManager->flush();
            }
        } catch (\Exception $e) {
            $logger->error($e->getMessage());
        }
    }
}
