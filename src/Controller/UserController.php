<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\ArticleRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/user')]
class UserController extends AbstractController
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }


    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, UserRepository $userRepository): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);
        $tabExtension = array("jpg","jpeg","png","gif");
        $errors=[];

        if ($form->isSubmitted() && $form->isValid()) {

            //On gère la photo de profil de l'utilisateur
            $pp = $form->get('profilePicture')->getData();
            if($pp){
                if(!in_array($pp->guessExtension(),$tabExtension)){
                    $errors[] = "Cette extension d'image n'est pas acceptée";
                }
            }
            //On vérifie que le pseudo n'est pas déjà utilisé
            if($userRepository->findOneBy(array('pseudo'=>$user->getPseudo()))){
                $errors[]='Pseudo déjà utilisé';

            }
            //Si la confirmation de mdp est différente du mdp
            if ($user->getPassword()!=$user->getConfirmPassword()){
                $errors[]='la confirmation du mot de passe est différente du mot de passe';

            }
            if(count($errors)>0){
                return $this->renderForm('user/new.html.twig', [
                    'user' => $user,
                    'form' => $form,
                    'errors' => $errors
                ]);
            } else{
                if($pp){
                    //Générer nom du fichier
                    $fichier = md5(uniqid()).'.'.$pp->guessExtension();

                    //Déplacer le fichier avec le nom généré ci-dessus dans le dossier upload
                    $pp->move($this->getParameter('image_directory'),$fichier);

                    //On ajoute les nom du fichier image à l'objet article
                    $user->setProfilePicture($fichier);
                }
                $user->setPassword($this->hasher->hashPassword($user,$user->getPassword()));

            }

            $userRepository->save($user, true);

            return $this->redirectToRoute('app_login', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
            'errors' => $errors
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, UserRepository $userRepository): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $user!=$this->getUser()){
            return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
        }
        $errors=[];
        $form = $this->createForm(UserType::class, $user);
        $form->remove('email')
            ->remove('password')
            ->remove('confirmPassword')
            ->remove('profilePicture');
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if($userRepository->findOneBy(array('pseudo'=>$user->getPseudo())) && $userRepository->findOneBy(array('pseudo'=>$user->getPseudo()))->getId()!=$user->getId()){
                $errors[]='Pseudo déjà utilisé';
            }else{
                $userRepository->save($user, true);
                return $this->redirectToRoute('app_user_show', ['id'=>$user->getId()], Response::HTTP_SEE_OTHER);
            }

        }

        return $this->renderForm('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
            'errors'=>$errors,
        ]);
    }

    #[Route('/{id}/editPicture', name: 'app_user_edit_picture', methods: ['GET', 'POST'])]
    public function editPicture(Request $request, User $user, UserRepository $userRepository): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $user!=$this->getUser()){
            return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
        }
        $form = $this->createForm(UserType::class, $user);
        $form->remove('email')
            ->remove('password')
            ->remove('confirmPassword')
            ->remove('pseudo')
            ->remove('nickname')
            ->remove('prizeList');
        $form->handleRequest($request);

        $errors = [];
        $tabExtension = array("jpg","jpeg","png","gif");

        if ($form->isSubmitted() && $form->isValid()) {
            //On gère la photo de profil de l'utilisateur
            $pp = $form->get('profilePicture')->getData();
            if($pp){
                if(!in_array($pp->guessExtension(),$tabExtension)){
                    $errors[] = "Cette extension d'image n'est pas acceptée";
                }
                if(count($errors)>0){
                    return $this->renderForm('user/editPicture.html.twig', [
                        'errors'=>$errors,
                        'user' => $user,
                        'form' => $form,
                    ]);
                }else{
                    //retire l'ancienne photo
                    if ($user->getProfilePicture()){
                        unlink(($this->getParameter('image_directory').'/'.$user->getProfilePicture()));
                    }
                    //Générer nom du fichier
                    $fichier = md5(uniqid()).'.'.$pp->guessExtension();

                    //Déplacer le fichier avec le nom généré ci-dessus dans le dossier upload
                    $pp->move($this->getParameter('image_directory'),$fichier);

                    //On ajoute les nom du fichier image à l'objet article
                    $user->setProfilePicture($fichier);

                    $userRepository->save($user, true);
                    return $this->redirectToRoute('app_user_show', ['id'=>$user->getId()], Response::HTTP_SEE_OTHER);

                }

            }
            //si le champs image n'a pas été rempli
            else{
                return $this->redirectToRoute('app_user_show', ['id'=>$user->getId()], Response::HTTP_SEE_OTHER);
            }

        }
        return $this->renderForm('user/editPicture.html.twig', [
            'errors'=>$errors,
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/deletePicture', name: 'app_user_delete_picture', methods: ['GET', 'POST'])]
    public function deletePicture(User $user, UserRepository $userRepository): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $user!=$this->getUser()){
            return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
        }
        if($user->getProfilePicture()==null){
            return $this->redirectToRoute('app_user_show', ['id'=>$user->getId()], Response::HTTP_SEE_OTHER);
        }
        unlink(($this->getParameter('image_directory').'/'.$user->getProfilePicture()));
        $user->setProfilePicture(null);
        $userRepository->save($user, true);
        return $this->redirectToRoute('app_user_show', ['id'=>$user->getId()], Response::HTTP_SEE_OTHER);

    }



    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, UserRepository $userRepository, ArticleRepository $articleRepository): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $user != $this->getUser()){
            return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
        }
        else{
            if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
                foreach ($user->getArticles() as $article){
                    $article->setUser(null);
                    $articleRepository->save($article,true);
                }
                if($user->getProfilePicture()){
                    unlink(($this->getParameter('image_directory').'/'.$user->getProfilePicture()));
                }

                if($user = $this->getUser()){
                    $session = new Session();
                    $session->invalidate();
                    $userRepository->remove($user, true);
                    return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
                }else{
                    $userRepository->remove($user, true);
                    return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);

                }
            }
        }
        return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/edit/password', name: 'app_user_edit_password', methods: ['GET', 'POST'])]
    public function modifPwd(Request $request, User $user, UserPasswordHasherInterface $hasher, UserRepository $userRepository): Response
    {
        $userConnect = $this->getUser();
        $tokenForm = $request->request->get('token');

        // Vérification du bon user
        if (!$this->isGranted('ROLE_ADMIN') && $user != $this->getUser()){
            return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
        }
        $error = "";

        if ($request->getMethod() === "POST" && $this->isCsrfTokenValid('app_user_edit_password', $tokenForm))
        {
            if ($hasher->isPasswordValid($user, $request->request->get("old"))) {
                if ($request->request->get("new") === $request->request->get("confirm")) {
                    $user->setPassword($hasher->hashPassword($user, $request->request->get("new")));
                    $userRepository->save($user, true);
                    return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
                } else {
                    $error = "Les 2 nouveaux mot de passe ne sont pas identique";
                }
            } else {
                $error = "Mauvais mot de passe actuel";
            }
        }

        return $this->renderForm('user/edit_password.html.twig', [
            'error' => $error,
            'user' => $user
        ]);
    }

    /*
    #[Route('/{id}/edit/password', name: 'app_user_edit_password', methods: ['GET', 'POST'])]
    public function editPwd(Request $request, User $user, UserRepository $userRepository): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $user != $this->getUser()){
            return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
        }
        $old = $request->request->get("oldPassword");
        $new = $request->request->get("newPassword");
        $confirm = $request->request->get("confirmPassword");
        $error = "";

        if ($old!="" && $new!="" && $confirm!="")
        {
            dd($old);
            if ($user->getPassword() === $this->hasher->hashPassword($user, $old)) {
                if ($new === $confirm) {
                    $user->setPassword($this->hasher->hashPassword($user, $new));
                    $userRepository->save($user, true);
                    return $this->redirectToRoute('app_user_show', ['id'=>$user->getId()], Response::HTTP_SEE_OTHER);
                } else {
                    $error = "Les 2 nouveaux mot de passe ne sont pas identique";
                }
            } else {
                $error = "Mauvais mot de passe actuel";
            }
        }

        return $this->renderForm('user/edit_password.html.twig', [
            'error' => $error,
            'user' => $user
        ]);
    }
    */





}
