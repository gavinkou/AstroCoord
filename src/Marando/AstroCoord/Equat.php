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

use \Marando\AstroCoord\Geo;
use \Marando\AstroCoord\Horiz;
use \Marando\AstroDate\Epoch;
use \Marando\IAU\IAU;
use \Marando\IAU\iauASTROM;
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
 * @param Geo   $obsrv Geographic observation location
 * @param Time  $ra    Right ascension
 * @param Angle $dec   Declination
 * @param Angle $dist  Observer to target distance
 */
class Equat {

  use Traits\CopyTrait;

  //----------------------------------------------------------------------------
  // Constructors
  //----------------------------------------------------------------------------

  /**
   * Creates a new equatorial coordinate
   *
   * @param Frame    $frame Reference frame
   * @param Epoch    $epoch Observation epoch
   * @param Time     $ra    Right ascension
   * @param Angle    $dec   Declination
   * @param Distance $dist  Distance
   */
  public function __construct(Frame $frame, Epoch $epoch, Time $ra, Angle $dec,
          Distance $dist = null) {

    // Set reference frame and observation epoch
    $this->frame = $frame;
    $this->epoch = $epoch;

    // Set right ascension, declination and distance
    $this->ra   = $ra->setUnit('hsm');
    $this->dec  = $dec;
    $this->dist = $dist;
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
  protected $obsrv;

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
      case 'obsrv':
        return $this->{$name};
    }
  }

  public function __set($name, $value) {
    switch ($name) {
      case 'obsrv':
        $this->{$name} = $value;
    }
  }

  //----------------------------------------------------------------------------
  // Functions
  //----------------------------------------------------------------------------

  /**
   * Returns the apparent coordinates of this instance. Optionally weather
   * parameters can be supplied to apply atmospheric refraction to the result.
   *
   * @param  Pressure    $pressure Atmospheric pressure
   * @param  Temperature $temp     Atmospheric temperature
   * @param  float       $humidity Relative humidity
   * @return static
   */
  public function apparent(/* Geo $geo = null, */ Pressure $pressure = null,
          Temperature $temp = null, $humidity = null) {

    // Check if aleady converted to apparent, and return that if so
    if ($this->apparent)
      return $this->copy();

    // Save original coordinates
    $this->orig = $this->copy();

    // If no topographic location set, return apparent geocentric
    if ($this->obsrv == false || $this->obsrv == null)
      return $this->IRCStoApparentGeo();

    // If topographic location set, but no weather, return topographic apparent
    if ($this->obsrv && !($pressure || $temp || $humidity))
      return $this->IRCStoTopo();

    // If topographic location set, and weather, return topographic observed
    if ($this->obsrv && ($pressure || $temp || $humidity))
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
  public function toHoriz(/* Geo $geo = null, */Pressure $pressure = null,
          Temperature $temp = null, $humidity = null) {

    // Return topographic observed horizontal coordinates
    return $this->IRCStoObserved('h', $pressure, $temp, $humidity);
  }

  public function toEclip(Angle $obli = null) {
    $α = $this->ra->toAngle()->rad;
    $δ = $this->dec->rad;
    $ε = $obli ? $obli->rad : $this->obli()->rad;

    $λ = atan2((sin($α) * cos($ε) + tan($δ) * sin($ε)), cos($α));
    $β = asin(sin($δ) * cos($ε) - cos($δ) * sin($ε) * sin($α));

    return new Eclip(Angle::rad($λ), Angle::rad($β), $this->dist);
  }

  // // // Protected

  protected function obli() {
    $jdTT = $this->epoch->toDate()->copy()->toTT()->jd;
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
    $date1 = $this->epoch->jd;
    $pr    = 0;
    $pd    = 0;
    $rv    = 0;
    $px    = 0;

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
    $date1 = $this->epoch->jd;
    $pr    = 0;
    $pd    = 0;
    $rv    = 0;
    $px    = 0;
    $utc1  = $this->epoch->toDate()->toUTC()->jd;
    $dut1  = .155;
    $elong = $this->obsrv ? $this->obsrv->lon->rad : 0;
    $phi   = $this->obsrv ? $this->obsrv->lat->rad : 0;
    $hm    = 0; //$this->obsrv->height->m;
    $xp    = 0;
    $yp    = 0;
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
      $horiz = new Horiz(Angle::rad(deg2rad(90) - $zob), Angle::rad($aob));
      return $horiz;
    }
  }

  // // // Overrides

  /**
   * Represents this instance as a string
   * @return string
   */
  public function __toString() {
    $drFormat = "%+03.0f";
    $ddFormat = "%+03.0f";
    $mFormat  = "%02.0f";
    $sFormat  = "%02.0f";
    $rD       = sprintf($drFormat, $this->ra->h);
    $rM       = sprintf($mFormat, abs($this->ra->m));
    $rS       = sprintf($sFormat, abs($this->ra->s));
    $dD       = sprintf($ddFormat, $this->dec->d);
    $dM       = sprintf($mFormat, abs($this->dec->m));
    $dS       = sprintf($sFormat, abs($this->dec->s));
    $rmic     = str_replace('0.', '', round(abs($this->ra->micro), 3));
    $rmic     = str_pad($rmic, 3, '0', STR_PAD_RIGHT);
    $dmic     = str_replace('0.', '',
            round(abs(intval($this->dec->s) - $this->dec->s), 3));
    $dmic     = str_pad($dmic, 3, '0', STR_PAD_RIGHT);
    $dist     = ''; //$dist = $this->dist ? " Dist {$this->dist}" : '';

    //$frame = $this->apparent ? "$this->epoch apparent" : "$this->frame";
    $mjd = round($this->epoch->toDate()->jd - 2450000.5, 3);
    $frame = $this->apparent ? "MJD {$mjd}" : "$this->frame.0";

    return "RA {$rD}ʰ{$rM}ᵐ{$rS}ˢ.{$rmic} Dec {$dD}°{$dM}'{$dS}\".{$dmic} ({$frame})";
  }

}
