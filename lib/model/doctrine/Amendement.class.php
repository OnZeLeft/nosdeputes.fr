<?php

/**
 * This class has been auto-generated by the Doctrine ORM Framework
 */
class Amendement extends BaseAmendement {

  public function getLink() {
    sfProjectConfiguration::getActive()->loadHelpers(array('Url'));
    return url_for('@amendement?loi='.$this->texteloi_id.'&numero='.$this->numero);
  }
  public function getLinkSource() {
    return $this->source;
  }
  public function getPersonne() {
    return '';
  }

  public function __toString() {
    $str = substr(strip_tags($this->expose), 0, 250);
    if (strlen($str) == 250) {
      $str .= '...';
    }
    return $str;
  }

  public function getDossier() {
    if ($section = $this->getSection())
      return $section->Section->getTitreComplet();
    return '';
  }

  public function setAuteurs($auteurs) {
//$debug = 1;
    $groupe = null;
    $sexe = null;
    $regexp = array();
    if (preg_match('/^\s*(.*),+\s*[dl]es\s+(.*\s+[gG]roupe|membres|sénateurs)\s+(.*)\s*$/' ,$auteurs, $match)) {
      $tmpgroupe = null;
      foreach (myTools::getGroupesInfos() as $gpe) {
        $regexp[] = $gpe[4];
        if (preg_match('/('.$gpe[4].'|'.$gpe[1].')/i', $groupe)) {
          $tmpgroupe = $gpe[1];
          $auteurs = preg_replace('/,[^,]*'.$gpe[0].'[^,]*/', '', $auteurs);
        }
      }
      if ($tmpgroupe) $groupe = $tmpgroupe;
      else $groupe = null;
    }
    if ($debug) echo $auteurs." // ".$groupe."\n";
    $arr = preg_split('/,/', $auteurs);
    $signataireindex = 1;
    foreach ($arr as $senateur) {
      if (preg_match('/^(.*)\((.*)\)/', $senateur, $match)) {
        $senateur = trim($match[1]);
        $circo = preg_replace('/\s/', '-', ucfirst(trim($match[2])));
      } else $circo = null;
      if (count($regexp)) if (preg_match('/('.implode("|", $regexp).')/i', $senateur)) {
        if ($debug) print "WARN: Skip auteur ".$senateur." for ".$this->source."\n";
        continue;
      }
      if (preg_match('/les membres du groupe/i', $senateur)) {
        if ($debug) print "WARN: Skip ".$senateur." for ".$this->source."\n";
        break;
      } elseif (preg_match('/(gouvernement|développement|activité|républicain|égalité|président|rapporteur|commission|formation|délégation|questeur|apparentés|rattachés|collègues)/i', $senateur)) {
        if ($debug) print "WARN: Skip auteur ".$senateur." for ".$this->source."\n";
        continue;
      } elseif (preg_match('/^\s*(M[Mmles]*)[\.\s]+(\w.*)\s*$/', $senateur, $match)) {
          $nom = $match[2];
          if (preg_match('/[el]/', $match[1]))
            $sexe = 'F';
          else $sexe = 'H';
      } else $nom = preg_replace("/^\s*(.*)\s*$/", "\\1", $senateur);
      $nom = ucfirst($nom);
      if ($debug) echo $nom."//".$sexe."//".$groupe."//".$circo." => ";
      $parl = Doctrine::getTable('Parlementaire')->findOneByNomSexeGroupeCirco($nom, $sexe, $groupe, $circo, $this);
      if (!$parl) print "ERROR: Auteur introuvable in ".$this->source." : ".$nom." // ".$sexe." // ".$groupe."\n";
      else {
        if ($debug) echo $parl->nom."\n";
        if (!$groupe && $parl->groupe_acronyme != "") $groupe = $parl->groupe_acronyme;
        $this->addParlementaire($parl, $signataireindex);
      }
      $signataireindex++;
    }
  }

  public function addParlementaire($senateur, $signataireindex) {
    foreach(Doctrine::getTable('ParlementaireAmendement')->createQuery('pa')->select('parlementaire_id')->where('amendement_id = ?', $this->id)->fetchArray() as $parlamdt) if ($parlamdt['parlementaire_id'] == $senateur->id) return true;

    $pa = new ParlementaireAmendement();
    $pa->_set('Parlementaire', $senateur);
    $pa->_set('Amendement', $this);
    $pa->numero_signataire = $signataireindex;
    if ($pa->save()) {
      return true;
    } else return false;
  }

