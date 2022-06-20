<?php

namespace App\Controller;

use Dompdf\Dompdf;
use Dompdf\Options;

use App\Entity\Proforma;
use App\Form\ProformaType;
use App\Entity\BackupProduit;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProformatController extends AbstractController
{

    /**
     * @Route("/proformat{id}", name="app_proformat")
     */
    public function index(Proforma $facture): Response
    {
        $manager = $this->getDoctrine()->getManager();
        $sum7 =0;
        $sum19 =0;
        foreach($facture->getProducts() as $product)
        {
           if($facture->getChoix()== 2)
           {
              if($product->getTva()==7)
              {
                $sum7= $sum7 + (($product->getPrixbeneficeHT()*$product->getTva())/100);
              }
               if($product->getTva()==19)
               {
                   $sum19= $sum19 + (($product->getPrixbeneficeHT()*$product->getTva())/100);
               }
           }
        }
        $repo=$this->getDoctrine()->getRepository(BackupProduit::class);
        $backups=$repo->findBy(['idFacture'=>$facture->getId()]);
        $prixtotal = 0;
        foreach($backups as $back)
        {
            $prixtotal = $prixtotal + ($back->getIdProduit()->getPrixbeneficeHT() * $back->getQuantity());
        }
        
        return $this->render('proformat/index.html.twig', [
            'facture' => $facture, 'sum7'=>$sum7,'sum19'=>$sum19,'list' => $backups
        ]);
    }




    /**
     * @Route("/backup{id}", name="backup")
     */
    public function afficherBackup(Proforma $facture,Request $request): Response
    {
        dump($request);
        $manager = $this->getDoctrine()->getManager();
        $backupList = [];
 if($request->request->count() > 0){
        foreach($facture->getProducts() as $product)
        {
            $backup = new BackupProduit(); 
            $backup->setIdFacture($facture);
            $backup->setIdClient($facture->getIdclient());
            $backup->setIdProduit($product);
            $backup->setQuantity(intval($request->request->get('Qte'.$product->getReference())));
            $backup->setPrixTotalHt($facture->getPrixtotalht());
            $backup->setPrixTotal($product->getPrixbeneficeHT()*intval($request->request->get('Qte'.$product->getReference())));
            $manager->persist($backup);
            $backupList[] = $backup;
        }
        $manager->flush();
        return  $this->redirectToRoute('app_proformat',[
            'id' => $facture->getId(),
            'list' => $backupList
        ]);
    }

        
        return $this->render('proformat/ajoutBackup.html.twig',[
            'facture' => $facture
        ]);
    }







   

   
    /**
     * @Route("/ajouterProformat", name="add_proformat")
     * @Route("/profoma/{id}/proformaedit", name="edit_allo")
     */
    public function ajouterProformat( Proforma $facture = null, Request $request)
    {

        
       
        if(!$facture)
        {
            $facture = new Proforma();
        }
       //$facture = new Facture();
       $form = $this->createForm(ProformaType::class, $facture);
       $form->handleRequest($request);
       $manager = $this->getDoctrine()->getManager();
       if ($form->isSubmitted() && $form->isValid()) {
           $benefice = $facture->getIdclient()->getPourcentagebenifice();
           $sum = 0;
           $sumHortaxe=0;
           foreach($facture->getProducts() as $product)
           {
               $product->setPrixbeneficeHT($product->getPrixdinar()*(100+$benefice)/100);
               $product->setPrixabeneficetnd($product->getPrixdollar()*(100+$benefice)/100);
               if($facture->getChoix()==3)
               {
                   $sum = $sum + ($product->getPrixdollar()*(100+$benefice)/100);
               }
               else{
                   $sumHortaxe = $sumHortaxe +  $product->getPrixbeneficeHT();
                   $sum = $sum + ($product->getPrixbeneficeHT()*(100+$product->getTva())/100);
               }
           }
if($facture->getChoix()==2) {
    $sum = $sum + 0.600;
}
           $facture->setDate(new \DateTime());
           $facture->setPrixTotal($sum);
           $facture->setPrixtotalht($sumHortaxe);
           
           $manager->persist($facture);
           $manager->flush();
           $listeProduit = $facture->getProducts();
           return  $this->redirectToRoute('backup',['id' => $facture->getId()]);
       }
        
        
        return $this->render('proformat/ajouterProformat.html.twig', [
            'controller_name' => 'ProformatController',
            'formf' => $form->createView()
            
        ]);
    }


     /**
     * @Route("/afficherProformat", name="show_proformat")
     */
    public function afficherProformat(): Response
    {
        $repo=$this->getDoctrine()->getRepository(Proforma::class);
        $factures=$repo->findAll();
        $datedeaujourdhui = new \DateTime();
        return $this->render('proformat/afficherProformat.html.twig', [
            'controller_name' => 'ProformatController',
            'factures' => $factures, 'datedeaujourdhui'=>  $datedeaujourdhui
        ]);
    }



}
