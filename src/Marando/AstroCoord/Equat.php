<?php

/*
 * Copyright (C) 2015 ashley
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

namespace Marando\AstroCoord;

use \Exception;
use \Marando\AstroCoord\Geo;
use \Marando\AstroCoord\Horiz;
use \Marando\AstroDate\Epoch;
use \Marando\IAU\IAU;
use \Marando\IAU\iauASTROM;
use \Marando\IERS\IERS;
use \Marando\Units\Angle;
use \Marando\Units\Distance;
use \Marando\Units\Pressure;
use \Marando\Units\Temperature;
use \Marando\Units\Time;

/**
 * Represents an equatorial coordinate
 *
 * @param Frame $frame Reference frame
 * @param Epoch $epoch Observation epoch
 * @param Geo   $topo  Geographic observation location
 * @param Time  $ra    Right ascension
 * @param Angle $dec   Declination
 * @param Angle $dist  Observer to target distance
 */
class Equat {

  use Traits\CopyTrait;

  //----------------------------------------------------------------------------
  // Constatns
  //----------------------------------------------------------------------------

  const FORMAT_DEFAULT = 'α {Rh%02d}ʰ{Rm%02d}ᵐ{Rs%02d}ˢ.{Ru%.3f}, δ {Dd%+03d}°{Dm%02d}\'{Ds%02d}".{Du%.2f}, {DAU%02.3f} ({FY M. c T})';
  const FORMAT_SPACED  = 'α {Rh%02d} {Rm%02d} {Rs%02d}.{Ru%.3f}, δ {Dd%+03d} {Dm%02d} {Ds%02d}.{Du%.2f} {DAU%02.3f} ({FY M. c T})';
  const FORMAT_DEGREES  = 'α {RD%02.4f}°, δ {DD%+02.4f}° {DAU%02.3f} ({FY M. c T})';

  //----------------------------------------------------------------------------
  // Constructors
  //----------------------------------------------------------------------------

  /**
   * Creates a new equatorial coordinate
   *
   * @param Frame           $frame Reference frame
   * @param Epoch|AstroDate $epoch Observation epoch
   * @param Time            $ra    Right ascension
   * @param Angle           $dec   Declination
   * @param Distance        $dist  Distance
   */
  public function __construct(Frame $frame, $epoch, Time $ra, Angle $dec,
          Distance $dist = null) {

    // Set reference frame and observation epoch
    $this->frame = $frame;
    $this->epoch = $epoch;

    // Set right ascension, declination and distance
    $this->ra   = $ra->setUnit('hms');
    $this->dec  = $dec;
    $this->dist = $dist;

    $this->format = static::FORMAT_DEFAULT;
  }

  //----------------------------------------------------------------------------
  // Properties
  //----------------------------------------------------------------------------

  /**
   * Reference frame
   * @var Frame
   */
  protected $frame;

  /**
   * Observation epoch
   * @var Epoch
   */
  protected $epoch;

  /**
   * Right ascension
   * @var Time
   */
  protected $ra;

  /**
   * Declination
   * @var Angle
   */
  protected $dec;

  /**
   * Distance
   * @var Distance
   */
  protected $dist;

  /**
   * If the coordinates are apparent
   * @var bool
   */
  protected $apparent;

  /**
   * Geographic observation location
   * @var Geo
   */
  protected $topo;

  /**
   * Holds a copy of this instance before being converted to apparent
   * @var static
   */
  protected $orig;
  protected $format;

  public function __get($name) {
    switch ($name) {
      case 'frame':
      case 'epoch':
      case 'ra':
      case 'dec':
      case 'dist':
      case 'topo':
        return $this->{$name};
    }
  }

  public function __set($name, $value) {
    switch ($name) {
      case 'topo':
        return $this->setTopo($value);

      case 'ra':
        return $this->setPosition($value, $this->dec);

      case 'dec':
        return $this->setPosition($this->ra, $value);

      case 'dist':
        return $this->setDistance($value);
    }
  }

  //----------------------------------------------------------------------------
  // Functions
  //----------------------------------------------------------------------------

  /**
   * Sets the right ascension and declination of this instance
   *
   * @param  Time   $ra
   * @param  Angle  $dec
   * @return static
   */
  public function setPosition(Time $ra, Angle $dec) {
    $this->ra  = $ra;
    $this->dec = $dec;

    return $this;
  }

  /**
   * Sets the topographic observation point of this instance
   *
   * @param  Geo    $geo
   * @return static
   */
  public function setTopo(Geo $geo) {
    $this->topo = $geo;

    return $this;
  }

