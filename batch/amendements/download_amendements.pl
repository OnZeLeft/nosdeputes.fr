#!/usr/bin/perl

use WWW::Mechanize;
use HTML::TokeParser;

$a = WWW::Mechanize->new();
$a->get("http://recherche2.assemblee-nationale.fr/amendements/resultats.jsp?typeEcran=avance&chercherDateParNumero=non&NUM_INIT=1841&NUM_AMEND=&AUTEUR=&DESIGNATION_ARTICLE=&DESIGNATION_ALINEA=&SORT_EN_SEANCE=&DELIBERATION=&NUM_PARTIE=&DateDebut=&DateFin=&periode=&LEGISLATURE=13Amendements&QueryText=&Scope=TEXTEINTEGRAL&SortField=NUM_AMEND&SortOrder=Asc&format=HTML&ResultCount=1000&searchadvanced=Rechercher");
   
$content = $a->content;
$p = HTML::TokeParser->new(\$content);
    
while ($t = $p->get_tag('a')) {
    if ($t->[1]{class} eq 'lienamendement') {
	$a->get($t->[1]{href});
	$file = $a->uri();
	next if ($file =~ /(index|javascript)/);
	$file =~ s/\//_/gi;
	$file =~ s/\#.*//;
	print "$file\n";
	open FILE, ">:utf8", "html/$file";
	print FILE $a->content;
	close FILE;
	$a->back();
    }
}

