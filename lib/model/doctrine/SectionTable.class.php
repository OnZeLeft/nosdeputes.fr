<?php
/**
 * This class has been auto-generated by the Doctrine ORM Framework
 */
class SectionTable extends Doctrine_Table
{
  public function findOneByContexteOrCreateIt($contexte, $date = '', $timestamp = '') {
    $contexte = self::cleanContexte($contexte);
    $section = $this->findOneByMd5(md5($contexte));
    if (!$section) {
      $section = new Section();
      $section->setTitreComplet($contexte);
    }
    if ($date && (! $section->min_date || $section->min_date > $date))
      $section->min_date = $date;
    if ($timestamp && (! $section->timestamp || $section->timestamp > $timestamp))
      $section->timestamp = $timestamp;
    if ($date && $shortdate = preg_replace('/\d{2}:\d{2}/', '', $date))
      $section->setMaxDate($shortdate);
    $section->save();
    return $section;
  }

  public function findOneByContexte($contexte) {
    $contexte = self::cleanContexte($contexte);
    return $this->findOneByMd5(md5($contexte));
  }

  private static function cleanContexte($contexte) {
    $contexte = preg_replace('/û/', 'u', $contexte);
    $contexte = preg_replace('/[\/\|«»]/', '', strtolower($contexte));
    $contexte = preg_replace('/\&\#8217\;/', '\'', $contexte);
    $contexte = preg_replace('/\&\#\d+\;/', '', $contexte);
    $contexte = preg_replace('/’/', "'", $contexte);
    $contexte = preg_replace('/\,/', ' ', $contexte);
    $contexte = preg_replace('/\s+/', ' ', $contexte);
    $contexte = trim($contexte);
    return $contexte;
  }

}