  /**
   * Sets the target to observer distance
   *
   * @param  Distance $dist
   * @return static
   */
  public function setDistance(Distance $dist) {
    $this->dist = $dist;

    return $this;
  }

  /**
   * Returns true of the instance is apparent
   * @return bool
   */
  public function isApparent() {
    return $this->apparent;
  }

  /**
   * Returns the apparent coordinates for this instance. Optional weather
   * parameters can be supplied to apply atmospheric refraction to the result.
   *
   * @param  Pressure    $pressure Atmospheric pressure
   * @param  Temperature $temp     Atmospheric temperature
   * @param  float       $humidity Relative humidity
   * @return static
   */
  public function apparent(Pressure $pressure = null, Temperature $temp = null,
          $humidity = null) {

    // Check if aleady converted to apparent, and return that if so
    if ($this->apparent)
      return $this->copy();

    // Save original coordinates
    $this->orig = $this->copy();

    // If no topographic location set, return apparent geocentric
    if ($this->topo == false || $this->topo == null)
      return $this->IRCStoApparentGeo();

    // If topographic location set, but no weather, return topographic apparent
    if ($this->topo && !($pressure || $temp || $humidity))
      return $this->IRCStoTopo();

    // If topographic location set, and weather, return topographic observed
    if ($this->topo && ($pressure || $temp || $humidity))
      return $this->IRCStoObserved();

    throw new Exception('An error has occured finding apparent coordinates');
  }

  /**
   * Converts this instance to horizontal coordinates
   *
   * @param  Geo         $geo      Geographic observation location
   * @param  Pressure    $pressure Atmospheric pressure
   * @param  Temperature $temp     Atmospheric temperature
   * @param  float       $humidity Relative humidity
   * @return Horiz
   */
  public function toHoriz(Pressure $pressure = null, Temperature $temp = null,
          $humidity = null) {

    // Return topographic observed horizontal coordinates
    return $this->IRCStoObserved('h', $pressure, $temp, $humidity);
  }

  /**
   * Converts this instance to ecliptic coordinates
   * @param  Angle $obli The Earth's obliquity
   * @return Eclip
   */
  public function toEclip(Angle $obli = null) {
    // Use original coordinates if they're present
    $radec       = $this;  //$this->orig ? $this->orig->copy() : $this;
    $radec->topo = null;

    $α = $radec->ra->toAngle()->rad;
    $δ = $radec->dec->rad;
    $ε = $obli ? $obli->rad : $radec->obli()->rad;

    $λ = atan2((sin($α) * cos($ε) + tan($δ) * sin($ε)), cos($α));
    $β = asin(sin($δ) * cos($ε) - cos($δ) * sin($ε) * sin($α));

    return new Eclip(Angle::rad($λ)->norm(), Angle::rad($β), $this->dist);
  }

  // // // Protected

  /**
   * Finds the Earth's true obliquity for this instances observational epoch if
   * the instance is apparent, or returns the mean obliquity if it is
   * astrometric
   *
   * @return Angle
   */
  protected function obli() {
    $jdTT = $this->epoch->toDate()->copy()->toTT()->toJD();
    $ε0   = Angle::rad(IAU::Obl06($jdTT, 0));

    if ($this->apparent) {
      // True obliquity
      IAU::Nut06a($jdTT, 0, $Δψ, $Δε);
      return $ε0->add(Angle::rad($Δε));
    }
    else {
      // Mean obliquity
      return $ε0;
    }
  }

  /**
   * Performs a [IRCS -> geocentric apparent] transformation for the parameters
   * of this instance
   * @return static
   */
  protected function IRCStoApparentGeo() {
    // Instance initial properties
    $rc    = $this->ra->toAngle()->rad;
    $dc    = $this->dec->rad;
    $date1 = $this->epoch->toDate()->copy()->toTDB()->toJD();
    $pr    = 0;
    $pd    = 0;
    $rv    = 0;
    $px    = $this->dist ? (8.794 / 3600) / $this->dist->au : 0;

    // ICRS -> CIRS (geocentric observer)
    IAU::Atci13($rc, $dc, $pr, $pd, $px, $rv, $date1, 0, $ri, $di, $eo);

    // CIRS -> ICRS (astrometric)
    IAU::Atic13($ri, $di, $date1, 0, $rca, $dca, $eo);

    // ICRS (astrometric) -> CIRS (geocentric observer)
    IAU::Atci13($rca, $dca, $pr, $pd, $px, $rv, $date1, 0, $ri, $di, $eo);

    // Conversion to apparent place via equation of origins
    $ra = $ri - $eo;
    $da = $di;

    // Copy this instance, and override the apparent RA and Decl
    $apparent           = $this->copy();
    $apparent->ra       = Angle::rad($ra)->toTime();
    $apparent->dec      = Angle::rad($da);
    $apparent->apparent = true;

    // Return apparent coordinates
    return $apparent;
  }

