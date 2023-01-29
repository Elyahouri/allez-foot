<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    #[Route('/users', name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/reporter/new', name: 'app_reporter_new', methods: ['GET', 'POST'])]
    public function newReporter(Request $request, UserRepository $userRepository): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);
        $errors=[];

        if ($form->isSubmitted() && $form->isValid()) {

            //On gère la photo de profil de l'utilisateur
            $pp = $form->get('profilePicture')->getData();
            if($pp){
                //Générer nom du fichier
                $fichier = md5(uniqid()).'.'.$pp->guessExtension();

                //Déplacer le fichier avec le nom généré ci-dessus dans le dossier upload
                $pp->move($this->getParameter('image_directory'),$fichier);

                //On ajoute les nom du fichier image à l'objet article
                $user->setProfilePicture($fichier);
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
            }else{
                $user->setRoles(["ROLE_REPORTER"]);
                $user->setPassword($this->hasher->hashPassword($user,$user->getPassword()));
            }

            $userRepository->save($user, true);

            return $this->redirectToRoute('app_reporters', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
            'errors' => $errors,
        ]);
    }
}
