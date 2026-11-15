<?php

namespace App\Service;


use App\Entity\Odpf\OdpfArticle;
use App\Entity\Odpf\OdpfCategorie;
use App\Entity\Odpf\OdpfEditionsPassees;
use App\Entity\Odpf\OdpfEquipesPassees;
use Doctrine\ORM\EntityManagerInterface;
use DateTimeInterface;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Fpdf\Fpdf;

class CreateInvitationPdf
{
    /**
     * @param array<int, array{nom: string, prenom: string}> $personnes
     */
    public function createInvitationPdf(
        array              $personnes,
        string             $numEdition,
        string             $lieu,
        int                $reservationId,
        ?DateTimeInterface $dateConcours = null
    ): Fpdf

    {

        $tructure = explode(',', $lieu)[0];
        $adress = explode(',', $lieu)[1];
        if ($_SERVER['SERVER_NAME'] == 'www.olymphys.fr') {
            $path = 'https://www.olymphys.fr/public/odpf/odpf-images/';
        };
        if (str_contains($_SERVER['SERVER_NAME'], 'olympessais.')) {
            $path = 'https://www.olymphys.fr/public/odpf/odpf-images/';
        };
        if ($_SERVER['SERVER_NAME'] == '127.0.0.1' or $_SERVER['SERVER_NAME'] == 'localhost') {
            $path = 'odpf/odpf-images/';
        }
        $pdf = new Fpdf('P', 'mm', 'A4');
        //$pdf->AddFont('Verdana');
        $pdf->SetFont('helvetica', '', 14);
        $pdf->SetMargins(20, 20);
        $pdf->SetLeftMargin(20);
        $pdf->SetRightMargin(20);

        foreach ($personnes as $index => $personne) {
            $pdf->AddPage();
            $pdf->image($path . 'logo-udppc.png', 20, null, 40);
            $pdf->image($path . 'logo-sfp.png', 170, 20, 20);
            $pdf->image($path . 'site-logo-398x106.png', 75, 40, 60);
            $y = $pdf->GetY() + 30;
            $pdf->setY($y);
            //$str = 'Paris le ' . $this->date_in_french($edition->getConcoursCia()->format('Y-m-d'));
            $pdf->SetTextColor(0, 0, 0);
            $str = 'Document indispensable pour être autorisé à entrer dans ';
            $strl = 'l\'enceinte de ' . $tructure;
            $str_ = 'pour assister ou participer aux Olympiades de Physique France';
            $strprim = 'A présenter à l\'entrée sur support papier ou en version numérique';
            $str = iconv('UTF-8', 'ISO-8859-1', $str);
            $strl = iconv('UTF-8', 'ISO-8859-1', $strl);
            $str_ = iconv('UTF-8', 'ISO-8859-1', $str_);
            $strprim = iconv('UTF-8', 'ISO-8859-1', $strprim);
            $pdf->Cell(0, 10, $str . "\n", 0, 0, 'C');
            $y = $pdf->GetY() + 8;
            $pdf->setY($y);
            $pdf->Cell(0, 10, $strl . "\n", 0, 0, 'C');
            $y = $pdf->GetY() + 8;
            $pdf->setY($y);
            $pdf->Cell(0, 10, $str_ . "\n", 0, 0, 'C');
            $y = $pdf->GetY() + 8;
            $pdf->setY($y);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(0, 10, '(' . $strprim . ')' . "\n", 0, 0, 'C');
            $qrData = json_encode([
                'nom' => $personne['nom'],
                'prenom' => $personne['prenom'],
                'reservation_id' => $reservationId,
                'person_index' => $index,
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $qrCode = QrCode::create($qrData)->setSize(200)->setMargin(2);
            $qrPath = tempnam(sys_get_temp_dir(), 'odpf-qr-');
            if ($qrPath === false) {
                throw new \RuntimeException('Impossible de créer le fichier temporaire du QR code.');
            }
            unlink($qrPath);
            $qrPath .= '.png';
            try {
                (new PngWriter())->write($qrCode)->saveToFile($qrPath);
                $pdf->Image($qrPath, 95, $pdf->GetY() + 10, 20, 20);
            } finally {
                unlink($qrPath);
            }
            $pdf->SetFont('helvetica', 'B', 18);
            $str1 = 'Invitation aux ';
            $x = $pdf->GetX();
            $y = $pdf->getY() + 35;
            $w = $pdf->GetStringWidth($str1);
            $x = (210 - $w) / 2;
            $pdf->SetXY($x, $y);
            $pdf->Cell($w, 20, $str1 . "\n", 0, 0, 'C');
            $pdf->SetFont('helvetica', 'B', 18);
            $w2 = $pdf->getStringWidth($numEdition . 'e Olympiades de Physique France');//pour connaître la longueur de la chaîne
            $x = (210 - $w2) / 2;//Calcule l'emplacement
            $str2 = $numEdition;//Définit str2
            $str21 = 'Olympiades de Physique France';//Définit str21
            $w3 = $pdf->getStringWidth($numEdition);//Pour connaître la longueur de la chaîne
            $y = $pdf->getY() + 10;//clacule l'emplacment
            $pdf->SetXY($x, $y);//fixe le curseur à cet emplaecment
            $pdf->Cell($w3, 20, $str2 . "\n", 0, 0, 'L');
            $x = $pdf->GetX();
            $y = $pdf->getY() - 2;
            $pdf->SetXY($x, $y);

            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(5, 20, 'e', 0, 0, 'L');
            $x = $pdf->GetX();
            $y = $pdf->getY() + 2;
            $pdf->SetXY($x, $y);
            $pdf->SetFont('helvetica', 'B', 18);
            $pdf->Cell(0, 20, $str21 . "\n", 0, 0, 'L');
            $x = $pdf->GetX();
            $y = $pdf->getY() + 5;
            $pdf->SetXY($x, $y);
            $pdf->SetFont('helvetica', '', 14);
            $str3 = iconv('UTF-8', 'windows-1252', 'Bonjour,');
            $x = $pdf->GetX();
            $y = $pdf->getY() + 10;
            $pdf->SetXY(20, $y);
            $pdf->Cell(0, 10, $str3 . "\n", 0, 0, 'C');
            $str5 = iconv('UTF-8', 'windows-1252', $personne['prenom'] . ' ' . $personne['nom']);
            $y = $pdf->getY() + 10;
            $pdf->SetXY(20, $y);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('helvetica', 'B', 20);
            $pdf->Cell(170, 12, $str5, 0, 0, 'C');
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('helvetica', '', 14);
            $y += 22;
            $phraseInvitation = iconv('UTF-8', 'windows-1252', 'Le comité national des OdPF vous invite');
            $pdf->SetXY(20, $y);
            $pdf->Cell(170, 8, $phraseInvitation, 0, 0, 'C');
            $y += 10;
            $w14 = $pdf->getStringWidth(iconv('UTF-8', 'windows-1252', 'à assister  au concours national '));
            $pdf->SetXY((210 - $w14) / 2, $y);
            $pdf->Cell($w14, 8, iconv('UTF-8', 'windows-1252',
                'à assister au concours national'), '', 'R');
            $y = $y + 10;
            $pdf->SetFont('helvetica', 'B', 14);
            $w15 = $pdf->getStringWidth(iconv('UTF-8', 'windows-1252', 'à ' . $tructure));
            $pdf->setXY((210 - $w15) / 2, $y);
            $pdf->Cell($w15, 8, iconv('UTF-8', 'windows-1252', 'à ' . $tructure), '', 'C');
            $y = $pdf->GetY();
            $w16 = $pdf->getStringWidth(iconv('UTF-8', 'windows-1252', $adress));
            $pdf->setXY((210 - $w16) / 2, $y);
            $pdf->Cell($w16, 8, iconv('UTF-8', 'windows-1252', $adress), '', 'C');
            if ($dateConcours !== null) {
                $date = iconv('UTF-8', 'windows-1252', 'Le ' . $dateConcours->format('d/m/Y'));
                $pdf->setXY(20, $pdf->GetY() + 8);
                $pdf->Cell(170, 8, $date, 0, 0, 'C');
            }
            $pdf->SetFont('helvetica', '', 14);
            $w13 = $pdf->getStringWidth(iconv('UTF-8', 'windows-1252', 'pour le comité national des Olympiades de Physique France'));
            $x = (210 - $w13) / 2;
            $y = $pdf->getY();
            $pdf->setXY($x, $y + 12);
            $pdf->Cell($w13, 8, iconv('UTF-8', 'windows-1252', 'Pour le comité national des Olympiades de Physique France'), '', 'R');
            $y = $pdf->getY();
            $pdf->image($path . 'signature_gd_format.png', 130, $y, 40);
            $y = $pdf->getY();
            $pdf->setXY(130, $y + 20);
            $pdf->Cell(0, 8, iconv('UTF-8', 'windows-1252', 'Pascale Hervé'), '', 'C');

        }

        return $pdf;


    }

}