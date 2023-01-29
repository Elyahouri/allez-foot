<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleType;
use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/reporter')]
class ReporterController extends AbstractController
{

    #[Route('/article/new', name: 'app_article_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ArticleRepository $articleRepository): Response
    {
        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);
        $error = "";
        $tabExtension = array("jpg","jpeg","png","gif");

        if ($form->isSubmitted() && $form->isValid()) {
            $img = $form->get('image')->getData();
            if($img){
                if(!in_array($img->guessExtension(),$tabExtension)){
                    $error = "Cette extension d'image n'est pas acceptée";
                    return $this->renderForm('article/new.html.twig', [
                        'error'=>$error,
                        'article' => $article,
                        'form' => $form,
                    ]);

                }
                //Générer nom du fichier
                $fichier = md5(uniqid()).'.'.$img->guessExtension();

                //Déplacer le fichier avec le nom généré ci-dessus dans le dossier upload
                $img->move($this->getParameter('image_directory'),$fichier);

                //On ajoute les nom du fichier image à l'objet article
                $article->setImage($fichier);
            }
            //$article->setUser($this->getUser());
            $ajd = new \DateTimeImmutable('now');
            $article->setCreatedAt($ajd);
            $article->setUser($this->getUser());
            $articleRepository->save($article, true);
            return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('article/new.html.twig', [
            'error'=>$error,
            'article' => $article,
            'form' => $form,
        ]);
    }


    #[Route('/article/{id}/edit', name: 'app_article_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Article $article, ArticleRepository $articleRepository): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $article->getUser()!=$this->getUser()){
            return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
        }
        $form = $this->createForm(ArticleType::class, $article);
        $form->remove('image');
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $articleRepository->save($article, true);

            return $this->redirectToRoute('app_article_show', ['id'=>$article->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('article/edit.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    #[Route('/article/{id}', name: 'app_article_delete', methods: ['POST'])]
    public function delete(Request $request, Article $article, ArticleRepository $articleRepository): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $article->getUser()!=$this->getUser()){
            return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
        }
        if ($this->isCsrfTokenValid('delete'.$article->getId(), $request->request->get('_token'))) {
            if($article->getImage()){
                unlink(($this->getParameter('image_directory').'/'.$article->getImage()));
            }

            $articleRepository->remove($article, true);
        }
        return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
    }


    #[Route('/article/{id}/editPicture', name: 'app_article_edit_picture', methods: ['GET', 'POST'])]
    public function editPicture(Request $request, Article $article, ArticleRepository $articleRepository): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $article->getUser() != $this->getUser()){
            return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
        }
        $form = $this->createForm(ArticleType::class, $article);
        $form->remove('title')
            ->remove('content');
        $form->handleRequest($request);

        $errors = [];
        $tabExtension = array("jpg","jpeg","png","gif");

        if ($form->isSubmitted() && $form->isValid()) {
            //On gère la photo de l'article
            $img = $form->get('image')->getData();
            if($img){
                //Vérification extension
                if(!in_array($img->guessExtension(),$tabExtension)){
                    $errors[] = "Cette extension d'image n'est pas acceptée";
                }
                if(count($errors)>0){
                    return $this->renderForm('article/editPicture.html.twig', [
                        'errors'=>$errors,
                        'article' => $article,
                        'form' => $form,
                    ]);
                }
                //Si il n'y a pas d'erreur
                else{
                    //retire l'ancienne photo
                    if ($article->getImage()){
                        unlink(($this->getParameter('image_directory').'/'.$article->getImage()));
                    }
                    //Générer nom du fichier
                    $fichier = md5(uniqid()).'.'.$img->guessExtension();

                    //Déplacer le fichier avec le nom généré ci-dessus dans le dossier upload
                    $img->move($this->getParameter('image_directory'),$fichier);

                    //On ajoute les nom du fichier image à l'objet article
                    $article->setImage($fichier);

                    $articleRepository->save($article, true);
                    return $this->redirectToRoute('app_article_show', ['id'=>$article->getId()], Response::HTTP_SEE_OTHER);

                }

            }
            //si le champs image n'a pas été rempli
            else{
                return $this->redirectToRoute('app_article_show', ['id'=>$article->getId()], Response::HTTP_SEE_OTHER);
            }

        }
        return $this->renderForm('article/editPicture.html.twig', [
            'errors'=>$errors,
            'article' => $article,
            'form' => $form,
        ]);
    }


    #[Route('article/{id}/deletePicture', name: 'app_article_delete_picture', methods: ['GET', 'POST'])]
    public function deletePicture(Article $article, ArticleRepository $articleRepository): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $article->getUser()!=$this->getUser()){
            return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
        }
        if($article->getImage()==null){
            return $this->redirectToRoute('app_article_show', ['id'=>$article->getId()], Response::HTTP_SEE_OTHER);
        }
        unlink(($this->getParameter('image_directory').'/'.$article->getImage()));
        $article->setImage(null);
        $articleRepository->save($article, true);
        return $this->redirectToRoute('app_article_show', ['id'=>$article->getId()], Response::HTTP_SEE_OTHER);

    }
}
