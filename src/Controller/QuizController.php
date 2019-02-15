<?php

namespace App\Controller;

use App\Entity\Quizz;
use App\Entity\Reponses;
use App\Form\UserType;
use App\Entity\User;
use App\Repository\CategoriesRepository;
use App\Repository\QuizzRepository;
use App\Repository\ReponsesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class QuizController extends AbstractController
{
    /**
     * @Route("/quiz", name="quiz")
     */
    public function index()
    {
        return $this->render('quiz/index.html.twig', [
            'controller_name' => 'QuizController',
        ]);
    }

    /**
     * @Route("/profil/{id}", name="profil_show", methods="GET")
     */
    public function display(User $user): Response
    {
        return $this->render('quiz/show.html.twig', ['user' => $user]);
    }

    /**
     * @Route("profil/{id}/edit", name="profil_edit", methods="GET|POST")
     */
    public function edit(Request $request, User $user): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('user_index', ['id' => $user->getId()]);
        }

        return $this->render('quiz/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("profil/{id}", name="profil_delete", methods="DELETE")
     */
    public function delete(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($user);
            $delete = $user->getId();
            $admin = $this->getUser();
            $admin = $admin->getId();
            $em->flush();

            if ($delete == $admin) {
                $this->get('security.token_storage')->setToken(null);
                $request->getSession()->invalidate();
            }
        }
        return $this->redirectToRoute('user_index');
    }

    /**
     * @Route("/", name="home")
     */
    public function home(CategoriesRepository $repo) {

        $data = $repo->findAll();
        return $this->render('quiz/quiz.html.twig', [
            "data" => $data
        ]);
    }

    /**
     * @Route("/list/{id}", name="list")
     */
    public function list(QuizzRepository $Quizz, $id) {
        $dataQuizz = $Quizz->findBy(
            ['Categorie' => $id]
        );
        return $this->render('quiz/list.html.twig', [
            "dataQuizz" => $dataQuizz
        ]);
    }


    /**
     * @Route("/quizz/{id}", name="quizz")
     */
    public function quizz(Request $request, Quizz $quizz, ReponsesRepository $reponse) {

        $message = '';
        $answer = $request->get('answer');
        $compteur = $request->get('next');
        $bonneRep = $request->get('bonneRep');
        $good = ['18120710525024520016027347.jpg', '18120710525024520016027345.jpg', '18120710525024520016027346.jpg'];
        $bad = ['18120710475424520016027342.jpg', '18120710475424520016027341.jpg', '18120710475424520016027343.jpg'];

        if (!isset($compteur)){
            $compteur = 0;
        } else {
            $compteur += 0.5;
        }

        if (!isset($bonneRep)){
            $bonneRep = 0;
        }

        if (isset($answer)) {

            $reponse = $reponse->findAll();
            $answer--;
            $reponse = $reponse[$answer]->getReponseExpected();
            if ($reponse === true) {
                $message = "Bonne réponse !";
                $bonneRep++;

            } else {
                $message = "Dommage ! La bonne réponse est : " ;
            }
        }

        if ($compteur == 10) {
            return $this->render('quiz/end.html.twig',[
                "bonneRep" => $bonneRep
            ]);
        }

        return $this->render('quiz/question.html.twig', [
            "quizz" => $quizz,
            "message" => $message,
            "compteur" => $compteur,
            "rep" => $request->get('answer'),
            "next" => $request->get('next'),
            "bonneRep" => $bonneRep,
            "yes" => $reponse,
            "bad" => $bad,
            "good" => $good
        ]);
    }
}
