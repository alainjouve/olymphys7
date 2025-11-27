<?php

namespace App\Service;

use Fpdf\Fpdf;
use Symfony\Component\String\Slugger\AsciiSlugger;

class createAttestationsElevesCia
{

    public function createAttestationsElevesCia($eleve){
        $slugger = new AsciiSlugger();
        $filePath='odpf/attestations_eleves/'.$eleve->getEquipe()->getNumero();
        if(!file_exists($filePath)) {
            mkdir($filePath);
        }
        $fileNamepdf = $filePath . '/' . $eleve->getEquipe()->getEdition()->getEd() . '_' . $slugger->slug($eleve->getequipe()->getCentre() . '_attestation_equipe_' . $eleve->getEquipe()->getNumero() . '_' . $eleve->getPrenom() . '_' . $eleve->getNom()) . '.pdf';

        $pdf = new Fpdf('P', 'mm', 'A4');
        //$pdf->AddFont('Verdana');
        $pdf->SetFont('helvetica', '', 14);
        $pdf->SetMargins(20, 20);
        $pdf->SetLeftMargin(20);
        $pdf->SetRightMargin(20);
        $pdf->AddPage();
        $pdf->image('https://www.olymphys.fr/public/odpf/odpf-images/site-logo-398x106.png', 20, null, 60);
        $str = iconv('UTF-8', 'windows-1252','Paris le '.$this->date_in_french($eleve->getEquipe()->getEdition()->getConcoursCia()->format('Y-m-d')));
        $wstr = $pdf->getStringWidth($str);
        $str_1 = 'Paris le 3';
        $str_2 = ' ';
        $str_3 = 'décembre 2025';
        $str_1 = iconv('UTF-8', 'windows-1252', $str_1);
        $str_2 = iconv('UTF-8', 'windows-1252', $str_2);
        $str_3 = iconv('UTF-8', 'windows-1252', $str_3);
        $pdf->setXY(190 - $wstr, $pdf->GetY());
        $pdf->Cell(0, 30, $str, 0, 0, 'L');
        // $pdf->setXY($pdf->getX(), $pdf->GetY() - 2);
        // $pdf->Cell(0, 30, $str_2, 0, 0, 'L');
        //$pdf->setXY($pdf->getX(), $pdf->GetY() + 2);
        //$pdf->Cell(0, 30, $str_3 . "\n", 0, 0, 'L');
        //$pdf->Cell(0, 30, $str, 0, 0, 'R');
        $pdf->SetFont('helvetica', 'B', 18);
        $str1 = 'Attestation de participation';
        $x = $pdf->GetX();
        $y = $pdf->getY() + 40;
        $w = $pdf->GetStringWidth($str1);
        $x = (210 - $w) / 2;
        $pdf->SetXY($x, $y);
        $pdf->Cell($w, 20, $str1 . "\n", 0, 0, 'C');
        $pdf->SetFont('helvetica', 'B', 18);
        $w2 = $pdf->getStringWidth('Aux ' .$eleve->getEquipe()->getEdition()->getEd() . 'e Olympiades de Physique France');
        $x = (210 - $w2) / 2;
        $str2 = 'Aux ' . $eleve->getEquipe()->getEdition()->getEd();
        $str21 = 'Olympiades de Physique France';
        $w3 = $pdf->getStringWidth('Aux ' . $eleve->getEquipe()->getEdition()->getEd());
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
        $str5 = iconv('UTF-8', 'windows-1252', $eleve->getPrenom() . ' ' . $eleve->getNom());
        $x = (210 - $w4) / 2;
        $w5 = $pdf->getStringWidth('l\'élève ');
        $y = $pdf->getY() + 10;
        $pdf->SetXY($x, $y);
        $pdf->Cell($w5 - 2, 10, $str4 . "\n", 0, 0, 'L');
        $pdf->SetTextColor(6, 100, 201);
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
        $pdf->Cell(0, 10, iconv('UTF-8', 'windows-1252', $eleve->getEquipe()->getLyceeAcademie()), '', 'L');
        $y = $pdf->getY();
        $pdf->setXY(20, $y + 8);
        $centre = '';
        $lieu = '';
        if ($eleve->getEquipe()->getCentre() != null) {

            $centre = $eleve->getEquipe()->getCentre();
            $lieu = $eleve->getEquipe()->getCentre()->getLieu();
        }
        $pdf->Write(8, iconv('UTF-8', 'windows-1252',
            'a participé le ' .
            $this->date_in_french($eleve->getEquipe()->getEdition()->getConcoursCia()->format('Y-m-d')) . ' au concours interacadémique de ' . $centre . ', ' .
            $lieu . '.'
        ));

        $w13 = $pdf->getStringWidth(iconv('UTF-8', 'windows-1252', 'pour le comité national des Olympiades de Physique France'));
        $x = (210 - $w13) / 2;
        $y = $pdf->getY();
        $pdf->setXY($x, $y + 10);
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



}