  /**
   * Performs a [IRCS -> topographic] coordinate transformation for the
   * parameters of this instance
   * @param  string       $type Coordintate type, 'e' for equat 'h' for horiz
   * @return static|Horiz
   */
  protected function IRCStoTopo($type = 'e') {
    // Topo is Same as observed but with no weather
    return $this->IRCStoObserved($type);
  }

  /**
   * Performs a [IRCS -> observed] coordinate transformation for the parameters
   * of this instance
   * @param  string       $type Coordintate type, 'e' for equat 'h' for horiz
   * @return static|Horiz
   */
  protected function IRCStoObserved($type = 'e', Pressure $pressure = null,
          Temperature $temp = null, $humidity = null) {

    // Instance initial properties
    $rc    = $this->ra->toAngle()->rad;
    $dc    = $this->dec->rad;
    $date1 = $this->epoch->toDate()->toTDB()->toJD();
    $pr    = 0;
    $pd    = 0;
    $rv    = 0;
    $px    = $this->dist->au > 0 ? (8.794 / 3600) / $this->dist->au : 0;
    $utc1  = $this->epoch->toDate()->toUTC()->toJD();
    $dut1  = IERS::jd($utc1)->dut1();
    $elong = $this->topo ? $this->topo->lon->rad : 0;
    $phi   = $this->topo ? $this->topo->lat->rad : 0;
    $hm    = 0; //$this->obsrv->height->m;
    $xp    = IERS::jd($utc1)->x() / 3600 * pi() / 180;
    $yp    = IERS::jd($utc1)->y() / 3600 * pi() / 180;
    $phpa  = $pressure ? $pressure->mbar : 0;
    $tc    = $temp ? $temp->c : 0;
    $rh    = $humidity ? $humidity : 0;
    $wl    = 0.55;

    // ICRS -> CIRS (geocentric observer)
    IAU::Atci13($rc, $dc, $pr, $pd, $px, $rv, $date1, 0, $ri, $di, $eo);

    // CIRS -> ICRS (astrometric)
    IAU::Atic13($ri, $di, $date1, 0, $rca, $dca, $eo);

    // ICRS (astrometric) -> CIRS (geocentric observer)
    IAU::Atci13($rca, $dca, $pr, $pd, $px, $rv, $date1, 0, $ri, $di, $eo);

    // Apparent place ?
    //$ri = $ri - $eo;
    //$di = $di;
    //
    // CIRS -> topocentric
    IAU::Atio13($ri, $di, $utc1, 0, $dut1, $elong, $phi, $hm, $xp, $yp, $phpa,
            $tc, $rh, $wl, $aob, $zob, $hob, $dob, $rob);

    if ($type == 'e') {
      // Copy this instance, and override the apparent RA and Decl
      $topocentric           = $this->copy();
      $topocentric->ra       = Angle::rad($rob)->toTime();
      $topocentric->dec      = Angle::rad($dob);
      $topocentric->apparent = true;

      // Return apparent coordinates
      return $topocentric;
    }
    else {
      // Prepare new horizontal instance
      $horiz = new Horiz(Angle::rad(deg2rad(90) - $zob), Angle::rad($aob),
              $this->dist);

      return $horiz;
    }
  }

  // // // Overrides

  /**
   * Represents this instance as a string
   * @return string
   */
  public function __toString() {
    return $this->format($this->format);

    // Right Ascension
    $αd = sprintf('%02d', abs($this->ra->h));
    $αm = sprintf('%02d', abs($this->ra->m));
    $αs = sprintf('%02d', abs($this->ra->s));
    $αμ = str_replace('0.', '', round($this->ra->s - intval($this->ra->s), 3));
    $αμ = str_pad(abs($αμ), 3, '0', STR_PAD_RIGHT);

    // Declination
    $δd = sprintf('%+03d', abs($this->dec->d));
    $δm = sprintf('%02d', abs($this->dec->m));
    $δs = sprintf('%02d', abs($this->dec->s));
    $δμ = str_replace('0.', '', round($this->dec->s - intval($this->dec->s), 3));
    $δμ = str_pad(abs($δμ), 3, '0', STR_PAD_RIGHT);

    // Distance and frame
    $r = $this->dist->copy()->setUnit('AU');
    $d = $r->au < Distance::pc(1)->au ? $r : $r->setUnit('pc');
    $d = $r->au < 1 ? $r->setUnit('km') : $r;
    $f = $this->apparent ? $this->epoch : "$this->frame";

    // Format string
    $α = "{$αd}ʰ{$αm}ᵐ{$αs}ˢ.{$αμ}";
    $δ = "{$δd}°{$δm}'{$δs}\".{$δμ}";
    return "α {$α}, δ {$δ}, {$d} ({$f})";
  }

