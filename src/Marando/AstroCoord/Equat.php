<?php

/*
 * Copyright (C) 2015 Ashley Marando
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
 * @param Epoch $epoch Observational epoch
 * @param Geo   $topo  Observational location
 * @param Time  $ra    Right ascension
 * @param Angle $dec   Declination
 * @param Angle $dist  Observer to target distance
 */
class Equat {

  use Traits\CopyTrait,
      Traits\EquatFormat;

  //----------------------------------------------------------------------------
  // Constants
  //----------------------------------------------------------------------------

  /**
   * Default Format:
   * α 13ʰ09ᵐ43ˢ.648, δ +01°00'09".387 (ICRF/J2000.0)
   */
  const FORMAT_DEFAULT = 'α RhʰRmᵐRsˢ.Ru, δ +Dd°Dm\'Ds".Du (F{Y M. c T})';

  /**
   * Full Format:
   * α 13ʰ09ᵐ43ˢ.648, δ +01°00'09".387, 0.798 AU (ICRF/J2000.0)
   */
  const FORMAT_FULL = 'α RhʰRmᵐRsˢ.Ru, δ +Dd°Dm\'Ds".Du, Da (F{Y M. c T})';

  /**
   * Degree Format:
   * α 197.43186°, δ +1.00261° (ICRF/J2000.0)
   */
  const FORMAT_DEGREES = 'α R°, δ +D° (F{Y M. c T})';

  /**
   * Spaced Format:
   * α 13 09 43.648, δ +01 00 09.387 (ICRF/J2000.0)
   */
  const FORMAT_SPACED = 'α Rh Rm Rs.Ru, δ +Dd Dm Ds.Du (F{Y M. c T})';

  //----------------------------------------------------------------------------
  // Constructors
  //----------------------------------------------------------------------------

  /**
   * Creates a new equatorial coordinate
   *
   * @param Frame           $frame Reference frame
   * @param Epoch|AstroDate $epoch Observational epoch
   * @param Time            $ra    Right ascension
   * @param Angle           $dec   Declination
   * @param Distance        $dist  Observer to target distance
   */
  public function __construct(Frame $frame, $epoch, Time $ra, Angle $dec,
          Distance $dist = null) {

    // Set reference frame and observation epoch
    $this->frame = $frame;
    $this->epoch = $epoch;

    // Set right ascension, declination and distance
    $this->setPosition($ra, $dec);
    $this->setDistance($dist);

    // Set default format
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
   * Observational epoch
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
   * @param  Time   $ra  Right ascension
   * @param  Angle  $dec Declination
   * @return static
   */
  public function setPosition(Time $ra, Angle $dec) {
    $this->ra  = $ra->setUnit('hms');
    $this->dec = $dec;

    return $this;
  }

  /**
   * Sets the topographic observational location of this instance
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
    return $this->apparent == true ? true : false;
  }

  /**
   * Returns the apparent coordinates for this instance. Optional weather
   * parameters may be supplied to apply atmospheric refraction to the result.
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
      return $this;

    // Save original coordinates
    $this->orig = $this->copy();

    // If no topographic location set, return apparent geocentric
    if ($this->topo == false || $this->topo == null)
      return $this->ICRStoApparentGeo();

    // If topographic location set, but no weather, return topographic apparent
    if ($this->topo && !($pressure || $temp || $humidity))
      return $this->ICRStoTopo();

    // If topographic location set, and weather, return topographic observed
    if ($this->topo && ($pressure || $temp || $humidity))
      return $this->ICRStoObserved();

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

    // Use original coordinates if already apparent
    $orig = $this->isApparent() ? $this->orig->copy() : $this->copy();

    // Return topographic observed horizontal coordinates
    return $orig->ICRStoObserved('h', $pressure, $temp, $humidity);
  }

  /**
   * Converts this instance to ecliptic coordinates
   * @param  Angle $obli The Earth's obliquity
   * @return Eclip
   */
  public function toEclip(Angle $obli = null) {
    // Use original coordinates if already apparent
    $orig       = $this->isApparent() ? $this->orig->copy() : $this->copy();
    $orig->topo = null;

    $α = $orig->ra->toAngle()->rad;
    $δ = $orig->dec->rad;
    $ε = $obli ? $obli->rad : $orig->obli()->rad;

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
  protected function ICRStoApparentGeo() {
    // Instance initial properties
    $rc    = $this->ra->toAngle()->rad;
    $dc    = $this->dec->rad;
    $date1 = $this->epoch->toDate()->copy()->toTDB()->toJD();
    $pr    = 0;
    $pd    = 0;
    $rv    = 0;
    $px    = $this->dist->au > 0 ? (8.794 / 3600) / $this->dist->au : 0;

    // ICRS -> CIRS (geocentric observer)
    IAU::Atci13($rc, $dc, $pr, $pd, $px, $rv, $date1, 0, $ri, $di, $eo);

    // CIRS -> ICRS (astrometric)
    //IAU::Atic13($ri, $di, $date1, 0, $rca, $dca, $eo);
    // ICRS (astrometric) -> CIRS (geocentric observer)
    //IAU::Atci13($rca, $dca, $pr, $pd, $px, $rv, $date1, 0, $ri, $di, $eo);
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
  protected function ICRStoTopo($type = 'e') {
    // Topo is Same as observed but with no weather
    return $this->ICRStoObserved($type);
  }

  /**
   * Performs a [IRCS -> observed] coordinate transformation for the parameters
   * of this instance
   * @param  string       $type Coordintate type, 'e' for equat 'h' for horiz
   * @return static|Horiz
   */
  protected function ICRStoObserved($type = 'e', Pressure $pressure = null,
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
    //IAU::Atic13($ri, $di, $date1, 0, $rca, $dca, $eo);
    // ICRS (astrometric) -> CIRS (geocentric observer)
    //IAU::Atci13($rca, $dca, $pr, $pd, $px, $rv, $date1, 0, $ri, $di, $eo);
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
  }

}
