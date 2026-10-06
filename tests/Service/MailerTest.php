<?php

namespace App\Tests\Service;

use App\Entity\Elevesinter;
use App\Entity\Equipesadmin;
use App\Entity\User;
use App\Repository\ElevesinterRepository;
use App\Service\Mailer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Twig\Environment;

class MailerTest extends TestCase
{
    /**
     * @dataProvider studentPresenceProvider
     */
    public function testSendConfirmationIncludesWhetherStudentsAreLinked(bool $hasStudents): void
    {
        $equipe = (new Equipesadmin())->setNumero(1);
        $student = $hasStudents ? new Elevesinter() : null;
        $studentRepository = $this->createMock(ElevesinterRepository::class);
        $studentRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['equipe' => $equipe])
            ->willReturn($student);

        $sentEmail = null;
        $symfonyMailer = $this->createMock(MailerInterface::class);
        $symfonyMailer->expects($this->once())
            ->method('send')
            ->with($this->callback(static function (Email $email) use (&$sentEmail): bool {
                $sentEmail = $email;
                return true;
            }));

        $user = new User();
        $user->setNom('Professeur');
        $user->setPrenom('Test');
        $user->setEmail('prof@example.com');

        $mailer = new Mailer(
            $symfonyMailer,
            $this->createMock(Environment::class),
            new RequestStack(),
            $studentRepository
        );
        $mailer->sendConfirmeInscriptionEquipe($equipe, $user, false, null);

        self::assertInstanceOf(TemplatedEmail::class, $sentEmail);
        self::assertSame($hasStudents, $sentEmail->getContext()['hasStudents']);
    }

    public static function studentPresenceProvider(): array
    {
        return [
            'students are linked' => [true],
            'no students are linked' => [false],
        ];
    }
}
