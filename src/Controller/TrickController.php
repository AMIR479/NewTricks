<?php

namespace App\Controller;

use App\Entity\Image;
use App\Entity\Trick;
use App\Entity\Video;
use Twig\Environment;
use App\Entity\Comment;
use App\Form\TrickType;
use App\Form\CommentType;
use App\Repository\ImageRepository;
use App\Repository\TrickRepository;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\String\Slugger\SluggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;



#[Route('/trick')]
class TrickController extends AbstractController
{
    #[Route('/', name: 'app_trick_index', methods: ['GET'])]
    public function index(TrickRepository $trickRepository): Response
    {
        return $this->render('trick/index.html.twig', [
            'tricks' => $trickRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_trick_new', methods: ['GET', 'POST'])]
    /**
     * @IsGranted("ROLE_USER")
     */
    public function new(Request $request, TrickRepository $trickRepository, SluggerInterface $slugger,): Response
    {
        $trick = new Trick();
// dummy code - add some example videos to the trick
        // (otherwise, the template will render an empty list of videos

        $form = $this->createForm(TrickType::class, $trick);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // ... do your form processing, like saving the Video and Video entities

            
        }

       if ($form->isSubmitted() && $form->isValid()) {
            $images = $form->get('images')->getData();
            foreach($images as $image){
                $fichier = md5(uniqid()).'.'.$image->guessExtension();
                $image->move(
                    $this->getParameter('images_directory'),
                    $fichier
                );
                $img = new Image();
                $img->setName($fichier);
                $trick->addImage($img);
            }
            $slug = strtolower($slugger->slug($trick->getTitle()));
            $trick->setSlug($slug);
            $trickRepository->add($trick, true);

            return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('trick/new.html.twig', [
            'trick' => $trick,
            'form' => $form,
        ]);
    }

    #[Route('/{id}-{slug}', name: 'app_trick_show', methods: ['GET', 'POST'])]
    public function show(Request $request, Environment $twig, Trick $trick, CommentRepository $commentRepository): Response
    {
        
        $offset = max(0, $request->query->getInt('offset', 0));
        $paginator = $commentRepository->getCommentPaginator($trick, $offset);
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setTrick($trick)->setCreatedBy($this->getUser());
            $commentRepository->add($comment, true);
            return $this->redirectToRoute('app_trick_show', 
            ['id'=>$trick->getId(), 'slug'=>$trick->getSlug()], 
                Response::HTTP_SEE_OTHER);
                
        }
        return new Response($twig->render('trick/show.html.twig', [
            'trick' => $trick,
            'comments' => $paginator,
            'form' => $form->createView(),
            'previous' => $offset - CommentRepository::PAGINATOR_PER_PAGE,
            'next' => min(count($paginator), $offset + CommentRepository::PAGINATOR_PER_PAGE),
        ]));
    }

    #[Route('/{id}/edit', name: 'app_trick_edit', methods: ['GET', 'POST'])]
    /**
     * @IsGranted("ROLE_USER")
     */
    public function edit($id, Request $request, TrickRepository $trickRepository, EntityManagerInterface $entityManager): Response
    {
        if (null === $trick = $entityManager->getRepository(Trick::class)->find($id)) {
            throw $this->createNotFoundException('No trick found for id '.$trick);
        }

        $originalVideos = new ArrayCollection();

        // Create an ArrayCollection of the current Video objects in the database
        foreach ($trick->getVideos() as $video) {
            $originalVideos->add($video);
        }

        $editForm = $this->createForm(TrickType::class, $trick);

        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            // remove the relationship between the video and the Trick
            foreach ($originalVideos as $video) {
                if (false === $trick->getVideos()->contains($video)) {
                    // remove the Trik from the video
                    $video->getTricks()->removeElement($trick);

                    

                    $entityManager->persist($video);

                    
                }
            }
            $entityManager->persist($trick);
            $entityManager->flush();

           

        }
        $form = $this->createForm(TrickType::class, $trick);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $images = $form->get('images')->getData();
            foreach($images as $image){
                $fichier = md5(uniqid()).'.'.$image->guessExtension();
                $image->move(
                    $this->getParameter('images_directory'),
                    $fichier
                );
                $img = new Image();
                $img->setName($fichier);
                $trick->addImage($img);
            }
            $trickRepository->add($trick, true);

            return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('trick/edit.html.twig', [
            'trick' => $trick,
            'form' => $form,
        ]);

        
    }

    #[Route('/{id}', name: 'app_trick_delete', methods: ['POST'])]
    public function delete(Request $request, Trick $trick, TrickRepository $trickRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$trick->getId(), $request->request->get('_token'))) {
            $trickRepository->remove($trick, true);
        }

        return $this->redirectToRoute('app_trick_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/supprime/image/{id}", name="tricks_delete_image", methods={"DELETE"})
     */
    public function deleteImage(Image $image, Request $request, ImageRepository $imageRepository){
        $data = json_decode($request->getContent(), true);

        if($this->isCsrfTokenValid('delete'.$image->getId(), $data['_token'])){
            $nom = $image->getName();
            unlink($this->getParameter('images_directory').'/'.$nom);
            $imageRepository->remove($image,true);
            return new JsonResponse(['success' => 1]);
        }else{
            return new JsonResponse(['error' => 'Token Invalide'], 400);
        }
    }
}
