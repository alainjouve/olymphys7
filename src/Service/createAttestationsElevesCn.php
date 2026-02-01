<?php

namespace App\Service;

use Fpdf\Fpdf;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\String\Slugger\AsciiSlugger;


class createAttestationsElevesCn
{

    public function createAttestationsEleveCn($eleve, $equipe)
    {
        $slugger = new AsciiSlugger();
        $fileSytem = new Filesystem();
        $path = 'odpf/attestations_eleves/';
        if (!file_exists($path . $equipe->getLettre())) {
            mkdir($path . $equipe->getLettre(), 0777, true);
        }
        $filePath = 'odpf/attestations_eleves/' . $eleve->getEquipe()->getLettre();
        $edition = $eleve->getEquipe()->getEdition();

        if (!file_exists($filePath)) {
            $fileSytem->mkdir($filePath);
        }
        $fileNamepdf = $filePath . '/' . $eleve->getEquipe()->getEdition()->getEd() . '_CN_' . 'attestation_equipe_' . $eleve->getEquipe()->getLettre() . '_' . $slugger->slug($eleve->getPrenom() . '_' . $eleve->getNom())->toString() . '.pdf';

        $pdf = new Fpdf('P', 'mm', 'A4');
        //$pdf->AddFont('Verdana');
        $pdf->SetFont('helvetica', '', 14);
        $pdf->SetMargins(20, 20);
        $pdf->SetLeftMargin(20);
        $pdf->SetRightMargin(20);
        $pdf->AddPage();
        $pdf->image('https://www.olymphys.fr/public/odpf/odpf-images/site-logo-398x106.png', 20, null, 60);
        $str = 'Paris le 1er février 2025';
        $wstr = $pdf->getStringWidth($str);
        $str_1 = 'Paris le 31';
        $str_2 = ' ';
        $str_3 = 'janvier 2026';
        $str_1 = iconv('UTF-8', 'windows-1252', $str_1);
        $str_2 = iconv('UTF-8', 'windows-1252', $str_2);
        $str_3 = iconv('UTF-8', 'windows-1252', $str_3);
        $wstr1 = $pdf->getStringWidth($str_1);
        $wstr2 = $pdf->getStringWidth($str_2);
        $wstr3 = $pdf->getStringWidth($str_3);
        $pdf->setXY(190 - $wstr, $pdf->GetY());
        $pdf->Cell($wstr1, 30, $str_1, 0, 0, 'L');
        $pdf->setXY(190 - $wstr + $wstr1, $pdf->GetY() - 2);
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell($wstr2, 30, $str_2, 0, 0, 'L');
        $pdf->SetFont('helvetica', '', 14);
        $pdf->setXY(190 - $wstr + $wstr1 + $wstr2, $pdf->GetY() + 2);
        $pdf->Cell($wstr3, 30, $str_3 . "\n", 0, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 18);
        $str1 = 'Attestation de participation';
        $x = $pdf->GetX();
        $y = $pdf->getY() + 40;
        $w = $pdf->GetStringWidth($str1);
        $x = (210 - $w) / 2;
        $pdf->SetXY($x, $y);
        $pdf->Cell($w, 20, $str1 . "\n", 0, 0, 'C');
        $pdf->SetFont('helvetica', 'B', 18);
        $w2 = $pdf->getStringWidth('Aux ' . $edition->getEd() . 'e Olympiades de Physique France');
        $x = (210 - $w2) / 2;
        $str2 = 'Aux ' . $edition->getEd();
        $str21 = 'Olympiades de Physique France';
        $w3 = $pdf->getStringWidth('Aux 33');
        $y = $pdf->getY() + 10;
        $pdf->SetXY($x, $y);
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
        $y = $pdf->getY() + 30;
        $pdf->SetXY($x, $y);
        $pdf->SetFont('helvetica', '', 14);
        $str3 = iconv('UTF-8', 'windows-1252', 'Le comité national des Olympiades de Physique France certifie que :');
        $x = $pdf->GetX();
        $y = $pdf->getY() + 10;
        $pdf->SetXY(0, $y);
        $pdf->Cell(0, 10, $str3 . "\n", 0, 0, 'C');
        $w4 = $pdf->getStringWidth(iconv('UTF-8', 'windows-1252', 'l\'élève ' . $eleve->getprenom() . ' ' . $eleve->getNom()));
        $str4 = iconv('UTF-8', 'windows-1252', 'l\'élève ');
        $str5 = iconv('UTF-8', 'windows-1252', $eleve->getprenom() . ' ' . $eleve->getNom());
        $x = (210 - $w4) / 2;
        $w5 = $pdf->getStringWidth('l\'élève ');
        $y = $pdf->getY() + 10;
        $pdf->SetXY($x, $y);
        $pdf->Cell($w5 - 2, 10, $str4 . "\n", 0, 0, 'L');
        $pdf->SetTextColor(84, 173, 209);
        $x = $pdf->getX() - 4;
        $pdf->setX($x);
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->cell(0, 10, $str5, '', 'L');
        $pdf->SetFont('helvetica', '', 14);
        $nomlycee = $eleve->getEquipe()->getNomLycee();
        $coordination = 'du ';
        if (str_contains($nomlycee, 'lycee') or str_contains($nomlycee, 'Lycee')) {
            $nomlycee = str_replace('lycee', 'lycée', $nomlycee);
            $nomlycee = str_replace('Lycee', 'lycée', $nomlycee);
        }

        if (str_contains($nomlycee, 'lycée') or str_contains($nomlycee, 'Lycée')) {
            $nomlycee = str_replace('Lycée', 'lycée', $nomlycee);
            $coordination = 'du ';
        }
        $str6 = iconv('UTF-8', 'windows-1252', $coordination . $nomlycee);
        $pdf->SetTextColor(0, 0, 0);

        $w6 = $pdf->getStringWidth($str6);

        $x = (210 - $w6) / 2;
        $y = $pdf->getY();
        $pdf->SetXY($x, $y);
        $w7 = $pdf->getStringWidth($coordination);
        $pdf->Cell($w7, 10, iconv('UTF-8', 'windows-1252', $coordination), '', 'R');
        $x = $pdf->getX() + $w7;
        $pdf->SetXY($x, $y);
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, iconv('UTF-8', 'windows-1252', $nomlycee), '', 'L');
        $pdf->SetFont('helvetica', '', 14);
        $str9 = 'à ' . $eleve->getEquipe()->getLyceeLocalite();
        $w9 = $pdf->getStringWidth($str9);
        $x = (210 - $w9) / 2;
        $y = $pdf->getY();
        $pdf->SetXY($x, $y);
        $w10 = $pdf->getStringWidth('à ');
        $pdf->Cell($w10, 10, iconv('UTF-8', 'windows-1252', 'à '), '', 'R');
        $x = $pdf->getX() + $w10;
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetXY($x, $y);
        $pdf->Cell(0, 10, iconv('UTF-8', 'windows-1252', $eleve->getEquipe()->getLyceeLocalite()), '', 'L');