  public function getAmendementPere() {
    if ($this->numero_pere && $a = doctrine::getTable('Amendement')->findLastOneByLoiNum($this->texte_loi_id, $this->numero_pere))
      return $a;
    return null;
  }

  public function setCommission($com) {
    if ($c = doctrine::getTable('Organisme')->findOneByNomType($com, 'parlementaire'))
      $this->_set('Commission', $c);
  }

  public function getSignataires($link = 0) {
    $signa = preg_replace("/M\s+/", "M. ", $this->_get('signataires'));
    if ($link && !preg_match('/gouvernement/i',$signa))
      $signa = preg_replace('/(M+[\.mles\s]+)?([A-ZÉ][^,]+)\s*(,\s*|$)/', '<a href="/senateurs/rechercher/\\2">\\1\\2</a>\\3', $signa);
    return $signa;
  }

  public function getSection() {
    return PluginTagTable::getObjectTaggedWithQuery('Section', array('loi:numero='.$this->texteloi_id))->fetchOne();
  }

  public function getIntervention($num_admt) {
    $query = PluginTagTable::getObjectTaggedWithQuery('Intervention', array('loi:numero='.$this->texteloi_id, 'loi:amendement='.$num_admt));
    $query->select('Intervention.id, Intervention.date, Intervention.seance_id, Intervention.md5')
      ->groupBy('Intervention.date')
      ->orderBy('Intervention.date DESC, Intervention.timestamp ASC');
    return $query->fetchOne();
  }

  public function getTitre($link = 0) {
    return $this->getPresentTitre($link).' au texte N° '.$this->texteloi_id.' - '.$this->sujet.' ('.$this->getPresentSort().')';
  }

  public function getShortTitre($link = 0) {
    return $this->getPresentTitre($link).' ('.$this->getPresentSort().')';
  }

  public function getPresentTitre($link = 0) {
    $parent = 0;
    $pluriel = "";
    if (preg_match('/^motion/i', $this->sujet))
      $titre = "Motion";
    else {
      if ($this->numero_pere)
        $titre = "Sous-";
      else $titre = "";
      $titre .= "Amendement";
    }
    $numeros = $this->numero;
    $lettre = $this->getLettreLoi();
#    $ident = $this->getTags(array('is_triple' => true,
#	  'namespace' => 'loi',
#	  'key' => 'amendement',
#	  'return'    => 'value'));
#    if (count($ident) > 1 && $lettre != "") {
#      sort($ident);
#      if ($lettre) foreach ($ident as $iden) $iden .= $lettre;
#      $numeros = implode(', ', $ident);
#      $pluriel = "s";
#    }
    $titre .= $pluriel." N° ".$numeros;
    if ($this->rectif == 1)
      $titre .= " rectifié".$pluriel;
    elseif($this->rectif > 1)
      $titre .= " ".$this->rectif."ème rectif.";
    if ($this->numero_pere) {
      $titre .= ' à ';
      if ($link && function_exists('url_for'))
	$titre .= '<a href="'.url_for('@amendement?loi='.$this->texteloi_id.'&numero='.$this->numero_pere).'">';
      else $link = 0;
      $titre .= 'l\'amendement N° '.$this->numero_pere.$lettre;
      if ($link) $titre .= '</a>';
    }
    return $titre;
  }

  public function getPresentSort() {
    return preg_replace('/indéfini/i', 'Sort indéfini', $this->getSort());
  }

  public function getTexte($style=1) {
    if ($style == 1)
      return preg_replace('/\<p\>\s*«\s*([^\<]+)\<\/p\>/', '<blockquote>«&nbsp;\1</blockquote>', $this->_get('texte'));
    return $this->_get('texte');
  }

  public function getLettreLoi() {
    if (preg_match('/^([A-Z])\d/', $this->numero, $match)) {
      return $match[1];
    }
    return;
  }

  public function getTitreNoLink() {
    return preg_replace('/\<a href.*\>(.*)<\/a\>/', '\1', $this->getTitre());
  }

  public function getIsLastVersion() {
    if ($this->sort === "Rectifié")
      return false;
    return true;
  }

}