  public function format($format) {
    $this->format = $format;

    if (preg_match('/{RD(%.[0-9]{0,2}\.{0,1}[0-9]{0,3}[a-zA-Z])}/', $format, $m)) {
      $αD     = sprintf($m[1], $this->ra->toAngle()->deg);
      $format = str_replace($m[0], $αD, $format);
    }

    if (preg_match('/{Rh(%.[0-9]{0,2}\.{0,1}[0-9]{0,3}[a-zA-Z])}/', $format, $m)) {
      $αh     = sprintf($m[1], $this->ra->h);
      $format = str_replace($m[0], $αh, $format);
    }

    if (preg_match('/{Rm(%.[0-9]{0,2}\.{0,1}[0-9]{0,3}[a-zA-Z])}/', $format, $m)) {
      $αm     = sprintf($m[1], $this->ra->m);
      $format = str_replace($m[0], $αm, $format);
    }

    if (preg_match('/{Rs(%.[0-9]{0,2}\.{0,1}[0-9]{0,3}[a-zA-Z])}/', $format, $m)) {
      $αs     = sprintf($m[1], $this->ra->s);
      $format = str_replace($m[0], $αs, $format);
    }

    if (preg_match('/{Ru(%.[0-9]{0,2}\.{0,1}[0-9]{0,3}[a-zA-Z])}/', $format, $m)) {
      $αu     = sprintf($m[1], $this->ra->micro);
      $format = str_replace($m[0], str_replace('0.', '', $αu), $format);
    }


    // // //

    if (preg_match('/{DD(%.[0-9]{0,2}\.{0,1}[0-9]{0,3}[a-zA-Z])}/', $format, $m)) {
      $δD     = sprintf($m[1], $this->dec->deg);
      $format = str_replace($m[0], $δD, $format);
    }

    if (preg_match('/{Dd(%.[0-9]{0,2}\.{0,1}[0-9]{0,3}[a-zA-Z])}/', $format, $m)) {
      $δd     = sprintf($m[1], $this->dec->d);
      $format = str_replace($m[0], $δd, $format);
    }
    if (preg_match('/{Dm(%.[0-9]{0,2}\.{0,1}[0-9]{0,3}[a-zA-Z])}/', $format, $m)) {
      $δm     = sprintf($m[1], $this->dec->m);
      $format = str_replace($m[0], $δm, $format);
    }
    if (preg_match('/{Ds(%.[0-9]{0,2}\.{0,1}[0-9]{0,3}[a-zA-Z])}/', $format, $m)) {
      $δs     = sprintf($m[1], $this->dec->s);
      $format = str_replace($m[0], $δs, $format);
    }
    if (preg_match('/{Du(%.[0-9]{0,2}\.{0,1}[0-9]{0,3}[a-zA-Z])}/', $format, $m)) {
      $μ      = $this->dec->s - intval($this->dec->s);
      $δμ     = sprintf($m[1], $μ);
      $format = str_replace($m[0], str_replace('0.', '', $δμ), $format);
    }
    // // //

    if (preg_match('/{D(AU|au|KM|km|PC|pc)*(%.[0-9]{0,1}\.{0,1}[0-9]{0,3}[a-zA-Z])}/',
                    $format, $m)) {
      $r = $this->dist->copy()->setUnit('AU');
      $d = $r->au < Distance::pc(1)->au ? $r : $r->setUnit('pc');
      $d = $r->au < 1 ? $r->setUnit('km') : $r;

      if ($m[1])
        $d = sprintf($m[2], $this->dist->{strtolower($m[1])}) . ' ' . $m[1];

      $format = str_replace($m[0], $d, $format);
    }

    if (preg_match('/{F([a-zA-Z-\s\.]*)}/', $format, $m)) {
      $F      = $this->frame->name;
      $Fd     = $this->apparent ? $this->epoch->toDate()->format($m[1]) : $this->frame->equinox;
      $F      = $this->apparent ? "$Fd" : "$F/$Fd";
      $format = str_replace($m[0], "$F", $format);
    }

    return $format;
  }

}