        $pdf->SetFont('helvetica', '', 14);
        $str11 = iconv('UTF-8', 'windows-1252', 'Académie de ' . $eleve->getEquipe()->getLyceeAcademie());
        $w11 = $pdf->getStringWidth($str11);
        $x = (210 - $w11) / 2;
        $y = $pdf->getY();
        $pdf->SetXY($x, $y);
        $w12 = $pdf->getStringWidth(iconv('UTF-8', 'windows-1252', 'Académie de '));
        $pdf->Cell($w12, 10, iconv('UTF-8', 'windows-1252', 'Académie de '), '', 'R');
        $x = $pdf->getX() + $w12;
        $pdf->SetXY($x, $y);
        $pdf->Cell(0, 10, iconv('UTF-8', 'windows-1252', $eleve->getEquipe()->getLyceeAcademie()), '', 'R');
        $y = $pdf->getY();
        $w14 = $pdf->getStringWidth(iconv('UTF-8', 'windows-1252', 'a participé le 31 janvier 2025 et le 1er février 2025 au'));
        $w15 = $pdf->getStringWidth(iconv('UTF-8', 'windows-1252', 'au 32e concours national des'));
        $pdf->SetXY((210 - $w14) / 2, $y);
        $w143 = $pdf->getStringWidth('a participé le 30 janvier 2026 et le 31');
        $pdf->Cell($w143, 8, iconv('UTF-8', 'windows-1252',
            'a participé le 30 janvier 2026 et le 31'), '', 'L');
        $pdf->SetXY(((210 - $w14) / 2) + $w143 - 4, $y - 2);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell($wstr2, 8, $str_2, '', 'L');
        $pdf->SetFont('helvetica', '', 14);
        $pdf->SetXY(((210 - $w14) / 2) + $w143 + $wstr2 - 4, $y);
        $pdf->Cell($wstr3 + 3, 8, $str_3 . ' au' . "\n", '', 'L');
        $y = $pdf->getY();
        $pdf->SetXY((210 - $w15) / 2, $y);
        $pdf->Cell(2, 8, iconv('UTF-8', 'windows-1252', '33'), '', 'R');
        $x = $pdf->GetX();
        $y = $y - 2;
        $pdf->setXY($x + 6, $y);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(5, 8, 'e', 0, 0, 'L');
        $x = $pdf->GetX();
        $y = $y + 2;
        $pdf->SetFont('helvetica', '', 14);
        $pdf->setXY($x - 2, $y);
        $pdf->Cell($w15, 8, iconv('UTF-8', 'windows-1252', ' concours national des'), '', 'L');
        $y = $pdf->GetY();
        $w16 = $pdf->getStringWidth(iconv('UTF-8', 'windows-1252', 'Olympiades de Physique France sur le campus '));
        $pdf->setXY((210 - $w16) / 2, $y);
        $pdf->Cell($w16, 8, iconv('UTF-8', 'windows-1252', 'Olympiades de Physique France sur le campus '), '', 'L');
        $w17 = $pdf->getStringWidth(iconv('UTF-8', 'windows-1252', $edition->getLieu() . ' de ' . $edition->getVille() . '.'));
        $y = $pdf->getY();
        $pdf->setXY((210 - $w17) / 2, $y);
        $pdf->Cell($w16, 8, iconv('UTF-8', 'windows-1252', $edition->getLieu() . ' de ' . $edition->getVille() . '.'), '', 'R');
        $pdf->setXY(20, $y + 12);
        $pdf->Write(8, iconv('UTF-8', 'windows-1252', 'Son équipe a obtenu un ' .
            $this->prixlit($equipe->getClassement()) . ' prix.'));
        $w13 = $pdf->getStringWidth(iconv('UTF-8', 'windows-1252', 'pour le comité national des Olympiades de Physique France'));
        $x = (210 - $w13) / 2;
        $y = $pdf->getY();
        $pdf->setXY($x, $y + 12);
        $pdf->Cell($w13, 8, iconv('UTF-8', 'windows-1252', 'Pour le comité national des Olympiades de Physique France'), '', 'C');
        $y = $pdf->getY();
        $pdf->image('odpf/odpf-images/signature_gd_format.png', 130, $y, 40);
        $y = $pdf->getY();
        $pdf->setXY(130, $y + 20);
        $pdf->Cell(0, 8, iconv('UTF-8', 'windows-1252', 'Pascale Hervé'), '', 'C');
        $pdf->Output('F', $fileNamepdf);


    }

    public function date_in_french($date)
    {
        $week_name = array("Dimanche", "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi");
        $month_name = array(" ", "janvier", "février", "mars", "avril", "mai", "juin", "juillet", "août",
            "septembre", "octobre", "novembre", "décembre");

        $split = explode('-', $date);
        $year = $split[0];
        $month = round($split[1]);
        $day = round($split[2]);

        $week_day = date("w", mktime(12, 0, 0, $month, $day, $year));
        return $date_fr = $day . ' ' . $month_name[$month] . ' ' . $year;
    }

    public function prixLit($palmares): string
    {
        $palmaresLit = '';
        switch ($palmares) {

            case '1er' :
                $palmaresLit = 'premier';
                break;
            case '2ème' :
                $palmaresLit = 'deuxième';
                break;
            case '3ème' :
                $palmaresLit = 'troisième';
                break;
        }
        return $palmaresLit;
    }


}