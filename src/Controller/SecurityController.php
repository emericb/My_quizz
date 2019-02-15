<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

use App\Entity\User;
use App\Form\RegistrationType;

class SecurityController extends Controller
{
    /**
     * @Route("/inscription", name="security_registration")
     */

    public function registration(Request $request, ObjectManager $manager, UserPasswordEncoderInterface $encoder, \Swift_Mailer $mailer) {

        $user = new User();
        $token = $this->generateToken(80);
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $hash = $encoder->encodePassword($user, $user->getPassword());
            $user->setPassword($hash);
            $user->setToken($token);
            $user->setRoles(["ROLE_UNDEFINED"]);

            $message = (new \Swift_Message('Hello Email'))
                ->setFrom('send@example.com')
                ->setTo($user->getEmail())
                ->setBody(
                    $this->renderView(
                        'email/registration.html.twig',
                        array('name' => $user->getUsername(),
                            'id' => $user->getId(),
                            'token' => $token)
                    ),
                    'text/html'
                );

            $mailer->send($message);

            $manager->persist($user);
            $manager->flush();

            return $this->redirectToRoute('security_login');
        }

        return $this->render('security/registration.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/connexion", name="security_login")
     */
    public function login(UserRepository $user, Request $request){
        return $this->render('security/login.html.twig');
    }

    /**
     * @Route("validation/{token}", name="register_token")
     */
    public function validToken($token, ObjectManager $manager, UserRepository $user, User $entityUser) {

        $occurence = $user->findBy([
            'token' => $token
        ]);

            $entityUser->setRoles(["ROLE_USER"]);
            $manager->persist($entityUser);
            $manager->flush();

            return $this->redirectToRoute('security_logout');
    }

    /**
     * @Route("/logout", name="security_logout")
     */
    public function logout() {

    }

    public function generateToken($length){
        $alphabet = "0123456789azertyuiopqsdfghjklmwxcvbnAZERTYUIOPQSDFGHJKLMWXCVBN";
        return substr(str_shuffle(str_repeat($alphabet, $length)), 0, $length);
    }
}
